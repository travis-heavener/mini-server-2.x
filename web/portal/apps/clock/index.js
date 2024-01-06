// document onready events
$(document).ready(() => {
    toggleAnim();

    // add clock markings
    for (let i = 0; i < 60; i++) {
        const marker = document.createElement("DIV");
        $(marker).addClass((i % 5 === 0) ? "live-hour-marker" : "live-min-marker");
        $(marker).css("transform", `rotate(${i * 6}deg) translateY(calc(var(--size) / 2 - var(--length) / 2 - var(--border-thickness))`);

        $("#live-clock-container").append(marker);
    }

    // add clock hands (prevents them from freaking out when first rotating)
    $("#live-clock-container").prepend(`
        <div id="live-sec-needle" class="live-needle"></div>
        <div id="live-min-needle" class="live-needle"></div>
        <div id="live-hour-needle" class="live-needle"></div>
    `);

    // calculate and start interval
    const update = (offsetSec=0) => {
        // determine the clock's needle rotations
        const tsLocal = Date.now() - (new Date().getTimezoneOffset()*6e4); // seconds since local epoch (1/1/1970 w/ LOCAL time)
        const secSinceTwelve = (tsLocal % 4.32e7) / 1000 + offsetSec + 1;
        
        // update each needle individually (easier to clamp bc of modulo limitations in CSS calc & operation unit restrictions)
        $("#live-hour-needle").css("transform", `rotate(${secSinceTwelve / 120}deg)`);
        $("#live-min-needle").css("transform", `rotate(${secSinceTwelve / 10}deg)`);
        $("#live-sec-needle").css("transform", `rotate(${secSinceTwelve * 6}deg)`);
    };

    // initially set each needle's position
    update(-1);
    setTimeout(update, 1); // add really small delay to allow animation to kick in

    // delay the initial start so we are on near-whole seconds
    const delay = 1000 - new Date().getMilliseconds();

    // start interval (delay doesn't matter here)
    setTimeout(() => {
        update();
        setInterval(update, 1000);
    }, delay);
});