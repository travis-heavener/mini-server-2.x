<?php
    // create icons for portal page
    function create_icon($name, $title) {
        return "
            <div class='app-container noselect' onclick=\"launchApp('$name')\">
                <div id='$name-icon' class='app-badge'>
                    <img class='app-icon' src='/assets/app-icons/$name.png' type='image/png' alt='$title icon.'>
                </div>
                <h1 class='app-title'>$title</h1>
            </div>
        ";
    }

    // determine what permissions the user has to see what apps they can use
    /*
    1: yes, 0: no
    all permissions would have a permissions value of the sum of each 2^(bit #)

    Bit app/permission
    0   gallery
    1   dvds
    2   fire
    3   clock
    4   todo
    */

    echo create_icon("gallery", "Gallery");
    echo create_icon("dvds", "Film Library");
    echo create_icon("fire", "Fireplace");
    echo create_icon("clock", "Clock");
    echo create_icon("notes", "Notes");
?>