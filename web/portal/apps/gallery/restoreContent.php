<?php
    include_once($_SERVER['DOCUMENT_ROOT'] . "/php/toolbox.php");
    
    // upload a file to the system
    include_once("./toolbox.php");

    // 1. check for POST
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        header('HTTP/1.0 403 Forbidden');
        exit("Error: Invalid request method\nExpected POST, got " . $_SERVER["REQUEST_METHOD"] . ".");
    }

    // 2. extract body
    $ids = json_decode($_POST["content-ids"]);

    // 3. verify auth & load mysqli
    include($_SERVER['DOCUMENT_ROOT'] . "/php/requireAuth.php");

    $envs = loadEnvs();
    $mysqli = new mysqli($envs["HOST"], $envs["USER"], $envs["PASS"], $envs["DBID"]);
    $user_data = check_auth(true, $envs, $mysqli);

    if (gettype($user_data) !== "array") {
        // tell the page to reload for anything that isn't proper user_data being returned (ie. invalid auth)
        header('HTTP/1.0 403 Forbidden');
        $mysqli->close();
        exit("Error: auth_error\nnull.");
    }

    $user_id = $user_data["id"];

    // 4. generate mysqli data
    $table = TABLE_STEM . dechex($user_id);

    // 5. restore the content from the database
    $idParams = "";
    foreach ($ids as $id) {
        if (!preg_match("/^\d+$/", $id)) {
            // disallow any user ids that aren't numbers
            header('HTTP/1.0 403 Forbidden');
            $mysqli->close();
            exit("Error: Invalid Content ID\nCannot restore non-digit content ID from database.");
        }

        // base case, keep adding now-sanitized params
        $idParams .= $id . ",";
    }

    // remove last comma
    $idParams = substr($idParams, 0, strlen($idParams)-1);

    // mark new items for recycling
    $statement = $mysqli->prepare("UPDATE `$table` SET `deletion_date`=NULL WHERE `id` IN ($idParams);");
    $statement->execute();
    $statement->close();
?>