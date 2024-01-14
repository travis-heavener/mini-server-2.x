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
    $EXTS = ["image/png", "image/jpeg", "image/heic", "video/mp4", "video/mkv", "video/quicktime", "video/x-msvideo"];
    if ($_FILES["user-media"]["name"][0] === "") {
        header('HTTP/1.0 403 Forbidden');
        exit("Error: Invalid File Count\nA minimum of one file must be uploaded.");
    }

    for ($i = 0; $i < count($_FILES["user-media"]["name"]); $i++) {
        $name = $_FILES["user-media"]["name"][$i];
        $content = file_get_contents($_FILES["user-media"]["tmp_name"][$i]);
        $MIME = mime_content_type($_FILES["user-media"]["tmp_name"][$i]);
        $ext = pathinfo($_FILES["user-media"]["name"][$i], PATHINFO_EXTENSION);

        // get EXIF rotation, if present
        $exif = @exif_read_data($_FILES["user-media"]["tmp_name"][$i]); // suppress warning that cannot really be avoided (https://stackoverflow.com/a/55353637)
        $orientation = ($exif && !empty($exif['Orientation'])) ? $exif["Orientation"] : 1;

        if (!in_array($MIME, $EXTS)) {
            header('HTTP/1.0 403 Forbidden');
            exit("Error: Invalid File Type\nThe content MIME type \"$MIME\" is not allowed.");
        }

        array_push($files_data, [ "name" => $name, "content" => $content, "MIME" => $MIME, "created" => $timestamps[$i], "orientation" => $orientation ]);
    }

    // 4. verify auth
    include($_SERVER['DOCUMENT_ROOT'] . "/php/requireAuth.php");
    $user_data = check_auth(true);
    $user_id = $user_data["id"];

    if (gettype($user_data) !== "array") {
        // tell the page to reload for anything that isn't proper user_data being returned (ie. invalid auth)
        header('HTTP/1.0 403 Forbidden');
        exit("Error: auth_error\nnull.");
    }

    // NOW we can store the image/video
    // 5. load mysqli
    $envs = parse_ini_file(dirname($_SERVER['DOCUMENT_ROOT']) . "/config/.env");
    $mysqli = new mysqli($envs["HOST"], $envs["USER"], $envs["PASS"], $envs["DBID"]);

    // 6. grab user's secret
    $key = $user_data["aes"];

    // 7. parse/format incoming files into an assoc array w/ other metadata
    $cipher = "aes-256-ctr";
    if (!in_array($cipher, openssl_get_cipher_methods())) {
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
            $compressed_thumb = resize_image($compressed_thumb, $THUMB_SIZE, $THUMB_SIZE, $width, $height);
            $file["MIME"] = "image/jpeg";
        } else if (str_starts_with($file["MIME"], "image")) {
            $compressed = $file["content"];
            $compressed_thumb = rotate_imagejpeg_str($file["content"], $file["orientation"]);
            $compressed_thumb = resize_image($compressed_thumb, $THUMB_SIZE, $THUMB_SIZE, $width, $height);
        } else {
            $compressed = $file["content"];
            $compressed_thumb = false;
        }

        // 8. insert into table
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        
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
        if (file_exists($image_path)) {
            header('HTTP/1.0 403 Forbidden');
            exit("Error: File Already Exists\nAn uploaded file already exists at this path: " . dechex($user_data["id"]) . "_" . dechex($id) . ".bin");
        } else if (file_exists($thumb_path) && $compressed_thumb !== false) {
            header('HTTP/1.0 403 Forbidden');
            exit("Error: File Already Exists\nAn uploaded file already exists at this path: " . dechex($user_data["id"]) . "_" . dechex($id) . "T.bin");
        }

        // 10. encrypt via openssl_encrypt and put image & thumbnail in file system
        $cipher_img = openssl_encrypt($compressed, $cipher, $key, $options=0, $iv);
        file_put_contents($image_path, $iv . $cipher_img);

        if ($compressed_thumb !== false) {
            $cipher_thumb = openssl_encrypt($compressed_thumb, $cipher, $key, $options=0, $iv);
            file_put_contents($thumb_path, $iv . $cipher_thumb);
        }
    }

    // wrap up
    $mysqli->close();

    // I think that's it
    // ^^^^^ this note is immediately after I wrote like 100+ lines without testing it once and it somehow worked (with an older setup of the database & such ofc)
    // still awesome tho
    // - Travis Heavener (7:48 PM, 01/13/2024)
?>