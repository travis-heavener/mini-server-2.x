<?php
    // 1. check for POST
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        header('HTTP/1.0 403 Forbidden');
        exit("Error: Invalid request method\nExpected POST, got " . $_SERVER["REQUEST_METHOD"] . ".");
    }

    // 2. extract body
    $album_name = $_POST["album-name"];

    // 3. verify file type is accepted
    $files_data = [];
    $EXTS = ["jpg", "jpeg", "png", "heic", "avi", "mov", "mkv", "mp4"];
    if ($_FILES["user-media"]["name"][0] === "") {
        header('HTTP/1.0 403 Forbidden');
        exit("Error: Invalid File Count\nA minimum of one file must be uploaded.");
    }

    for ($i = 0; $i < count($_FILES["user-media"]["name"]); $i++) {
        $name = $_FILES["user-media"]["name"][$i];
        $content = file_get_contents($_FILES["user-media"]["tmp_name"][$i]);
        $MIME = mime_content_type($_FILES["user-media"]["tmp_name"][$i]);
        $ext = pathinfo($_FILES["user-media"]["name"][$i], PATHINFO_EXTENSION);

        if (!in_array(strtolower($ext), $EXTS)) {
            header('HTTP/1.0 403 Forbidden');
            exit("Error: Invalid File Type\nThe file extension \"$ext\" is not allowed.");
        }

        array_push($files_data, [ "name" => $name, "content" => $content, "MIME" => $MIME ]);
    }

    // 4. verify auth
    include($_SERVER['DOCUMENT_ROOT'] . "/php/requireAuth.php");
    $user_data = check_auth(true);

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
    foreach ($files_data as $file) {
        // 8. insert into table
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);

        $statement = $mysqli->prepare("INSERT INTO `$table` (`name`, `album_name`, `mime`, `vector`) VALUES (?,?,?,?)");
        $statement->bind_param("ssss", $file["name"], $album_name, $file["MIME"], $iv);
        $statement->execute();
        $statement->close();

        // 9. get id
        $statement = $mysqli->prepare("SELECT LAST_INSERT_ID()");
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $id = $rows[0]["LAST_INSERT_ID()"];
        $statement->close();

        $path = $envs["GALLERY_PATH"] . dechex($user_data["id"]) . "_" . dechex($id) . ".bin";
        if (file_exists($path)) {
            header('HTTP/1.0 403 Forbidden');
            exit("Error: File Already Exists\nAn uploaded file already exists at this path: " . dechex($id) . ".bin.");
        }

        // 10. openssl_encrypt
        $ciphertext = openssl_encrypt($file["content"], $cipher, $key, $options=0, $iv);
        print_r($ciphertext);
        file_put_contents($path, $ciphertext);
    }

    // I think that's it
?>