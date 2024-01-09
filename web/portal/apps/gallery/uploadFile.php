<?php
    // 1. check for POST
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        header('HTTP/1.0 403 Forbidden');
        exit("Error: Invalid request method\nExpected POST, got " . $_SERVER["REQUEST_METHOD"] . ".");
    }

    // 2. extract body
    $post_data = json_decode(file_get_contents('php://input'));
    $album_id = $post_data->album_id;

    // 3. verify file type is accepted
    $files_data = [];
    $EXTS = ["jpg", "jpeg", "png", "heic", "avi", "mov", "mkv", "mp4"];
    for ($i = 0; $i < count($_FILES["user-media"]); $i++) {
        $name = basename($_FILES["user-media"]["name"][$i]);
        $content = file_get_contents($_FILES["user-media"]["tmp_name"][$i]);
        $ext = pathinfo($_FILES["userImg"]["name"], PATHINFO_EXTENSION);

        if (!in_array(strtolower($ext), $EXTS)) {
            header('HTTP/1.0 403 Forbidden');
            exit("Error: Invalid File Type\nThe file extension \"$ext\" is not allowed.");
        }

        array_push($files_data, [ "name" => $name, "content" => $content ]);
    }

    if (count($files_data) === 0) {
        header('HTTP/1.0 403 Forbidden');
        exit("Error: Invalid File Count\nA minimum of one file must be uploaded.");
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

    // 6. verify the album exists and belongs to the user
    $statement = $mysqli->prepare("SELECT * FROM `gallery` WHERE `user_id`=?, `id`=?");
    $statement->bind_param("ii", $user_data["id"], $album_id);
    $statement->execute();
    $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();

    if (count($rows) == 0) {
        header('HTTP/1.0 403 Forbidden');
        exit("Error: Invalid Album\nAlbum does not exist with that id.");
    } else if (count($rows) > 1) {
        header('HTTP/1.0 403 Forbidden');
        exit("Error: Duplicate Album\nMore than one album was returned from the database. Please try again or contact a system administrator.");
    }

    $path = $envs["GALLERY_PATH"] . dechex($album_id) . "/";
    if (!is_dir($path)) {
        header('HTTP/1.0 403 Forbidden');
        exit("Error: Invalid Destination\nThe upload album specified could not be found on the server.");
    }

    // 7. grab user's secret
    // ex. secret_16 (or however long the key must be)

    // 8. parse/format incoming files into an assoc array w/ other metadata
    $cipher = "aes-256-ctr";
    if (!in_array($cipher, openssl_get_cipher_methods())) {
        header('HTTP/1.0 403 Forbidden');
        exit("Error: Invalid Cipher\nThe provided cipher was not recognized by the server.");
    }

    function get_next_id() {
        // get next id
        $statement = $mysqli->prepare("SELECT `next_id` FROM `gallery` WHERE `user_id`=?, `id`=?");
        $statement->bind_param("ii", $user_data["id"], $album_id);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        $next_id = $rows[0]['next_id'];

        // update database
        $statement = $mysqli->prepare("UPDATE `gallery` SET `next_id`=? WHERE `user_id`=?, `id`=?");
        $statement->bind_param("iii", $next_id+1, $user_data["id"], $album_id);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        return $next_id;
    }

    foreach ($files_data as $file) {
        $data_json = json_encode($file);

        // 9. openssl_encrypt the parsed & formatted json_encode(assoc_array)
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext = openssl_encrypt($data_json, $cipher, $secret, 0, $iv);

        // 10. upload file to correct album
        $next_id = get_next_id();
        file_put_contents($path . dechex($next_id), $ciphertext);
    }

    // I think that's it
?>