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
    loadContent({"albumName": "Cars", "page": 1});

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

function loadContent({albumName, page}) {
    // determine how many rows we can fit
    const bounds = getMaxIcons();
    const imgSize = $("#album-content").css("--size").slice(0, -2); // get image size, remove 'px'
    const amtPerPage = 50;
    const pageOffset = (page-1) * amtPerPage;

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
            "url": "loadContent.php",
            "method": "GET",
            "headers": {
                "MS2_offset": pageOffset + (bounds.across * i),
                "MS2_maxAmt": bounds.across,
                "MS2_albumName": albumName
            },
            "contentType": "application/json",
            "success": function(res) { // success, show editor
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
                        // rescale image (theoretically reduce memory footprint)
                        const src = await resizeImage(data.src, imgSize, imgSize, 2);
                        elem.outerHTML = `<img id="${id}" src="${src}" alt="Album image.">`;
                        // elem.outerHTML = `<img id="${id}" src="${data.src}" alt="Album image.">`;
                    } else if (data.mime.startsWith("video")) {
                        elem.outerHTML = `<video id="${id}" src="${data.src}" alt="Album video.">`;
                        $(elem).on("mouseenter", function() {
                            this.play();
                            
                            $(this).on("mouseleave", function() {
                                this.pause();
                                this.currentTime = 0;
                            });
                        });
                    } else {
                        console.warn("Unsupported MIME type: " + data.mime);
                    }
                });
            },
            "error": function(e) { // error, remove from url and reload
                console.log("failure", e);
                const msg = e.responseText.substring(7); // remove 'Error: ' from beginning
                const title = msg.split("\n")[0];
                const body = msg.split("\n")[1];

                if (title === "auth_error") {
                    window.location.reload(true);
                } else {
                    promptUser(title, body, false);
                }
            }
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
    // stagger loading per rows (using the getMaxIcons results)

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

// this is really smart thanks SO (https://stackoverflow.com/a/20965997)
async function resizeImage(uri, width, height, scaleFactor) {
    width = parseInt(width) * scaleFactor;
    height = parseInt(height) * scaleFactor;

    const canvas = document.createElement("CANVAS");
    const ctx = canvas.getContext("2d");
    
    canvas.width = width;
    canvas.height = height;
    
    // create and resolve promise for resizing images onload
    return new Promise((resolve) => {
        const img = document.createElement("IMG");
        $(img).attr("src", uri);
        $(img).on("load", function() {
            const imgWidth = this.width;
            const imgHeight = this.height;
            const ratio = imgWidth/imgHeight;

            // this next bit clips off the overflow to center the image in the bounds (prevent squishing)
            if (imgWidth >= imgHeight) {
                const scaledWidth = ratio * height;
                const overflow = scaledWidth - width;
                ctx.drawImage(this, -overflow/2, 0, scaledWidth + overflow/2, height);
            } else {
                const scaledHeight = width / ratio;
                const overflow = scaledHeight - height;
                ctx.drawImage(this, 0, -overflow/2, width, scaledHeight + overflow/2);
            }

            resolve(canvas.toDataURL('image/jpeg', 0.25));
        });
    });
}

function uploadFile() {
    let data = new FormData($("form")[0]);
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
    return false;
}