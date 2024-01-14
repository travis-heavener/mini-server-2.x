<?php
    // create album icons for gallery
    include("./toolbox.php");
    
    function get_user_albums($user_id, $key) {
        // load up mysqli
        $envs = parse_ini_file(dirname($_SERVER['DOCUMENT_ROOT']) . "/config/.env");
        $mysqli = new mysqli($envs["HOST"], $envs["USER"], $envs["PASS"], $envs["DBID"]);
        
        $table = "gal__" . dechex($user_id); // we already know the id must be valid since it comes directly from the database
        $statement = $mysqli->prepare("SELECT `id`, `album_name`, `mime` FROM `$table` WHERE `id` IN (SELECT MAX(`id`) AS `id` FROM `gal__1` GROUP BY `album_name`) ORDER BY `uploaded` DESC;");
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        // add album previews
        for ($i = 0; $i < count($rows); $i++) {
            // get the file path
            $row = $rows[$i];
            $path = gen_thumb_path($envs["GALLERY_PATH"], $user_id, $row["id"]);
            $MIME = $row["mime"];

            // get this image as the album cover since it's an image
            if (str_starts_with($MIME, "image") && file_exists($path)) {
                // get iv from file
                $content_handle = fopen($path, "rb");
                $iv = fread($content_handle, 16); // iv length of 16 w/ aes-256
                fclose($content_handle);
                
                $data = file_get_contents($path, false, null, 16); // skip iv
                $image = openssl_decrypt($data, "aes-256-ctr", $key, $options=0, $iv);
                $src = "data:$MIME;base64," . base64_encode($image);
                $rows[$i]["preview"] = "<img src='$src' class='album-icon-img' alt='Album icon image.'>";
                continue;
            } else if (!str_starts_with($MIME, "image")) {
                // get the next image if the newest album entry is not an image (ie. video)
                $statement = $mysqli->prepare("SELECT * FROM `gal__1` WHERE `mime` LIKE 'image%' AND `album_name`=? ORDER BY `uploaded` DESC LIMIT 1;");
                $statement->bind_param("s", $rows[$i]["album_name"]);
                $statement->execute();
                $temp_rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
                $statement->close();
                
                if (count($temp_rows) > 0) {
                    $path = $envs["GALLERY_PATH"] . dechex($user_id) . "_" . dechex($temp_rows[0]["id"]) . ".bin";
                    $path = gen_thumb_path($envs["GALLERY_PATH"], $user_id, $row["id"]);
                    if (file_exists($path)) {
                        // get iv from file
                        $content_handle = fopen($path, "rb");
                        $iv = fread($content_handle, 16); // iv length of 16 w/ aes-256
                        fclose($content_handle);

                        $data = file_get_contents($path, false, null, 16); // skip iv
                        $image = openssl_decrypt($data, "aes-256-ctr", $key, $options=0, $iv);
                        $MIME = $temp_rows[0]["mime"];
                        $src = "data:$MIME;base64," . base64_encode($image);
                        
                        $rows[$i]["preview"] = "<img src='$src' class='album-icon-img' alt='Album icon image.'>";
                        $rows[$i]["mime"] = $temp_rows[0]["mime"];
                        continue;
                    }
                }
            }

            // base case, placeholder image is inserted
            $rows[$i]["preview"] = "<img src='/assets/app-icons/gallery.png' class='album-icon-img default-icon' alt='Album icon image.'>";
        }

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