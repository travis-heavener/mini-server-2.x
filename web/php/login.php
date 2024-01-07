<?php
    function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    // verify post request
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        echo "Invalid request method: expected POST, got " . $_SERVER["REQUEST_METHOD"] . ".";
        exit();
    }
    
    // extract field data
    $post_data = json_decode(file_get_contents('php://input'));
    $email = $post_data->email;
    $pass = $post_data->pass;

    // load envs
    $envs = parse_ini_file(dirname($_SERVER["DOCUMENT_ROOT"]) . "/config/.env");

    // check that the user exists
    $mysqli = new mysqli($envs["HOST"], $envs["USER"], $envs["PASS"], $envs["DBID"]);

    if ($mysqli -> connect_error) {
        die("Failed to connect to database: " . $mysqli -> connect_error);
    }

    $statement = $mysqli->prepare("SELECT `id`, `first`, `last`, `pass` FROM `users` WHERE email=?");
    $statement->bind_param("s", $email);
    $statement->execute();

    $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();

    if (count($rows) == 0) {
        echo "Invalid credentials.";
        $mysqli->close();
        exit();
    }

    if (count($rows) > 1) {
        echo "Duplicate email entries.";
        $mysqli->close();
        exit();
    }
    
    // if the user exists, check their password
    $row = $rows[0];
    $db_pass = $row["pass"];
    $is_match = password_verify($pass, $db_pass);
    if (!$is_match) {
        echo "Invalid credentials.";
        $mysqli->close();
        exit();
    }

    // knowing that the login is valid, update last_login
    $statement = $mysqli->prepare("UPDATE `users` SET `last_login` = CURRENT_TIMESTAMP WHERE `email` = ?");
    $statement->bind_param("s", $email);
    $statement->execute();
    $statement->close();
    $mysqli->close();

    // generate a JWT and store in cookies for 1 hour (https://stackoverflow.com/a/33773850)
    $token_lifespan = $envs["AUTH_LIFESPAN"]; // in seconds, how long the token will live for
    $headers = ["alg" => "HS256", "typ" => "JWT", "iat" => time(), "exp" => time()+$token_lifespan, "sub" => $email];
    $headers = base64url_encode(json_encode($headers));

    $body = ["id" => $row["id"], "email" => $email, "first" => $row["first"], "last" => $row["last"]];
    $body = base64url_encode(json_encode($body));

    $key = $envs["AUTH_SECRET"];
    $sig = hash_hmac("sha256", "$headers.$body", $key);
    $sig_enc = base64url_encode($sig);

    $token = "$headers.$body.$sig_enc"; // the final JWT token (test with https://www.jstoolset.com/jwt)

    // store token in cookies
    $worked = setcookie("ms-user-auth", $token, time() + $token_lifespan, "/");
?>