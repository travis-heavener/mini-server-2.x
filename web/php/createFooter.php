<?php
    // create apps footers
    echo "<div id='footer-content'>";

    // pick random footnote
    $notes = [
        "Made with ❤.",
        "Est. 2023.",
        "Never share your password.",
        "<a href='mailto:travis.heavener@gmail.com'>travis.heavener@gmail.com</a> for inquiries."
    ];
    echo "<p>" . $notes[array_rand($notes)] ."</p>";

    // add anim controls if set
    if (isset($show_anim_ctrls)) {
        echo "
            <div id='anim-controls'>
                <p id='anim-desc'>Animate:</p>
                <input type='checkbox' checked='true' id='anim-checkbox' onclick='toggleAnim(this)' title='Toggle background animation'>
            </div>
        ";
    }

    // close div
    echo "</div>";
?>