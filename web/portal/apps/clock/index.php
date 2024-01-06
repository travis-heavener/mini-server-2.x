<!DOCTYPE html>
<html lang="en">
    <head>
        
        <?php
            include($_SERVER['DOCUMENT_ROOT'] . "/php/requireAuth.php");
            include($_SERVER['DOCUMENT_ROOT'] . "/php/importAssets.php"); // import other assets & add meta tag, jQuery, & preloads
            
            $user_data = check_auth(); // actually call to check the auth
            echo format_title("Clock"); // add document title
        ?>

        <!-- preload icons for smoother load -->
        <link rel="preload" fetchpriority="high" href="/assets/apps/clock/live-clock.png" as="image">
        <link rel="preload" fetchpriority="high" href="/assets/apps/clock/stopwatch.png" as="image">
        <link rel="preload" fetchpriority="high" href="/assets/apps/clock/timer.png" as="image">

        <link rel="stylesheet" href="index.css" type="text/css">

    </head>
    <body>

        <?php 
            $show_waffle = false;
            include($_SERVER['DOCUMENT_ROOT'] . "/php/createHeader.php");
        ?>

        <div id="clock-content">
            <div id="clock-menu">
                <img id="live-clock-icon" class="noselect" src="/assets/apps/clock/live-clock.png" alt="Live clock icon.">
                <img id="stopwatch-icon" class="noselect" src="/assets/apps/clock/stopwatch.png" alt="Stopwatch icon.">
                <img id="timer-icon" class="noselect" src="/assets/apps/clock/timer.png" alt="Timer icon.">
            </div>
            <div id="clock-body">
                <div id="live-content" class="clock-mode noselect">
                    <div id="live-clock-container">
                        <div id="live-sec-needle" class="live-needle"></div>
                        <div id="live-min-needle" class="live-needle"></div>
                        <div id="live-hour-needle" class="live-needle"></div>
                        <div id="live-center-dot" class="live-needle"></div>
                    </div>
                </div>
                <div id="stopwatch-content" class="clock-mode"></div>
                <div id="timer-content" class="clock-mode"></div>
            </div>
        </div>

        <?php
            include($_SERVER['DOCUMENT_ROOT'] . "/php/createFooter.php");
        ?>

        <script src="index.js" type="text/javascript"></script>

    </body>
</html>