<?php
    // upload a file to the system
    include("./toolbox.php");

    // 1. check for POST
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        header('HTTP/1.0 403 Forbidden');
        exit("Error: Invalid request method\nExpected POST, got " . $_SERVER["REQUEST_METHOD"] . ".");
    }

    // 2. extract body
    $album_name = $_POST["album-name"];
    $dimensions = json_decode($_POST["dimensions"]);
    $timestamps = json_decode($_POST["timestamps"]);

    // 3. verify file type is accepted
    $files_data = [];
    if ($_FILES["user-media"]["name"][0] === "") {
        header('HTTP/1.0 403 Forbidden');
        exit("Error: Invalid File Count\nA minimum of one file must be uploaded.");
    }

    for ($i = 0; $i < count($_FILES["user-media"]["name"]); $i++) {
        $name = $_FILES["user-media"]["name"][$i];
        $tmp_name = $_FILES["user-media"]["tmp_name"][$i];
        $content = file_get_contents($tmp_name);
        $MIME = mime_content_type($tmp_name);
        $ext = pathinfo($_FILES["user-media"]["name"][$i], PATHINFO_EXTENSION);

        // get EXIF rotation, if present
        $exif = @exif_read_data($tmp_name); // suppress warning that cannot really be avoided (https://stackoverflow.com/a/55353637)
        $orientation = ($exif && !empty($exif['Orientation'])) ? $exif["Orientation"] : 1;

        if (!in_array($MIME, SUPPORTED_MIMES)) {
            header('HTTP/1.0 403 Forbidden');
            exit("Error: Invalid File Type\nThe content MIME type \"$MIME\" is not allowed.");
        }

        array_push($files_data, [ "name" => $name, "content" => $content, "MIME" => $MIME, "created" => $timestamps[$i], "orientation" => $orientation, "tmp_name" => $tmp_name ]);
    }

    // 4. verify auth
    include($_SERVER['DOCUMENT_ROOT'] . "/php/requireAuth.php");
    $user_data = check_auth(true);

    if (gettype($user_data) !== "array") {
        // tell the page to reload for anything that isn't proper user_data being returned (ie. invalid auth)
        header('HTTP/1.0 403 Forbidden');
        exit("Error: auth_error\nnull.");
    }

    $user_id = $user_data["id"];

    // NOW we can store the image/video
    // 5. load mysqli
    $envs = parse_ini_file(dirname($_SERVER['DOCUMENT_ROOT']) . "/config/.env");
    $mysqli = new mysqli($envs["HOST"], $envs["USER"], $envs["PASS"], $envs["DBID"]);

    // 6. grab user's secret
    $key = $user_data["aes"];

    // 7. parse/format incoming files into an assoc array w/ other metadata
    if (!in_array(CIPHER, openssl_get_cipher_methods())) {
        header('HTTP/1.0 403 Forbidden');
        exit("Error: Invalid Cipher\nThe provided cipher was not recognized by the server.");
    }

    $table = "gal__" . dechex($user_data["id"]);
    for ($i = 0; $i < count($files_data); $i++) {
        $file = $files_data[$i];
        $width = $dimensions[$i][0];
        $height = $dimensions[$i][1];
        
        // compress file format, if necessary
        if ($file["MIME"] === "image/png" || $file["MIME"] === "image/jpeg") {
            // compress images to JPEG
            $compressed = rotate_imagejpeg_str($file["content"], $file["orientation"]);
            $compressed_thumb = rotate_imagejpeg_str($file["content"], $file["orientation"]);
            $compressed_thumb = resize_image($compressed_thumb, THUMB_SIZE, THUMB_SIZE, $width, $height);
            $file["MIME"] = "image/jpeg";
        } else if (str_starts_with($file["MIME"], "image")) {
            $compressed = $file["content"];
            $compressed_thumb = rotate_imagejpeg_str($file["content"], $file["orientation"]);
            $compressed_thumb = resize_image($compressed_thumb, THUMB_SIZE, THUMB_SIZE, $width, $height);
        } else if(str_starts_with($file["MIME"], "video")) {
            // if we have a video, create a thumbnail
            $compressed = $file["content"];
            $compressed_thumb = resize_video($file["tmp_name"], THUMB_SIZE, THUMB_SIZE);
        } else {
            $compressed = $file["content"];
            $compressed_thumb = false;
        }

        // 8. insert into table
        $iv = openssl_random_pseudo_bytes(IVLEN);
        
        $created = intval( $file["created"] )/1e3;
        $created_ts = date("Y-m-d H:i:s", $created);

        $orientation = $file["orientation"]-1;

        $statement = $mysqli->prepare("INSERT INTO `$table` (`name`, `album_name`, `mime`, `width`, `height`, `orientation`, `created`) VALUES (?,?,?,?,?,?,?);");
        $statement->bind_param("ssssiis", $file["name"], $album_name, $file["MIME"], $width, $height, $orientation, $created_ts);
        $statement->execute();
        $statement->close();

        // 9. get id
        $statement = $mysqli->prepare("SELECT LAST_INSERT_ID();");
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $id = $rows[0]["LAST_INSERT_ID()"];
        $statement->close();

        $image_path = gen_media_path($envs["GALLERY_PATH"], $user_id, $id);
        $thumb_path = gen_thumb_path($envs["GALLERY_PATH"], $user_id, $id);
        if (file_exists($image_path) || (file_exists($thumb_path) && $compressed_thumb !== false)) {
            // remove from database
            $statement = $mysqli->prepare("DELETE FROM `$table` WHERE `id`=?");
            $statement->bind_param("i", $id);
            $statement->execute();
            $statement->close();
            $mysqli->close();
            header('HTTP/1.0 403 Forbidden');
            exit("Error: File Already Exists\nThe generated name for file is already taken: \"" . $file["name"] . "\"<br>Contact a system administrator.");
        }

        // 10. encrypt via openssl_encrypt and put image & thumbnail in file system
        content_encrypt($compressed, $image_path, $key, $iv);
        
        if ($compressed_thumb !== false)
            content_encrypt($compressed_thumb, $thumb_path, $key, $iv);
    }

    // wrap up
    $mysqli->close();

    // I think that's it
    // ^^^^^ this note is immediately after I wrote like 100+ lines without testing it once and it somehow worked (with an older setup of the database & such ofc)
    // still awesome tho
    // - Travis Heavener (7:48 PM, 01/13/2024)
?>