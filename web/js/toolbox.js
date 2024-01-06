/*************** START USER PROMPTS ***************/

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

function hideUserPrompt() {
    $(".user-prompt").each(function() {
        this.parentElement.removeChild(this);
    });
    window.history.replaceState({}, document.title, window.location.href.replace(window.location.search, ""));
}

/*************** END USER PROMPTS ***************/