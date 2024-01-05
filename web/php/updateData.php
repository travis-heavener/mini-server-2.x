<?php
    // verify post request
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        echo "Invalid request method: expected POST, got " . $_SERVER["REQUEST_METHOD"] . ".";
        exit();
    }

    // extract post headers
    $post_data = json_decode(file_get_contents('php://input'));
    $first = $post_data->first;
    $last = $post_data->last;
    $email = $post_data->email;

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

    // knowing that the login is valid, update last_login
    $statement = $mysqli->prepare("UPDATE `users` SET `first`=?, `last`=?, `email`=? WHERE `id`=?");
    $statement->bind_param("sssi", $first, $last, $email, $user_data["id"]);
    $statement->execute();
    $statement->close();
    $mysqli->close();

    // generate a JWT and store in cookies for 1 hour (https://stackoverflow.com/a/33773850)
    $token_lifespan = $envs["AUTH_LIFESPAN"]; // in seconds, how long the token will live for
    $headers = ["alg" => "HS256", "typ" => "JWT", "iat" => time(), "exp" => time()+$token_lifespan, "sub" => $email];
    $headers = base64url_encode(json_encode($headers));

    $body = ["id" => $user_data["id"], "email" => $email, "first" => $first, "last" => $last];
    $body = base64url_encode(json_encode($body));

    $key = $envs["AUTH_SECRET"];
    $sig = hash_hmac("sha256", "$headers.$body", $key);
    $sig_enc = base64url_encode($sig);

    $token = "$headers.$body.$sig_enc"; // the final JWT token (test with https://www.jstoolset.com/jwt)

    // store token in cookies
    $worked = setcookie("ms-user-auth", $token, time() + $token_lifespan, "/");
?>