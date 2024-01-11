$(document).ready(() => {
    // bind text scroll to all album-icon h1
    $(".album-icon").on("mouseenter", function(e) {
        const h1 = $(this).find("h1")[0];
        if (h1.scrollWidth === h1.clientWidth) return; // only scroll elements that overflow

        // wait initial time
        const DELAY = 1.5e3;
        let interval = null;

        let timeout = setTimeout(() => {
            let offset = 0;
            let lastStopped = 0;
            interval = setInterval(() => {
                if (Date.now() - lastStopped < DELAY) return;

                h1.scrollTo({left: offset, behavior: "smooth"});
                offset += 1;

                if (offset >= h1.scrollWidth - h1.clientWidth) {
                    offset = 0;
                    lastStopped = Date.now();
                    setTimeout(() => h1.scrollTo(0, 0), 0.67 * DELAY);
                }
            }, 50);
        }, 1e3);

        $(this).on("mouseleave", function(e) {
            clearTimeout(timeout);
            interval !== null && clearInterval(interval);
            h1.scrollTo(0, 0);
        });
    });

    // load initial content
});

function checkScroll() {
    // determine how many 
}

function getPhotos(start, end) {

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