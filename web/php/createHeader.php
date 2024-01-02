<?php
    // create apps headers
    echo "
        <div id='header-content'>
            <img id='logo-img' src='/assets/happy-worm.png' alt='Happy worm icon.' onclick='window.location.href = \"/portal/index.php\"'>
    ";

    // show either apps waffle or my account icon
    if ($show_waffle) {
        echo "
                <img id='waffle-img' src='/assets/waffle.png' alt='Dashboard icon.' onclick='window.location.href = \"/portal/index.php\"'>
        ";
    } else {
        echo "
                <img id='my-account-img' src='/assets/profile-icon.png' alt='My Account icon.' onclick='window.location.href = \"/portal/account/index.php\"'>
        ";
    }

    // close div
    echo "</div>";
?>