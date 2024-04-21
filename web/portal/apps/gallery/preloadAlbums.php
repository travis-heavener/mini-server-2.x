<?php
    include_once($_SERVER['DOCUMENT_ROOT'] . "/php/toolbox.php");

    // create album icons for gallery
    include_once("./toolbox.php");
    
    function get_user_albums($user_id, $key, $mysqli, $envs) {
        $table = TABLE_STEM . dechex($user_id); // we already know the id must be valid since it comes directly from the database
        $statement = $mysqli->prepare(
            "SELECT `id`, `album_name` FROM `$table` WHERE `id` IN (SELECT MAX(`id`) AS `id` FROM `$table` " .
            "WHERE `deletion_date` IS NULL GROUP BY `album_name`) ORDER BY `uploaded` DESC;"
        );
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        // add album previews
        for ($i = 0; $i < count($rows); $i++) {
            // get the file path
            $row = $rows[$i];
            $path = gen_thumb_path($envs["GALLERY_PATH"], $user_id, $row["id"]);

            // if the file doesn't exist, grab the next item
            if (!file_exists($path)) {
                // get the next image if the newest album entry is not an image (ie. video)
                $table = TABLE_STEM . dechex($user_id);
                $statement = $mysqli->prepare("SELECT * FROM $table WHERE `album_name`=? ORDER BY `uploaded` DESC, `id` DESC LIMIT 1;");
                $statement->bind_param("s", $rows[$i]["album_name"]);
                $statement->execute();
                $temp_rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
                $statement->close();
                
                if (count($temp_rows) > 0) {
                    $path = $envs["GALLERY_PATH"] . dechex($user_id) . "_" . dechex($temp_rows[0]["id"]) . ".bin";
                    $path = gen_thumb_path($envs["GALLERY_PATH"], $user_id, $row["id"]);
                    if (file_exists($path)) {
                        $rows[$i] = $temp_rows[0];
                        continue;
                    }
                }
            } else {
                continue; // we have a hit, so keep everything as-is
            }

            // base case, placeholder image is inserted
            $rows[$i]["id"] = null;
        }

        // check for recycled content
        $statement = $mysqli->prepare("SELECT MAX(`id`) FROM `$table` WHERE `deletion_date` IS NOT NULL;");
        $statement->execute();
        $recycled_rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        if (sizeof($recycled_rows) > 0 && !is_null($recycled_rows[0]["MAX(`id`)"]))
            array_push($rows, ["id"=>$recycled_rows[0]["MAX(`id`)"], "album_name"=>RECYCLE_BIN_NAME]);

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