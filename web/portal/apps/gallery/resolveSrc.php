<?php
    // retrieve the content src for an individual item from the server
    include("./toolbox.php");
    include($_SERVER['DOCUMENT_ROOT'] . "/php/requireAuth.php");

    // 1. check for GET
    if ($_SERVER["REQUEST_METHOD"] !== "GET") {
        header('HTTP/1.0 403 Forbidden');
        exit("Error: Invalid Request Method\nExpected GET, got " . $_SERVER["REQUEST_METHOD"] . ".");
    }

    // 2. extract headers (best way to do this with custom headers I guess)
    $headers = getallheaders();
    $id = $headers["MS2_id"];
    $is_thumb = $headers["MS2_isThumb"];
    
    if (!$is_thumb) {
        $img_width = $headers["MS2_imgWidth"];
        $img_height = $headers["MS2_imgHeight"];
    }

    // 3. load up mysqli
    $envs = parse_ini_file(dirname($_SERVER['DOCUMENT_ROOT']) . "/config/.env");
    $mysqli = new mysqli($envs["HOST"], $envs["USER"], $envs["PASS"], $envs["DBID"]);

    // 4. verify auth
    $user_data = check_auth(true, $envs, $mysqli);

    if (gettype($user_data) !== "array") {
        // tell the page to reload for anything that isn't proper user_data being returned (ie. invalid auth)
        header('HTTP/1.0 403 Forbidden');
        exit("Error: auth_error\nnull.");
    }

    $user_id = $user_data["id"];
    
    // 5. get data from db
    $table = "gal__" . dechex($user_id); // we already know the id must be valid since it comes directly from the database
    $statement = $mysqli->prepare("SELECT `name`, `mime`, `width`, `height`, `orientation` FROM `$table` WHERE `id`=? LIMIT 1;");
    $statement->bind_param("i", $id);
    $statement->execute();
    $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();
    $mysqli->close();

    // 6. format return data
    if (count($rows) === 0) {
        header('HTTP/1.0 403 Forbidden');
        exit("Error: Internal Server Error\nThe requested asset could not be found.");
    }

    $row = $rows[0];
    
    // unencrypt content & add raw data
    $key = $user_data["aes"];
    $content_path = gen_media_path($envs["GALLERY_PATH"], $user_id, $id);
    $thumb_path = gen_thumb_path($envs["GALLERY_PATH"], $user_id, $id);
    $MIME = $row["mime"];

    // check that the file exists on the backend
    header("MS2_mime: $MIME");
    header("MS2_id: " . $id);
    header("MS2_name: " . $row["name"]);
    header("MS2_width: " . $row["width"]);
    header("MS2_height: " . $row["height"]);
    header("MS2_orientation: " . $row["orientation"]+1);

    if (!file_exists($content_path) || (!str_starts_with($MIME, "image") && !str_starts_with($MIME, "video"))) {
        header('Content-type: application/json');
        header("MS2_isDefaultIcon: true");
        $row["src"] = json_encode(["s"=>"/assets/app-icons/gallery.png"]);
    } else {
        // decrypt the file w/ toolbox helper function (really proud I wrote that)
        $raw = content_decrypt(($is_thumb && str_starts_with($MIME, "image")) ? $thumb_path : $content_path, $key);

        // handle different source types for image/video
        if (str_starts_with($MIME, "image")) {
            // declare content-type as JSON
            header('Content-type: application/json');

            if ($is_thumb) {
                // grab thumbnail
                $row["src"] = "data:$MIME;base64," . base64_encode($raw);
            } else {
                // resize each image (also strips metadata, IMPORTANT!!!!)
                $row["src"] = "data:$MIME;base64," . base64_encode(resize_image($raw, $img_width, $img_height, $row["width"], $row["height"], $row["orientation"]+1));
            }

            // json encode
            $row["src"] = json_encode(["s"=>$row["src"]]);
        } else {
            // declare content-type as blob
            header('Content-type: application/octet-stream');
            $row["src"] = $raw;
        }
    }

    // 7. shrink return data & remove IV
    echo $row["src"];
?>