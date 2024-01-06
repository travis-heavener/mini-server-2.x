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

    // calculate and start interval
    const delay = 1000 - Date.now() % 1000; // how many ms since last whole second
    const update = () => {
        // determine the clock's needle rotations
        const tsLocal = Date.now() - (new Date().getTimezoneOffset()*6e4); // seconds since local epoch (1/1/1970 w/ LOCAL time)
        const secSinceTwelve = ~~((tsLocal % 4.32e7) / 1000); // bit operator round
        
        $("#live-clock-container").css("--sec-since-twelve", secSinceTwelve+1); // ADD 1 SECOND, IT'S A SECOND OFF ON EACH DEVICE?? LOL
    };

    // start interval and queue after delay
    update(); // initial call
    setTimeout(() => {
        $(".live-needle").css("transition", "1s linear transform"); // add animation
        update(); // call after delay (on second)
        setInterval(update, 1000); // call on each subsequent SECOND
    }, delay);
});