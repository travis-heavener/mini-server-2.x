// document onready events
$(document).ready(() => {
    toggleAnim();

    // update header width
    const updateWidth = () => {
        const width = $("html").prop("clientWidth");
        $("body").css("width", width + "px");
        $("html").css("width", width + "px");
    };

    $(window).on("resize", updateWidth);
    updateWidth();
});