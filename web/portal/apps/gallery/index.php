<!DOCTYPE html>
<html lang="en">
    <head>
        
        <?php
            include($_SERVER['DOCUMENT_ROOT'] . "/php/requireAuth.php");
            include($_SERVER['DOCUMENT_ROOT'] . "/php/importAssets.php"); // import other assets & add meta tag, jQuery, & preloads
            include($_SERVER['DOCUMENT_ROOT'] . "/php/createBackAnim.php"); // add background animation

            $user_data = check_auth(); // actually call to check the auth
            verify_perms($user_data); // verify the user has access to this page
            echo format_title("Gallery"); // add document title
        ?>

        <link rel="stylesheet" href="index.css" type="text/css">

    </head>
    <body>

        <?php include($_SERVER['DOCUMENT_ROOT'] . "/php/createHeader.php") ?>

        <div id="main-content">
            <?php include("createAlbums.php"); ?>
        </div>

        <form action="javascript:uploadFile()" method="post" enctype="multipart/form-data">
            <input type="file" name="user-media[]" multiple>
            <input type="text" name="album-id" value="1">
            <input type="text" name="album-name" value="My First Name">
            <input type="submit" value="Submit">
        </form>

        <?php include($_SERVER['DOCUMENT_ROOT'] . "/php/createFooter.php") ?>

        <script src="index.js" type="text/javascript"></script>

    </body>
</html>