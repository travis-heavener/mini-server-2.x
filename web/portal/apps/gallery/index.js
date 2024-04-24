const CONTENT = {
    "album": {
        "name": "",
        "currentPage": 1,
        "numPages": null // if not null, we've found the max per album
    },
    "selection": [], // array of selected elements' ids
    "isSelecting": false
};

const __PAGE_MAX_CONTENT = 25;

$(document).ready(async () => {
    // initial binding of text scroll
    bindTextScroll();
    
    // also change text scrolling behavior on portrait/mobile
    screen.orientation.addEventListener("change", bindTextScroll);

    // load initial content
    const albums = await loadAlbums();
    if (albums.length) focusAlbum(albums[0]["album_name"]);

    // bind menu picker events
    $("#add-btn").click(function(e) {
        if (e.target === this) $(this.parentElement).toggleClass("selected");
    });

    // make file upload areas drag and droppable
    $("input[type=file], #form-file-drop").on("dragenter dragover", e => e.preventDefault());
    $("input[type=file], #form-file-drop").on("drop", function(e) {
        e.preventDefault();

        // append existing files
        const form = $(this).is("form") ? this : $(this).find("input")[0];
        const transfer = new DataTransfer();
        for (let file of form.files)
            transfer.items.add(file);

        // take file and append to files list
        for (let file of e.originalEvent.dataTransfer.files)
            transfer.items.add(file);

        // reset the files list
        form.files = transfer.files;

        // update display
        const display = (form.files ? form.files.length : 0);
        $(form.parentElement).find("h2 > span").html(display);

        // check for form submit disable
        $("#upload-form-content").find("input[type=submit]")
            .attr("disabled", (form.files ? form.files.length : 0) === 0);
    });

    $("#form-file-drop").click(function(e) {  $(this).find("input")[0].click();  });

    $("input[type=file]").on("change", function(e) {
        e.preventDefault();

        // update display
        const display = (this.files ? this.files.length : -10);
        $(this.parentElement).find("h2 > span").html(display);

        // check for form submit disable
        $("#upload-form-content").find("input[type=submit]")
            .attr("disabled", (this.files ? this.files.length : 0) === 0);
    });

    // prevent "cancel" button from submitting UPLOAD form
    $("#upload-form-content, #form-button-row > button").click(function(e) {
        if (e.target === this) {
            e.preventDefault(); // prevent resubmitting form
            $("#upload-form-content").css("display", "none");
        }
    });

    // bind select mode to shift key
    $(window).on("keydown", e => {
        if ((e.shiftKey || e.ctrlKey) && !CONTENT.isSelecting) {
            e.preventDefault();
            toggleSelectMode.bind($("#selection-checkbox")[0])(true);
        }
    });
    
    $(window).on("keyup", e => {
        // shift is 16, ctrl is 17
        if ((e.keyCode === 16 || e.keyCode === 17) && !CONTENT.selection.length) {
            e.preventDefault();
            toggleSelectMode.bind($("#selection-checkbox")[0])(false);
        }
    });

    // bind page number form submit
    $("#page-number-form").on("submit", function(e) {
        e.preventDefault();
        jumpToPage( parseInt(this.children[0].value) );
        return false;
    });
});

// toggle touches/clicks between selecting content and opening content
// NOTE: the element is bound to the function via `this` keyword (gotta love .bind())
function toggleSelectMode(overrideTo=null) {
    // toggle the attribute
    if (overrideTo === null) {
        CONTENT.isSelecting = $(this).attr("data-select-content") === "false";
        $(this).attr("data-select-content", CONTENT.isSelecting);
        
        // notify user
        passivePrompt("Click to select: o" + (CONTENT.isSelecting ? "n" : "ff"), true);
    } else {
        // allow this function to be overridden
        CONTENT.isSelecting = overrideTo;
        $(this).attr("data-select-content", overrideTo);
    }

    // clear current selection
    // disable icons if not selecting
    if (!CONTENT.isSelecting) {
        $("#delete-icon").attr("data-disabled", "true");
        $("#restore-icon").attr("data-disabled", "true");
        $("#download-icon").attr("data-disabled", "true");
    }

    CONTENT.selection = [];

    // unselect each selected content container
    $(".content-container.content-selected").each(function() {
        $(this).removeClass("content-selected");
        $(this).attr("data-is-selected", false);
    });
}

/********************* content displaying & such *********************/

async function loadContent({albumName, page}, willClearBody=false) {
    // verify the page number is not the max for the album
    if (CONTENT.album.name === albumName &&
        (CONTENT.album.numPages !== null && page > CONTENT.album.numPages))
        return;

    // determine how many rows we can fit
    const pageOffset = (page-1) * __PAGE_MAX_CONTENT;

    // get initial information about all incoming data
    let preloadInfo;
    try {
        preloadInfo = await $.ajax({
            "url": "preloadContent.php",
            "method": "GET",
            "headers": {
                "MS2_offset": pageOffset, "MS2_maxAmt": __PAGE_MAX_CONTENT, "MS2_albumName": albumName
            },
            "cache": false
        });
    } catch (err) {
        return handleError(err);
    }

    // parse the response
    preloadInfo = JSON.parse(preloadInfo);

    // if no content returned, don't update page
    if (preloadInfo.length === 0) {
        CONTENT.album.numPages = page-1;
        return;
    }

    // there IS content, so wipe the body if necessary
    if (willClearBody) {
        // reset content manager
        toggleSelectMode.bind($("#selection-checkbox")[0])(false); // uncheck the selection mode
            
        // wipe body content for loading
        $("#album-content > .content-container").each(function() {
            // revoke BLOB url & remove
            let url = $(this).find("#album-content-" + this.id.split("-")[2])[0].src;
            URL.revokeObjectURL(url);
            $(this).remove();
        });
    }

    // display placeholder info & queue an ajax call for each
    const ids = Object.values(preloadInfo).map(info => info.id);
    for (let i = 0; i < ids.length; i++) {
        // create img placeholder
        let elem = document.createElement("IMG"); // hey future self (idiot), don't make this a const (please), when replacing img w/ video this needs to be reassigned
        const id = `album-content-${i}`;
        elem.id = id;
        $(elem).addClass("default-icon");
        $(elem).attr("src", "/assets/app-icons/gallery.png");
        $(elem).attr("alt", "Album content placeholder.");
        $(elem).attr("draggable", "false");

        const wrapper = document.createElement("DIV");
        wrapper.id = id.replace("album-content", "content-container");
        $(wrapper).addClass("content-container noselect");
        $(wrapper).append(elem);
        
        $("#album-content").append(wrapper);

        // queue ajax call (rather than awaiting, this allows all image placeholders AND thus content to load at once instead of one-by-one)
        resolveSrcToBlob(ids[i], preloadInfo[i].mime.startsWith("image"))
            .then(({url, headers}) => buildContentElem(url, headers, preloadInfo[i].mime, elem))
            .catch(handleError);
    }

    // if switching from different album, reset numPages
    if (CONTENT.album.name !== albumName) CONTENT.album.numPages = null;

    // update the page we are on
    CONTENT.album.currentPage = page;
    CONTENT.album.name = albumName;

    // update page display elem
    $("#page-number-field")[0].value = page;
}

// separated from above method, creates element to hold BLOB data
function buildContentElem(url, headers, mime, elem) {
    const wrapper = elem.parentElement;
    const id = parseInt(headers.id);

    if (url === null) {
        elem.src = "/assets/app-icons/gallery.png"; // load default icon
    } else if (mime.startsWith("image")) { // we have image, so just replace this one
        elem.src = url;
        if (!headers.isDefaultIcon) $(elem).removeClass("default-icon");
        $(elem).attr("alt", "Album image.");
    } else if (mime.startsWith("video")) { // we have video, replace this image w/ a video
        elem.outerHTML = `<video id="${elem.id}" src="${url}" alt="Album video.">`;
        elem = $("#" + elem.id); // update reference to elem

        // play on hover
        $(elem).on("mouseenter", function() {
            this.muted = this.loop = true;
            this.play();
            $(this).on("mouseleave", function() {
                this.pause();
                this.currentTime = 0;
            });
        });

        // append an additional play icon overlay
        $(wrapper).append(`<div class="play-overlay">
            <img draggable="false" src="/assets/apps/gallery/play-icon.png">
        </div>`);
    } else {
        return console.warn("Unexpected MIME type: " + mime);
    }

    // if the source or element has changed, add click events
    if (url !== null) {
        // initially set custom attributes
        $(wrapper).attr("data-is-selected", false);

        // bind click events to the wrapper
        $(wrapper).click(async function() {
            if (CONTENT.isSelecting) { // allow wrapper container to be selected/unselected when clicked
                const isSelected = $(this).attr("data-is-selected") === "false";
                $(this).attr("data-is-selected", isSelected);

                // update the element
                if (isSelected) {
                    CONTENT.selection.push(this.id);
                    // enable icons
                    $("#download-icon").attr("data-disabled", "false");
                    $("#delete-icon").attr("data-disabled", "false");
                    if (CONTENT.album.name === "Recycle Bin")
                        $("#restore-icon").attr("data-disabled", "false");
                    $(this).addClass("content-selected");
                } else {
                    $(this).removeClass("content-selected");
                    const index = CONTENT.selection.indexOf(this.id);
                    CONTENT.selection.splice(index, 1);
                    
                    // disable icons if selection is empty
                    if (!CONTENT.selection.length) {
                        $("#download-icon").attr("data-disabled", "true");
                        $("#restore-icon").attr("data-disabled", "true");
                        $("#delete-icon").attr("data-disabled", "true");
                    }
                }
            } else { // allow each wrapper container to focus in large view
                // resolve raw content source if image,
                // if video use raw source (video thumbnails only for album icons)
                showLargeContent(id, elem, headers);
            }
        });
    }

    // append extra metadata
    $(elem).attr("data-content-id", id);
    $(elem).attr("data-fname", headers.name);
    $(elem).attr("data-mime", headers.mime);
}

// switch between pages
function jumpToPage(page) {
    // return if nothing has changed or invalid page num (non-integer or zero/negative)
    if (page === CONTENT.album.currentPage || page < 1 || page != ~~page) return;

    // passthru to load content & flag it to wipe the body
    loadContent({ albumName: CONTENT.album.name, page: page }, true);
}

// show a larger, full-size preview of a picture/video
async function showLargeContent(contentID, thumbnailElem, thumbnailHeaders) {
    const isVideo = $(thumbnailElem).is("video");
    let largeSrc = isVideo ? $(thumbnailElem)[0].src : null;
    let filesize = thumbnailHeaders.filesize;
    
    if (largeSrc === null) { // load large src
        const largeRes = await resolveSrcToBlob(contentID, false);
        if (largeRes.url === null || largeRes.headers.isDefaultIcon) {
            // free BLOB url
            if (largeRes.url !== null) URL.revokeObjectURL(largeRes.url);

            // an error occured grabbing the image, so prevent loading
            handleError({"responseText": "Error: Content Resolve Issue\nThe requested \
                        content could not be fetched from the server. Try reloading \
                        the page."});
            return;
        }

        // base case, update src info
        largeSrc = largeRes.url;
        filesize = largeRes.headers.filesize;
    }

    // append large view
    let preview = `<img class="noselect" draggable="false" src="${largeSrc}"
                        data-content-id="${contentID}" data-fname="${thumbnailHeaders.name}"
                        data-mime="${thumbnailHeaders.mime}">`;
    
    if (isVideo)
        preview = `<video class="noselect" controls src="${largeSrc}" data-content-id="${contentID}"
                          data-fname="${thumbnailHeaders.name}" data-mime="${thumbnailHeaders.mime}">`;

    $("body").append(`
        <div class="large-content-container">
            <h1>${thumbnailHeaders.name}</h1>
            <h2>Encrypted size: ${formatByteSize(filesize)}</h2>
            ${preview}
        </div>
    `);

    // bind events
    let textScrollInterval = null;
    
    const closeView = () => {
        // free BLOB if not a video
        if (!isVideo) URL.revokeObjectURL(largeSrc);
        $(".large-content-container").remove();
        $(window).off("keydown.closeLargeContent");
        if (textScrollInterval !== null)
            clearInterval(textScrollInterval);
    };
    
    $(".large-content-container").click(function(e) {
        if (e.target === this) closeView();
    });

    $(window).on("keydown.closeLargeContent", (e) => {
        if (e.originalEvent.code === "Escape") closeView();
    });

    // bind text scroll event to file name
    const h1 = $(".large-content-container > h1")[0];
    const DELAY = 1.5e3;
    const INITIAL_DELAY = 1e3;
    const OFFSET_INC = 1;
    const RATE = 50; // in ms, interval callback rate
    
    setTimeout(() => {
        // store interval
        let offset = 0;
        let lastStopped = 0;
        textScrollInterval = setInterval(() => {
            if (Date.now() - lastStopped < DELAY) return;

            h1.scrollTo({left: offset, top: 0, behavior: "smooth"});
            offset += OFFSET_INC;

            if (offset >= h1.scrollWidth - h1.clientWidth) {
                offset = 0;
                lastStopped = Date.now();
                setTimeout(() => h1.scrollTo(0, 0), 0.67 * DELAY);
            }
        }, RATE);
    }, INITIAL_DELAY);
}

/********************* album focusing & such *********************/

function focusAlbum(albumName, forceLoad=false) {
    // prevent reloading all content if we are still on the same page of the same album
    if (albumName === CONTENT.album.name && CONTENT.album.currentPage === 1 && !forceLoad) return;

    // reset content manager
    toggleSelectMode.bind($("#selection-checkbox")[0])(false); // uncheck the selection mode
    
    // remove all the content-container elements on the DOM already and load up the new album from page 1
    $("#album-content > .content-container").each(function() {
        // revoke BLOB url & remove
        let url = $(this).find("#album-content-" + this.id.split("-")[2])[0].src;
        URL.revokeObjectURL(url);
        $(this).remove();
    });

    // highlight the album picker icon
    $(".album-icon.selected-album-icon").removeClass("selected-album-icon");
    $(`.album-icon[data-album-name="${albumName}"]`).addClass("selected-album-icon");

    // load the new content
    loadContent({"albumName": albumName, "page": 1});
}

async function loadAlbums() {
    // dynamically load in thumbnails for each album picker icon
    return new Promise((resolve, reject) => {
        $.ajax({
            "url": "preloadAlbums.php",
            "method": "GET",
            "success": async (res) => {
                // add each resulting object's metadata to the DOM
                const body = JSON.parse(res);

                for (let entry of body) {
                    // parse each item
                    const albumName = entry["album_name"];
                    let previewImg = "<img src='/assets/app-icons/gallery.png' class='album-icon-img default-icon' draggable='false' alt='Album icon image.'>";

                    if (entry.id !== null) {
                        const {url, headers} = await resolveSrcToBlob(entry.id, true);
                        if (!headers.isDefaultIcon)
                            previewImg = `<img src="${url}" class='album-icon-img' draggable='false' alt='Album icon image.'>`;

                        $("#album-picker").append(`
                            <div class='album-icon noselect' data-album-name="${albumName}" onclick="focusAlbum($(this).attr('data-album-name'))">
                                ${previewImg}
                                <h1>${albumName}</h1>
                            </div>
                        `);
                    }
                }

                // return the raw JSON response
                resolve(body);
            },
            "error": err => handleError(err),
            "cache": false
        });
    });
}

function resolveSrcToBlob(id, isThumb=true) {
    // queue ajax call
    return new Promise((resolve, reject) => {
        $.ajax({
            "url": "resolveSrc.php",
            "method": "GET",
            "headers": {
                "MS2_id": id,
                "MS2_isThumb": isThumb
            },
            "xhrFields": {
                responseType: "blob" // I've been needing this for literal HOURS
            },
            "success": (res, status, xhr) => {
                // replace image body
                const headers = {
                    "id":            xhr.getResponseHeader("MS2_id"),
                    "name":          xhr.getResponseHeader("MS2_name"),
                    "mime":          xhr.getResponseHeader("MS2_mime"),
                    "width":         parseInt(xhr.getResponseHeader("MS2_width")),
                    "height":        parseInt(xhr.getResponseHeader("MS2_height")),
                    "orientation":   parseInt(xhr.getResponseHeader("MS2_orientation")),
                    "isDefaultIcon": !!(xhr.getResponseHeader("MS2_isDefaultIcon") || false),
                    "filesize":      xhr.getResponseHeader("MS2_filesize")
                };
                
                // generate url from blob
                url = !headers.isDefaultIcon ? URL.createObjectURL(res) : null;

                resolve({"url": url, "headers": headers});
            },
            "error": err => handleError(err),
            "cache": false
        });
    });
}

/********************* content upload stuff *********************/

// shown when a user selects "new album" in the menu
async function showNewAlbumMenu() {
    // hide content upload form
    $("#upload-form-content").css("display", "none");

    let albumName;
    try {
        // get new album name
        albumName = await textPrompt("New Album", "Enter a name between 3 and 32 characters.", 3, 32);

        // verify the name isn't in use
        const isInUse = await $.ajax({
            "url": "checkNameUsage.php",
            "method": "POST",
            "data": { "albumName": albumName }
        });

        // also check that the album isn't in the dom
        const doElemsExist = [...$("div[data-album-name=\"" + albumName + "\"]")].length > 0;

        if (isInUse === "true" || doElemsExist) {
            handleError({"responseText": `Error: Album Name Taken\nThe requested album name \"${albumName}\" is already in use.`});
            return;
        }
    } catch (e) {
        return handleError(e);
    }


    // create a new album-icon and bind the edit function to it
    $("#album-picker").prepend(`
        <div class="album-icon noselect" data-album-name="${albumName}"
            onclick="focusAlbum($(this).attr('data-album-name'))">
            <img src='/assets/app-icons/gallery.png' class='album-icon-img default-icon'
                draggable='false' alt='Album icon image.'>
            <h1>${albumName}</h1>
        </div>
    `);

    // focus that album
    focusAlbum(albumName);

    // force update the albumName and metadata
    // (since album is empty, doesn't update after receiving empty preload data)
    CONTENT.album.name = albumName;
    CONTENT.album.currentPage = 1;
    CONTENT.album.numPages = null;
}

// shown when a user selects "upload" in the menu
function showUploadMenu() {
    // show the new album writer
    $("#upload-form-content").css("display", "flex");

    // update display
    const form = $("#upload-form-content").find("form")[0];
    const display = (form.files ? form.files.length : 0);
    $(form.parentElement).find("h2 > span").html(display);

    // check for form submit disable
    $("#upload-form-content").find("input[type=submit]")
        .attr("disabled", (form.files ? form.files.length : 0) === 0);
}

// shown when a user selects "edit album" in the menu
async function showEditMenu() {
    // allow users to edit an album (ie. rename, delete)
    const albumName = CONTENT.album.name;
    let choice;
    try {
        choice = await triplePrompt("Edit Album", "Select an option:", "Rename", "Delete", "Cancel");
    } catch (e) {
        return; // ignore silent errors
    }

    switch (choice) {
        case 1: {
            // show rename prompt
            const newName = await textPrompt("Rename Album", "Enter a name between 3 and 32 characters.", 3, 32);

            $.ajax({
                "url": "renameAlbum.php",
                "method": "POST",
                "data": { "album-name": albumName, "new-name": newName },
                "success": () => {
                    CONTENT.album.name = newName;
                    $(`#album-picker > div[data-album-name="${albumName}"] > h1`).html(newName);
                },
                "error": e => handleError(e)
            });
            break;
        }
        case 2: {
            // show delete prompt
            const willDelete = await confirmPrompt(
                "Delete Album?",
                `Are you sure you want to delete "${albumName}" and move its contents to the Recycle Bin?`,
                "Yes", "Cancel"
            );

            if (CONTENT.album.name === "Recycle Bin")
                return promptUser("Delete Cancelled", "Cannot delete \"Recycle Bin\" album.");

            if (!willDelete)
                return promptUser("Delete Cancelled", "Album not deleted.");

            // base case, delete
            $.ajax({
                "url": "deleteAlbum.php",
                "method": "DELETE",
                "data": JSON.stringify({ "album-name": albumName }),
                "success": () => window.location.reload(),
                "error": e => handleError(e)
            });
            break;
        }
    }
}

// function to upload all files in the file upload input elem
function uploadFile() {
    const form = $("#upload-form")[0];
    const fileInput = $(form).find("input[type='file']")[0];
    let data = new FormData(form);

    const fail = (title, text) => {
        fileInput.value = null;// clear files
        $(form.parentElement).find("h2 > span").html(0); // update display
        promptUser(title, text);
    };

    // prevent uploading to no album
    if (CONTENT.album.name === "" || CONTENT.album.name === "Recycle Bin")
        return fail("Invalid Album", "Cannot upload to album or Recycle Bin. Either create a new album.");
    
    // prevent uploading too many files
    if (fileInput.files.length > 100)
        return fail("Too Many Files", "Cannot upload more than 100 files at a time.");

    // prevent exceeding max post file size
    const maxBytes = 500_000_000; // 500MB
    let totalBytes = 0;
    for (let file of fileInput.files) {
        totalBytes += file.size;
        if (totalBytes > maxBytes)
            return fail("Max Size Exceeded", "Cannot upload more than 500MB at a time.");
    }

    // add album name
    data.set("album-name", CONTENT.album.name);

    // add file creation timestamps
    const timestamps = [...fileInput.files].map(val => val.lastModified);
    data.set("timestamps", JSON.stringify(timestamps));

    // add the file width and heights
    const dimensions = [...fileInput.files].map(() => null);

    const checkCompletion = () => {
        if (dimensions.includes(null)) return;
        data.set("dimensions", JSON.stringify(dimensions));
        $("#upload-form-content").css("display", "none");

        $.ajax({
            "url": "uploadFile.php",
            "method": "POST",
            "data": data,
            "contentType": false,
            "processData": false,
            "success": function(res) { // success, prompt user with success message
                promptUser("Success", "Files uploaded successfully.", false);
                focusAlbum(CONTENT.album.name, true); // focus first page of album
            },
            "error": function(e) { // error, alert user
                const msg = e.responseText.substring(7); // remove 'Error: ' from beginning
                promptUser(...msg.split("\n"), false); // show title and body
            }
        });
    };

    for (let i = 0; i < fileInput.files.length; i++) {
        const file = fileInput.files[i];
        if (file.type.startsWith("video")) {
            const dummyVideo = document.createElement("VIDEO");
            const url = URL.createObjectURL(file);
            $(dummyVideo).on("loadedmetadata", function() {
                dimensions[i] = [this.videoWidth, this.videoHeight];
                URL.revokeObjectURL(url);
                checkCompletion();
            });
            dummyVideo.src = url;
        } else if (file.type.startsWith("image")) {
            const dummyImage = document.createElement("IMG");
            const url = URL.createObjectURL(file);
            $(dummyImage).on("load", function() {
                dimensions[i] = [this.width, this.height];
                URL.revokeObjectURL(url);
                checkCompletion();
            });
            dummyImage.src = url;
        }
    }
    
    return false;
}

/********************* content manager button functions *********************/

// asks to confirm deletion of all things selected
async function deleteSelection() {
    // get selected elements by their content ids
    const contentIds = [
        ...$("[data-is-selected=true] > img, [data-is-selected=true] > video")
    ].map(elem => parseInt($(elem).attr("data-content-id")));

    // prevent call since nothing is selected
    if (!contentIds.length) return $("#delete-icon").attr("data-disabled", "true");

    // confirm delete
    const willDelete = await confirmPrompt("Confirm Delete",
        `Are you sure you want to delete ${contentIds.length} file${contentIds.length > 1 ? "s" : ""}?`,
        "Yes", "Cancel");

    if (!willDelete)
        return promptUser("Delete Cancelled", "Files not deleted from server.", false);

    // ajax call to delete
    $.ajax({
        "url": "deleteContent.php",
        "method": "DELETE",
        "data": JSON.stringify({
            "album-name": CONTENT.album.name,
            "content-ids": contentIds
        }),
        "success": () => window.location.reload(),
        "error": e => handleError(e)
    });
}

// asks to restore selected content
async function restoreSelection() {
    // get selected elements by their content ids
    const contentIds = [
        ...$("[data-is-selected=true] > img, [data-is-selected=true] > video")
    ].map(elem => parseInt($(elem).attr("data-content-id")));

    // prevent call since nothing is selected
    if (!contentIds.length) return $("#restore-icon").attr("data-disabled", "true");

    // confirm delete
    const willRestore = await confirmPrompt("Restore Item?",
        `Are you sure you want to restore ${contentIds.length} file${contentIds.length > 1 ? "s" : ""}?`,
        "Yes", "Cancel");

    if (!willRestore)
        return promptUser("Restore Cancelled", "Files not restored from recycle bin.", false);

    // ajax call to delete
    $.ajax({
        "url": "restoreContent.php",
        "method": "POST",
        "data": { "content-ids": JSON.stringify(contentIds) },
        "success": () => window.location.reload(),
        "error": e => handleError(e)
    });
}

// download all selected content one-at-a-time
async function downloadSelection() {
    // iterate over all selected content, download its source
    for (const elemId of CONTENT.selection) {
        // grab the BLOB url
        const elem = $("#" + elemId);
        const isImg = elem.find("video").length === 0; // can't use IMG, videos have IMG play icon
        let blob, MIME, name;

        // resolve SRC if image, otherwise grab video SRC
        if (isImg) {
            const imgId = $("#" + elemId + ">img").attr("data-content-id");
            await resolveSrcToBlob(parseInt(imgId), false)
                .then(res => {
                    blob = res.url;
                    MIME = res.headers.mime;
                    name = res.headers.name;
                })
                .catch(err => handleError(err));
        } else { // grab video SRC
            blob = elem.find("video")[0].src;
            MIME = elem.find("video").attr("data-mime");
            name = elem.find("video").attr("data-fname");
        }

        // download BLOB url
        saveAs(blob, name, {type: MIME});

        // free IMG blob URLs since they're only temp generated
        if (isImg) URL.revokeObjectURL(blob);
    }
}

/********************* misc *********************/

const __TEXT_SCROLL_INTERVALS = [];
function bindTextScroll() {
    // recheck text scrolling
    const isLandscape = screen.orientation.type.startsWith("landscape");

    // remove all existing mouseenter events
    $(".album-icon").each(function() {
        $(this).off("mouseenter");
    });

    // remove all intervals for text scrolling
    __TEXT_SCROLL_INTERVALS.forEach(interval => clearInterval(interval));

    // bind text scroll to all album-icon h1
    const DELAY = 1.5e3;
    const INITIAL_DELAY = 1e3;
    const OFFSET_INC = 1;
    const RATE = 50; // in ms, interval callback rate

    if (isLandscape) {
        // bind for landscape devices
        $(".album-icon").on("mouseenter", function(e) {
            const h1 = $(this).find("h1")[0];
            if (h1.scrollWidth === h1.clientWidth) return; // only scroll elements that overflow

            // wait initial time
            let interval = null;

            let timeout = setTimeout(() => {
                let offset = 0;
                let lastStopped = 0;
                interval = setInterval(() => {
                    if (Date.now() - lastStopped < DELAY) return;

                    h1.scrollTo({left: offset, top: 0, behavior: "smooth"});
                    offset += OFFSET_INC;

                    if (offset >= h1.scrollWidth - h1.clientWidth) {
                        offset = 0;
                        lastStopped = Date.now();
                        setTimeout(() => h1.scrollTo(0, 0), 0.67 * DELAY);
                    }
                }, RATE);
            }, INITIAL_DELAY);

            $(this).on("mouseleave", function(e) {
                clearTimeout(timeout);
                interval !== null && clearInterval(interval);
                h1.scrollTo(0, 0);
            });
        });
    } else {
        // bind for portrait devices
        $(".album-icon").each(function() {
            const h1 = $(this).find("h1")[0];

            setTimeout(() => {
                // double check the orientation hasn't changed
                if (screen.orientation.type.startsWith("landscape")) return;

                // store interval
                let offset = 0;
                let lastStopped = 0;
                const interval = setInterval(() => {
                    if (Date.now() - lastStopped < DELAY) return;

                    h1.scrollTo({left: 0, top: offset, behavior: "smooth"});
                    offset += OFFSET_INC;

                    if (offset >= h1.scrollHeight - h1.clientHeight) {
                        offset = 0;
                        lastStopped = Date.now();
                        setTimeout(() => h1.scrollTo(0, 0), 0.67 * DELAY);
                    }
                }, RATE);
                __TEXT_SCROLL_INTERVALS.push(interval);
            }, INITIAL_DELAY);
        });
    }
}

function handleError(e) {
    const msg = e.responseText.substring(7); // remove 'Error: ' from beginning
    const title = msg.split("\n")[0];
    const body = msg.split("\n")[1];
    
    if (title === "silent_error") {
        return;
    } else if (title === "auth_error") {
        window.location.reload(true);
    } else {
        promptUser(title, body, false);
    }
}