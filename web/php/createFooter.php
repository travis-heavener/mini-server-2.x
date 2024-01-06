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

    // close div
    echo "</div>";
?>