<?php
    include_once($_SERVER['DOCUMENT_ROOT'] . "/php/toolbox.php");

    // load content from database on command
    include_once("./toolbox.php");

    // 1. check for GET
    if ($_SERVER["REQUEST_METHOD"] !== "GET") {
        header('HTTP/1.0 403 Forbidden');
        exit("Error: Invalid request method\nExpected GET, got " . $_SERVER["REQUEST_METHOD"] . ".");
    }

    // 2. extract headers (best way to do this with custom headers I guess)
    $headers = getallheaders();
    $album_name = $headers["MS2_albumName"];
    $max_amt = $headers["MS2_maxAmt"];
    $offset = $headers["MS2_offset"];

    // 3. load up mysqli
    $envs = loadEnvs();
    $mysqli = new mysqli($envs["HOST"], $envs["USER"], $envs["PASS"], $envs["DBID"]);

    // 4. verify auth
    include($_SERVER['DOCUMENT_ROOT'] . "/php/requireAuth.php");
    $user_data = check_auth(true, $envs, $mysqli);

    if (gettype($user_data) !== "array") {
        // tell the page to reload for anything that isn't proper user_data being returned (ie. invalid auth)
        header('HTTP/1.0 403 Forbidden');
        exit("Error: auth_error\nnull.");
    }
    
    $user_id = $user_data["id"];
    
    // 5. get data from db
    $table = TABLE_STEM . dechex($user_id); // we already know the id must be valid since it comes directly from the database

    if ($album_name == RECYCLE_BIN_NAME) {
        $statement = $mysqli->prepare("SELECT `id` FROM `$table` WHERE `deletion_date` IS NOT NULL AND "
                    . "`deletion_date` > CURRENT_TIMESTAMP ORDER BY `deletion_date` DESC, `created` DESC LIMIT ? OFFSET ?");
        $statement->bind_param("ii", $max_amt, $offset);
    } else {
        $statement = $mysqli->prepare("SELECT `id` FROM `$table` WHERE `album_name`=? AND "
                    . "`deletion_date` IS NULL ORDER BY `created` DESC, `id` DESC LIMIT ? OFFSET ?");
        $statement->bind_param("sii", $album_name, $max_amt, $offset);
    }

    $statement->execute();
    $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();
    $mysqli->close();

    // 6. return data
    echo json_encode($rows);
?>