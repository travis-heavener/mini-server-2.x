// document onready events
$(document).ready(() => {
    toggleAnim();
});

// account info update stuff
function submit() {
    let data = new FormData($("#account-info-form")[0]);
    data = JSON.stringify(Object.fromEntries(data));
    $.ajax({
        "url": "/php/updateData.php",
        "method": "POST",
        "data": data,
        "contentType": "application/json",
        "success": function(res) {
            console.log(res);
            if (res.trim()) {
                promptUser("Server Error", "An internal server error occured.");
            } else {
                window.location.reload(true);
            }
        },
        "error": function(e) {
            promptUser("Server Error", "An internal server error occured.");
        }
    });
    return false;
}