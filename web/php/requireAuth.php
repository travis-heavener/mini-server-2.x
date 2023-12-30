<?php
    function base64url_decode($string) {
        return base64_decode(str_replace(['-','_'], ['+','/'], $string));
    }

    function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    function remove_cookie() {
        // remove cookie
        unset($_COOKIE["ms-user-auth"]);
        setcookie("ms-user-auth", "", time() - 3600, "/");
    }

    // check the cookie exists
    if (!isset($_COOKIE["ms-user-auth"])) {
        header("Location: /index.php?reason=exp"); // redirect to login
        return;
    }

    $cookie = $_COOKIE["ms-user-auth"];

    // extract JWT from cookie
    $parts = explode(".", $cookie);
    $headers = $parts[0];
    $body = $parts[1];
    $sig = $parts[2];

    $headers_dec = json_decode(base64url_decode($headers));
    $body_dec = json_decode(base64url_decode($body));

    // check that the JWT hasn't expired
    if ($headers_dec->exp <= time()) {
        remove_cookie(); // remove cookie
        header("Location: /index.php?reason=exp"); // redirect to login
        return;
    }
    
    // load envs
    $envs = parse_ini_file("../../config/mysql.env");
    
    // check that the signature matches
    $check_sig = hash_hmac("sha256", "$headers.$body", $envs["AUTH_SECRET"]);
    $check_sig = base64url_encode($check_sig);
    
    if ($check_sig !== $sig) {
        remove_cookie(); // remove cookie
        header("Location: /index.php?reason=sig"); // redirect to login
        return;
    }

    // check that the email is assigned to a user in the users table with the same id
    $mysqli = new mysqli($envs["HOST"], $envs["USER"], $envs["PASS"], $envs["DBID"]);
    $id = $body_dec -> id;
    $email = $body_dec -> email;

    if ($mysqli -> connect_error) {
        die("Failed to connect to database: " . $mysqli -> connect_error);
    }

    $statement = $mysqli->prepare("SELECT `id` FROM `users` WHERE `id`=? AND `email`=?");
    $statement->bind_param("ss", $id, $email);
    $statement->execute();

    $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();

    if (count($rows) == 0) {
        remove_cookie(); // remove cookie
        header("Location: /index.php?reason=inv"); // redirect to login
        $mysqli->close();
        return;
    } else if (count($rows) > 1) {
        remove_cookie(); // remove cookie
        header("Location: /index.php?reason=dup"); // redirect to login
        $mysqli->close();
        return;
    }

    $mysqli->close();
?>