<?php
    // load envs
    function loadEnvs() {
        return parse_ini_file(dirname($_SERVER["DOCUMENT_ROOT"]) . "/config/.env");
    }
?>