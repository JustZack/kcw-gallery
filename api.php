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

function kcw_gallery_api_GenerateThumbnailsIfNeeded($gallery, $images) {
    global $kcw_gallery_thumbnail_width;
    global $kcw_gallery_thumbnail_height;
    //Be sure all the thumbnails for this page exist
    foreach ($images as $img) {
        $imgfile = $gallery["basedir"] . $img["name"];
        $pinf = pathinfo($imgfile);
        $thumbfile = $gallery["thumbsdir"] . $pinf["filename"] . ".jpg";

        $should_create_thumb = kcw_gallery_ShouldGenerateThumbnail($thumbfile, $kcw_gallery_thumbnail_width, $kcw_gallery_thumbnail_height);
        if ($should_create_thumb) $folder = kcw_gallery_generate_thumb($imgfile, $kcw_gallery_thumbnail_width, $kcw_gallery_thumbnail_height);
    }
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
    
    kcw_gallery_api_GenerateThumbnailsIfNeeded($gallery, $gallery_page["images"]);

    return kcw_gallery_api_Success($gallery_page);
}
//Filter bad meaningless characters out of a search string
function kcw_gallery_api_FilterString($search) {
    $search = preg_replace("/(%20|\+|\s)/", " ", $search);
    $search = preg_replace("/[^A-Za-z0-9\s]/", "", $search);
    $search = strtolower($search);
    return $search;
}
//Check if two strings contain eachother
function kcw_gallery_api_StringsMatch($a, $b) {
    //Both zero length = match
    if (!strlen($a) && !strlen($b)) return true;
    //Exactly one zero length = no match
    else if (!strlen($a) xor !strlen($b)) return false;
    //Either one contains the other = match
    return strpos($a, $b) > -1 || strpos($b, $a) > -1;
}
//Compute how similar the search array is to the possible match array
function kcw_gallery_api_ComputeLikeness($search, $possible_match) {
    //Convert string to array of strings
    $search = explode(" ", $search); $possible_match = explode(" ", $possible_match);
    //Keep track of matches
    $total_matches = 0;
    //Iterate over all parts and track likeness
    foreach ($search as $spart)
        foreach ($possible_match as $pmpart)
            if (kcw_movies_api_StringsMatch($spart, $pmpart)) 
                { $total_matches++; break; }
    //Return # of matches / # of search words
    return $total_matches / (1.0*count($search));
}
//Check if either the search or possible match are similar
function kcw_gallery_api_SearchMatches($search, $possible_match) {
    //Search contains video title OR Video title contains search 
    if (kcw_gallery_api_StringsMatch($search, $possible_match)) return true;
    //compare likeness of the two arrays and ensure it is >= 33.3%
    else return kcw_gallery_api_ComputeLikeness($search, $possible_match) >= .333;
}
//Return any galleries matching the given search string
function kcw_gallery_api_Search($string) {
    $list = kcw_gallery_GetListData();
    if (isset($string) && strlen($string) > 0) {
        $filtered = kcw_gallery_api_FilterString($string);
        $search_list = array();
        foreach ($list as $item)
            if (kcw_gallery_api_SearchMatches($filtered, kcw_gallery_api_FilterString($item["friendly_name"])))
                $search_list[] = $item;
        $list = $search_list;
    }
    return $list;
}
//Return any galleries matching the given search string
function kcw_gallery_api_GetSearch($data) {
    $data["lpage"] = 1;
    return kcw_gallery_api_GetSearchPage($data);
}
//Return any galleries matching the given search string
function kcw_gallery_api_GetSearchPage($data) {
    $lpage = (int)$data["lpage"];
    $list = kcw_gallery_api_Search($data["lsearch"]);
    $list_page = kcw_gallery_api_Page($list, $lpage, 40, "items");
    $list_page["search"] = ($data["lsearch"]);
    return kcw_gallery_api_Success($list_page);
}

function kcw_gallery_RegisterListRoutes() {
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
}
function kcw_gallery_RegisterSearchRoutes() {
    global $kcw_gallery_api_namespace;
    //Route for /list/search-string
    register_rest_route( "$kcw_gallery_api_namespace/v1", '/search/(?P<lsearch>[^/]+)', array(
        'methods' => 'GET',
        'callback' => 'kcw_gallery_api_GetSearch',
    ));
    //Route for /search/search-string/page
    register_rest_route( "$kcw_gallery_api_namespace/v1", '/search/(?P<lsearch>[^/]+)/(?P<lpage>\d+)', array(
        'methods' => 'GET',
        'callback' => 'kcw_gallery_api_GetSearchPage',
    ));
}
function kcw_gallery_RegisterGalleryRoutes() {
    global $kcw_gallery_api_namespace;
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
//Register all the API routes
function kcw_gallery_api_RegisterRestRoutes() {
    kcw_gallery_RegisterListRoutes();
    kcw_gallery_RegisterSearchRoutes();
    kcw_gallery_RegisterGalleryRoutes();
}

add_action( 'rest_api_init', "kcw_gallery_api_RegisterRestRoutes");

?>