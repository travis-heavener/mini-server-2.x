<?php
    // helper functions for content scaling & such

    $THUMB_SIZE = 128; // size of image thumbnails, in px
    
    // modified from SO, handy function for resizing images (reduces client memory footprint dramatically) (https://stackoverflow.com/a/45479025)
    function resize_image($data, $width, $height, $raw_width, $raw_height) {
        if (gettype($data) === "object" && get_class($data) == "GdImage")
            $raw_image = $data;
        else
            $raw_image = imagecreatefromstring($data);
        $new_image = imagecreatetruecolor($width, $height);

        // calculate aspect ratios
        $native_ratio = $raw_width / $raw_height;
        $target_ratio = $width / $height;

        // calculate crop dimensions
        if ($native_ratio > $target_ratio) {
            // image is wider, crop width
            $scaled_width = $raw_height * $target_ratio;
            $scaled_height = $raw_height;
        } else {
            // image is taller or square, crop height
            $scaled_width = $raw_width;
            $scaled_height = $raw_width / $target_ratio;
        }

        // crop image
        $scaled_x = ($raw_width - $scaled_width) / 2;
        $scaled_y = ($raw_height - $scaled_height) / 2;
        $raw_image = imagecrop($raw_image, ["x" => $scaled_x, "y" => $scaled_y, "width" => $scaled_width, "height" => $scaled_height]);

        // resize the cropped image to the target dimensions
        imagecopyresampled($new_image, $raw_image, 0, 0, 0, 0, $width, $height, $scaled_width, $scaled_height);

        ob_start();
        imagepng($new_image);
        $contents = ob_get_contents();
        ob_end_clean();

        return $contents;
    }

    function rotate_imagejpeg_str($content, $orientation, $quality=85) {
        $rotated = imagecreatefromstring($content);

        // rotate image, if necessary
        switch ($orientation) {
            case 2:
                imageflip($rotated, IMG_FLIP_HORIZONTAL);
                break;
            case 3:
                $rotated = imagerotate($rotated, 180, 0);
                break;
            case 4:
                imageflip($rotated, IMG_FLIP_VERTICAL);
                break;
            case 5:
                imageflip($rotated, IMG_FLIP_HORIZONTAL);
                $rotated = imagerotate($rotated, 90, 0);
                break;
            case 6:
                $rotated = imagerotate($rotated, -90, 0);
                break;
            case 7:
                $rotated = imagerotate($rotated, 90, 0);
                imageflip($rotated, IMG_FLIP_HORIZONTAL);
                break;
            case 8:
                $rotated = imagerotate($rotated, 90, 0);
                break;
        }

        // buffer and return
        ob_start();
        imagejpeg($rotated, null, $quality); // quality factor is last, 0-100 (low-high)
        $rotated = ob_get_contents();
        ob_end_clean();

        return $rotated;
    }

    function gen_media_path($root, $user_id, $id) {
        return $root . dechex($user_id) . "_" . dechex($id) . ".bin";
    }
    
    function gen_thumb_path($root, $user_id, $id) {
        return $root . dechex($user_id) . "_" . dechex($id) . "T.bin";
    }
?>