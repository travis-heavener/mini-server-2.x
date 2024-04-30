$(document).ready(() => {
    // bind text scroll to all content H1s
    $(".film-icon-wrapper > h1").each(function() { bindTextScroll(this) });
});

// binds a text scroll interval to the element given
function bindTextScroll(elem) {
    let interval = null;
    $(elem.parentElement).on("mouseenter", function() {
        if (interval !== null) return;
        
        const DELAY = 1.5e3;
        const RATE = 25; // in ms, interval callback rate
        
        let offset = 0, lastStopped = 0;
        interval = setTimeout(() =>{
            interval = setInterval(() => {
                if (Date.now() - lastStopped < DELAY) return;
    
                elem.scrollTo({left: offset++, top: 0});
    
                if (offset >= elem.scrollWidth - elem.clientWidth) {
                    offset = 0;
                    lastStopped = Date.now();
                    setTimeout(() => elem.scrollTo(0, 0), 0.67 * DELAY);
                }
            }, RATE);
        }, 1e3);
    });

    $(elem.parentElement).on("mouseleave", function() {
        if (interval === null) return;
        clearInterval( interval );
        interval = null;
        elem.scrollTo({left: 0, top: 0, behavior: "smooth"});
    });
}