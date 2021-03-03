<?php
/*
* Plugin Name:       KCW Gallery
* Description:       Provide a home for all KCW image uploads
* Version:           1.0.0
* Requires at least: 5.2
* Requires PHP:      7.2
* Author:            Zack Jones
*/

include_once "api.php";

function  kcw_gallery_register_dependencies() {
    wp_register_style("kcw-gallery", plugins_url("kcw-gallery.css", __FILE__), null, "1.0.0");
}
add_action("wp_enqueue_scripts", "kcw_gallery_register_dependencies");

function kcw_gallery_enqueue_dependencies() {
    wp_enqueue_style("kcw-gallery");
}

function kcw_gallery_BuildGalleryListItem($gallery) {
    $name = $gallery["name"];
    $cat = $gallery["category"];
    $files = $gallery["files"];
    $uid = $gallery["uid"];
    $html = "<tr data-id='$uid'>
    <td class='kcw-gallery-list-title'>$name</td>
    <td class='kcw-gallery-list-category'>$cat</td>
    <td class='kcw-gallery-list-files'>$files</td>
</tr>\n";
    return $html;
}
function kcw_gallery_BuildGalleryList() {
    $html = "<table class='kcw-gallery-list'>";
    $data = kcw_gallery_api_GetGalleryList();
    for ($i = 0;$i < count($data);$i++) {

        $html .= kcw_gallery_BuildGalleryListItem($data[$i], $i);
    }
    $html .= "</table>";

    return $html;
}

function kcw_gallery_BuildGalleryDisplay($guid, $gpage) {
    $data["guid"] = $guid; $data["gpage"] = $gpage;
    $gallery = kcw_gallery_api_GetGalleryPage($data);
    
}
function kcw_gallery_GetHTML() {

}
function kcw_gallery_StartBlock() {
    return "<div class='kcw-gallery-wrapper'>\n";
} 
function kcw_gallery_EndBlock() {
    return "</div>";
}
function kcw_gallery_Init() {
    kcw_gallery_enqueue_dependencies();

    $html = kcw_gallery_StartBlock();
    $guid = $_GET["guid"];
    $gpage = $_GET["gpage"];
    if (isset($guid)) {
        if (isset($gpage)) $html .= kcw_gallery_BuildGalleryDisplay($guid, $gpage);
        else $html .= kcw_gallery_BuildGalleryDisplay($guid, 1);
    } else {
        $html .= kcw_gallery_BuildGalleryList();
    }
    $html .= kcw_gallery_EndBlock();
    echo $html;
}
add_shortcode("kcw-gallery", 'kcw_gallery_Init');
?>