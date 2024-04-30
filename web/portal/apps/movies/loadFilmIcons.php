<?php

/**
 * INCLUDED IN: index.php
 * FOR: querying database and loading film icons to DOM
 * AUTHOR: Travis Heavener
*/

include_once($_SERVER['DOCUMENT_ROOT'] . "/php/toolbox.php");
$user_id = $user_data["id"];

// 1. get envs & query db (auth just confirmed by page)
$envs = loadEnvs();
$mysqli = new mysqli($envs["HOST"], $envs["USER"], $envs["PASS"], $envs["DBID"]);

$result = $mysqli->query("SELECT * FROM `film_library` ORDER BY `title` ASC;")->fetch_all(MYSQLI_ASSOC);

// 2. for all entries, create & echo element
foreach ($result as $row) {
    // grab thumbnail image source for each image
    // using B64 instead of BLOBs because these are really small thumbnail images (order of 1 KiB)
    $id = $row["id"];
    $thumb_path = $envs["MOVIES_PATH"] . dechex($id) . "/thumb.jpg";
    $src = file_get_contents($thumb_path);
    $src_b64 = "data:image/jpeg;base64," . base64_encode($src);

    $title = $row["title"];
    $year = $row["year"];
    $runtime = (int)$row["runtime"];
    if ($runtime >= 1)
        $runtime_text = $runtime . " minute" . ($runtime === 1 ? "" : "s");
    else
        $runtime_text = "<1 minute";

    echo "
        <div class='film-icon-wrapper' data-film-id='$id'>
            <img src='$src_b64'>
            <h1>$title</h1>
            <h2>$year</h2>
            <h2>$runtime_text</h2>
        </div>
    ";
}