// app click functions
function launchApp(name) {
    switch (name) {
        case "gallery":
            location.href = "/portal/apps/gallery/index.php";
            break;
        case "clock":
            location.href = "/portal/apps/clock/index.php";
            break;
        case "fire":
            location.href = "/portal/apps/fireplace/index.php";
            break;        
        case "notes":
            location.href = "/portal/apps/notes/index.php";
            break;
        case "admin":
            location.href = "/portal/apps/admin/index.php";
            break;
        default:
            console.log("Unknown app/service:", name);
            break;
    }
}