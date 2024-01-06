// document onready events
$(document).ready(() => {
    toggleAnim();
});

// app click functions
function launchApp(name) {
    switch (name) {
        case "clock":
            location.href = "/portal/apps/clock/index.php";
            break;
        case "fire":
            location.href = "/portal/apps/fireplace/index.php";
            break;        
        case "notes":
            location.href = "/portal/apps/notes/index.php";
            break;
        default:
            console.log("Unknown app/service:", name);
            break;
    }
}