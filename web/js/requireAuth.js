// require that the user is logged in for the remainder of the session
$(document).ready(() => {
    // check that the cookie exists
    if (!Cookies.get("ms-user-auth")) {
        window.location.replace("/login/");
    }
});