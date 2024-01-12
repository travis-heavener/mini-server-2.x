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
    const getMaxIcons = () => {
        const size = parseInt( $("#album-content").css("--size").slice(0, -2) ); // get icon size and remove 'px'
        const gap = parseInt( $("#album-content").css("gap").slice(0, -2) ); // get flex gap and remove 'px'
        const width = $("#album-content").width(); // width w/o padding or border
        const height = $("#album-content").height(); // height w/o padding or border

        ALBUM_CONTENT["maxAcross"] = Math.floor((width + gap) / (size + gap)); 
        ALBUM_CONTENT["maxDown"] = Math.ceil((height + gap) / (size + gap));
    };

    getMaxIcons();

    // and bind this getMaxIcons method to window resize events
    $(window).on("resize", getMaxIcons);
    */

    // load initial content
    loadContent({"page": 1});

    // and check for more content that must be loaded when scrolling stops and the bottom row of placeholders is in view
    $()
});

function loadContent({page}) {
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