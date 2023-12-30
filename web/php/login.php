<?php
    // verify post request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo "Invalid request method: expected POST.";
        return;
    }

    
    $email = $_POST["email"];
    $pass = $_POST["pass"];

    echo $email . "\n" . $pass;
?>