<!DOCTYPE html>
<html lang="en">
    <head>
        
        <?php
            include($_SERVER['DOCUMENT_ROOT'] . "/php/requireAuth.php");
            include($_SERVER['DOCUMENT_ROOT'] . "/php/importAssets.php"); // import other assets & add meta tag, jQuery, & preloads
            include($_SERVER['DOCUMENT_ROOT'] . "/php/createBackAnim.php"); // add background animation

            $user_data = check_auth(); // actually call to check the auth
            verify_perms($user_data); // verify the user has access to this page
            echo format_title("Notes"); // add document title
        ?>

        <link rel="stylesheet" href="index.css" type="text/css">

    </head>
    <body>

        <?php include($_SERVER['DOCUMENT_ROOT'] . "/php/createHeader.php") ?>

        <div id="main-content">
            <div id="editor-body"></div>
            <div id="notes-menu">
                <?php
                    // load in all the user's notes
                    $envs = parse_ini_file(dirname($_SERVER['DOCUMENT_ROOT']) . "/config/.env");
                    $mysqli = new mysqli($envs["HOST"], $envs["USER"], $envs["PASS"], $envs["DBID"]);
                    
                    $statement = $mysqli->prepare("SELECT `id`, `name`, `last_edit` FROM `notes` WHERE `user_id`=? ORDER BY `last_edit` ASC");
                    $statement->bind_param("i", $user_data["id"]);
                    $statement->execute();

                    $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
                    $statement->close();
                    $mysqli->close();

                    // iterate over each row and display the file
                    foreach ($rows as $row) {
                        $id = $row["id"];
                        $name = $row["name"];
                        $last_date = date("m/d/y", strtotime($row["last_edit"]));
                        $last_time = date("h:ia", strtotime($row["last_edit"]));

                        // get preview text
                        $path = $envs["NOTE_PATH"] . dechex($id) . ".txt";
                        $ref = fopen($path, "rb");
                        $preview = fread($ref, 1024);
                        $preview = str_replace("\n", "<br>", $preview);
                        fclose($ref);

                        echo "
                            <div class='note-icon noselect' data-id='$id' onclick=\"focusNote(parseInt($(this).attr('data-id')))\">
                                <div class='note-data'>
                                    <h1 class='note-title'>$name</h1>
                                    <h2 class='note-datetime'>$last_date</h2>
                                    <h2 class='note-datetime'>$last_time</h2>
                                </div>
                                <div class='note-preview'>
                                    <p>$preview</p>
                                </div>
                            </div>
                        ";
                        echo "
                            <div class='note-icon noselect' data-id='$id' onclick=\"focusNote(parseInt($(this).attr('data-id')))\">
                                <div class='note-data'>
                                    <h1 class='note-title'>$name</h1>
                                    <h2 class='note-datetime'>$last_date</h2>
                                    <h2 class='note-datetime'>$last_time</h2>
                                </div>
                                <div class='note-preview'>
                                    <p>$preview</p>
                                </div>
                            </div>
                        ";
                    }
                ?>
            </div>
        </div>

        <?php include($_SERVER['DOCUMENT_ROOT'] . "/php/createFooter.php") ?>

        <script src="index.js" type="text/javascript"></script>

    </body>
</html>