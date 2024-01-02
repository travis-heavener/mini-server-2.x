<!DOCTYPE html>
<html lang="en">
    <head>
        
        <meta charset="utf-8">
        <title>Mini.me | Dashboard</title>
        
        <?php
            include($_SERVER['DOCUMENT_ROOT'] . "/php/requireAuth.php");
            include($_SERVER['DOCUMENT_ROOT'] . "/php/importAssets.php");
        ?>

        <link rel="stylesheet" href="/css/portal/index.css">

    </head>
    <body>

        <?php 
            $show_waffle = false;
            include($_SERVER['DOCUMENT_ROOT'] . "/php/createHeader.php");
        ?>

        <div id="apps-content">
            <div id="apps-grid">
                <?php include($_SERVER['DOCUMENT_ROOT'] . "/php/portal/createIcons.php"); ?>
            </div>
        </div>

        <?php
            $show_anim_ctrls = true;
            include($_SERVER['DOCUMENT_ROOT'] . "/php/createFooter.php");
        ?>

        <script src="/js/portal/index.js"></script>

    </body>
</html>