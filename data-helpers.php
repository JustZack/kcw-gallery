<?php

include_once "cache-helpers.php";
include_once "old-gallery-helpers.php";


function kcw_gallery_RootFolder() {
    return wp_get_upload_dir()["basedir"] . '/' . "Gallery";
}

function kcw_gallery_RootUrl() {
    return wp_get_upload_dir()["baseurl"] . '/' . "Gallery";
}

function kcw_gallery_BuildUid($cat, $name, $dirtime) {
    $uid = $cat . '.' . $name . '.' . dechex($dirtime);
    $uid = str_replace(' ', '-', $uid);
    return $uid;
}
//Construct the list of filesystem gallerys
function kcw_gallery_BuildFilesystemListData() {
    $oldgallery = kcw_gallery_BuildOldGalleryListData(kcw_gallery_RootFolder());
    for ($i = 0;$i < count($oldgallery);$i++) {
        $category = $oldgallery[$i]["category"];
        $name = $oldgallery[$i]["name"];
        $path = kcw_gallery_RootFolder() . '/' . $category . '/' . $name;
        $dirtime = filectime($path);
        $oldgallery[$i]["uid"] = kcw_gallery_BuildUid($category, $name, $dirtime);
    }
    return $oldgallery;
}
function kcw_gallery_BuildFilesystemGalleryData($guid) {
    $list = kcw_gallery_GetListData();
    $gallery = NULL;
    foreach ($list as $item) {
        if ($item["uid"] == $guid) {
            $gallery = kcw_gallery_BuildOldGalleryData($item, kcw_gallery_RootFolder(), kcw_gallery_RootUrl());
            break;
        }
    }
    return $gallery;
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
        $status = kcw_gallery_GetCacheDataJSON($stat_file);
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
        $list = kcw_gallery_GetCacheDataJSON($list_file);
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

function kcw_gallery_GetGalleryData($guid) {
    $g_file = kcw_gallery_GetCacheFile($guid);
    $gallery_data = NULL;

    if (!file_exists($g_file)) {
        $gallery_data = kcw_gallery_BuildFilesystemGalleryData($guid);
        if ($gallery_data != NULL) kcw_gallery_Cache($g_file, $gallery_data);
    } else {
        $gallery_data = kcw_gallery_GetCacheDataJSON($g_file);
    }
    return $gallery_data;
}
?>