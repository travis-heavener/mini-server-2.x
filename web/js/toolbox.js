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

const passivePrompt = (text, deleteExisting=false) => {
    if (deleteExisting) $(".passive-prompt").remove();

    const div = document.createElement("DIV");
    $(div).addClass("passive-prompt");
    $(div).append("<p>" + text + "</p>");
    $("body").append(div);

    setTimeout(() => $(div).remove(div), 4e3 + 1); // wait the 4 second duration plus buffer
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
                    <button type="button" class="cancel-btn">Cancel</button>
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

const triplePrompt = (title, text, textA="Option A", textB="Option B", textC="Option C") => {
    // create modal
    const wrapper = document.createElement("div");
    $(wrapper).addClass("user-prompt");
    $(wrapper).attr("data-resolve-to", false);

    $(wrapper).append(`
        <div>
            <h1>${title}</h1>
            <p>${text}</p>
            <div class="button-row">
                <button class="prompt-triple" data-resolve-to="1">${textA}</button>
                <button class="prompt-triple" data-resolve-to="2">${textB}</button>
                <button class="prompt-triple" data-resolve-to="3">${textC}</button>
            </div>
        </div>
    `);
    $("body").append(wrapper);

    return new Promise((resolve) => {
        $(wrapper).click(function(e) {
            const attr = $(e.target).attr("data-resolve-to");
            if (typeof(attr) === "undefined" || attr === false) return;

            wrapper.parentElement.removeChild(wrapper);
            resolve(parseInt(attr));
        });
    });
};

/*************** END USER PROMPTS ***************/
/*************** START MISC ***************/

// wrote this myself, I should really be more active on StackOverflow since this is really helpful :)
function formatByteSize(bytes=0) {
    let raw = parseInt(bytes);
    const suffixes = ["B", "KiB", "MiB", "GiB", "TiB"];

    let i = 0;
    while (raw >= 2 ** (10 * (i+1)) && i < suffixes.length)
        i++;

    return (raw / (2 ** (10*i))).toFixed(1) + " " + suffixes[i];
}

/**
 * Thanks to https://stackoverflow.com/a/6039930 (for requestFullscreen) and
 * https://stackoverflow.com/a/36672683 (for exitFullscreen) help with handling cross-compatibility.
 */

/**
 * Returns true if the document is fullscreened, false otherwise.
 */
const getIsFullscreen = () => !(document.fullscreenElement === null || document.webkitFullscreenElement === null);

/**
 * Returns a function that, when called, will exit fullscreen.
 * 
 * NOTE: This will raise an exception if the document is not in fullscreen mode.
 */
const getExitFullscreenFunc = () => {
    return (document.exitFullscreen || document.webkitExitFullscreen ||
            document.mozCancelFullScreen || document.msExitFullscreen).bind(document);
};
    
/**
 * Returns a function that, when called, will request fullscreen on the provided element.
 */
const getRequestFullscreenFunc = (elem) => {
    return (elem.requestFullscreen || elem.mozRequestFullScreen || elem.mozRequestFullScreen ||
            elem.webkitRequestFullscreen || elem.webkitRequestFullscreen || elem.msRequestFullscreen).bind(elem);
};

/*************** END MISC ***************/