<?php

include_once "data-helpers.php";

$kcw_gallery_api_namespace = "kcwgallery";
$kcw_gallery_api_url = home_url('wp-json/' . $kcw_gallery_api_namespace . '/v1/');

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
//Return the gallery list
function kcw_gallery_api_GetGalleryList() {
    $list = kcw_gallery_GetListData();
    return kcw_gallery_api_Success($list);
}
//Return meta data about a given gallery
function kcw_gallery_api_GetGalleryMeta($data) {
    $meta = kcw_gallery_GetGalleryData($data["guid"]);
    //Trade the entire array of images for a small bit of meta data
    $meta["images"] = count($meta["images"]);
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
    $gpage = $data['gpage'];
    $gallery =  kcw_gallery_GetGalleryData($guid);

    if ($gallery == NULL) return kcw_gallery_api_Error("Unrecognized Gallery UID: " . $guid . ", with Page: " . $gpage);

    $per_page = 30;
    $total = count($gallery["images"]);

    $start = ($gpage - 1) * $per_page; $end = 0;
    if ($start >= $total) {
        $start = $total;  
        $end = $start;
        return kcw_gallery_api_Error("Unknown PageID: " . $gpage . ", with Gallery: " . $guid);
    } else {
        $end = $start + $per_page;
        if ($end > $total) 
            $end = $total;
        $end--;
    }

    $gallery["start"] = $start;
    $gallery["end"] = $end;
    $gallery["page"] = $gpage;
    $gallery["per_page"] = $per_page;
    $gallery["total"] = $total;

    $gallery_page = $gallery;
    $gallery_page["images"] = array();
    for ($i = $start;$i <= $end;$i++) {
        $gallery_page["images"][] = $gallery["images"][$i];
    }
    return kcw_gallery_api_Success($gallery_page);
}

//Register all the API routes
function kcw_gallery_api_RegisterRestRoutes() {
    global $kcw_gallery_api_namespace;
    //Route for /list
    register_rest_route( "$kcw_gallery_api_namespace/v1", '/list', array(
        'methods' => 'GET',
        'callback' => 'kcw_gallery_api_GetGalleryList',
    ));
    //Route for /gallery-id
    register_rest_route( "$kcw_gallery_api_namespace/v1", '/(?P<guid>[a-zA-Z0-9-\.]+)', array(
        'methods' => 'GET',
        'callback' => 'kcw_gallery_api_GetGallery',
    ));
    //Route for /gallery-id/meta
    register_rest_route( "$kcw_gallery_api_namespace/v1", '/(?P<guid>[a-zA-Z0-9-\.]+)/meta', array(
        'methods' => 'GET',
        'callback' => 'kcw_gallery_api_GetGalleryMeta',
    ));
    //Route for /gallery-id/page
    register_rest_route("$kcw_gallery_api_namespace/v1", '/(?P<guid>[a-zA-Z0-9-\.]+)/(?P<gpage>\d+)', array(        'methods' => 'GET',
        'callback' => 'kcw_gallery_api_GetGalleryPage',
    ));
}
add_action( 'rest_api_init', "kcw_gallery_api_RegisterRestRoutes");

?>