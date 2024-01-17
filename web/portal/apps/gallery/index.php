<!DOCTYPE html>
<html lang="en">
    <head>
        
        <?php
            include($_SERVER['DOCUMENT_ROOT'] . "/php/requireAuth.php");
            include($_SERVER['DOCUMENT_ROOT'] . "/php/importAssets.php"); // import other assets & add meta tag, jQuery, & preloads
            include($_SERVER['DOCUMENT_ROOT'] . "/php/createBackAnim.php"); // add background animation
            
            $user_data = check_auth(); // actually call to check the auth
            verify_perms($user_data); // verify the user has access to this page
            echo format_title("Gallery"); // add document title
            
            // check that the user has their own gallery table, create if not
            include("createUserTable.php");
            $user_data["gal_table"] = check_user_table($user_data["id"]);
        ?>

        <link rel="stylesheet" href="index.css" type="text/css">

    </head>
    <body>

        <?php include($_SERVER['DOCUMENT_ROOT'] . "/php/createHeader.php") ?>

        <div id="main-content">
            <div id="album-picker"></div>
            <div id="album-content">
                <div id="content-manager">
                    <div id="selection-checkbox" title="Select content" data-select-content="false" onclick="toggleSelectMode.bind(this)()"></div>
                </div>
                <div id="add-container" class="noselect">
                    <div id="add-album-icon" onclick="showForm('new-album')">
                        <h1>New Album</h1>
                    </div>
                    <div id="upload-icon" onclick="showForm('upload')">
                        <h1>Upload</h1>
                    </div>
                    <div id="add-btn">
                        <img src="/assets/apps/gallery/up-caret.png">
                    </div>
                </div>
            </div>
        </div>

        <div id="upload-form-content">
            <form id="upload-form" action="javascript:uploadFile()" method="post" enctype="multipart/form-data">
                <h1>Upload Files</h1>
                <div id="form-file-drop">
                    <h2>0 files selected.</h2>
                    <input type="file" name="user-media[]" multiple accept="<?php include("toolbox.php"); echo join(",", SUPPORTED_MIMES); ?>">
                </div>
                <div id="form-button-row">
                    <input type="submit" value="Submit">
                    <button>Cancel</button>
                </div>
            </form>
        </div>

        <?php include($_SERVER['DOCUMENT_ROOT'] . "/php/createFooter.php") ?>

        <script src="index.js" type="text/javascript"></script>

    </body>
</html>