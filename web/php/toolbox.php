<?php
    // load envs
    function loadEnvs() {
        // resolve document root if it's a symlink
        if (is_link($_SERVER["DOCUMENT_ROOT"])) {
            return parse_ini_file(dirname(readlink($_SERVER["DOCUMENT_ROOT"])) . "/config/.env");
        } else {
            return parse_ini_file(dirname($_SERVER["DOCUMENT_ROOT"]) . "/config/.env");
        }
    }
?>