$(document).ready(() => {});

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