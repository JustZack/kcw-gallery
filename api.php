<?php

include_once "data-helpers.php";
include_once "img-helpers.php";

$kcw_gallery_api_namespace = "kcwgallery";
$kcw_gallery_api_url = home_url('wp-json/' . $kcw_gallery_api_namespace . '/v1/');
//$kcw_gallery_api_url = "https://kustomcoachwerks.com/wp-json/kcwgallery/v1/";

$kcw_gallery_thumbnail_width = 130;
$kcw_gallery_thumbnail_height = 130;

//Api request ran into error
function kcw_gallery_api_Error($msg) {
    $data = array();
    $data["message"] = $msg;
    $data["status"] = "Error";
    return $data;
}
//Api request succeeded!
function kcw_gallery_api_Success($data) {
    $data["status"] = "Success";
    $data["time"] = time();
    return $data;
}

//Return a page of the given data
function kcw_gallery_api_Page($fulldata, $page, $per_page, $data_key) {
    $data = array();

    $total = count($fulldata);
    $data["total"] = $total;
    $data["page"] = $page;
    $data["per_page"] = $per_page;

    $data[$data_key] = array();

    $data["start"] = 0;
    $data["end"] = 0;
    if ($page < 1) return $data;

    $start = ($page - 1) * $per_page; $end = 0;
    if ($start >= $total) {
        $start = $total;
        $end = $start;
    } else {
        $end = $start + $per_page;
        if ($end > $total)
            $end = $total;
        $end--;

        for ($i = $start;$i <= $end;$i++) $data[$data_key][] = $fulldata[$i];
    }

    $data["start"] = $start;
    $data["end"] = $end;

    return $data;
}

//Return the gallery list with an assumed first page
function kcw_gallery_api_GetGalleryList() {
    $data = array();
    $data["lpage"] = 1;
    return kcw_gallery_api_GetGalleryListPage($data);
}
//Return the gallery list given the page
function kcw_gallery_api_GetGalleryListPage($data) {
    $list = kcw_gallery_GetListData();
    $lpage = (int)$data["lpage"];
    $list_page = kcw_gallery_api_Page($list, $lpage, 40, "items");
    return kcw_gallery_api_Success($list_page);
}

//Return meta data about a given gallery
function kcw_gallery_api_GetGalleryMeta($data) {
    $meta = kcw_gallery_GetGalleryData($data["guid"]);
    //Trade the entire array of images for a small bit of meta data
    $meta["images"] = count($meta["images"]);
    //Get rid of information that should not be included in the response
    unset($meta["basedir"]); unset($meta["thumbsdir"]);
    return kcw_gallery_api_Success($meta);
}

//Return the given gallery, with an assumed first page
function kcw_gallery_api_GetGallery($data) {
    $data['gpage'] = 1;
    return kcw_gallery_api_GetGalleryPage($data); 
}
//Return the given page of the given gallery id
function kcw_gallery_api_GetGalleryPage($data) {
    $guid = $data['guid'];
    $gpage = (int)$data['gpage'];
    $gallery = kcw_gallery_GetGalleryData($guid);
    global $kcw_gallery_thumbnail_width;
    global $kcw_gallery_thumbnail_height;

    if ($gallery == NULL) return kcw_gallery_api_Error("Unrecognized Gallery UID: " . $guid . ", with Page: " . $gpage);
    //Get the right page
    $gallery_page = kcw_gallery_api_Page($gallery["images"], $gpage, 42, "images");
    //Build the response
    $gallery_page["uid"] = $guid;
    $gallery_page["name"] = $gallery["name"];
    $gallery_page["category"] = $gallery["category"];
    $gallery_page["friendly_name"] = $gallery["friendly_name"];
    $gallery_page["visibility"] = $gallery["visibility"];
    $gallery_page["baseurl"] = $gallery["baseurl"];
    $gallery_page["thumbsurl"] = $gallery["thumbsurl"];
    
    //Be sure all the thumbnails for this page exist
    foreach ($gallery_page["images"] as $img) {
        $imgfile = $gallery["basedir"] . $img["name"];
        $pinf = pathinfo($imgfile);
        $thumbfile = $gallery["thumbsdir"] . $pinf["filename"] . ".jpg";
        $create_thumb = false;
        //If the file exists,
        if (file_exists($thumbfile)) {
            $size = getimagesize($thumbfile);
            if ($size != false && ($size[0] != $kcw_gallery_thumbnail_width && $size[1] != $kcw_gallery_thumbnail_height)) {
                unlink($thumbfile);
                $create_thumb = true;
            }
        } else {
            $create_thumb = true;
        }

        if ($create_thumb) $folder = kcw_gallery_generate_thumb($imgfile, $kcw_gallery_thumbnail_width, $kcw_gallery_thumbnail_height);
    }

    return kcw_gallery_api_Success($gallery_page);
}

//Filter bad meaningless characters out of a search string
function kcw_gallery_api_FilterString($search) {
    $search = preg_replace("/\%20/", ' ', $search);
    $search = preg_replace("/[^A-Za-z0-9]+/", ' ', $search);
    $search = strtolower($search);
    return $search;
}
//Check if two strings contain eachother
function kcw_gallery_api_SearchMatches($search, $possible_match) {
    return strpos($search, $possible_match) > -1 || strpos($possible_match, $search) > -1;
}
//Return any galleries matching the given search string
function kcw_gallery_Search($string) {
    $list = kcw_gallery_GetListData();
    $string = kcw_gallery_api_FilterString($string);
    $search_list = array();
    foreach ($list as $item) {
        $name = kcw_gallery_api_FilterString($item["friendly_name"]);
        if (kcw_gallery_api_SearchMatches($string, $name)) {
            $search_list[] = $item;
            continue;
        }
        //Break up the current gallery name based on its spaces
        //And check if the search string matches any of those
        /*$name = explode(' ', $name);
        $search_arr = explode(' ', $string);
        //foreach ($search_arr as $search_part) {
            foreach ($name as $part) {
                if (kcw_gallery_api_SearchMatches($string, $part)) {
                    $search_list[] = $item;
                    $fullbreak = true;
                    break 1;
                }
            }*/
        //}
    }
    return $search_list;
}
//Return any galleries matching the given search string
function kcw_gallery_api_GetSearch($data) {
    $data["lpage"] = 1;
    return kcw_gallery_api_GetSearchPage($data);
}
//Return any galleries matching the given search string
function kcw_gallery_api_GetSearchPage($data) {
    $lpage = (int)$data["lpage"];
    $list = kcw_gallery_Search($data["lsearch"]);
    $list_page = kcw_gallery_api_Page($list, $lpage, 40, "items");
    $list_page["search"] = ($data["lsearch"]);
    return kcw_gallery_api_Success($list_page);
}

//Register all the API routes
function kcw_gallery_api_RegisterRestRoutes() {
    global $kcw_gallery_api_namespace;
    //Route for /list
    register_rest_route( "$kcw_gallery_api_namespace/v1", '/list', array(
        'methods' => 'GET',
        'callback' => 'kcw_gallery_api_GetGalleryList',
    ));
    //Route for /list/page
    register_rest_route( "$kcw_gallery_api_namespace/v1", '/list/(?P<lpage>\d+)', array(
        'methods' => 'GET',
        'callback' => 'kcw_gallery_api_GetGalleryListPage',
    ));
    //Route for /list/search-string
    register_rest_route( "$kcw_gallery_api_namespace/v1", '/list/(?P<lsearch>[^/]+)', array(
        'methods' => 'GET',
        'callback' => 'kcw_gallery_api_GetSearch',
    ));
    //Route for /list/search-string/page
    register_rest_route( "$kcw_gallery_api_namespace/v1", '/list/(?P<lsearch>[^/]+)/(?P<lpage>\d+)', array(
        'methods' => 'GET',
        'callback' => 'kcw_gallery_api_GetSearchPage',
    ));
    //Route for /gallery-id
    register_rest_route( "$kcw_gallery_api_namespace/v1", '/(?P<guid>[a-zA-Z0-9-\.\(\)_h]+)', array(
        'methods' => 'GET',
        'callback' => 'kcw_gallery_api_GetGallery',
    ));
    //Route for /gallery-id/meta
    register_rest_route( "$kcw_gallery_api_namespace/v1", '/(?P<guid>[a-zA-Z0-9-\.\(\)_h]+)/meta', array(
        'methods' => 'GET',
        'callback' => 'kcw_gallery_api_GetGalleryMeta',
    ));
    //Route for /gallery-id/page
    register_rest_route("$kcw_gallery_api_namespace/v1", '/(?P<guid>[a-zA-Z0-9-\.\(\)_h ]+)/(?P<gpage>\d+)', array(
        'methods' => 'GET',
        'callback' => 'kcw_gallery_api_GetGalleryPage',
    ));
}
add_action( 'rest_api_init', "kcw_gallery_api_RegisterRestRoutes");

?>