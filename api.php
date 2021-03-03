<?php

include_once "data-helpers.php";

function kcw_gallery_api_GetGallery($data) {
    $gallery = kcw_gallery_GetGalleryData($data["guid"]);
    return $gallery;
}
function kcw_gallery_api_GetGalleryPage($data) {

}

function kcw_gallery_api_RegisterRestRoutes() {
    $api_namespace = "kcwgallery";
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