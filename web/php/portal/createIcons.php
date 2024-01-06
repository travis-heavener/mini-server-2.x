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
    4   notes
    */

    $PERMS = [
        ["gallery", "Gallery"],
        ["dvds",    "Film Library"],
        ["fire",    "Fireplace"],
        ["clock",   "Clock"],
        ["notes",   "Notes"]
    ];

    $user_perms = $user_data["permissions"];

    for ($i = 0; $i < count($PERMS); $i++) {
        if (((0b1 << $i) & $user_perms) > 0) { // we have access to the perm
            $perm = $PERMS[$i];
            echo create_icon($perm[0], $perm[1]);
        }
    }
?>