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
    wp_register_style("kcw-gallery", plugins_url("kcw-gallery.css", __FILE__), null, "1.1.0");
    wp_register_script("kcw-gallery", plugins_url("kcw-gallery.js", __FILE__), array('jquery'), "1.1.0");
}
add_action("wp_enqueue_scripts", "kcw_gallery_register_dependencies");

function kcw_gallery_enqueue_dependencies() {
    wp_enqueue_style("kcw-gallery");
    wp_enqueue_script("kcw-gallery");
}

function kcw_gallery_BuildGalleryListItem($gallery) {
    $name = $gallery["name"];
    $cat = $gallery["category"];
    $files = $gallery["files"];
    $uid = $gallery["uid"];

    $html = "<li data-id='$uid'>
                <a class='kcw-gallery-list-title'>$name</a>
            </li>\n";
    return $html;
}
function kcw_gallery_BuildGalleryListDisplay() {
    $html = "<div class='kcw-gallery-list-container'>";
    $html .= "<ul class='kcw-gallery-list'>";
    $list = kcw_gallery_api_GetGalleryList();
    for ($i = 0;$i < count($list["items"]);$i++) {
        $html .= kcw_gallery_BuildGalleryListItem($list["items"][$i]);
    }
    $html .= "</ul>";
    $html .= "</div>";
    $html .= kcw_gallery_PutJSData(json_encode($list, true), "list");
    return $html;
}

function kcw_gallery_BuildGalleryThumbnail($image, $baseurl) {
    $html = "";
    $url = $baseurl . $image["name"];
    $html .= "<img src='$url'>";
    $html .= $image["taken"];
    return $html;
}
function kcw_gallery_BuildGalleryDisplay($guid, $gpage) {
    $html = "";
    $data["guid"] = $guid; $data["gpage"] = $gpage;
    $gallery = kcw_gallery_api_GetGalleryPage($data);
    foreach ($gallery["images"] as $image) {
        $html .= kcw_gallery_BuildGalleryThumbnail($image, $gallery["baserurl"]);
    }
    return $html;
}

function kcw_gallery_PutJSData($data, $key) {
    $html = "<script>";
    $html .= "kcw_gallery.$key = $data;";
    $html .= "</script>";
    return $html;
}
function kcw_gallery_SetJSData() {
    global $kcw_gallery_api_url;
    $html = "<script>";
    $html .= "var kcw_gallery = {api_url: '$kcw_gallery_api_url'};";
    $html .= "</script>";
    return $html;
}

function kcw_gallery_GetListHTML() {
    $html = "<div class='kcw-gallery-list-container'>";
    $html .= "<ul class='kcw-gallery-list'></ul>";
    $html .= "</div>";
    return $html;
}
function kcw_gallery_GetGalleryHTML() {
    $html = "<div class='kcw-gallery-display' style='opacity: 0'>";
    $html .= "<a class='kcw-gallery-list-home'>List Home</a>";
    $html .= "<div class='kcw-gallery-title'></div>";
    $html .= "<div class='kcw-gallery-pagination-wrapper'><ul class='kcw-gallery-pagination pagination-top'></ul></div>";
    $html .= "<center><ul class='kcw-gallery-thumbs'></ul></center>";
    $html .= "<div class='kcw-gallery-pagination-wrapper'><ul class='kcw-gallery-pagination pagination-bottom'></ul></div>";
    $html .= "</div>";
    return $html;
}

function kcw_gallery_DoDisplay($guid, $gpage, $lpage) {
    $html = "";

    $html .= kcw_gallery_SetJSData();

    //$html .= kcw_gallery_BuildGalleryListHome();

    if (isset($guid)) {
        $html .= kcw_gallery_GetListHTML();
        if (!isset($gpage)) $gpage = 1;
        $html .= kcw_gallery_BuildGalleryDisplay($guid, $gpage);
    } else {
        $html .= kcw_gallery_BuildGalleryListDisplay();
        $html .= kcw_gallery_GetGalleryHTML();
    }

    return $html;
}

function kcw_gallery_StartBlock() {
    return "<div class='kcw-gallery-wrapper'>\n";
} 
function kcw_gallery_EndBlock() {
    return "</div>";
}
function kcw_gallery_Init() {
    kcw_gallery_enqueue_dependencies();

    $guid = $_GET["guid"];
    $gpage = $_GET["gpage"];
    $lpage = $_GET["lpage"];

    $html = kcw_gallery_StartBlock();

    $html .= kcw_gallery_DoDisplay($guid, $gpage, $lpage);

    $html .= kcw_gallery_EndBlock();
    echo $html;
}
add_shortcode("kcw-gallery", 'kcw_gallery_Init');
?>