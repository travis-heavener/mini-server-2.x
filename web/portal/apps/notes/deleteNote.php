<?php
    include_once($_SERVER['DOCUMENT_ROOT'] . "/php/toolbox.php");

    // verify post request
    if ($_SERVER["REQUEST_METHOD"] !== "DELETE") {
        header('HTTP/1.0 403 Forbidden');
        exit("Error: Invalid request method\nExpected DELETE, got " . $_SERVER["REQUEST_METHOD"] . ".");
    }
    
    // get note id
    $post_data = json_decode(file_get_contents('php://input'));
    $note_id = $post_data->note_id;

    // recheck auth
    include($_SERVER['DOCUMENT_ROOT'] . "/php/requireAuth.php");
    $user_data = check_auth(true);

    if (gettype($user_data) != "array") {
        // tell the page to reload for anything that isn't proper user_data being returned (ie. invalid auth)
        header('HTTP/1.0 403 Forbidden');
        exit("Error: auth_error\nnull.");
    }

    // we know the auth token is still valid, so check that the note belongs to the user
    $envs = loadEnvs();
    $mysqli = new mysqli($envs["HOST"], $envs["USER"], $envs["PASS"], $envs["DBID"]);
    
    $statement = $mysqli->prepare("DELETE FROM `notes` WHERE `user_id`=? AND `id`=?");
    $statement->bind_param("ii", $user_data["id"], $note_id);
    $statement->execute();
    $statement->close();
    
    // delete the file
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
    if (file_exists($path)) {
        unlink($path);
    }
?>