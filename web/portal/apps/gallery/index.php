<!DOCTYPE html>
<html lang="en">
    <head>
        
        <?php
            include_once($_SERVER['DOCUMENT_ROOT'] . "/php/requireAuth.php");
            include_once($_SERVER['DOCUMENT_ROOT'] . "/php/importAssets.php"); // import other assets & add meta tag, jQuery, & preloads
            include_once($_SERVER['DOCUMENT_ROOT'] . "/php/createBackAnim.php"); // add background animation
            
            $user_data = check_auth(); // actually call to check the auth
            verify_perms($user_data); // verify the user has access to this page
            echo format_title("Gallery"); // add document title
            
            // check that the user has their own gallery table, create if not
            include_once("createUserTable.php");
            $user_data["gal_table"] = check_user_table($user_data["id"]);
        ?>

        <link rel="stylesheet" href="index.css" type="text/css">

        <!-- for easy BLOB downloading -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"
                integrity="sha512-Qlv6VSKh1gDKGoJbnyA5RMXYcvnpIqhO++MhIM2fStMcGT9i2T//tSwYFlcyoRRDcDZ+TYHpH8azBBCyhpSeqw=="
                crossorigin="anonymous" referrerpolicy="no-referrer">
        </script>

    </head>
    <body>

        <?php include_once($_SERVER['DOCUMENT_ROOT'] . "/php/createHeader.php") ?>

        <div id="main-content">
            <div id="album-picker"></div>
            <div id="album-content">
                <div id="content-manager">
                    <!-- left-side -->
                    <div>
                        <div id="selection-checkbox" title="Toggle content selection." data-select-content="false" onclick="toggleSelectMode.bind(this)()"></div>
                        <div id="download-icon" title="Download selection." onclick="downloadSelection()" data-disabled="true"></div>
                        <div id="restore-icon" title="Restore selection." onclick="restoreSelection()" data-disabled="true"></div>
                        <div id="delete-icon" title="Delete selection." onclick="deleteSelection()" data-disabled="true"></div>
                    </div>
                    
                        <!-- right-side -->
                    <div>
                        <div id="last-page-icon" title="View last page." onclick="jumpToPage(CONTENT.album.currentPage-1)"></div>
                        <form id="page-number-form" action="#">
                            <input id="page-number-field" class="input-num-no-arrow" type="number" min="1" title="Jump to page.">
                        </form>
                        <div id="next-page-icon" title="View next page." onclick="jumpToPage(CONTENT.album.currentPage+1)"></div>
                    </div>
                </div>
                <div id="add-container" class="noselect">
                    <div id="edit-album-icon" onclick="showEditMenu()">
                        <h1>Edit Album</h1>
                        <!-- add icon to the right of each caption (make it less verbose) -->
                    </div>
                    <div id="add-album-icon" onclick="showNewAlbumMenu()">
                        <h1>New Album</h1>
                        <!-- add icon to the right of each caption (make it less verbose) -->
                    </div>
                    <div id="upload-icon" onclick="showUploadMenu()">
                        <h1>Upload</h1>
                        <!-- add icon to the right of each caption (make it less verbose) -->
                    </div>
                    <div id="add-btn">
                        <img src="/assets/apps/gallery/plus-icon.png">
                    </div>
                </div>
            </div>
        </div>

        <div id="upload-form-content">
            <form id="upload-form" action="javascript:uploadFile()" method="post" enctype="multipart/form-data">
                <h1>Upload Files</h1>
                <div id="form-file-drop">
                    <h2><span>0</span> files selected.<br>(Limit 100)</h2>
                    <input type="file" name="user-media[]" multiple accept="<?php include_once("toolbox.php"); echo join(",", SUPPORTED_MIMES); ?>">
                </div>
                <div id="form-button-row">
                    <input type="submit" value="Submit">
                    <button>Cancel</button>
                </div>
            </form>
        </div>

        <div id="edit-form-content">
            <!--
                NOTES TO SELF:
                I made renameAlbum.php but have not tested it, 95% confident it works.
                Also I need to add something to display the edit/remove album content (in this div)
                as well as in index.js:470.
            -->
        </div>

        <?php include($_SERVER['DOCUMENT_ROOT'] . "/php/createFooter.php") ?>

        <script src="index.js" type="text/javascript"></script>

    </body>
</html>