<?php
    include_once($_SERVER['DOCUMENT_ROOT'] . "/php/toolbox.php");

    // check that the user has their own table and if they don't, create one
    // the user's secret key is still assigned to all their images so even if
    // someone were to try and pull from another user's table, decryption would fail

    /*
        table naming scheme: gallery__<id>
            where `id` is the hex representation of the user_id
        
        file naming scheme: <user_id>_<item_id>
            where `user_id` is the hex representation of the user_id and `item_id` is the hex representation of the item_id
    */

    include_once("./toolbox.php");

    function check_user_table($user_id) {
        // verify the $user_id is legal
        if (gettype($user_id) !== "integer") {
            // remove cookie
            unset($_COOKIE["ms-user-auth"]);
            setcookie("ms-user-auth", "", time() - 3600, "/");
            
            // redirect to login for invalid session data
            header("Location: /index.php?reason=sig");
            exit();
        }

        // generate table name
        $name = TABLE_STEM . dechex($user_id);

        // mysqli connect
        $envs = loadEnvs();
        $mysqli = new mysqli($envs["HOST"], $envs["USER"], $envs["PASS"], $envs["DBID"]);
        
        $statement = $mysqli->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME=?;");
        $statement->bind_param("s", $name);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        
        if ($rows[0]["COUNT(*)"] === 0) {
            // table doesn't exist, so create it
            $statement = $mysqli->prepare("
                CREATE TABLE `$name` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
                    `name` VARCHAR(192) NOT NULL ,
                    `album_name` VARCHAR(32) NOT NULL ,
                    `mime` VARCHAR(20) NOT NULL ,
                    `width` MEDIUMINT UNSIGNED NOT NULL ,
                    `height` MEDIUMINT UNSIGNED NOT NULL ,
                    `orientation` BIT(3) NOT NULL ,
                    `uploaded` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
                    `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
                    `deletion_date` timestamp NULL DEFAULT NULL
                    PRIMARY KEY (`id`)
                ) ENGINE = InnoDB;
            ");
            $statement->execute();
            $statement->close();
        }

        $mysqli->close();

        return $name;
    }
?>