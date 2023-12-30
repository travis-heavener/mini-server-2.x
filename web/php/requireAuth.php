<?php
    if (!isset($_COOKIE["ms-user-auth"])) {
        // redirect to login
        header("Location: /");
    }
?>