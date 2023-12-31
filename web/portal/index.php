<!DOCTYPE html>
<html lang="en">
    <head>
        
        <meta charset="utf-8">
        <title>Mini.me | My Account</title>
        
        <?php
            include($_SERVER['DOCUMENT_ROOT'] . "/php/requireAuth.php");
            include($_SERVER['DOCUMENT_ROOT'] . "/php/importAssets.php");
        ?>

        <link rel="stylesheet" href="/css/portal/index.css">

    </head>
    <body>

        <div id="header-content">
            <img id="logo-img" src="/assets/happy-worm.png" alt="Happy worm icon." title="Home" onclick="window.open('/portal/index.php', '_self')">
            <img id="my-account-img" src="/assets/profile-icon.png" alt="My Account icon." title="My Account" onclick="window.open('/portal/account/index.php', '_self')">
        </div>

        <div id="apps-content">
            <div id="apps-grid">
                <?php include($_SERVER['DOCUMENT_ROOT'] . "/php/portal/createIcons.php"); ?>
            </div>
        </div>

        <div id="footer-content">
            <?php
                // pick random footnote
                $notes = [
                    "Made with ❤.",
                    "Est. 2023.",
                    "Never share your password.",
                    "<a href='mailto:travis.heavener@gmail.com'>travis.heavener@gmail.com</a> for inquiries."
                ];
                echo "<p>" . $notes[array_rand($notes)] ."</p>";
            ?>

            <div id="anim-controls">
                <p id="anim-desc">Animate:</p>
                <input type="checkbox" id="anim-checkbox" onclick="toggleAnim(this)" title="Toggle background animation">
            </div>
        </div>

        <script src="/js/portal/index.js"></script>

    </body>
</html>