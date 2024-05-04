<?php
    // create apps headers
    echo "
        <div id='header-content'>
            <a href='/index.php' target='_self' title='Home'>
                <img id='logo-img' class='noselect' draggable='false' src='/assets/happy-worm.png' type='image/png' alt='Happy worm icon.'>
            </a>
    ";

    // show apps waffle and profile icon
    echo "
        <a href='/portal/index.php' target='_self' title='Dashboard'>
            <img id='waffle-img' class='noselect' draggable='false' src='/assets/app-icons/waffle.png' type=\"image/png\" alt='Dashboard icon.'>
        </a>
        <a href='/portal/account/index.php' target='_self' title='My Account'>
            <img id='my-account-img' class='noselect' draggable='false' src='/assets/app-icons/profile.png' type=\"image/png\" alt='My Account icon.'>
        </a>
    ";

    // close div
    echo "</div>";
?>