<?php
    include_once($_SERVER['DOCUMENT_ROOT'] . "/php/toolbox.php");

    // verify post request
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        header('HTTP/1.0 403 Forbidden');
        exit("Error: Invalid request method\nExpected POST, got " . $_SERVER["REQUEST_METHOD"] . ".");
    }

    // recheck auth
    include($_SERVER['DOCUMENT_ROOT'] . "/php/requireAuth.php");
    $user_data = check_auth(true);

    if (gettype($user_data) != "array") {
        // tell the page to reload for anything that isn't proper user_data being returned (ie. invalid auth)
        header('HTTP/1.0 403 Forbidden');
        exit("Error: auth_error\nnull.");
    }

    // load up mysqli
    $envs = loadEnvs();
    $mysqli = new mysqli($envs["HOST"], $envs["USER"], $envs["PASS"], $envs["DBID"]);

    // we know the user is valid, so create the note
    $statement = $mysqli->prepare("INSERT INTO `notes` (`user_id`) VALUES (?)");
    $statement->bind_param("i", $user_data["id"]);
    $statement->execute();
    $statement->close();
    
    // get the newest entry
    $statement = $mysqli->prepare("SELECT LAST_INSERT_ID()");
    $statement->execute();
    
    $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $note_id = $rows[0]["LAST_INSERT_ID()"];

    $statement->close();
    $mysqli->close();

    // create the new text file
    $path = $envs["NOTE_PATH"] . dechex($note_id) . ".txt";
    file_put_contents($path, "");

    // echo back the new note_id
    echo $note_id;
?>