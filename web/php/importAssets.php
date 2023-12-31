<?php

    // adds all necessary HTML tags for content (ie. jQuery, global .js files, etc.)
    
    // jQuery
    echo "<script src=\"https://code.jquery.com/jquery-3.7.1.min.js\" integrity=\"sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=\" crossorigin=\"anonymous\"></script>";

    // happy worm favicon
    echo "<link rel='icon' href='/assets/favicon.ico'>";
    
    // global css & such
    echo "<link rel='stylesheet' href='/css/toolbox.css'>";

    // global JS toolbox funcs
    echo "<script src=\"/js/toolbox.js\"></script>";

    // for responsive mobile design
    echo "<meta name='viewport' content='width=device-width, initial-scale=1.0, minimum-scale=1.0'>";

?>