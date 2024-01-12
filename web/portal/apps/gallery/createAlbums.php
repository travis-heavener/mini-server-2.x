<?php
    // create album icons for gallery
    function get_user_albums($user_id, $key) {
        // load up mysqli
        $envs = parse_ini_file(dirname($_SERVER['DOCUMENT_ROOT']) . "/config/.env");
        $mysqli = new mysqli($envs["HOST"], $envs["USER"], $envs["PASS"], $envs["DBID"]);
        
        $table = "gal__" . dechex($user_id); // we already know the id must be valid since it comes directly from the database
        $statement = $mysqli->prepare("SELECT * FROM `$table` WHERE `id` IN (SELECT MAX(`id`) AS `id` FROM `gal__1` GROUP BY `album_name`) ORDER BY `created` DESC;");
        $statement->execute();

        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);

        $statement->close();
        $mysqli->close();

        // add album previews
        for ($i = 0; $i < count($rows); $i++) {
            // get the file path
            $row = $rows[$i];
            $path = $envs["GALLERY_PATH"] . dechex($user_id) . "_" . dechex($row["id"]) . ".bin";
            $MIME = $row["mime"];
            if (file_exists($path) && str_starts_with($MIME, "image")) {
                $data = file_get_contents($path);
                $image = openssl_decrypt($data, "aes-256-ctr", $key, $options=0, $row["vector"]);
                $src = "data:$MIME;base64," . base64_encode($image);
                $rows[$i]["preview"] = "<img src='$src' class='album-icon-img' alt='Album icon image.'>";
            } else {
                $rows[$i]["preview"] = "<img src='/assets/app-icons/gallery.png' class='album-icon-img default-icon' alt='Album icon image.'>";
            }
        }

        return $rows;
    }

    function get_full_album($user_id, $album_name) {
        // load up mysqli
        $envs = parse_ini_file(dirname($_SERVER['DOCUMENT_ROOT']) . "/config/.env");
        $mysqli = new mysqli($envs["HOST"], $envs["USER"], $envs["PASS"], $envs["DBID"]);
        
        $table = "gal__" . dechex($user_id); // we already know the id must be valid since it comes directly from the database
        $statement = $mysqli->prepare("SELECT `id`, `name`, `mime`, `vector` FROM `$table` WHERE `album_name`=? ORDER BY `created` DESC");
        $statement->bind_param("s", $album_name);
        $statement->execute();

        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);

        $statement->close();
        $mysqli->close();
        return $rows;
    }

    // get all the user's albums
    echo "<div id='album-picker'>";

    $user_albums = get_user_albums($user_data["id"], $user_data["aes"]);
    foreach ($user_albums as $album) {
        $preview = $album["preview"];
        $album_name = $album["album_name"];
        echo "
            <div class='album-icon noselect'>
                $preview
                <h1>$album_name</h1>
            </div>
        ";
    }

    echo "</div>";
?>