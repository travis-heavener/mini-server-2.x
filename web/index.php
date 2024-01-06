<!DOCTYPE html>
<html lang="en">
    <head>
        
        <?php
            include($_SERVER['DOCUMENT_ROOT'] . "/php/importAssets.php"); // import other assets & add meta tag, jQuery, & preloads
            include($_SERVER['DOCUMENT_ROOT'] . "/php/createBackAnim.php"); // add background animation
            echo format_title("Login"); // add document title
        ?>

        <link rel="stylesheet" href="index.css" type="text/css">

    </head>
    <body>

        <div id="content">
            <img id="logo" src="/assets/happy-worm.png" type="image/png" alt="Happy Worm logo.">

            <h1 id="title">Welcome</h1>

            <form id="login-form" action="javascript:submit()" enctype="multipart/form-data">
                <div id="email-field" class="field">
                    <img class="field-icon" src="/assets/mail-icon.png" type="image/png" alt="Email icon.">
                    <input id="email-input" name="email" type="email" placeholder="Email" required autocomplete="email">
                </div>
                <div id="password-field" class="field">
                    <img class="field-icon" src="/assets/password-icon.png" type="image/png" alt="Password key icon.">
                    <input id="password-input" name="pass" type="password" placeholder="Password" required autocomplete="current-password">
                </div>
                <input id="submit-btn" type="submit" value="Log In">
            </form>
        </div>

        <script src="index.js" type="text/javascript"></script>

    </body>
</html>