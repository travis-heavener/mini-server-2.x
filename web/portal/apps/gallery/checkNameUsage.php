<?php
    include_once($_SERVER['DOCUMENT_ROOT'] . "/php/toolbox.php");

    // check for the availability of an album name
    include_once("./toolbox.php");
    
    // 1. check for POST
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        header('HTTP/1.0 403 Forbidden');
        exit("Error: Invalid request method\nExpected POST, got " . $_SERVER["REQUEST_METHOD"] . ".");
    }

    // 2. load up mysqli
    $envs = loadEnvs();
    $mysqli = new mysqli($envs["HOST"], $envs["USER"], $envs["PASS"], $envs["DBID"]);

    // 3. verify auth
    include($_SERVER['DOCUMENT_ROOT'] . "/php/requireAuth.php");
    $user_data = check_auth(true, $envs, $mysqli);

    if (gettype($user_data) !== "array") {
        // tell the page to reload for anything that isn't proper user_data being returned (ie. invalid auth)
        header('HTTP/1.0 403 Forbidden');
        exit("Error: auth_error\nnull.");
    }

    // 4. get album name
    $album_name = $_POST["albumName"];

    // 5. get from database
    $table = $table = TABLE_STEM . dechex($user_data["id"]);
    $statement = $mysqli->prepare("SELECT COUNT(*) FROM `$table` WHERE `album_name`=?;");
    $statement->bind_param("s", $album_name);
    $statement->execute();
    $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC)[0];
    $statement->close();
    $mysqli->close();

    // 6. return
    header("Content-type: text/plain");
    echo $rows["COUNT(*)"] > 0 ? "true" : "false";
?>