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
    $album_name = $_POST["album-name"];
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

    // 5. delete the content from the database
    $idParams = "";
    foreach ($ids as $id) {
        if (!preg_match("/^\d+$/", $id)) {
            // disallow any user ids that aren't numbers
            header('HTTP/1.0 403 Forbidden');
            $mysqli->close();
            exit("Error: Invalid Content ID\nCannot remove non-digit content ID from database.");
        }

        // base case, keep adding now-sanitized params
        $idParams .= $id . ",";
    }

    // remove last comma
    $idParams = substr($idParams, 0, strlen($idParams)-1);

    // permanently delete any ids marked for deletion from recycle bin
    $statement = $mysqli->prepare("DELETE FROM `$table` WHERE `id` IN ($idParams) AND `deletion_date` IS NOT NULL;");
    $statement->execute();
    $statement->close();
    
    // mark new items for recycling
    $delete_ts = date("Y-m-d H:i:s", time() + (int)$envs["GALLERY_DELETE_DAYS"] * 86400);
    $statement = $mysqli->prepare("UPDATE `$table` SET `deletion_date`=? WHERE `album_name`=? AND `id` IN ($idParams);");
    $statement->bind_param("ss", $delete_ts, $album_name);
    $statement->execute();
    $statement->close();
    
    // 6. determine what rows weren't removed
    $statement = $mysqli->prepare("SELECT `id` FROM `$table` WHERE `id` IN ($idParams);");
    $statement->execute();
    $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();
    $mysqli->close();

    $remaining_ids = [];
    if (sizeof($rows) > 0)
        foreach ($rows as $key => $val)
            array_push($remaining_ids, $val["id"]);
    
    // 7. remove content from file system
    foreach ($ids as $id) {
        // only remove if the id doesn't exist
        if (in_array((int)$id, $remaining_ids))
            continue;

        $image_path = gen_media_path($envs["GALLERY_PATH"], $user_id, $id);
        $thumb_path = gen_thumb_path($envs["GALLERY_PATH"], $user_id, $id);

        if (file_exists($image_path)) unlink($image_path);
        if (file_exists($thumb_path)) unlink($thumb_path);
    }
?>