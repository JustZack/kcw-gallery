<?php
/*
* Plugin Name:       KCW Gallery
* Description:       Provide a home for all KCW image uploads
* Version:           1.0.0
* Requires at least: 5.2
* Requires PHP:      7.2
* Author:            Zack Jones
*/

include_once "old-gallery-helpers.php";

function kcw_gallery_GetHTML() {

}
function kcw_gallery_Init() {
    $folderdata = kcw_gallery_GetFolderData(KCW_OLD_GALLERY_ROOT);
    $gallerydata = kcw_gallery_GetGalleryData($folderdata);
    var_dump($gallerydata);
}
add_shortcode("kcw-gallery", 'kcw_gallery_Init');
?>