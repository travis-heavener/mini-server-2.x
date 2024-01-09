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

/*************** END USER PROMPTS ***************/