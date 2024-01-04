// document onready events
$(document).ready(() => {
    toggleAnim();

    // handle field pattern formatting
    $("#email-input").attr("pattern", "[a-zA-Z0-9\._\\-]+@[a-zA-Z0-9\._\\-]+\\.[a-zA-Z]{2,4}$");
    $("#password-input").attr("pattern", "^\.{6,}$");

    // if we've redirected, display the reason (ie. session timeout, signature mismatch)
    const urlParams = new URLSearchParams(window.location.search);

    switch (urlParams.get("reason")) {
        case "exp": // prompt session timeout
            promptUser("Session Timeout", "The current session has expired,<br>please log in again.");
            break;
        case "sig": // prompt auth error
            promptUser("Authentication Error", "Session expired due to unexpected authentication mismatch, please log in again.");
            break;
        case "inv": // prompt user id not in users table
            promptUser("User Verification Error", "The user could not be found in the database (try logging in again or contacting a system administrator).");
            break;
        case "dup": // prompt user id not in users table
            promptUser("User Verification Error", "User database lookup returned more than 1 result, contact a system administrator.");
            break;
    }
});

function submit() {
    let data = new FormData($("#login-form")[0]);
    data = JSON.stringify(Object.fromEntries(data));
    $.ajax({
        "url": "/php/login.php",
        "method": "POST",
        "data": data,
        "contentType": "application/json",
        "success": function(res) {
            if (res.trim()) {
                promptUser("Login Error", "The supplied credentials are invalid.");
            } else {
                window.open("/portal/index.php", "_self");
            }
        },
        "error": function(e) {
            promptUser("Login Error", "An internal server error occured, try contacting an administrator if this continues to occur.");
        }
    });
    return false;
}