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

function hideUserPrompt() {
    $(".user-prompt").each(function() {  this.parentElement.removeChild(this);  });
    window.history.replaceState({}, document.title, window.location.href.replace(window.location.search, ""));
}

// document onready events
$(document).ready(() => {
    toggleAnim();

    // handle field pattern formatting
    $("#email-input").attr("pattern", "[a-zA-Z0-9\._\\-]+@[a-zA-Z0-9\._\\-]+\\.[a-zA-Z]{2,4}$");
    $("#password-input").attr("pattern", "^\.{6,}$");

    // if we've redirected, display the reason (ie. session timeout, signature mismatch)
    const urlParams = new URLSearchParams(window.location.search);
    
    const promptUser = (title, text) => {
        const wrapper = document.createElement("div");
        $(wrapper).addClass("user-prompt");
        $(wrapper).click(function(e) {
            e.target === this && hideUserPrompt();
        });

        $(wrapper).append(`
            <div>
                <h1>${title}</h1>
                <p>${text}</p>
                <button onclick="hideUserPrompt()">Close</button>
            </div>
        `);
        $("body").append(wrapper);
    };

    switch (urlParams.get("reason")) {
        case "exp": // prompt session timeout
            promptUser("Session Timeout", "The current session has expired,<br>please log in again.");
            break;
        case "sig": // prompt auth error
            promptUser("Authentication Error", "Session expired due to unexpected authentication mismatch, please log in again.");
            break;
        case "inv": // prompt user id not in users table
            promptUser("User Verification Error", "The user could not be found in the database (try logging in again or contacting a system administrator).");
            break;
        case "dup": // prompt user id not in users table
            promptUser("User Verification Error", "User database lookup returned more than 1 result, contact a system administrator.");
            break;
    }
});

function toggleAnim() {
    $("#anim-checkbox").attr("checked", backAnim.toggle());
}