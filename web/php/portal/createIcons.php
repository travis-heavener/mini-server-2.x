<?php
    // create icons for portal page
    function create_icon($name, $title) {
        return "
            <div class='app-container noselect'>
                <div id='$name-icon' class='app-badge'>
                    <img class='app-icon' src='/assets/app-icons/$name.png' alt='$title icon.'>
                </div>
                <h1 class='app-title'>$title</h1>
            </div>
        ";
    }

    echo create_icon("gallery", "Gallery");
    echo create_icon("dvds", "Film Library");
    echo create_icon("fire", "Fireplace");
    echo create_icon("clock", "Clock");
?>