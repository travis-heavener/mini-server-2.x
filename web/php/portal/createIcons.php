<?php
    // create icons for portal page
    function create_icon($type, $title, $desc, $icon_path) {
        return "
            <div class='app-container'>
                <div id='$type' class='app-badge' title='$desc'>
                    <img class='app-icon' src='$icon_path' alt='$title icon.'>
                </div>
                <h1 class='app-title'>$title</h1>
            </div>
        ";
    }

    echo create_icon("gallery", "Gallery", "View all your saved photos and videos.", "/assets/icons/gallery.png");
    echo create_icon("dvds", "Film Library", "Watch DVDs from the DVD library.", "/assets/icons/dvds.png");
    echo create_icon("fireplace", "Fireplace", "Your own pretend fireplace.", "/assets/icons/fire.png");
    echo create_icon("clock", "Clock", "Live clock.", "/assets/icons/clock.png");
?>