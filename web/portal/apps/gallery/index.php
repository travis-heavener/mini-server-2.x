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
            
            // check that the user has their own gallery table, create if not
            include("createUserTable.php");
            $user_data["gal_table"] = check_user_table($user_data["id"]);
        ?>

        <link rel="stylesheet" href="index.css" type="text/css">

    </head>
    <body>

        <?php include($_SERVER['DOCUMENT_ROOT'] . "/php/createHeader.php") ?>

        <div id="main-content">
            <?php include("createAlbums.php"); ?>
            <div id="album-content"></div>
        </div>

        <form action="javascript:uploadFile()" method="post" enctype="multipart/form-data">
            <input type="file" name="user-media[]" multiple accept="image/png, image/jpeg, image/heic, video/mp4, video/mkv, video/quicktime, video/x-msvideo">
            <input type="text" name="album-name" value="My First Name">
            <input type="submit" value="Submit">
        </form>

        <?php include($_SERVER['DOCUMENT_ROOT'] . "/php/createFooter.php") ?>

        <script src="index.js" type="text/javascript"></script>

    </body>
</html>