<!DOCTYPE html>
<html lang="en">
    <head>
        
        <meta charset="utf-8">
        <title>Mini.me | Dashboard</title>
        
        <?php
            include($_SERVER['DOCUMENT_ROOT'] . "/php/requireAuth.php");
            include($_SERVER['DOCUMENT_ROOT'] . "/php/importAssets.php");

            // actually call to check the auth
            $user_data = check_auth();
        ?>

        <link rel="stylesheet" href="/css/portal/account/index.css" type="text/css">

    </head>
    <body>

        <?php 
            $show_waffle = false;
            include($_SERVER['DOCUMENT_ROOT'] . "/php/createHeader.php");
        ?>

        <div id="account-info">
            <h1>My Account</h1>

            <form id="account-info-form" action="javascript:submit()" enctype="multipart/form-data">
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
                <input type="submit" value="Update">
            </form>
            
            <h2>Password</h2>
            <h2>Danger Zone</h2>
        </div>

        <?php
            $show_anim_ctrls = true;
            include($_SERVER['DOCUMENT_ROOT'] . "/php/createFooter.php");
        ?>

        <script src="/js/portal/account/index.js" type="text/javascript"></script>

    </body>
</html>