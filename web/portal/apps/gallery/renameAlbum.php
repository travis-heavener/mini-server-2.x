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
    $new_name = $_POST["new-name"];

    // 3. verify auth
    include($_SERVER['DOCUMENT_ROOT'] . "/php/requireAuth.php");
    $envs = loadEnvs();
    $mysqli = new mysqli($envs["HOST"], $envs["USER"], $envs["PASS"], $envs["DBID"]);
    $user_data = check_auth(true, $envs, $mysqli);

    if (gettype($user_data) !== "array") {
        // tell the page to reload for anything that isn't proper user_data being returned (ie. invalid auth)
        header('HTTP/1.0 403 Forbidden');
        exit("Error: auth_error\nnull.");
    }

    $user_id = $user_data["id"];
    
    // 4. prevent using the recycle bin name as an album name
    if ($new_name == RECYCLE_BIN_NAME) {
        header('HTTP/1.0 403 Forbidden');
        exit("Error: Invalid Name\nThe specified album name is not allowed.");
    }

    $table = TABLE_STEM . dechex($user_id);

    // 5. rename content in the database
    $statement = $mysqli->prepare("UPDATE `$table` SET `album_name`=? WHERE `album_name`=?;");
    $statement->bind_param("ss", $new_name, $album_name);
    $statement->execute();
    $statement->close();
    $mysqli->close();
?>