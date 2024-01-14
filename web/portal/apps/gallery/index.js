const ALBUM_CONTENT = {
    "name": "",
    "currentPage": 1
};

$(document).ready(() => {
    // initial binding of text scroll
    bindTextScroll();
    
    // also change text scrolling behavior on portrait/mobile
    screen.orientation.addEventListener("change", bindTextScroll);

    /*
    // determine how many icons fit on the page at a time
    

    getMaxIcons();

    // and bind this getMaxIcons method to window resize events
    $(window).on("resize", getMaxIcons);
    */

    // load initial content
    loadContent({"albumName": "My First Name", "page": 1});

    // and check for more content that must be loaded when scrolling stops and the bottom row of placeholders is in view
    $()
});

function getMaxIcons() {
    const size = parseInt( $("#album-content").css("--size").slice(0, -2) ); // get icon size and remove 'px'
    const gap = parseInt( $("#album-content").css("gap").slice(0, -2) ); // get flex gap and remove 'px'
    const width = $("#album-content").width(); // width w/o padding or border
    const height = $("#album-content").height(); // height w/o padding or border

    const maxAcross = Math.floor((width + gap) / (size + gap));
    const maxDown = Math.ceil((height + gap) / (size + gap));

    return {"across": maxAcross, "down": maxDown};
};

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
    const bounds = getMaxIcons();
    const imgSize = $("#album-content").css("--size").slice(0, -2); // get image size, remove 'px'
    const amtPerPage = 50;
    const pageOffset = (page-1) * amtPerPage;

    // error handler shorthand
    const handleError = e => {
        console.log("failure", e);
        const msg = e.responseText.substring(7); // remove 'Error: ' from beginning
        const title = msg.split("\n")[0];
        const body = msg.split("\n")[1];

        if (title === "auth_error") {
            window.location.reload(true);
        } else {
            promptUser(title, body, false);
        }
    };

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
            },
            "contentType": "application/json"
        });
        preloadInfo = JSON.parse(preloadInfo);
    } catch (err) {
        handleError(err);
        return;
    }

    const ids = [];
    for (let info of preloadInfo)
        ids.push(info.id);

    // display placeholder info & queue an ajax call for each
    // for (let i = 0; i < 1; i++) {
    for (let i = 0; i < ids.length; i++) {
        // create img placeholder
        let elem = document.createElement("IMG");
        const id = `album-content-${i}`;
        elem.id = id;
        $(elem).addClass("default-icon");
        $(elem).attr("src", "/assets/app-icons/gallery.png");
        $(elem).attr("alt", "Album content placeholder.");
        $("#album-content").append(elem);

        // queue ajax call
        $.ajax({
            "url": "resolveSrc.php",
            "method": "GET",
            // "dataType": "application/octet-stream",
            // "contentType": "application/octet-stream",
            "headers": {
                "MS2_id": ids[i],
                "MS2_isThumb": true
            },
            "success": (res, status, xhr) => {
                // replace image body
                const contentType = xhr.getResponseHeader("content-type");
                const id = xhr.getResponseHeader("MS2_id");
                const name = xhr.getResponseHeader("MS2_name");
                const mime = xhr.getResponseHeader("MS2_mime");
                const width = parseInt(xhr.getResponseHeader("MS2_width"));
                const height = parseInt(xhr.getResponseHeader("MS2_height"));
                const orientation = parseInt(xhr.getResponseHeader("MS2_orientation"));
                const isDefaultIcon = !!(xhr.getResponseHeader("MS2_isDefaultIcon") || false);

                console.log(res);
                console.log(mime, width, height, orientation);

                if (contentType === "application/json") {
                    // we have an image
                    const src = res["s"];

                    // we have an image, so just replace this one
                    elem.src = src;
                    if (!isDefaultIcon) $(elem).removeClass("default-icon");
                    $(elem).attr("alt", "Album image.");
                } else if (contentType === "application/octet-stream") {
                    // we have a video, so replace this image with a video
                    console.log(res);
                    /*
                    elem.outerHTML = `<video id="${id}" src="${src}" alt="Album video.">`;
                    elem = $("#" + id); // update reference after changing outerHTML

                    // play on hover
                    console.log(elem.outerHTML);
                    $(elem).on("mouseenter", function() {
                        this.muted = true;
                        this.play();
                        
                        $(this).on("mouseleave", function() {
                            this.pause();
                            this.currentTime = 0;
                        });
                    });
                    */
                } else {
                    console.warn("Unexpected MIME type: " + mime);
                }

                // append extra metadata
                $(elem).attr("data-content-id", id);
                $(elem).attr("data-fname", name);
            },
            "error": err => handleError(err)
        });
    }

    return;

    for (let i = 0; i < bounds.down; i++) {
        // add placeholder images (to prevent things that load faster from being messed up in terms of order)
        const childrenIds = [];

        for (let j = 0; j < bounds.across; j++) {
            const elem = document.createElement("IMG");
            const id = "album-content-" + (j+1) + "-" + (i+1);
            $(elem).attr("id", id); // format: album-content-<row+1>-<col+1>
            $(elem).addClass("default-icon");
            $(elem).attr("src", "/assets/app-icons/gallery.png");
            $(elem).attr("alt", "Album content placeholder.");

            childrenIds.push(id);
            $("#album-content").append(elem);
        }

        // load in the content in for each row
        $.ajax({
            "url": "preloadContent.php",
            "method": "GET",
            "headers": {
                "MS2_offset": pageOffset + (bounds.across * i),
                "MS2_maxAmt": bounds.across,
                "MS2_albumName": albumName,
                "MS2_imgWidth": imgSize, // specifying imgWidth allows for image resizing on the backend
                "MS2_imgHeight": imgSize // specifying imgWidth allows for image resizing on the backend
            },
            "contentType": "application/json",
            "success": function(res) { // success, show editor
                console.log(res);
                const {content} = JSON.parse(res);
                childrenIds.forEach(async (id, index) => {
                    const elem = $("#" + id)[0];

                    // remove placeholders if at the end of album
                    if (index >= content.length) {
                        elem.parentElement.removeChild(elem);
                        return;
                    }

                    // otherwise, inject new HTML
                    const data = content[index];
                    
                    if (data.mime.startsWith("image")) {
                        elem.outerHTML = `<img id="${id}" data-content-id="${data.id}" src="${data.src}" alt="Album image.">`;
                    } else if (data.mime.startsWith("video")) {
                        // try {
                            const raw = await getContentSrc(data.id, imgSize, imgSize);
                            // const byteChars = atob(JSON.parse(raw).src);
                            // const byteNumbers = new Array(byteChars);
                            // for (let i = 0; i < byteChars.length; i++) {
                            //     byteNumbers[i] = byteChars.charAt(i);
                            // }
                            // const byteArray = new Uint8Array(byteNumbers);
                            // const url = URL.createObjectURL( new Blob([byteArray]) );

                            // var byteCharacters = atob(JSON.parse(raw).src);
                            // var byteNumbers = new Array(byteCharacters.length);
                            // for (var i = 0; i < byteCharacters.length; i++) {
                            //     byteNumbers[i] = byteCharacters.charCodeAt(i);
                            // }
                            // var byteArray = new Uint8Array(byteNumbers);
                            // var blob = URL.createObjectURL(new Blob([byteArray]));

                            elem.outerHTML = `<video id="${id}" src="${JSON.parse(raw).src}" alt="Album video.">`;
                            $(elem).on("mouseenter", function() {
                                this.play();
                                
                                $(this).on("mouseleave", function() {
                                    this.pause();
                                    this.currentTime = 0;
                                });
                            });
                        // } catch (e) {
                        //     handleError(e);
                        // }
                    } else {
                        console.warn("Unsupported MIME type: " + data.mime);
                    }
                });
            },
            "error": e => handleError(e)
        });
    }

    // finally, update the page we are on
    ALBUM_CONTENT.currentPage = page;
    ALBUM_CONTENT.name = albumName;

    // ajax call, get images (limit=ALBUM_CONTENT["amtPerPage"], start on page no. 'page')
    // SELECT `id`, `name`, `mime`, `vector` FROM `$table` WHERE `album_name`=? ORDER BY `created` DESC LIMIT $pageCount OFFSET $currentPageNo*$pageCount;
    // allow the results per page to be toggled via dropdown (ie 25,50,75,100) alongside page pickers
    // default 50, but when changing results per page revert to page 1
    // when uploading files, reload the content on that page of the album, NOT the actual page

    // add a plus button fixed to bottom right of album content
    // this brings up a modal that shows an upload button
    // and shows all files that have been attached as well as
    // the destination album (what's focused currently)
    // and a submit button

    // on bottom of album picker, add a new album icon similar in size to the actual albums that are there
    // when adding an album, don't do anything on the backend until we upload files
    // focus this new album when we create it

    // UPDATE
    // plus button in bottom right of content when clicked brings up two options: 'new album' or 'upload'

    // on top, allow a select button for multi select or allow shift clicking
    // have a delete icon as well, confirm when pressed to delete any things selected (gray out when nothing is selected)

    // when on mobile, have the gallery picker on the bottom (horiz scroll)
    // make album picker icons square, hide cover icons
    // top toolbar stays on top
}

function uploadFile() {
    const form = $("form")[0];
    const fileInput = $(form).find("input[type='file']")[0];
    let data = new FormData(form);

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
            "success": function(res) { // success, show editor
                console.log("success", res);
            },
            "error": function(e) { // error, remove from url and reload
                console.log("failure", e);
                const msg = e.responseText.substring(7); // remove 'Error: ' from beginning
                const title = msg.split("\n")[0];
                const body = msg.split("\n")[1];
                promptUser(title, body, false);
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
                console.log(i, this.videoWidth, this.videoHeight);

                checkCompletion();
            });
            dummyVideo.src = url;
        } else if (file.type.startsWith("image")) {
            const dummyImage = document.createElement("IMG");
            const url = URL.createObjectURL(file);
            $(dummyImage).on("load", function() {
                dimensions[i] = [this.width, this.height];
                URL.revokeObjectURL(url);
                console.log(i, this.width, this.height);

                checkCompletion();
            });
            dummyImage.src = url;
        }
    }
    
    return false;
}