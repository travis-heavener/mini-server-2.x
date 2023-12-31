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

        <div id="anim-controls">
            <p id="anim-desc">Animate:</p>
            <input type="checkbox" id="anim-checkbox" onclick="toggleAnim(this)" title="Toggle background animation">
        </div>

        <script src="/js/portal/index.js"></script>

    </body>
</html>