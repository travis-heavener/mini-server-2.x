$(document).ready(() => {
    // determine if we've focused a note from the URL params
    const urlParams = new URLSearchParams(window.location.search);
    const noteId = urlParams.get("n");

    if (noteId === null) { // focus the editor body
        focusMenu();
    } else { // focus a note
        focusNote(noteId);
    }
});

function focusMenu() {
    $("#notes-menu").css("display", "flex");
}

// rewrite url to then call focusNote
function redirectToNote(noteId) {
    window.location.href = window.location.href + (window.location.href.includes("?") ? "&" : "?") + "n=" + noteId;
}

function focusNote(noteId) {
    $.ajax({
        "url": "fetchNote.php?id=" + noteId,
        "method": "GET",
        "contentType": "application/json",
        "success": function(res) { // success, show editor
            const body = JSON.parse(res);
            console.log(body);
        },
        "error": function(e) { // error, remove from url and reload
            const msg = e.responseText.substring(7); // remove 'Error: ' from beginning
            const title = msg.split("\n")[0];
            const body = msg.split("\n")[1];
            promptUser(title, body, true, () => window.location.reload());
        }
    });
}