<?php
    // verify post request
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        header('HTTP/1.0 403 Forbidden');
        exit("Error: Invalid request method\nExpected POST, got " . $_SERVER["REQUEST_METHOD"] . ".");
    }
    
    // get note id
    $post_data = json_decode(file_get_contents('php://input'));
    $note_id = $post_data->note_id;
    $title = $post_data->title;
    $body = $post_data->body;

    // recheck auth
    include($_SERVER['DOCUMENT_ROOT'] . "/php/requireAuth.php");
    $user_data = check_auth(true);

    if (gettype($user_data) != "array") {
        // tell the page to reload for anything that isn't proper user_data being returned (ie. invalid auth)
        header('HTTP/1.0 403 Forbidden');
        exit("Error: auth_error\nnull.");
    }

    // we know the auth token is still valid, so check that the note belongs to the user
    $envs = parse_ini_file(dirname($_SERVER['DOCUMENT_ROOT']) . "/config/.env");
    $mysqli = new mysqli($envs["HOST"], $envs["USER"], $envs["PASS"], $envs["DBID"]);
    
    $statement = $mysqli->prepare("SELECT COUNT(*) FROM `notes` WHERE `user_id`=? AND `id`=?");
    $statement->bind_param("ii", $user_data["id"], $note_id);
    $statement->execute();

    $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();
    
    if (count($rows) == 0) {
        header('HTTP/1.0 403 Forbidden');
        exit("Error: Invalid Note\nNote does not exist with that id.");
    } else if (count($rows) > 1) {
        header('HTTP/1.0 403 Forbidden');
        exit("Error: Duplicate Note\nMore than one note was returned from the database. Please try again or contact a system administrator.");
    }

    // check that file exists
    $path = $envs["NOTE_PATH"] . dechex($note_id) . ".txt";
    if (!is_readable($path)) {
        header('HTTP/1.0 403 Forbidden');
        exit("Error: Internal Read Error\nThe file could not be read from the server. Please try again or contact a system administrator.");
    }

    // we know the note is valid, so finally we can update
    $statement = $mysqli->prepare("UPDATE `notes` SET `name`=?, `last_edit`=CURRENT_TIMESTAMP WHERE `id`=?");
    $statement->bind_param("si", $title, $note_id);
    $statement->execute();
    $statement->close();
    $mysqli->close();

    // update the text file
    file_put_contents($path, $body);
?>