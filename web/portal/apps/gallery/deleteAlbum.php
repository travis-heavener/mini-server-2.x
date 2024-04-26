<?php
    include_once($_SERVER['DOCUMENT_ROOT'] . "/php/toolbox.php");
    
    // upload a file to the system
    include_once("./toolbox.php");

    // 1. check for DELETE
    if ($_SERVER["REQUEST_METHOD"] !== "DELETE") {
        header('HTTP/1.0 403 Forbidden');
        exit("Error: Invalid request method\nExpected DELETE, got " . $_SERVER["REQUEST_METHOD"] . ".");
    }

    // 2. extract body
    $data = json_decode(file_get_contents("php://input"), true); // true forces assoc array return
    $album_name = $data["album-name"];

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
    $table = TABLE_STEM . dechex($user_id);

    // 4. verify not trying to delete Recycle Bin
    if ($album_name === RECYCLE_BIN_NAME) {
        header('HTTP/1.0 403 Forbidden');
        $mysqli->close();
        exit("Error: Delete Cancelled\nCannot delete \"Recycle Bin\" album.");
    }

    // 5. delete album's non-recycled content and move to recycle bin
    $delete_ts = get_deletion_ts($envs["GALLERY_DELETE_DAYS"]);
    $statement = $mysqli->prepare("UPDATE `$table` SET `deletion_date`=? WHERE `album_name`=? AND `deletion_date` IS NULL;");
    $statement->bind_param("ss", $delete_ts, $album_name);
    $statement->execute();
    $statement->close();
    $mysqli->close();
?>