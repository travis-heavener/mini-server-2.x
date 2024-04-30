<!DOCTYPE html>
<html lang="en">
    <head>
        
        <?php
            include($_SERVER["DOCUMENT_ROOT"] . "/php/requireAuth.php");
            include($_SERVER["DOCUMENT_ROOT"] . "/php/importAssets.php"); // import other assets & add meta tag, jQuery, & preloads
            include($_SERVER["DOCUMENT_ROOT"] . "/php/createBackAnim.php"); // add background animation

            $user_data = check_auth(); // actually call to check the auth
            verify_perms($user_data); // verify the user has access to this page
            echo format_title("Watch"); // add document title
        ?>

        <link rel="stylesheet" href="index.css" type="text/css">

        <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>

    </head>
    <body>

        <?php include($_SERVER["DOCUMENT_ROOT"] . "/php/createHeader.php") ?>

        <div id="main-content">
            <video id="video-container"></video>
            <svg id="play-overlay" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                <circle cx="50%" cy="50%" r="43%" stroke-width="7%" />
                <polygon points="38,33 67,50 38,67" />
            </svg>
        </div>

        <?php include($_SERVER["DOCUMENT_ROOT"] . "/php/createFooter.php") ?>

        <script src="index.js" type="text/javascript"></script>

    </body>
</html>