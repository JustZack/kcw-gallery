<?php

include_once "cache-helpers.php";
include_once "old-gallery-helpers.php";

define("KCW_OLD_GALLERY_ROOT", wp_get_upload_dir()["basedir"] . '/' . "Gallery");

//Construct the list of filesystem gallerys
function kcw_gallery_BuildFilesystemListData() {
    return kcw_gallery_GetOldGalleryData(KCW_OLD_GALLERY_ROOT);
}

//Construct the list of forums gallerys
function kcw_gallery_BuildForumsListData() {
    return array();
}
function kcw_gallery_UpdateForumsListData($fromtime) {
    return array();
}

//Return the status of the list cache
function kcw_gallery_GetListStatusData() {
    $stat_file = kcw_gallery_GetCacheFile("list-status");
    //Create time variables for later use
    $now = time();
    $hourfromnow = $now + (60 * 60);
    //Get the status of the cache
    $status = array();
    if (!file_exists($stat_file)) {
        $status["filesystem"] = 0;
        $status["forums"] = $hourfromnow;
        kcw_gallery_Cache($stat_file, $status);
    } else {
        $status = json_decode(kcw_gallery_GetCacheData($stat_file), true);
    }
    return $status;
}
function kcw_gallery_UpdateListStatusData($status) {
    $stat_file = kcw_gallery_GetCacheFile("list-status");
    kcw_gallery_Cache($stat_file, $status);
}

//Return the complete and UP TO DATE list of gallery 'folders'
function kcw_gallery_GetListData() {
    $status = kcw_gallery_GetListStatusData();
    $list_file = kcw_gallery_GetCacheFile("list");
    //Create time variables for later use
    $now = time();
    $hourfromnow = $now + (60 * 60);
    $list = array();
    if (!file_exists($list_file)) {
        $fs_list = kcw_gallery_BuildFilesystemListData();
        $forums_list = kcw_gallery_BuildForumsListData();
        $list = array_merge($fs_list, $forums_list);
        kcw_gallery_Cache($list_file, $list);
    } else {
        $list = json_decode(kcw_gallery_GetCacheData($list_file), true);
    }

    //Update the forums gallery list if it is out of date
    if ($status["forums"] < $now) {
        //Get any new gallery data from the forums
        $new = kcw_gallery_UpdateForumsListData($status);
        if (count($new) > 0) {
            //Update the list cache
            $list = array_merge($list, $new);
            kcw_gallery_Cache($list_file, $list);
        }
        //Update the status cache
        $status["forums"] = $now;
        kcw_gallery_UpdateListStatusData($status);
    }

    return $list;
}
?>