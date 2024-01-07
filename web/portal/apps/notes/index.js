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

function focusNote(noteId) {
    // $path = $envs["NOTE_PATH"] . dechex($id) . ".txt";
    console.log(noteId);
    // AJAX Call
    // success, show editor
    // error, remove from url and focusMenu()
    // window.history.replaceState({}, document.title, window.location.href.replace(window.location.search, ""));
}