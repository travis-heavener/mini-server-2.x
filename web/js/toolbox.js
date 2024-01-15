/*************** START USER PROMPTS ***************/

const promptUser = (title, text, resetUrlParams=true, callback=()=>{}) => {
    const wrapper = document.createElement("div");
    $(wrapper).addClass("user-prompt");
    $(wrapper).attr("data-reset", resetUrlParams);
    $(wrapper).attr("data-resolves-modal", true);
    $(wrapper).click(function(e) {
        if ($(e.target).attr("data-resolves-modal") === "true") {
            hideUserPrompt();
            callback();
        }
    });

    $(wrapper).append(`
        <div>
            <h1>${title}</h1>
            <p>${text}</p>
            <button data-resolves-modal="true" onclick="hideUserPrompt()">Close</button>
        </div>
    `);
    $("body").append(wrapper);
};

function hideUserPrompt() {
    $(".user-prompt").each(function() {
        const willReset = $(this).attr("data-reset") === "true";
        if (willReset) // consequently reloads the page and thus escapes the function
            window.history.replaceState({}, document.title, window.location.href.replace(window.location.search, ""));
        this.parentElement.removeChild(this);
    });
}

const passivePrompt = (text) => {
    const div = document.createElement("DIV");
    $(div).addClass("passive-prompt");
    $(div).append("<p>" + text + "</p>");
    $("body").append(div);

    setTimeout(() => div.parentElement.removeChild(div), 4e3 + 1); // wait the 4 second duration plus buffer
};

const confirmPrompt = (title, text, confirmText="Yes", rejectText="No") => {
    // create modal
    const wrapper = document.createElement("div");
    $(wrapper).addClass("user-prompt");
    $(wrapper).attr("data-resolve-to", false);

    $(wrapper).append(`
        <div>
            <h1>${title}</h1>
            <p>${text}</p>
            <div class="button-row">
                <button data-resolve-to="true">${confirmText}</button>
                <button data-resolve-to="false">${rejectText}</button>
            </div>
        </div>
    `);
    $("body").append(wrapper);

    return new Promise((resolve) => {
        $(wrapper).click(function(e) {
            const attr = $(e.target).attr("data-resolve-to");
            if (typeof(attr) === "undefined" || attr === false) return;

            wrapper.parentElement.removeChild(wrapper);
            resolve(attr === "true");
        });
    });
};

const textPrompt = (title, text, minLength=0, maxLength=null) => {
    // create modal
    const wrapper = document.createElement("div");
    $(wrapper).addClass("user-prompt");

    $(wrapper).append(`
        <div>
            <h1>${title}</h1>
            <p>${text}</p>
            <form>
                <input
                    type="text" value="" placeholder="Album Name"
                    pattern="([a-zA-Z0-9][a-zA-Z0-9\\._\\- ]*){${minLength},${maxLength}}$"
                    minlength="${minLength}" ${maxLength === null ? "" : "maxlength='" + maxLength + "'"}
                    required
                >
                <div class="button-row">
                    <input type="submit" value="Done">
                    <button class="cancel-btn">Cancel</button>
                </div>
            </form>
        </div>
    `);
    $("body").append(wrapper);

    return new Promise((resolve, reject) => {
        $(wrapper).click(function(e) {
            if (e.target === wrapper || $(e.target).is("button")) {
                // invalid text is entered
                $(wrapper).remove();
                reject({"responseText": "Error: silent_error"});
            }
        });
        $(wrapper).find("form").attr("action", "javascript:void 0;");
        $(wrapper).find("form").on("submit", function(e) {
            // we know the text is of valid length
            const text = $(wrapper).find("input")[0].value;
            $(wrapper).remove();
            resolve(text.trim());
            return false;
        });
    });
};

/*************** END USER PROMPTS ***************/