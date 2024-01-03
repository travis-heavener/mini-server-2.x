<?php
    // create apps headers
    echo "
        <div id='header-content'>
            <img id='logo-img' src='/assets/happy-worm.png' type='image/png' alt='Happy worm icon.' onclick=\"window.location.href = '/index.php'\">
    ";

    // show apps waffle and profile icon
    echo "
        <img id='waffle-img' src='/assets/app-icons/waffle.png' type=\"image/png\" alt='Dashboard icon.' onclick=\"window.location.href = '/portal/index.php'\">
        <img id='my-account-img' src='/assets/app-icons/profile.png' type=\"image/png\" alt='My Account icon.' onclick=\"window.location.href = '/portal/account/index.php'\">
    ";

    // close div
    echo "</div>";
?>