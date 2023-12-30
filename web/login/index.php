<!DOCTYPE html>
<html lang="en">
    <head>
        
        <meta charset="utf-8">
        <title>Mini.me</title>
        
        <?php include($_SERVER['DOCUMENT_ROOT'] . "/php/importAssets.php");?>

        <link rel="stylesheet" href="/css/login/index.css">

    </head>
    <body>

        <div id="content">
            <img id="logo" src="/assets/happy-worm.png" alt="Happy Worm logo.">

            <h1 id="title">Welcome Home</h1>

            <form id="login-form">
                <div id="email-field" class="field">
                    <img class="field-icon" src="/assets/mail-icon.png" alt="Email icon.">
                    <input id="email-input" type="email" placeholder="Email" alt="Email address field." required>
                </div>
                <div id="password-field" class="field">
                    <img class="field-icon" src="/assets/password-icon.png" alt="Password key icon.">
                    <input id="password-input" type="password" placeholder="Password" alt="Password entry field." required>
                </div>
                <input id="submit-btn" type="submit" value="Log In">
            </form>
        </div>

        <div id="anim-controls">
            <p id="anim-desc">Animate:</p>
            <input type="checkbox" id="anim-checkbox" onclick="toggleAnim(this)" title="Toggle background animation" alt="Background animation toggle checkbox.">
        </div>

        <script src="/js/login/index.js"></script>

    </body>
</html>