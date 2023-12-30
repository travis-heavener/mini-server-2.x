// background animation
const backAnim = {
    "int": null,
    "rot": 0,
    "freq": 60,
    "isRunning": false,
    "setFreq": function(freq, willRestart=true) {
        // update value
        this.freq = freq;

        // stop anim
        this.stop();

        // restart anim
        if (willRestart)
            setTimeout(() => this.start(), freq);
    },
    "start": function() {
        this.stop(); // stop any running intervals
        this.int = setInterval(() => {
            this.rot++;
            this.rot %= 360;

            $("body").css({
                "background-image": `
                    linear-gradient(${(225 + this.rot) % 360}deg, #535edbcc, #0000 80%),
                    linear-gradient(${(135 + this.rot) % 360}deg, #ff0c, #0000 80%),
                    linear-gradient(${(330 + this.rot) % 360}deg, #eb1d1dcc, #0000 80%)
                `.trim()
            });
        }, this.freq);

        this.isRunning = true;
    },
    "stop": function() {
        this.int !== null && clearInterval(this.int);
        this.int = null;

        this.isRunning = false;
    },
    "toggle": function() {
        if (this.int === null)
            this.start();
        else
            this.stop();

        return this.isRunning;
    }
};

// document onready events
$(document).ready(() => {
    toggleAnim();

    // handle field pattern formatting
    $("#email-input").attr("pattern", "[a-zA-Z0-9\._\\-]+@[a-zA-Z0-9\._\\-]+\\.[a-zA-Z]{2,4}$");
    $("#password-input").attr("pattern", "^\.{6,}$");
});

function toggleAnim() {
    $("#anim-checkbox").attr("checked", backAnim.toggle());
}