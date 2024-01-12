<?php
    // load content from database on command

    // 1. check for GET
    if ($_SERVER["REQUEST_METHOD"] !== "GET") {
        header('HTTP/1.0 403 Forbidden');
        exit("Error: Invalid request method\nExpected GET, got " . $_SERVER["REQUEST_METHOD"] . ".");
    }

    // 2. extract headers (best way to do this with custom headers I guess)
    $headers = getallheaders();
    $album_name = $headers["MS2_albumName"];
    $max_amt = $headers["MS2_maxAmt"];
    $offset = $headers["MS2_offset"];

    // 3. verify auth
    include($_SERVER['DOCUMENT_ROOT'] . "/php/requireAuth.php");
    $user_data = check_auth(true);
    $user_id = $user_data["id"];

    if (gettype($user_data) !== "array") {
        // tell the page to reload for anything that isn't proper user_data being returned (ie. invalid auth)
        header('HTTP/1.0 403 Forbidden');
        exit("Error: auth_error\nnull.");
    }

    // 4. load up mysqli
    $envs = parse_ini_file(dirname($_SERVER['DOCUMENT_ROOT']) . "/config/.env");
    $mysqli = new mysqli($envs["HOST"], $envs["USER"], $envs["PASS"], $envs["DBID"]);
    
    // 5. get data from db
    $table = "gal__" . dechex($user_id); // we already know the id must be valid since it comes directly from the database
    $statement = $mysqli->prepare("SELECT `id`, `name`, `mime`, `vector` FROM `$table` WHERE `album_name`=? ORDER BY `created` DESC LIMIT ? OFFSET ?");
    $statement->bind_param("sii", $album_name, $max_amt, $offset);
    $statement->execute();
    $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();
    $mysqli->close();

    // 6. format return data
    $output = [];
    $key = $user_data["aes"];

    foreach ($rows as $row) {
        // unencrypt content
        $iv = $row["vector"];

        // add raw data
        $path = $envs["GALLERY_PATH"] . dechex($user_id) . "_" . dechex($row["id"]) . ".bin";
        $MIME = $row["mime"];

        // check that the file exists on the backend
        if (!file_exists($path) || (!str_starts_with($MIME, "image") && !str_starts_with($MIME, "video"))) {
            continue;
        }

        $data = file_get_contents($path);
        $raw = openssl_decrypt($data, "aes-256-ctr", $key, $options=0, $iv);

        // handle different source types for image/video
        if (str_starts_with($MIME, "image")) {
            $row["src"] = "data:$MIME;base64," . base64_encode($raw);
        } else {
            $row["src"] = "data:$MIME;base64," . base64_encode($raw);
        }

        // remove sensitive info
        unset($row["vector"]);

        // append to output
        array_push($output, $row);
    }

    // 7. final return of data
    echo json_encode(["content" => $output]);
?>