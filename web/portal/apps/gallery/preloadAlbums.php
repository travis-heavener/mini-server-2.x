<?php
    include_once($_SERVER['DOCUMENT_ROOT'] . "/php/toolbox.php");

    // create album icons for gallery
    include_once("./toolbox.php");
    
    function get_user_albums($user_id, $key, $mysqli, $envs) {
        // grab all unique albums, most recently updated first
        $table = TABLE_STEM . dechex($user_id); // we already know the id must be valid since it comes directly from the database
        $statement = $mysqli->prepare(
            "SELECT DISTINCT `album_name` FROM `$table` WHERE `deletion_date` IS NULL ORDER BY `uploaded` DESC;"
        );
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        // add album previews
        for ($i = 0; $i < count($rows); $i++) {
            // grab the most recent thumbnail from this album
            $statement = $mysqli->prepare(
                "SELECT * FROM `$table` WHERE `deletion_date` IS NULL AND `album_name`=? ORDER BY `created` DESC LIMIT 1;"
            );
            $statement->bind_param("s", $rows[$i]["album_name"]);
            $statement->execute();
            $temp_rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
            $statement->close();

            // get the file path
            $path = gen_thumb_path($envs["GALLERY_PATH"], $user_id, $temp_rows[0]["id"]);

            // if thumbnail doesn't exist, placeholder image is inserted
            $rows[$i]["id"] = !file_exists($path) ? null : $temp_rows[0]["id"];
        }

        // check for recycled content
        $statement = $mysqli->prepare("SELECT `id` FROM `$table` WHERE `deletion_date` IS NOT NULL "
                    . "AND `deletion_date` > CURRENT_TIMESTAMP ORDER BY `deletion_date` DESC LIMIT 1;");
        $statement->execute();
        $recycled_rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        if (sizeof($recycled_rows) > 0 && !is_null($recycled_rows[0]["id"]))
            array_push($rows, ["id"=>$recycled_rows[0]["id"], "album_name"=>RECYCLE_BIN_NAME]);

        $mysqli->close();
        return json_encode($rows);
    }

    // 1. check for GET
    if ($_SERVER["REQUEST_METHOD"] !== "GET") {
        header('HTTP/1.0 403 Forbidden');
        exit("Error: Invalid request method\nExpected GET, got " . $_SERVER["REQUEST_METHOD"] . ".");
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

    // 4. return
    echo get_user_albums($user_data["id"], $user_data["aes"], $mysqli, $envs);
?>