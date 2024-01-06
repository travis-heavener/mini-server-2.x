// document onready events
const CLOCK = {
    live: 0,
    digital: 0
};

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

    // focus a clock
    focusClock("digital");
});

function updateLive(offsetSec=0) {
    // determine the clock's needle rotations
    const tsLocal = Date.now() - (new Date().getTimezoneOffset()*6e4); // seconds since local epoch (1/1/1970 w/ LOCAL time)
    const secSinceTwelve = (tsLocal % 4.32e7) / 1000 + offsetSec + 1;
    
    // update each needle individually (easier to clamp bc of modulo limitations in CSS calc & operation unit restrictions)
    $("#live-hour-needle").css("transform", `rotate(${secSinceTwelve / 120}deg)`);
    $("#live-min-needle").css("transform", `rotate(${secSinceTwelve / 10}deg)`);
    $("#live-sec-needle").css("transform", `rotate(${secSinceTwelve * 6}deg)`);
}

function updateDigital() {
    const timeStr = new Date().toLocaleTimeString();
    const hour = timeStr.split(":")[0];
    const min = timeStr.split(":")[1];
    const sec = timeStr.split(":")[2].split(" ")[0];

    $("#digital-hour").html(hour.padStart(2, "0"));
    $("#digital-min").html(min.padStart(2, "0"));
    $("#digital-sec").html(sec.padStart(2, "0"));
    $("#digital-half").html(timeStr.endsWith("AM") ? "AM" : "PM");
}

function focusClock(type) {
    clearInterval(CLOCK.live);
    clearInterval(CLOCK.digital);

    switch (type) {
        case "live":
            $("#live-content").css("display", "flex");
            $("#digital-content").css("display", "none");

            $("#live-clock-icon > .icon-selector").css("display", "flex");
            $("#digital-icon > .icon-selector").css("display", "none");
            
            // restart interval
            updateLive(-1); // initially set each needle's position
            setTimeout(updateLive, 1); // add really small delay to allow animation to kick in

            setTimeout(() => {
                updateLive();
                CLOCK.live = setInterval(updateLive, 1000);
            }, 1000 - new Date().getMilliseconds()); // delay the initial start so we are on near-whole seconds
            break;
        case "digital":
            $("#live-content").css("display", "none");
            $("#digital-content").css("display", "flex");

            $("#live-clock-icon > .icon-selector").css("display", "none");
            $("#digital-icon > .icon-selector").css("display", "flex");

            // restart interval
            updateDigital();

            setTimeout(() => {
                updateDigital();
                CLOCK.live = setInterval(updateDigital, 1000); // update twice per second to add to ensure proper display
            }, 1000 - new Date().getMilliseconds()); // delay the initial start so we are on near-whole seconds
            break;
    }
}