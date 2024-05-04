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
                    linear-gradient(calc(0deg   + var(--back-anim-rot)), #ed7b00ee, #0000 80%),
                    linear-gradient(calc(90deg  + var(--back-anim-rot)), #741eb1ee, #0000 80%),
                    linear-gradient(calc(180deg + var(--back-anim-rot)), #b51b6aee, #0000 80%),
                    linear-gradient(calc(270deg + var(--back-anim-rot)), #e73164ee, #0000 80%);
                
                animation: 30s linear backAnim infinite running;
                
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