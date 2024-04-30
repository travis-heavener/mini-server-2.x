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
            <div class="video-container">
                <video id="video-out"></video>

                <div class="video-control-bar">
                    <svg class="video-play-icon" viewBox="0 0 10 10" xmlns="http://www.w3.org/2000/svg">
                        <polygon class="svg-play-icon" points="0.5,0 9.5,5 0.5,10" />
                        <rect class="svg-pause-icon" x="1.25" y="0" width="2.75" height="10" />
                        <rect class="svg-pause-icon" x="6.25" y="0" width="2.75" height="10" />
                    </svg>
                </div>
            </div>

            <svg class="play-overlay" viewBox="0 0 10 10" xmlns="http://www.w3.org/2000/svg">
                <polygon points="1,0 9,5 1,10" />
            </svg>
        </div>

        <?php include($_SERVER["DOCUMENT_ROOT"] . "/php/createFooter.php") ?>

        <script src="index.js" type="text/javascript"></script>

    </body>
</html>