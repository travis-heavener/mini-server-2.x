<!DOCTYPE html>
<html lang="en">
    <head>
        
        <meta charset="utf-8">
        <title>Mini.me</title>
        
        <?php include($_SERVER['DOCUMENT_ROOT'] . "/php/importAssets.php");?>

        <link rel="stylesheet" href="/css/index.css">

    </head>
    <body>

        <div id="content">
            <img id="logo" src="/assets/happy-worm.png" alt="Happy Worm logo.">

            <h1 id="title">Welcome</h1>

            <form id="login-form" method="post" action="/php/login.php">
                <div id="email-field" class="field">
                    <img class="field-icon" src="/assets/mail-icon.png" alt="Email icon.">
                    <input id="email-input" name="email" type="email" placeholder="Email" required>
                </div>
                <div id="password-field" class="field">
                    <img class="field-icon" src="/assets/password-icon.png" alt="Password key icon.">
                    <input id="password-input" name="pass" type="password" placeholder="Password" required>
                </div>
                <input id="submit-btn" type="submit" value="Log In">
            </form>
        </div>

        <div id="anim-controls">
            <p id="anim-desc">Animate:</p>
            <input type="checkbox" id="anim-checkbox" onclick="toggleAnim(this)" title="Toggle background animation">
        </div>

        <script src="/js/index.js"></script>

    </body>
</html>