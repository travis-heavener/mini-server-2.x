$(document).ready(() => {
    // grab id from URL
    const id = new URLSearchParams(location.search).get("id");
    const src = "/movies/" + id.toString(16) + "/stream.m3u8";
    const video = $("#video-out")[0];
    if (Hls.isSupported()) {
        const hls = new Hls();
        hls.loadSource(src);
        hls.attachMedia(video);
        hls.on(Hls.Events.MANIFEST_PARSED, () => $(".play-overlay").css("display", "block"));
    } else if (video.canPlayType("application/vnd.apple.mpegurl")) {
        video.src = src;
        video.addEventListener("canplay", () => $(".play-overlay").css("display", "block"));
    } else {
        // display unable to play message
    }

    // bind video play to play overlay
    const initialPlay = () => {
        video.play();
        $(".play-overlay").css("display", "");
        bindControlEvents(); // bind control bar events
        bindKeyEvents(); // bind keyboard events

        // unbind all initialPlay calls
        $(".play-overlay").remove();
        $(video).off("click.initialPlay");
        $(window).off("keydown.initialPlay");
    };
    $(".play-overlay, #video-out").on("click.initialPlay", initialPlay);
    $(window).on("keydown.initialPlay", e => e.code === "Space" && initialPlay());
});

/**
 * show control bar when:
    * paused
    * when playing and the mouse is moving over the video
*/
function bindControlEvents() {
    const video = $("#video-out")[0];
    const idleTimeoutMs = 2e3; // after this interval, bar will fade out
    const fadeMs = 33; // duration of fade in/out
    let timeout = null; // interval for when bar will hide next

    // shorthand to show the control bar & cursor
    const showControls = () => {
        $(".video-control-bar").fadeIn(fadeMs);
        $(video).css("cursor", "");
    };
    
    // bind icon click events
    const togglePlay = (e) => {
        e.preventDefault();
        video.paused ? video.play() : video.pause();
    };
    $(".video-control-bar > .video-play-icon").click(e => togglePlay(e));

    // handle pause and play events
    $(video).on("pause", function(e) {
        // show control bar
        if (timeout !== null) clearTimeout(timeout);
        timeout = null;
        showControls();

        // show paused icons
        $(".video-control-bar .svg-play-icon").show();
        $(".video-control-bar .svg-pause-icon").hide();
    });

    $(video).on("play", () => {
        // show resumed icons
        $(".video-control-bar .svg-play-icon").hide();
        $(".video-control-bar .svg-pause-icon").show();
    });

    // handle mousemove to reveal control bar on video
    $(video).on("play mousemove", function(e) {
        if (!this.paused) {
            showControls();
            
            // queue interval
            if (timeout !== null) clearTimeout(timeout);
            timeout = setTimeout(() => {
                $(".video-control-bar").fadeOut(fadeMs);
                $(video).css("cursor", "none");
                timeout = null;
            }, idleTimeoutMs);
        }
    });
}

/**
 * Bind all keyboard shortcut events
 * Space/k: play/pause
 * F11/Double click/f: toggle fullscreen
 * Esc: leave fullscreen
 * Left Arrow: back 10 sec
 * Right Arrow: seek 10 sec
 * Up Arrow: volume up 10%
 * Down Arrow: volume down 10%
 * m: toggle mute
 */
function bindKeyEvents() {
    const video = $("#video-out")[0];
    const keysDown = {};
    
    // from /toolbox.js
    const __reqFull = getRequestFullscreenFunc($("#main-content")[0]);
    const __reqExit = getExitFullscreenFunc();
    const requestFullscreen = () => {
        $(video).css("max-height", "100vh");
        __reqFull();
    };
    const exitFullscreen = () => {
        $(video).css("max-height", "");
        __reqExit();
    };
    const toggleFullscreen = () => getIsFullscreen() ? exitFullscreen() : requestFullscreen();

    // fix: bind event to browser fullscreenchange to fix video max-height
    $(document).on("fullscreenchange", () => !getIsFullscreen() && $(video).css("max-height", ""));

    // constants
    const holdThreshMs = 20; // ms, between valid keypresses for held keys
    const seekStep = 10; // how many seconds to seek fwd/back
    const ctrlSeekMult = 3; // multiplier when held for seeking
    const shiftSeekMult = 0.5; // multiplier when held for seeking
    const volStep = 0.1; // how much to change volume by when pressing up/down arrows

    // bind play/pause to spacebar and "k" keys
    $(window).on("keydown", e => {
        const {code} = e;
        const now = Date.now();

        // prevent invalid key holding
        if (code in keysDown && now - keysDown[code] < holdThreshMs)
            return e.preventDefault(); // still prevent default

        // key is valid and not held
        switch (code) {
            case "Space": case "KeyK": {  video.paused ? video.play() : video.pause(); break;  }
            case "ArrowUp": {  video.volume = Math.min(video.volume + volStep, 1); break;  }
            case "ArrowDown": {  video.volume = Math.max(video.volume - volStep, 0); break;  }
            case "KeyM": {  video.muted = !video.muted; break;  }
            case "F11": case "KeyF": {  toggleFullscreen(); break;  }
            case "Escape": {  getIsFullscreen() && exitFullscreen(); break;  }
            case "ArrowLeft": {
                const mult = e.shiftKey && !e.ctrlKey ? shiftSeekMult : e.ctrlKey && !e.shiftKey ? ctrlSeekMult : 1;
                video.currentTime = Math.max(video.currentTime - seekStep * mult, 0);
                break;
            }
            case "ArrowRight": {
                const mult = e.shiftKey && !e.ctrlKey ? shiftSeekMult : e.ctrlKey && !e.shiftKey ? ctrlSeekMult : 1;
                video.currentTime = Math.min(video.currentTime + seekStep * mult, video.duration);
                break;
            }
            default: return; // prevent from preventing default
        }
        
        // didn't return, so prevent default and update key
        e.preventDefault();
        keysDown[code] = now;
    });

    // on key up, delete the code from the keysDown
    $(window).on("keyup", e => {
        const {code} = e;
        delete keysDown[code];
    });

    // -------------- mouse specific events --------------
    const dblClickThreshMs = 500; // ms between clicks to count as double click

    // bind clicks to video
    let lastVidClick = null;
    $(video).click(function(e) {
        e.preventDefault();
        this.paused ? this.play() : this.pause();

        // if double click, also toggle fullscreen
        const now = Date.now();
        if (lastVidClick !== null && now - lastVidClick <= dblClickThreshMs) {
            toggleFullscreen();
            lastVidClick = null;
        } else {
            lastVidClick = now;
        }
    });
}