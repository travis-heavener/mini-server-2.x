const CONTENT = {
    "album": {
        "name": "",
        "currentPage": 1
    },
    "selection": [], // array of selected elements' ids
    "isSelecting": false
}

$(document).ready(async () => {
    // initial binding of text scroll
    bindTextScroll();
    
    // also change text scrolling behavior on portrait/mobile
    screen.orientation.addEventListener("change", bindTextScroll);

    // load initial content
    const albums = await loadAlbums();
    if (albums.length)
        focusAlbum(albums[0]["album_name"]);

    // bind menu picker events
    $("#add-btn").click(function(e) {
        if (e.target === this)
            $(this.parentElement).toggleClass("selected");
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
        const display = (form.files ? form.files.length : 0) + " files selected.";
        $(form.parentElement).find("h2").html(display);

        // check for form submit disable
        $("#upload-form-content").find("input[type=submit]").attr("disabled", (form.files ? form.files.length : 0) === 0);
    });

    $("#form-file-drop").click(function(e) {
        $(this).find("input")[0].click();
    });

    $("input[type=file]").on("change", function(e) {
        e.preventDefault();

        // update display
        const display = (this.files ? this.files.length : 0) + " files selected.";
        $(this.parentElement).find("h2").html(display);

        // check for form submit disable
        $("#upload-form-content").find("input[type=submit]").attr("disabled", (this.files ? this.files.length : 0) === 0);
    });

    $("#upload-form-content, #form-button-row > button").click(function(e) {
        if (e.target === this) {
            $("#upload-form-content").css("display", "none");
            e.preventDefault(); // prevent resubmitting form
        }
    });
});

// toggle touches/clicks between selecting content and opening content
// NOTE: the element is bound to the function via `this` keyword (gotta love .bind())
function toggleSelectMode(overrideTo=null) {
    // toggle the attribute
    let isSelecting;
    if (overrideTo === null) {
        isSelecting = $(this).attr("data-select-content") === "false";
        $(this).attr("data-select-content", isSelecting);
    } else {
        // allow this function to be overridden
        isSelecting = overrideTo;
        $(this).attr("data-select-content", overrideTo);
    }

    // clear current selection
    CONTENT.isSelecting = isSelecting;
    CONTENT.selection = [];

    // unselect each selected content container
    $(".content-container.content-selected").each(function() {
        $(this).removeClass("content-selected");
        $(this).attr("data-is-selected", false);
    });
}

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

async function loadContent({albumName, page}) {
    // determine how many rows we can fit
    const amtPerPage = 50;
    const pageOffset = (page-1) * amtPerPage;

    // get initial information about all incoming data
    let preloadInfo;
    try {
        preloadInfo = await $.ajax({
            "url": "preloadContent.php",
            "method": "GET",
            "headers": {
                "MS2_offset": pageOffset,
                "MS2_maxAmt": amtPerPage,
                "MS2_albumName": albumName
            }
        });
    } catch (err) {
        handleError(err);
        return;
    }

    // parse the response
    preloadInfo = JSON.parse(preloadInfo);

    const ids = [];
    for (let info of preloadInfo)
        ids.push(info.id);

    // display placeholder info & queue an ajax call for each
    for (let i = 0; i < ids.length; i++) {
        // create img placeholder
        let elem = document.createElement("IMG"); // hey future self (idiot), don't make this a const (please), when replacing img w/ video this needs to be reassigned
        const id = `album-content-${i}`;
        elem.id = id;
        $(elem).addClass("default-icon");
        $(elem).attr("src", "/assets/app-icons/gallery.png");
        $(elem).attr("alt", "Album content placeholder.");

        const wrapper = document.createElement("DIV");
        wrapper.id = id.replace("album-content", "content-container");
        $(wrapper).addClass("content-container noselect");
        $(wrapper).append(elem);
        
        $("#album-content").append(wrapper);

        // queue ajax call (rather than awaiting, this allows all image placeholders AND thus content to load at once instead of one-by-one)
        resolveSrcToBlob(ids[i], preloadInfo[i].mime.startsWith("image"))
            .then(({url, headers}) => {
                const mime = preloadInfo[i].mime;
                let hasUpdated = false;

                if (url === null) {
                    // load default icon
                    elem.src = "/assets/app-icons/gallery.png";
                } else if (mime.startsWith("image")) {
                    // we have an image, so just replace this one
                    hasUpdated = true;
                    elem.src = url;
                    if (!headers.isDefaultIcon) $(elem).removeClass("default-icon");
                    $(elem).attr("alt", "Album image.");
                } else if (mime.startsWith("video")) {
                    // we have a video, so replace this image with a video
                    hasUpdated = true;
                    elem.outerHTML = `<video id="${id}" src="${url}" alt="Album video.">`;
                    elem = $("#" + id); // update reference after changing outerHTML

                    // play on hover
                    $(elem).on("mouseenter", function() {
                        this.muted = true;
                        this.loop = true;
                        this.play();
                        $(this).on("mouseleave", function() {
                            this.pause();
                            this.currentTime = 0;
                        });
                    });

                    // append an additional play icon overlay
                    $(wrapper).append(`
                        <div class="play-overlay">
                            <img src="/assets/apps/gallery/play-icon.png">
                        </div>
                    `);
                } else {
                    console.warn("Unexpected MIME type: " + mime);
                }

                // if the source or element has changed, add click events
                if (hasUpdated) {
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
                                $(this).addClass("content-selected");
                            } else {
                                $(this).removeClass("content-selected");
                                const index = CONTENT.selection.indexOf(this.id);
                                CONTENT.selection.splice(index, 1);
                            }
                        } else { // allow each wrapper container to focus in large view
                            // resolve raw content source if image, if video use the source (video thumbnails are only for album icons)
                            let largeSrc = $(elem).is("video") ? url : null;
                            let filesize = headers.filesize;
                            
                            if (largeSrc === null) {
                                // load large src
                                const largeRes = await resolveSrcToBlob(ids[i], false);
                                if (largeRes.url === null || largeRes.headers.isDefaultIcon) {
                                    // an error occured grabbing the image, so prevent loading
                                    handleError({"responseText": "Error: Content Resolve Issue\nThe requested content could not be fetched from the server. Try reloading the page."});
                                    return;
                                }

                                // base case, update src info
                                largeSrc = largeRes.url;
                                filesize = largeRes.headers.filesize;
                            }

                            // format metadata
                            filesize = formatByteSize(filesize);

                            // append large view
                            let previewContainer;
                            
                            if ($(elem).is("video")) {
                                previewContainer = `
                                    <video class="noselect" controls src="${largeSrc}" data-content-id="${ids[i]}" data-fname="${headers.name}">
                                `;
                            } else {
                                previewContainer = `
                                    <img class="noselect" src="${largeSrc}" data-content-id="${ids[i]}" data-fname="${headers.name}">
                                `;
                            }

                            $("body").append(`
                                <div class="large-content-container">
                                    <h1>${headers.name}</h1>
                                    <h2>Encrypted size: ${filesize}</h2>
                                    ${previewContainer}
                                </div>
                            `);

                            let textScrollInterval = null;

                            $(".large-content-container").click(function(e) {
                                if (e.target === this) {
                                    $(this).remove();
                                    if (textScrollInterval !== null)
                                        clearInterval(textScrollInterval);
                                }
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
                    });
                }

                // append extra metadata
                $(elem).attr("data-content-id", ids[i]);
                $(elem).attr("data-fname", headers.name);
            })
            .catch((err) => {
                handleError(err);
            });
    }

    // update the page we are on
    CONTENT.album.currentPage = page;
    CONTENT.album.name = albumName;

    // allow the results per page to be toggled via dropdown (ie 25,50,75,100) alongside page pickers
    // default 50, but when changing results per page revert to page 1
    // when uploading files, reload the content on that page of the album, NOT the actual page

    // when adding an album, don't do anything on the backend until we upload files
    // allow small pngs to be saved as pngs instead of jpegs

    // on top, allow a select button for multi select or allow shift clicking
    // have a delete icon as well, confirm when pressed to delete any things selected (gray out when nothing is selected)
}

function focusAlbum(albumName, forceLoad=false) {
    if (albumName === CONTENT.album.name && CONTENT.album.currentPage === 1 && !forceLoad) return; // prevent reloading all content if we are still on the same page of the same album

    // reset content manager
    toggleSelectMode(false); // uncheck the selection mode
    
    // remove all the content-container elements on the DOM already and load up the new album from page 1
    $("#album-content > .content-container").remove();

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
                    let previewImg = "<img src='/assets/app-icons/gallery.png' class='album-icon-img default-icon' alt='Album icon image.'>";

                    if (entry.id !== null) {
                        const {url, headers} = await resolveSrcToBlob(entry.id);
                        if (!headers.isDefaultIcon)
                            previewImg = `<img src="${url}" class='album-icon-img' alt='Album icon image.'>`;

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
            "error": err => handleError(err)
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
            "error": err => handleError(err)
        });
    });
}

function handleError(e) {
    console.warn("Failure", e);
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
};

/********************* content upload stuff *********************/

async function showForm(formType) {
    if (formType === "new-album") {
        // hide content upload form
        $("#upload-form-content").css("display", "none");

        let albumName;
        try {
            // get new album name
            albumName = await textPrompt("New Album", "Enter an album name between 3 and 32 characters.", 3, 32);

            // verify the name isn't in use
            const isInUse = await $.ajax({
                "url": "checkNameUsage.php",
                "method": "POST",
                "data": JSON.stringify({
                    "albumName": albumName
                })
            });

            // also check that the album isn't in the dom
            const doElemsExist = [...$("div[data-album-name=\"" + albumName + "\"]")].length > 0;

            if (isInUse === "true" || doElemsExist) {
                handleError({"responseText": `Error: Album Name Taken\nThe requested album name \"${albumName}\" is already in use.`});
                return;
            }
        } catch (e) {
            handleError(e);
            return;
        }


        // create a new album-icon and bind the edit function to it
        $("#album-picker").prepend(`
            <div class="album-icon noselect" data-album-name="${albumName}" onclick="focusAlbum($(this).attr('data-album-name'))">
                <img src='/assets/app-icons/gallery.png' class='album-icon-img default-icon' alt='Album icon image.'>
                <h1>${albumName}</h1>
            </div>
        `);

        // focus that album
        focusAlbum(albumName);
    } else if (formType === "upload") {
        // show the new album writer
        $("#upload-form-content").css("display", "flex");

        // update display
        const form = $("#upload-form-content").find("form")[0];
        const display = (form.files ? form.files.length : 0) + " files selected.";
        $(form.parentElement).find("h2").html(display);

        // check for form submit disable
        $("#upload-form-content").find("input[type=submit]").attr("disabled", (form.files ? form.files.length : 0) === 0);
    }
}

function uploadFile() {
    const form = $("#upload-form")[0];
    const fileInput = $(form).find("input[type='file']")[0];
    let data = new FormData(form);

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

        $.ajax({
            "url": "uploadFile.php",
            "method": "POST",
            "data": data,
            "contentType": false,
            "processData": false,
            "success": function(res) { // success, prompt user with success message
                promptUser("Form Submission", "Files uploaded successfully.", false);
                $("#upload-form-content").css("display", "none");

                // focus the first page of the album
                focusAlbum(CONTENT.album.name, true);
            },
            "error": function(e) { // error, alert user
                const msg = e.responseText.substring(7); // remove 'Error: ' from beginning
                const title = msg.split("\n")[0];
                const body = msg.split("\n")[1];
                promptUser(title, body, false);
                $("#upload-form-content").css("display", "none");
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
                // console.log(i, this.videoWidth, this.videoHeight);

                checkCompletion();
            });
            dummyVideo.src = url;
        } else if (file.type.startsWith("image")) {
            const dummyImage = document.createElement("IMG");
            const url = URL.createObjectURL(file);
            $(dummyImage).on("load", function() {
                dimensions[i] = [this.width, this.height];
                URL.revokeObjectURL(url);
                // console.log(i, this.width, this.height);

                checkCompletion();
            });
            dummyImage.src = url;
        }
    }
    
    return false;
}