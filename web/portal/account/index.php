<!DOCTYPE html>
<html lang="en">
    <head>
        
        <?php
            include($_SERVER['DOCUMENT_ROOT'] . "/php/requireAuth.php");
            include($_SERVER['DOCUMENT_ROOT'] . "/php/importAssets.php"); // import other assets & add meta tag, jQuery, & preloads
            include($_SERVER['DOCUMENT_ROOT'] . "/php/createBackAnim.php"); // add background animation

            $user_data = check_auth(); // actually call to check the auth
            echo format_title("My Account"); // add document title
        ?>

        <title>Mini - My Account</title>
        <link rel="stylesheet" href="index.css" type="text/css">

    </head>
    <body>

        <?php 
            $show_waffle = false;
            include($_SERVER['DOCUMENT_ROOT'] . "/php/createHeader.php");
        ?>

        <div id="account-info">
            <h1>My Account</h1>

            <form id="account-info-form" action="javascript:submitInfo()" enctype="multipart/form-data">
                <?php
                    function create_detail($label, $key, $value, $pattern, $is_disabled) {
                        $is_disabled = $is_disabled ? "disabled" : "";
                        return "
                            <div class='account-detail'>
                                <p>$label: </p>
                                <input type='text' id='$key-input' name='$key' pattern='$pattern' value='$value' $is_disabled>
                            </div>
                        ";
                    }

                    echo create_detail("ID", "id", $user_data["id"], ".*", true);
                    echo create_detail("First", "first", $user_data["first"], ".*", false);
                    echo create_detail("Last", "last", $user_data["last"], ".*", false);
                    echo create_detail("Email", "email", $user_data["email"], "[a-zA-Z0-9\._\\-]+@[a-zA-Z0-9\._\\-]+\\.[a-zA-Z]{2,4}$", false);
                ?>
                <input id="account-info-submit" type="submit" value="Save" disabled>
            </form>
            
            <h2>Password</h2>
            
            <form id="pass-update-form" action="javascript:submitPass()" enctype="multipart/form-data">
                <?php
                function create_pass_field($label, $key, $autofill) {
                    return "
                        <div class='account-detail'>
                            <p>$label: </p>
                            <input type='password' id='$key-input' name='$key' pattern='^.{6,}$' autocomplete='$autofill'>
                        </div>
                    ";
                }
                    echo create_pass_field("Current", "current-pass", "current-password");
                    echo create_pass_field("New", "new-pass", "new-password");
                    echo create_pass_field("Confirm", "confirmed-pass", "new-password");
                ?>
                <input id="pass-update-submit" type="submit" value="Save" disabled>
            </form>
        </div>

        <?php include($_SERVER['DOCUMENT_ROOT'] . "/php/createFooter.php") ?>

        <script src="index.js" type="text/javascript"></script>

    </body>
</html>