<?php
    // create album icons for gallery
    function get_user_albums($user_id) {
        // load up mysqli
        $envs = parse_ini_file(dirname($_SERVER['DOCUMENT_ROOT']) . "/config/.env");
        $mysqli = new mysqli($envs["HOST"], $envs["USER"], $envs["PASS"], $envs["DBID"]);
        
        $statement = $mysqli->prepare("SELECT `id`, `name` FROM `gallery` WHERE `user_id`=? ORDER BY `last_modified` DESC");
        $statement->bind_param("i", $user_id);
        $statement->execute();

        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);

        $statement->close();
        $mysqli->close();

        // add album previews
        for ($i = 0; $i < count($rows); $i++) {
            // get the file path
            $row = $rows[$i];
            $path = $envs["GALLERY_PATH"] . dechex($row["id"]) . "/";
            if (is_dir($path) && count(scandir($path)) > 2) {
                // grab the newest file
                $newest = scandir($path, SCANDIR_SORT_DESCENDING)[0];
                $src = "/assets/favicon.ico";
                $rows[$i]["preview"] = "<img src='$src' class='album-icon-img' alt='Album icon image.'>";
            } else {
                $rows[$i]["preview"] = "<img src='/assets/app-icons/gallery.png' class='album-icon-img default-icon' alt='Album icon image.'>";
            }
        }

        return $rows;
    }

    // get all the user's albums
    echo "<div id='album-picker'>";

    $user_albums = get_user_albums($user_data["id"]);
    foreach ($user_albums as $album) {
        $preview = $album["preview"];
        $name = $album["name"];
        echo "
            <div class='album-icon noselect'>
                $preview
                <h1>$name</h1>
            </div>
        ";
    }

    echo "</div>";

    // display first album, if there are albums
    echo "<div id='album-content'>";
    echo "</div>";
?>