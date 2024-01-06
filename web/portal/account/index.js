// document onready events
$(document).ready(() => {
    // enable update button after input is changed
    $("#first-input").on("input propertychange", () => $("#account-info-submit")[0].disabled = false);
    $("#last-input").on("input propertychange", () => $("#account-info-submit")[0].disabled = false);
    $("#email-input").on("input propertychange", () => $("#account-info-submit")[0].disabled = false);
    
    $("#current-pass-input").on("input propertychange", () => $("#pass-update-submit")[0].disabled = false);
    $("#new-pass-input").on("input propertychange", () => $("#pass-update-submit")[0].disabled = false);
    $("#confirmed-pass-input").on("input propertychange", () => $("#pass-update-submit")[0].disabled = false);
});

// account info update stuff
function submitInfo() {
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

// account info update stuff
function submitPass() {
    let data = new FormData($("#pass-update-form")[0]);
    data = JSON.stringify(Object.fromEntries(data));
    dataParsed = JSON.parse(data);

    if (dataParsed["new-pass"] !== dataParsed["confirmed-pass"]) {
        promptUser("Password Mismatch", "Passwords must match.");
        return;
    }

    $.ajax({
        "url": "/php/updatePass.php",
        "method": "POST",
        "data": data,
        "contentType": "application/json",
        "success": function(res) {
            console.log(res);
            if (res.trim() === "Password mismatch") {
                promptUser("Password Mismatch", "Passwords must match.");
            } else if (res.trim()) {
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