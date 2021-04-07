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
    
    if (!kcw_gallery_scaleImage($path, $tpath, $width, $height)) {
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
function kcw_gallery_scaleImage($sourceFile, $destinationFile, $toWidth = null, $toHeight = null, $percent = null, $scaleAxis = IMAGES_SCALE_AXIS_BOTH) {
    $toWidth = (int)$toWidth;
    $toHeight = (int)$toHeight;
    $percent = (int)$percent;
    $result = false;

    if (($toWidth | $toHeight | $percent)
        && file_exists($sourceFile)
        && (file_exists(dirname($destinationFile)) || mkdir(dirname($destinationFile), 0777, true))) {

        $mime = getimagesize($sourceFile);
        $src_img = kcw_gallery_GetSrcImg($mime["mime"], $sourceFile);
        if (!$src_img) return false;

        $original_width = imagesx($src_img);
        $original_height = imagesy($src_img);
        $scale_ratio = kcw_gallery_ComputeScaleRatio($original_width, $original_height, $toWidth, $toHeight, $percent, $scaleAxis);

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

function kcw_gallery_ComputeScaleRatio($width, $height, $toWidth, $toHeight, $percent, $scaleAxis) {
    $scaleAxis = kcw_gallery_DetermineScaleAxis($toWidth, $toHeight, $percent, $scaleAxis);
    $scale_ratio = 1;

    if ($scaleAxis == IMAGES_SCALE_AXIS_X && $toWidth) {
        $scale_ratio = $width / $toWidth;
    } elseif ($scaleAxis == IMAGES_SCALE_AXIS_Y && $toHeight) {
        $scale_ratio = $height / $toHeight;
    } elseif ($percent) {
        $scale_ratio = 100 / $percent;
    } else {
        $scale_ratio_width = $width / $toWidth;
        $scale_ratio_height = $height / $toHeight;

        if ($width / $scale_ratio_width < $toWidth && $height / $scale_ratio_height < $toHeight) {
            $scale_ratio = min($scale_ratio_width, $scale_ratio_height);
        } else {
            $scale_ratio = max($scale_ratio_width, $scale_ratio_height);
        }
    }

    return $scale_ratio;

}

function kcw_gallery_DetermineScaleAxis($width, $height, $percent, $scaleAxis) {
    if ($scaleAxis == IMAGES_SCALE_AXIS_BOTH) {
        if (!($width | $percent)) {
            $scaleAxis = IMAGES_SCALE_AXIS_Y;
        } elseif (!($height | $percent)) {
            $scaleAxis = IMAGES_SCALE_AXIS_X;
        }
    }
    return $scaleAxis;
}

function kcw_gallery_GetSrcImg($path, $mimetype) {
    $src_img = false;
    
    if (in_array($mimetype, ['image/jpg', 'image/jpeg', 'image/pjpeg'])) {
        $src_img = imagecreatefromjpeg($sourceFile);
    } elseif ($mimetype == 'image/png') {
        $src_img = imagecreatefrompng($sourceFile);
    } else if($mimetype == 'image/webp') {
        $src_img = imagecreatefromwebp($sourceFile);
    } else if($mimetype == 'image/gif') {
        $src_img = imagecreatefromgif($sourceFile);
    }

    return $src_img;
}
?>