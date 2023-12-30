<?php
    function base64url_decode($string) {
        return base64_decode(str_replace(['-','_'], ['+','/'], $string));
    }

    function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
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
        header("Location: /index.php?reason=exp"); // redirect to login
        return;
    }
    
    // load envs
    $envs = parse_ini_file("../../config/mysql.env");
    
    // check that the signature matches
    $check_sig = hash_hmac("sha256", "$headers.$body", $envs["AUTH_SECRET"]);
    $check_sig = base64url_encode($check_sig);
    
    if ($check_sig !== $sig) {
        // remove cookie
        unset($_COOKIE["ms-user-auth"]);
        setcookie("ms-user-auth", "", time() - 3600, "/");

        // redirect to login
        header("Location: /index.php?reason=sig");
        return;
    }
?>