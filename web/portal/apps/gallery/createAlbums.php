<?php
    // create album icons for gallery
    function get_user_albums($user_id, $key) {
        // load up mysqli
        $envs = parse_ini_file(dirname($_SERVER['DOCUMENT_ROOT']) . "/config/.env");
        $mysqli = new mysqli($envs["HOST"], $envs["USER"], $envs["PASS"], $envs["DBID"]);
        
        $statement = $mysqli->prepare(
            "SELECT `id`, `name`, `album_name`, `MIME`, `vector` FROM `gallery` WHERE `id` IN (SELECT `id` FROM (SELECT MAX(`created`), MAX(`id`) AS `id` FROM `gallery` WHERE `user_id`=? GROUP BY `album_name`)d) ORDER BY `created` DESC"
        );
        $statement->bind_param("i", $user_id);
        $statement->execute();

        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);

        $statement->close();
        $mysqli->close();

        // filter out any duplicate entries ()

        // add album previews
        for ($i = 0; $i < count($rows); $i++) {
            // get the file path
            $row = $rows[$i];
            $path = $envs["GALLERY_PATH"] . dechex($row["id"]) . ".bin";
            $MIME = $row["MIME"];
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
        
        $statement = $mysqli->prepare("SELECT `id`, `name`, `MIME`, `vector` FROM `gallery` WHERE `user_id`=? AND `album_name`=? ORDER BY `created` DESC");
        $statement->bind_param("is", $user_id, $album_name);
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

    // display first album, if there are albums
    echo "<div id='album-content'>";
    if (count($user_albums) > 0) {
        $envs = parse_ini_file(dirname($_SERVER['DOCUMENT_ROOT']) . "/config/.env");
        $key = $user_data["aes"];
        $album = get_full_album($user_data["id"], $user_albums[0]["album_name"]);

        foreach ($album as $media) {
            $path = $envs["GALLERY_PATH"] . dechex($media["id"]) . ".bin";
            $MIME = $media["MIME"];
            if (!file_exists($path) || (!str_starts_with($MIME, "image") && !str_starts_with($MIME, "video"))) {
                echo "<img src='/assets/app-icons/gallery.png' class='default-icon' alt='Album icon image.'>\n";
                continue;
            }

            $data = file_get_contents($path);
            $raw = openssl_decrypt($data, "aes-256-ctr", $key, $options=0, $media["vector"]);

            if (str_starts_with($MIME, "image")) { // display image
                $src = "data:$MIME;base64," . base64_encode($raw);
                echo "<img src='$src' class='album-icon-img' alt='Album image.'>";
            } else if (str_starts_with($MIME, "video")) { // display video
                echo "video stuff";
            }
        }
    }
    echo "</div>";
?>