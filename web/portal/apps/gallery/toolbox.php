<?php
    // helper functions for content scaling & such

    define("THUMB_SIZE", 128); // size of image thumbnails, in px
    define("CIPHER", "aes-128-ctr");
    define("IVLEN", openssl_cipher_iv_length(CIPHER));
    define("CHUNK_COUNT", 5000); // each chunk is N bytes, so memory usage is CHUNK_COUNT * N bytes
    define("BLOCK_SIZE", 10240);
    define("SUPPORTED_MIMES", [
        // source: https://mimetype.io/all-types
        "image/png", // .png
        "image/jpeg", // .jpe, .jpeg, .jpg, .pjpg, .jfif, .jfif-tbnl, .jif
        "image/heic", // .heif, .heic
        "video/mp4", // .mp4, .mp4v, .mpg4
        "video/x-matroska", // .mkv
        "video/quicktime", // .mov, .qt
        "video/x-msvideo" // .avi
    ]);
    define("TABLE_STEM", "gallery__"); // stem for all table names in database
    define("RECYCLE_BIN_NAME", "Recycle Bin"); // name for recycled content destination
    define("MAX_NAME_LEN", 32); // max and min length of album names
    define("MIN_NAME_LEN", 3); // max and min length of album names

    // shorthand to get deletion date timestamp from current time, UTC TIME!!!!!
    function get_deletion_ts($num_days) {
        date_default_timezone_set("America/New_York"); // ensure proper timezone is set
        return date("Y-m-d H:i:s", strtotime($num_days . " days", time()));
    }

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

    // thanks https://www.php.net/manual/en/function.tmpfile.php#120062
    // I want to sleep.
    // - Travis Heavener @ 1:10 AM, 1/15/2024
    function temporaryFile($name, $content) {
        $file = DIRECTORY_SEPARATOR .
                trim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) .
                DIRECTORY_SEPARATOR .
                ltrim($name, DIRECTORY_SEPARATOR);

        file_put_contents($file, $content);

        register_shutdown_function(function() use($file) {
            unlink($file);
        });

        return $file;
    }

    function resize_video($path, $width, $height) {
        // create a temp file
        $tmp_path = pathinfo($path)["filename"] . ".jpg";

        // crop the video down
        exec("ffmpeg -ss 00:00:00.00 -i \"$path\" -vf crop='min(in_w,in_h)':'min(in_w,in_h)' -vf scale=$width:$height -vframes 1 \"$tmp_path\"");

        // return the content from the file
        $contents = file_get_contents($tmp_path);
        unlink($tmp_path);
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

    function content_encrypt($content_str, $dest, $key, $iv) {
        // buffer the content from the file to reduce memory footprint
        $success = false;
        $in_handle = fopen("php://memory", "r+"); // create a handle to make managing the pointer and reading easier
        fwrite($in_handle, $content_str);
        rewind($in_handle);

        if ($out_handle = fopen($dest, "wb")) {
            // print IV
            fwrite($out_handle, $iv);

            // encrypt and print the rest of the data
            while (!feof($in_handle)) {
                // encrypt and output
                $data = fread($in_handle, BLOCK_SIZE * CHUNK_COUNT);
                $ciphertext = openssl_encrypt($data, CIPHER, $key, OPENSSL_RAW_DATA, $iv);
                fwrite($out_handle, $ciphertext);
            }

            fclose($in_handle);
            fclose($out_handle); // regardless, close the output stream
        }

        return $success;
    }
    
    function content_decrypt($in_file, $key) {
        // buffer the content from the file to reduce memory footprint
        $output = false;
        $out_file = fopen("php://memory", "wb");

        if ($in_handle = fopen($in_file, "rb")) {
            $output = "";
            $iv = fread($in_handle, IVLEN); // get IV

            $data = null;
            while (!feof($in_handle)) {
                // encrypt and output
                $data = fread($in_handle, BLOCK_SIZE * CHUNK_COUNT);
                $new = openssl_decrypt($data, CIPHER, $key, OPENSSL_RAW_DATA, $iv);
                fwrite($out_file, $new);
            }

            fclose($in_handle);
            rewind($out_file);
            $output = stream_get_contents($out_file);
            fclose($out_file);
        }

        return $output;
    }

?>