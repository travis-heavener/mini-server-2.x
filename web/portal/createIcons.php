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
    $user_perms = $user_data["permissions"];

    for ($i = 0; $i < count($PERMS); $i++) {
        if (((0b1 << $i) & $user_perms) > 0) { // we have access to the perm
            $perm = $PERMS[$i];
            echo create_icon($perm[0], $perm[1]);
        }
    }
?>