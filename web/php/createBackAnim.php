<?php
    // add background animation
    echo "
        <style>
            @property --back-anim-rot {
                syntax: '<angle>';
                inherits: false;
                initial-value: 0deg;
            }

            body {
                --back-anim-rot: 0deg;
                background-image:
                    linear-gradient(calc(225deg + var(--back-anim-rot)), #535edbcc, #0000 80%),
                    linear-gradient(calc(135deg + var(--back-anim-rot)), #ff0c, #0000 80%),
                    linear-gradient(calc(330deg + var(--back-anim-rot)), #eb1d1dcc, #0000 80%);
                
                animation: 20s linear backAnim infinite running;
                
                background-position: center;
                background-repeat: no-repeat;
                background-size: cover;
                background-attachment: fixed;
            }

            @keyframes backAnim {
                from {  --back-anim-rot: 0deg;  }
                to {  --back-anim-rot: 360deg;  }
            }
        </style>
    ";
?>