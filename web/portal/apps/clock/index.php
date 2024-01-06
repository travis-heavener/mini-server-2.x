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
                <div id="live-clock-icon" class="mode-icon">
                    <img class="noselect" src="/assets/apps/clock/live-clock.png" onclick="focusClock('live')" alt="Live clock icon.">
                    <div class="icon-selector"></div>
                </div>
                <div id="digital-icon" class="mode-icon">
                    <img class="noselect" src="/assets/apps/clock/digital-clock.png" onclick="focusClock('digital')" alt="Digital clock icon.">
                    <div class="icon-selector"></div>
                </div>
            </div>
            <div id="clock-body">
                <div id="live-content" class="clock-mode noselect">
                    <div id="live-clock-container">
                        <div id="live-center-dot" class="live-needle"></div>
                    </div>
                </div>
                <div id="digital-content" class="clock-mode">
                    <div id="digital-clock-container">
                        <div id="digital-main">
                            <h1>
                                <span id="digital-hour">00</span>:<span id="digital-min">00</span>
                            </h1>
                        </div>
                        <div id="digital-side">
                            <h1 id="digital-half">AM</h1>
                            <h1 id="digital-sec">00</h1>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php
            include($_SERVER['DOCUMENT_ROOT'] . "/php/createFooter.php");
        ?>

        <script src="index.js" type="text/javascript"></script>

    </body>
</html>