$(document).ready(() => {
    // grab id from URL
    const id = new URLSearchParams(location.search).get("id");
    const src = "/movies/" + id.toString(16) + "/stream.m3u8";
    const video = $("#video-container")[0];
    if (Hls.isSupported()) {
        const hls = new Hls();
        hls.loadSource(src);
        hls.attachMedia(video);
        hls.on(Hls.Events.MANIFEST_PARSED, () => $("#play-overlay").css("display", "block"));
    } else if (video.canPlayType("application/vnd.apple.mpegurl")) {
        video.src = src;
        video.addEventListener("canplay", () => $("#play-overlay").css("display", "block"));
    } else {
        // display unable to play message
    }

    // bind video play to play overlay
    $("#play-overlay").one("click", function() {
        video.play();
        $(this).css("display", "");
    });
});