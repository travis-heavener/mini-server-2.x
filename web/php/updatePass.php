<?php
    // verify post request
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        echo "Invalid request method: expected POST, got " . $_SERVER["REQUEST_METHOD"] . ".";
        exit();
    }

    // extract post headers
    $post_data = json_decode(file_get_contents('php://input'));
    $current = $post_data->{"current-pass"};
    $new = $post_data->{"new-pass"};
    $confirmed = $post_data->{"confirmed-pass"};

    // verify passwords match
    if ($new !== $confirmed) {
        echo "Password mismatch";
        exit();
    }

    // verify auth is still valid
    include($_SERVER['DOCUMENT_ROOT'] . "/php/requireAuth.php");
    $user_data = check_auth();

    // load envs
    $envs = parse_ini_file(dirname($_SERVER["DOCUMENT_ROOT"]) . "/config/mysql.env");
    
    // connect to database
    $mysqli = new mysqli($envs["HOST"], $envs["USER"], $envs["PASS"], $envs["DBID"]);

    if ($mysqli -> connect_error) {
        echo "Failed to connect to database.";
        exit();
    }
    
    // check password against database
    $statement = $mysqli->prepare("SELECT `pass` FROM `users` WHERE id=?");
    $statement->bind_param("i", $user_data["id"]);
    $statement->execute();

    $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();

    if (count($rows) == 0) {
        echo "Invalid credentials.";
        $mysqli->close();
        exit();
    }

    if (count($rows) > 1) {
        echo "Duplicate user entries.";
        $mysqli->close();
        exit();
    }
    
    $row = $rows[0];
    $db_pass = $row["pass"];
    $is_match = password_verify($current, $db_pass);
    if (!$is_match) {
        echo "Invalid credentials.";
        $mysqli->close();
        exit();
    }

    // knowing that the login is valid, update data
    $new_hash = password_hash($new, PASSWORD_BCRYPT, ["cost"=>12]);

    $statement = $mysqli->prepare("UPDATE `users` SET `pass`=?, `last_login`=CURRENT_TIMESTAMP WHERE `id`=?");
    $statement->bind_param("si", $new_hash, $user_data["id"]);
    $statement->execute();
    $statement->close();
    $mysqli->close();

    // generate a JWT and store in cookies for 1 hour (https://stackoverflow.com/a/33773850)
    $token_lifespan = $envs["AUTH_LIFESPAN"]; // in seconds, how long the token will live for
    $headers = ["alg" => "HS256", "typ" => "JWT", "iat" => time(), "exp" => time()+$token_lifespan, "sub" => $user_data["email"]];
    $headers = base64url_encode(json_encode($headers));

    $body = ["id" => $user_data["id"], "email" => $user_data["email"], "first" => $user_data["first"], "last" => $user_data["last"]];
    $body = base64url_encode(json_encode($body));

    $key = $envs["AUTH_SECRET"];
    $sig = hash_hmac("sha256", "$headers.$body", $key);
    $sig_enc = base64url_encode($sig);

    $token = "$headers.$body.$sig_enc"; // the final JWT token (test with https://www.jstoolset.com/jwt)

    // store token in cookies
    $worked = setcookie("ms-user-auth", $token, time() + $token_lifespan, "/");
?>