<?php
    // upload a file to the system
    include("./toolbox.php");

    // 1. check for POST
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        header('HTTP/1.0 403 Forbidden');
        exit("Error: Invalid request method\nExpected POST, got " . $_SERVER["REQUEST_METHOD"] . ".");
    }

    // 2. extract body
    $album_name = $_POST["album-name"];
    $ids = json_decode($_POST["content-ids"]);

    // 3. verify auth
    include($_SERVER['DOCUMENT_ROOT'] . "/php/requireAuth.php");
    $user_data = check_auth(true);

    if (gettype($user_data) !== "array") {
        // tell the page to reload for anything that isn't proper user_data being returned (ie. invalid auth)
        header('HTTP/1.0 403 Forbidden');
        exit("Error: auth_error\nnull.");
    }

    $user_id = $user_data["id"];

    // 4. load mysqli
    $envs = parse_ini_file(dirname($_SERVER['DOCUMENT_ROOT']) . "/config/.env");
    $mysqli = new mysqli($envs["HOST"], $envs["USER"], $envs["PASS"], $envs["DBID"]);
    
    if (!preg_match("/^\d+$/", $user_data["id"])) {
        // disallow any user ids that aren't numbers
        header('HTTP/1.0 403 Forbidden');
        exit("Error: auth_error\nnull.");
    }

    $table = "gal__" . dechex($user_data["id"]);

    // 5. delete the content from the database
    $idParams = "";
    foreach ($ids as $id) {
        if (!preg_match("/^\d+$/", $id)) {
            // disallow any user ids that aren't numbers
            header('HTTP/1.0 403 Forbidden');
            exit("Error: Invalid Content ID\nCannot remove non-digit content ID from database.");
        }

        // base case, keep adding now-sanitized params
        $idParams .= $id . ",";
    }

    // remove last comma
    $idParams = substr($idParams, 0, strlen($idParams)-1);

    $statement = $mysqli->prepare("DELETE FROM `$table` WHERE `album_name`=? AND `id` IN ($idParams);");
    $statement->bind_param("s", $album_name);
    $statement->execute();
    $statement->close();
    $mysqli->close();

    // 6. remove content from file system
    foreach ($ids as $id) {
        $image_path = gen_media_path($envs["GALLERY_PATH"], $user_data["id"], $id);
        $thumb_path = gen_thumb_path($envs["GALLERY_PATH"], $user_data["id"], $id);

        if (file_exists($image_path)) unlink($image_path);
        if (file_exists($thumb_path)) unlink($thumb_path);
    }
?>