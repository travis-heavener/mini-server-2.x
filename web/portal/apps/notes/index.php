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
        <link rel="preload" as="image" href="/assets/floppy.png">
        <link rel="preload" as="image" href="/assets/trash.png">
        <link rel="preload" as="image" href="/assets/exit.png">

    </head>
    <body>

        <?php include($_SERVER['DOCUMENT_ROOT'] . "/php/createHeader.php") ?>

        <div id="main-content">
            <div id="editor-body">
                <div id="editor-top">
                    <textarea id="editor-title" rows="1" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"></textarea>
                    <img id="editor-delete" class="editor-icon" onclick="deleteNote()" class="noselect" src="/assets/trash.png"></img>
                    <img id="editor-save" class="editor-icon" onclick="saveNote()" class="noselect" src="/assets/floppy.png"></img>
                    <img id="editor-back" class="editor-icon" onclick="redirectToMenu()" class="noselect" src="/assets/exit.png"></img>
                </div>
                <textarea id="editor-text" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"></textarea>
            </div>
            <div id="notes-menu">
                <?php
                    include_once($_SERVER['DOCUMENT_ROOT'] . "/php/toolbox.php");

                    // load in all the user's notes
                    $envs = loadEnvs();
                    $mysqli = new mysqli($envs["HOST"], $envs["USER"], $envs["PASS"], $envs["DBID"]);
                    
                    $statement = $mysqli->prepare("SELECT `id`, `name`, `last_edit` FROM `notes` WHERE `user_id`=? ORDER BY `last_edit` DESC");
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
                        $preview = "";

                        if (is_readable($path)) {
                            $ref = fopen($path, "rb");
                            $preview = fread($ref, 1024);
                            $preview = str_replace("\n", "<br>", $preview);
                            fclose($ref);   
                        } else {
                            $preview = "<em>Error: file could not be retrieved.</em>";
                        }

                        echo "
                            <div class='note-icon noselect' data-id='$id' onclick=\"redirectToNote(parseInt($(this).attr('data-id')))\">
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

                    // add blank note creator button
                    echo "
                        <div id='note-creator' class='note-icon noselect' onclick='createNote()'>
                            <p>+</p>
                        </div>
                    ";
                ?>
            </div>
        </div>

        <?php include($_SERVER['DOCUMENT_ROOT'] . "/php/createFooter.php") ?>

        <script src="index.js" type="text/javascript"></script>

    </body>
</html>