<?php
function kcw_gallery_generate_thumb($path, $width, $height) {
    $pinf = pathinfo($path);
    $name = $pinf["basename"];
    $filename = $pinf["filename"];
    $ext = $pinf["extension"];
    $base = $pinf["dirname"];
    //The thumbnail path
    $tbase = $base . "/thumbs/";
    //Save all thumbnails as JPG - its much smaller
    $tpath = $tbase . $filename . '.jpg';
    //Generate the right thumbnail
    //var_dump(scaleImage($path, $tpath, $width));
    
    if (!scaleImage($path, $tpath, $width, $height)) {
        die("Couldnt make thumbnail: " . $path . " [" . file_exists(dirname($tpath)) . "]");
    }
    return $tpath;
}

//STOLEN FROM: https://stackoverflow.com/a/57109100/10100947
    /** Use X axis to scale image. */
define('IMAGES_SCALE_AXIS_X', 1);
/** Use Y axis to scale image. */
define('IMAGES_SCALE_AXIS_Y', 2);
/** Use both X and Y axes to calc image scale. */
define('IMAGES_SCALE_AXIS_BOTH', IMAGES_SCALE_AXIS_X ^ IMAGES_SCALE_AXIS_Y);
/** Compression rate for JPEG image format. */
define('JPEG_COMPRESSION_QUALITY', 65);
/** Compression rate for PNG image format. */
define('PNG_COMPRESSION_QUALITY', 6);

/**
 * Scales an image with save aspect ration for X, Y or both axes.
 *
 * @param string $sourceFile Absolute path to source image.
 * @param string $destinationFile Absolute path to scaled image.
 * @param int|null $toWidth Maximum `width` of scaled image.
 * @param int|null $toHeight Maximum `height` of scaled image.
 * @param int|null $percent Percent of scale of the source image's size.
 * @param int $scaleAxis Determines how of axis will be used to scale image.
 *
 * May take a value of {@link IMAGES_SCALE_AXIS_X}, {@link IMAGES_SCALE_AXIS_Y} or {@link IMAGES_SCALE_AXIS_BOTH}.
 * @return bool True on success or False on failure.
 */
function scaleImage($sourceFile, $destinationFile, $toWidth = null, $toHeight = null, $percent = null, $scaleAxis = IMAGES_SCALE_AXIS_BOTH) {
    $toWidth = (int)$toWidth;
    $toHeight = (int)$toHeight;
    $percent = (int)$percent;
    $result = false;

    if (($toWidth | $toHeight | $percent)
        && file_exists($sourceFile)
        && (file_exists(dirname($destinationFile)) || mkdir(dirname($destinationFile), 0777, true))) {

        $mime = getimagesize($sourceFile);

        $mytype = $mime["mime"];
        if (in_array($mytype, ['image/jpg', 'image/jpeg', 'image/pjpeg'])) {
            $src_img = imagecreatefromjpeg($sourceFile);
        } elseif ($mytype == 'image/png') {
            $src_img = imagecreatefrompng($sourceFile);
        } else if($mytype == 'image/webp') {
            $src_img = imagecreatefromwebp($sourceFile);
        } else if($mytype == 'image/gif') {
            $src_img = imagecreatefromgif($sourceFile);
        } else {
            return false;
        }

        $original_width = imagesx($src_img);
        $original_height = imagesy($src_img);

        if ($scaleAxis == IMAGES_SCALE_AXIS_BOTH) {
            if (!($toWidth | $percent)) {
                $scaleAxis = IMAGES_SCALE_AXIS_Y;
            } elseif (!($toHeight | $percent)) {
                $scaleAxis = IMAGES_SCALE_AXIS_X;
            }
        }

        if ($scaleAxis == IMAGES_SCALE_AXIS_X && $toWidth) {
            $scale_ratio = $original_width / $toWidth;
        } elseif ($scaleAxis == IMAGES_SCALE_AXIS_Y && $toHeight) {
            $scale_ratio = $original_height / $toHeight;
        } elseif ($percent) {
            $scale_ratio = 100 / $percent;
        } else {
            $scale_ratio_width = $original_width / $toWidth;
            $scale_ratio_height = $original_height / $toHeight;

            if ($original_width / $scale_ratio_width < $toWidth && $original_height / $scale_ratio_height < $toHeight) {
                $scale_ratio = min($scale_ratio_width, $scale_ratio_height);
            } else {
                $scale_ratio = max($scale_ratio_width, $scale_ratio_height);
            }
        }

        $scale_width = $original_width / $scale_ratio;
        $scale_height = $original_height / $scale_ratio;

        $dst_img = imagecreatetruecolor($scale_width, $scale_height);

        imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $scale_width, $scale_height, $original_width, $original_height);

        //Added: Only save files as jpeg, they are smaller
        $result = imagejpeg($dst_img, $destinationFile, JPEG_COMPRESSION_QUALITY);

        imagedestroy($dst_img);
        imagedestroy($src_img);
    }

    return $result;
}
?>