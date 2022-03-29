<?php
/*
* Plugin Name:       KCW Gallery
* Description:       Provide a home for all KCW image uploads
* Version:           1.2.9
* Requires at least: 5.2
* Requires PHP:      7.2
* Author:            Zack Jones
*/

include_once "api.php";

function  kcw_gallery_register_dependencies() {
    wp_register_style("kcw-gallery", plugins_url("kcw-gallery.css", __FILE__), null, "1.4.6");
    wp_register_script("kcw-gallery", plugins_url("kcw-gallery.js", __FILE__), array('jquery'), "1.4.6");
}
add_action("wp_enqueue_scripts", "kcw_gallery_register_dependencies");

function kcw_gallery_enqueue_dependencies() {
    wp_enqueue_style("kcw-gallery");
    wp_enqueue_script("kcw-gallery");
}

function kcw_gallery_BuildGalleryListItem($gallery) {
    $name = $gallery["nice_name"];
    
    $cat = $gallery["category"];
    $files = $gallery["files"];
    $uid = $gallery["uid"];

    $html = "<li data-id='$uid'>
                <div class='kcw-gallery-list-name-wrapper'>
                    <a class='kcw-gallery-list-title'>$name</a>
                    %s
                </div>
                <span class='dashicons dashicons-images-alt2'></span>
                <span>$files</span>
            </li>\n";
    if ($cat != NULL) $html = sprintf($html, "<a class='kcw-gallery-list-category'>".$gallery["nice_category"]."</a>");
    else $html = sprintf($html, "");
    return $html;
}

function kcw_gallery_GetList($lpage, $lsearch) {
    $data = array(); 
    $data["lpage"] = $lpage;
    $list = NULL;

    if (isset($lsearch)) {
        $data["lsearch"] = $lsearch;
        $list = kcw_gallery_api_GetSearchPage($data);
    } else {
        $list = kcw_gallery_api_GetGalleryListPage($data);
    }

    return $list;
}

function kcw_gallery_GetListDisplayHtml($list) {
    $html = "";
    
    for ($i = 0;$i < count($list["items"]);$i++) {
        if ($list["items"][$i]["visibility"] == "visible")
            $html .= kcw_gallery_BuildGalleryListItem($list["items"][$i]);
        else
            continue;
    }

    return $html;
}

function kcw_gallery_BuildGalleryListDisplay($lpage, $lsearch) {
    $list = kcw_gallery_GetList($lpage, $lsearch);

    $html = kcw_gallery_GetListDisplayHtml($list);

    $list["pages"] = array();
    $list["pages"][$list["page"]-1] = $list["items"];
    $list["current"] = $list["page"];
    
    unset($list["items"]);unset($list["page"]);
    unset($list["start"]);unset($list["end"]);
    
    $after = kcw_gallery_PutJSData(json_encode($list), "list");
    return kcw_gallery_GetListHTML($html, $after);
}

function kcw_gallery_BuildGalleryThumbnail($image, $baseurl, $thumburl) {
    $type = $image["type"];
    if ($type == "img") {
        $url = str_replace("{0}", $image["name"], $baseurl);
        $pathinf = pathinfo($image["name"]);
        $fname = $pathinf["filename"];
        $path = $pathinf["dirname"] . "/";
        $turl = str_replace("{0}", $path . $fname . ".jpg", $thumburl);
    } else if ($image["type"] == "iframe") {
        $url = $image["name"];
        $turl = $image["thumb"];
    }
    $html = "<li><a data-type='" . $image["type"] . "' data-src='$url' data-permalink='" . $image["permalink"] . "'>";
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

    $title = $gallery["friendly_name"];
    $permalink = $gallery["permalink"];

    return kcw_gallery_GetGalleryHTML($title, $permalink, $html, $after);
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

function kcw_gallery_GetSearchHTML($search) {
    $html = "<div class='kcw-gallery-search'>";
    $html .= "<input type='text' aria-label='search' name='kcw-gallery-search' value='%s' placeholder='Search'>";
    $html .= "<span class='dashicons dashicons-search'></span></div>";
    return sprintf($html, $search);
}
function kcw_gallery_GetListHTML($list_html = null, $after = null) {
    $html = "<div class='kcw-gallery-list-container' style='%s'>";
    $html .= "<h3 class='kcw-gallery-list-message' style='display: none;'></h3>";
    $html .= "<ul class='kcw-gallery-list'>%s</ul>";
    $html .= "</div>";
    $html .= "%s";

    if ($list_html != null && $after != null) return sprintf($html, "opacity: 0;", $list_html, $after);
    else                                    return sprintf($html, "opacity: 0;", "", "");
}
function kcw_gallery_GetGalleryHTML($title = null, $permalink = null, $gallery_list_html = null, $after = null) {
    $html = "<div class='kcw-gallery-display' style='%s'>";
    $html .= "<a class='kcw-gallery-list-home'>";
    $html .= "<span class='dashicons dashicons-undo'></span>";
    $html .= "<span class='kcw-gallery-list-home-name'>List</span>";
    $html .= "</a>";
    $html .= "<a class='kcw-gallery-title-link' href='%s'><div class='kcw-gallery-title'>%s</div></a>";
    $html .= "<center><ul class='kcw-gallery-thumbs'>%s</ul></center>";
    $html .= "</div>%s";
    if ($title != null && $gallery_list_html != null && $after != null)
        return sprintf($html, "opacity: 0;", $permalink, $title, $gallery_list_html, $after);
    else
        return sprintf($html, "opacity: 0;", "", "", "", "");
}

function kcw_gallery_DoDisplay($guid, $gpage, $lpage, $lsearch) {
    $html = "";
    $html .= kcw_gallery_SetJSData();
    $html .= kcw_gallery_GetSearchHTML($lsearch);
    $html .= "<div class='kcw-gallery-pagination-wrapper'><ul class='kcw-gallery-pagination pagination-top'></ul></div>";
    if (isset($guid)) {
        $html .= kcw_gallery_GetListHTML();
        $html .= kcw_gallery_BuildGalleryDisplay($guid, $gpage);
    } else {
        $html .= kcw_gallery_BuildGalleryListDisplay($lpage, $lsearch);
        $html .= kcw_gallery_GetGalleryHTML();
    }
    $html .= "<div class='kcw-gallery-pagination-wrapper'><ul class='kcw-gallery-pagination pagination-bottom'></ul></div>";
    return $html;
}

function kcw_gallery_GetLoadingBox() {
    $url = plugins_url("loading.gif", __FILE__);
    $html = "<div class='kcw-gallery-loading-wrapper' style='top: -999px;left: -999px;opacity: 0'>";
    $html .= "<img src='%s' class='kcw-gallery-loading'>";
    $html .= "<center><p class='kcw-gallery-loading-status'></p></center>";
    $html .= "</div>";

    return sprintf($html, $url);
}

function kcw_gallery_GetLightbox() {
    $html = "<div class='kcw-gallery-lightbox-background'></div>";
    $html .= "<div class='kcw-gallery-lightbox-wrapper' style='top: -999px;left: -999px;opacity: 0'>";
    $html .= "<div class='kcw-gallery-lightbox-content'></div>";
    $html .= "<div class='kcw-gallery-lightbox-buttons'>";
    $html .= "<span><a class='kcw-gallery-lightbox-full-res' href=''>Full Size<span class='dashicons dashicons-external'></span></a></span>";
    $html .= "<span><a class='kcw-gallery-lightbox-embed'>Embed<span class='dashicons dashicons-shortcode'></span></a></span>";
    $html .= "<span><a class='kcw-gallery-lightbox-permalink' href=''>Original Post<span class='dashicons dashicons-format-chat'></span></a></span></div>";
    $html .= "</div>";
    return $html;
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
    $lsearch = $_GET["lsearch"];
    
    $gpage = $_GET["gpage"];
    if (!isset($gpage)) $gpage = 1;
    $lpage = $_GET["lpage"];
    if (!isset($lpage)) $lpage = 1;

    $html = kcw_gallery_StartBlock();

    $html .= kcw_gallery_DoDisplay($guid, $gpage, $lpage, $lsearch);
    $html .= kcw_gallery_GetLoadingBox();
    $html .= kcw_gallery_GetLightbox();

    $html .= kcw_gallery_EndBlock();
    echo $html;
}
add_shortcode("kcw-gallery", 'kcw_gallery_new_Init');
?>