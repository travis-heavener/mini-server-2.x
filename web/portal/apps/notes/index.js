$(document).ready(() => {
    // determine if we've focused a note from the URL params
    const urlParams = new URLSearchParams(window.location.search);
    const noteId = urlParams.get("n");

    if (noteId === null) { // focus the editor body
        focusMenu();
    } else { // focus a note
        focusNote(noteId);

        // bind keyboard shortcuts
        $(document).on("keydown", (e) => {
            if (e.code === "KeyS" && e.ctrlKey) {
                saveNote();
                e.preventDefault();
            }
        });

        $("#editor-body").on("input", (e) => {
            __hasNoteChanged = true;
            $("#editor-save").css("filter", "");
        });

        // gray out floppy icon
        $("#editor-save").css("filter", "grayscale(1)");

        $(window).on('beforeunload', (e) => {
            if (__hasNoteChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    }
});

let __canCreateNote = true;
function createNote() {
    if (!__canCreateNote) return;
    __canCreateNote = false;

    $.ajax({
        "url": "createNote.php",
        "method": "POST",
        "contentType": "application/json",
        "success": function(noteId) { // refocus to note
            redirectToNote(noteId);
            __canCreateNote = true;
        },
        "error": function(e) {
            const msg = e.responseText.substring(7); // remove 'Error: ' from beginning
            const title = msg.split("\n")[0];
            const body = msg.split("\n")[1];
            if (title === "auth_error") {
                window.location.reload(true);
            } else {
                promptUser(title, body, true, () => window.location.reload());
            }
            __canCreateNote = true;
        }
    });
}

function focusMenu() {
    $("#notes-menu").css("display", "flex");
}

// rewrite url to then call focusNote
function redirectToNote(noteId) {
    window.location.href = window.location.href + (window.location.href.includes("?") ? "&" : "?") + "n=" + noteId;
}

function redirectToMenu() {
    const params = window.location.search;

    // initally remove params
    window.history.replaceState({}, document.title, window.location.href.replace(window.location.search, ""));
    window.location.reload(true);
    
    // if we havent't reloaded (ie. user cancel), replace the params
    window.history.replaceState({}, document.title, window.location.href + params);
}

let __hasNoteChanged = false;
function saveNote() {
    if ($("#editor-body").css("display") !== "flex") return;
    if ($("#editor-save").attr("data-locked") === "true") return;
    if (!__hasNoteChanged) return;

    // ajax call
    $.ajax({
        "url": "saveNote.php",
        "method": "POST",
        "contentType": "application/json",
        "data": JSON.stringify({
            "note_id": (new URLSearchParams(window.location.search)).get("n"),
            "title": $("#editor-title")[0].value,
            "body": $("#editor-text")[0].value
        }),
        "success": function() { // success, show editor
            __hasNoteChanged = false;
            $("#editor-save").css("filter", "grayscale(1)");
            passivePrompt("Note saved.");

            // prevent spam w/ timeout
            $("#editor-save").attr("data-locked", true);
            setTimeout(() => $("#editor-save").attr("data-locked", false), 150);
        },
        "error": function(e) {
            const msg = e.responseText.substring(7); // remove 'Error: ' from beginning
            const title = msg.split("\n")[0];
            const body = msg.split("\n")[1];
            if (title === "auth_error") {
                window.location.reload(true);
            } else if (title === "Text Oversize Error") {
                promptUser(title, body, false);
            } else {
                promptUser(title, body, true, () => window.location.reload());
            }
        }
    });
}

async function deleteNote() {
    // if (!confirm("Are you sure you want to delete this note?")) return;
    const isConfirmed = await confirmPrompt("Confirm Delete", "Are you sure you want to delete this note?");
    if (!isConfirmed) return;

    // ajax call
    $.ajax({
        "url": "deleteNote.php",
        "method": "DELETE",
        "contentType": "application/json",
        "data": JSON.stringify({
            "note_id": (new URLSearchParams(window.location.search)).get("n")
        }),
        "success": function() { // revert to notes picker
            window.history.replaceState({}, document.title, window.location.href.replace(window.location.search, ""));
            window.location.reload(true);
        },
        "error": function(e) {
            const msg = e.responseText.substring(7); // remove 'Error: ' from beginning
            const title = msg.split("\n")[0];
            const body = msg.split("\n")[1];
            if (title === "auth_error") {
                window.location.reload(true);
            } else {
                promptUser(title, body, true, () => window.location.reload());
            }
        }
    });
}

function focusNote(noteId) {
    $.ajax({
        "url": "fetchNote.php?id=" + noteId,
        "method": "GET",
        "contentType": "application/json",
        "success": function(res) { // success, show editor
            const body = JSON.parse(res);

            // write out content
            $("#editor-title").text(body.name);
            $("#editor-text").text(body.body);

            // unhide editor
            $("#editor-body").css("display", "flex");
        },
        "error": function(e) { // error, remove from url and reload
            const msg = e.responseText.substring(7); // remove 'Error: ' from beginning
            const title = msg.split("\n")[0];
            const body = msg.split("\n")[1];
            promptUser(title, body, true, () => window.location.reload());
        }
    });
}