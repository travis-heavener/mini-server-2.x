<?php

    include_once($_SERVER['DOCUMENT_ROOT'] . "/php/toolbox.php");

    // PERMISSIONS STUFF

    /*
    1: yes, 0: no
    all permissions would have a permissions value of the sum of each 2^(bit #)
    */

    $PERMS = [
        ["gallery", "Gallery",          "gallery"],
        ["dvds",    "Film Library",     "movies"],
        ["fire",    "Fireplace",        "fireplace"],
        ["clock",   "Clock",            "clock"],
        ["notes",   "Notes",            "notes"],
        ["admin",   "Admin Panel",      "admin"]
    ];

    function verify_perms($user_data) {
        global $PERMS;
        $has_access = false;
        $path = $_SERVER["SCRIPT_NAME"];
        for ($i = 0; $i < count($PERMS); $i++) {
            if (str_starts_with($path, "/portal/apps/" . $PERMS[$i][2]) && ((0b1 << $i) & $user_data["permissions"]) > 0) { // we have access to the perm
                $has_access = true;
            }
        }

        if (!$has_access) {
            header("Location: /portal/index.php"); // redirect to user dashboard
            exit();
        }
    }

    // cookies & auth stuff

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

    function check_auth($force_return=false, $_envs=null, $_mysqli=null) {
        // check the cookie exists
        if (!isset($_COOKIE["ms-user-auth"])) {
            header("Location: /index.php?reason=exp"); // redirect to login
            if ($force_return) {
                return;
            } else {
                exit();
            }
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
            if ($force_return) {
                return;
            } else {
                exit();
            }
        }
        
        // load envs
        $envs = ($_envs === null) ? loadEnvs() : $_envs;

        // check that the signature matches
        $check_sig = hash_hmac("sha256", "$headers.$body", $envs["AUTH_SECRET"]);
        $check_sig = base64url_encode($check_sig);
        
        if ($check_sig !== $sig) {
            remove_cookie(); // remove cookie
            header("Location: /index.php?reason=sig"); // redirect to login
            if ($force_return) {
                return;
            } else {
                exit();
            }
        }

        // check that the email is assigned to a user in the users table with the same id
        $mysqli = ($_mysqli === null) ? new mysqli($envs["HOST"], $envs["USER"], $envs["PASS"], $envs["DBID"]) : $_mysqli;
        $id = $body_dec -> id;
        $email = $body_dec -> email;

        if ($mysqli -> connect_error) {
            exit("Failed to connect to database: " . $mysqli -> connect_error);
        }

        $statement = $mysqli->prepare("SELECT `first`, `last`, `pass`, `permissions`, `last_login`, `aes`, `created` FROM `users` WHERE `id`=? AND `email`=?");
        $statement->bind_param("ss", $id, $email);
        $statement->execute();

        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        if (count($rows) == 0) {
            remove_cookie(); // remove cookie
            header("Location: /index.php?reason=inv"); // redirect to login
            if ($_mysqli === null)
                $mysqli->close();

            if ($force_return)
                return;
            else
                exit();
        } else if (count($rows) > 1) {
            // the `id` is auto-incremented and thus shouldn't return more than one user
            // BUT if it does handle this
            remove_cookie(); // remove cookie
            header("Location: /index.php?reason=dup"); // redirect to login
            if ($_mysqli === null)
                $mysqli->close();

            if ($force_return)
                return;
            else
                exit();
        }

        if ($_mysqli === null)
            $mysqli->close();
        
        // disallow any user ids that aren't numbers
        if (!preg_match("/^\d+$/", $id)) {
            header('HTTP/1.0 403 Forbidden');
            $mysqli->close();
            if ($force_return)
                return;
            else
                exit("Error: auth_error\nnull.");
        }

        // store user data in disclosed variables
        $user_data = $rows[0];
        $user_data["id"] = $id;
        $user_data["email"] = $email;

        return $user_data;
    }
?>