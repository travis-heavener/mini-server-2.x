<?php
    function base64url_decode($string) {
        return base64_decode(str_replace(['-','_'], ['+','/'], $string));
    }

    // verify post request
    if ($_SERVER["REQUEST_METHOD"] !== "GET") {
        echo "Invalid request method: expected GET, got " . $_SERVER["REQUEST_METHOD"] . ".";
        exit();
    }
    
    // get note id
    $note_id = $_GET["id"];

    // get user_id from JWT (the auth is rechecked when the page reloads anyways so we don't have to check here)
    $cookie = $_COOKIE["ms-user-auth"];
    $body = explode(".", $cookie)[1];
    $body_dec = json_decode(base64url_decode($body));
    $user_id = $body_dec->id;
    
    // load up mysqli
    $envs = parse_ini_file(dirname($_SERVER['DOCUMENT_ROOT']) . "/config/.env");
    $mysqli = new mysqli($envs["HOST"], $envs["USER"], $envs["PASS"], $envs["DBID"]);
    
    $statement = $mysqli->prepare("SELECT `id`, `name`, `last_edit`, `created` FROM `notes` WHERE `user_id`=? AND `id`=? ORDER BY `last_edit` ASC");
    $statement->bind_param("ii", $user_id, $note_id);
    $statement->execute();

    $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();
    $mysqli->close();

    if (count($rows) == 0) {
        header('HTTP/1.0 403 Forbidden');
        exit("Error: Invalid Note\nNote does not exist with that id.");
    } else if (count($rows) > 1) {
        header('HTTP/1.0 403 Forbidden');
        exit("Error: Duplicate Note\nMore than one note was returned from the database. Please try again or contact a system administrator.");
    }

    // iterate over each row and display the file
    $row = $rows[0];
    
    // check that file exists
    $path = $envs["NOTE_PATH"] . dechex($note_id) . ".txt";
    if (!is_readable($path)) {
        header('HTTP/1.0 403 Forbidden');
        exit("Error: Internal Read Error\nThe file could not be read from the server. Please try again or contact a system administrator.");
    }

    // read the text
    $ref = fopen($path, "rb");
    $text = fread($ref, filesize($path));
    $row["body"] = $text;
    fclose($ref);

    // return body
    echo json_encode($row);
?>