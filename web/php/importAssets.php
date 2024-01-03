<?php

    /**
     * adds all necessary HTML tags for content (ie. jQuery, global .js files, etc.)
     */

    // preloads fonts
    echo "<link rel='preload' href='/assets/fonts/Poppins-Medium.woff' as='font' type='font/woff' crossorigin>";
    echo "<link rel='preload' href='/assets/fonts/Poppins-Bold.woff' as='font' type='font/woff' crossorigin>";
    echo "<link rel='preload' href='/assets/fonts/Ubuntu-Regular.woff' as='font' type='font/woff' crossorigin>";

    // jQuery
    echo "<script src='https://code.jquery.com/jquery-3.7.1.min.js' integrity='sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=' crossorigin='anonymous'></script>";

    // happy worm favicon
    echo "<link rel='icon' href='/assets/favicon.ico'>";
    
    // global css & such
    echo "<link rel='stylesheet' href='/css/toolbox.css'>";

    // global JS toolbox funcs
    echo "<script src='/js/toolbox.js'></script>";

    // js-cookie (I'm lazy and this looks really cool)
    echo "<script src='https://cdn.jsdelivr.net/npm/js-cookie@3.0.5/dist/js.cookie.min.js'></script>";

    // for responsive mobile design
    echo "<meta name='viewport' content='width=device-width, initial-scale=1.0, minimum-scale=1.0'>";

?>