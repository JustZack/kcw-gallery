<?php
/*
* Plugin Name:       KCW Gallery
* Description:       Provide a home for all KCW image uploads
* Version:           1.0.5
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
    $name = $gallery["friendly_name"];
    $cat = $gallery["category"];
    $files = $gallery["files"];
    $uid = $gallery["uid"];

    $html = "<li data-id='$uid'>
                <a class='kcw-gallery-list-title'>$name</a>
                <span><span>$files</span><span class='dashicons dashicons-images-alt2'></span></span>
            </li>\n";
    return $html;
}
function kcw_gallery_BuildGalleryListDisplay($lpage) {
    $data = array(); $data["lpage"] = $lpage;
    $list = kcw_gallery_api_GetGalleryListPage($data);
    for ($i = 0;$i < count($list["items"]);$i++) {
        $html .= kcw_gallery_BuildGalleryListItem($list["items"][$i]);
    }

    $list["pages"] = array();
    $list["pages"][$list["page"]-1] = $list["items"];
    $list["current"] = $list["page"];
    unset($list["items"]);
    unset($list["start"]);
    unset($list["end"]);
    unset($list["page"]);
    $after = kcw_gallery_PutJSData(json_encode($list), "list");
    return kcw_gallery_GetListHTML($html, $after);
}

function kcw_gallery_BuildGalleryThumbnail($image, $baseurl, $thumburl) {
    $url = $baseurl . $image["name"];
    $fname = pathinfo($image["name"])["filename"];
    $turl = $thumburl . $fname . ".jpg";
    
    $html = "<li><a data-src='$url'>";
    $html .= "<img src='$turl'>";
    $html .= "</a></li>";

    return $html;
}
function kcw_gallery_BuildGalleryDisplay($guid, $gpage) {
    $html = "";

    $data["guid"] = $guid; $data["gpage"] = $gpage;
    $gallery = kcw_gallery_api_GetGalleryPage($data);
    $base = $gallery["baseurl"]; $thumbs = $gallery["thumbsurl"];
    foreach ($gallery["images"] as $image)
        $html .= kcw_gallery_BuildGalleryThumbnail($image, $base, $thumbs);

    $gallery["pages"] = array();
    $gallery["pages"][$gallery["page"] - 1] = $gallery["images"];
    $gallery["current"] = $gallery["page"];
    unset($gallery["images"]);
    unset($gallery["start"]);
    unset($gallery["end"]);
    unset($gallery["page"]);
    $after = kcw_gallery_PutJSData(json_encode($gallery), "gallery");

    $title = $gallery["name"];

    return kcw_gallery_GetGalleryHTML($title, $html, $after);
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

function kcw_gallery_GetListHTML($list_html = null, $after = null) {
    $html = "<div class='kcw-gallery-list-container' style='%s'>";
    $html .= "<ul class='kcw-gallery-list'>%s</ul>";
    $html .= "</div>";
    $html .= "%s";

    if ($list_html != null && $after != null) return sprintf($html, "opacity: 1;", $list_html, $after);
    else                                    return sprintf($html, "opacity: 0;", "", "");
}
function kcw_gallery_GetGalleryHTML($title = null, $gallery_list_html = null, $after = null) {
    $html = "<div class='kcw-gallery-display' style='%s'>";
    $html .= "<a class='kcw-gallery-list-home'>List Home</a>";
    $html .= "<div class='kcw-gallery-title'>%s</div>";
    $html .= "<div class='kcw-gallery-pagination-wrapper'><ul class='kcw-gallery-pagination pagination-top'></ul></div>";
    $html .= "<center><ul class='kcw-gallery-thumbs'>%s</ul></center>";
    $html .= "<div class='kcw-gallery-pagination-wrapper'><ul class='kcw-gallery-pagination pagination-bottom'></ul></div>";
    $html .= "</div>%s";
    if ($title != null && $gallery_list_html != null && $after != null)
        return sprintf($html, "", $title, $gallery_list_html, $after);
    else
        return sprintf($html, "opacity: 0;", "", "", "");
}

function kcw_gallery_DoDisplay($guid, $gpage, $lpage) {
    $html = "";
    $html .= kcw_gallery_SetJSData();

    if (isset($guid)) {
        if (!isset($gpage)) $gpage = 1;
        $html .= kcw_gallery_GetListHTML();
        $html .= kcw_gallery_BuildGalleryDisplay($guid, $gpage);
    } else {
        if (!isset($lpage)) $lpage = 1;
        $html .= kcw_gallery_BuildGalleryListDisplay($lpage);
        $html .= kcw_gallery_GetGalleryHTML();
    }

    return $html;
}

function kcw_gallery_GetLoadingGif() {
    $url = plugins_url("loading.gif", __FILE__);
    return sprintf("<img src='%s' class='kcw-gallery-loading' style='top: -999px;left: -999px;opacity: 0;'>", $url);
}

function kcw_gallery_StartBlock() {
    return "<div class='kcw-gallery-wrapper'>\n";
} 
function kcw_gallery_EndBlock() {
    return "</div>";
}
function kcw_gallery_new_Init() {
    kcw_gallery_enqueue_dependencies();

    $guid = $_GET["guid"];
    $gpage = $_GET["gpage"];
    $lpage = $_GET["lpage"];

    $html = kcw_gallery_StartBlock();

    $html .= kcw_gallery_DoDisplay($guid, $gpage, $lpage);
    $html .= kcw_gallery_GetLoadingGif();
    $html .= kcw_gallery_EndBlock();
    echo $html;
}
add_shortcode("kcw-gallery", 'kcw_gallery_new_Init');
?>