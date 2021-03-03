<?php

include_once "data-helpers.php";

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
    return $data;
}

function kcw_gallery_api_GetGalleryList() {
    return kcw_gallery_GetListData();
}
function kcw_gallery_api_GetGallery($data) {
    $data['gpage'] = 1;
    return kcw_gallery_api_GetGalleryPage($data); 
}
function kcw_gallery_api_GetGalleryPage($data) {
    $gallery =  kcw_gallery_GetGalleryData($data["guid"]);

    if ($gallery == NULL) return kcw_gallery_api_Error("Unrecognized Gallery UID");

    $page = $data["gpage"];
    $per_page = 40;

    $start = ($page - 1) * $per_page; $end = 0;
    if ($start >= count($gallery["images"])) {
        $start = count($gallery["images"]);  
        $end = $start;
    } else {
        $end = $page + $per_page;
        if ($end > count($gallery["images"])) 
            $end = count($gallery["images"]);
        $end--;
    }

    $gallery["start"] = $start;
    $gallery["end"] = $end;
    $gallery["page"] = $page;
    $gallery["per_page"] = $per_page;

    $gallery_page = $gallery;
    $gallery_page["images"] = array();
    for ($i = $start;$i <= $end;$i++) {
        $gallery_page["images"][] = $gallery["images"][$i];
    }
    return kcw_gallery_api_Success($gallery_page);
}

function kcw_gallery_api_RegisterRestRoutes() {
    $api_namespace = "kcwgallery";
    register_rest_route( "$api_namespace/v1", '/gallery/list', array(
        'methods' => 'GET',
        'callback' => 'kcw_gallery_api_GetGalleryList',
    ));
    register_rest_route( "$api_namespace/v1", '/gallery/(?P<guid>.*+)', array(
        'methods' => 'GET',
        'callback' => 'kcw_gallery_api_GetGallery',
    ));
    register_rest_route("$api_namespace/v1", '/gallery/(?P<guid>.*+)/(?P<gpage>\d+)', array(
        'methods' => 'GET',
        'callback' => 'kcw_gallery_api_GetGalleryPage',
    ));
}
add_action( 'rest_api_init', "kcw_gallery_api_RegisterRestRoutes");

?>