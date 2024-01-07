<!DOCTYPE html>
<html lang="en">
    <head>
        
        <?php
            include($_SERVER['DOCUMENT_ROOT'] . "/php/requireAuth.php");
            include($_SERVER['DOCUMENT_ROOT'] . "/php/importAssets.php"); // import other assets & add meta tag, jQuery, & preloads
            include($_SERVER['DOCUMENT_ROOT'] . "/php/createBackAnim.php"); // add background animation
            
            $user_data = check_auth(); // actually call to check the auth
            echo format_title("Dashboard"); // add document title
        ?>

        <link rel="stylesheet" href="index.css" type="text/css">

    </head>
    <body>

        <?php include($_SERVER['DOCUMENT_ROOT'] . "/php/createHeader.php") ?>

        <div id="apps-content">
            <div id="apps-grid">
                <?php include($_SERVER['DOCUMENT_ROOT'] . "/php/portal/createIcons.php"); ?>
            </div>
        </div>

        <?php include($_SERVER['DOCUMENT_ROOT'] . "/php/createFooter.php") ?>

        <script src="index.js" type="text/javascript"></script>

    </body>
</html>