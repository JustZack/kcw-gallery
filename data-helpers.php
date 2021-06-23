<?php

include_once "env_helpers.php";
include_once "cache-helpers.php";
include_once "old-gallery-helpers.php";
include_once "forums-gallery-helpers.php";

function kcw_gallery_RootGalleryName() {
    if (kcw_gallery_IsLive()) $galleryname = "KCW";
    else $galleryname = "Gallery";
    
    return $galleryname;
}

function kcw_gallery_RootFolder() {
    return wp_get_upload_dir()["basedir"] . '/' . kcw_gallery_RootGalleryName();
}

function kcw_gallery_RootUrl() {
    return wp_get_upload_dir()["baseurl"] . '/' . kcw_gallery_RootGalleryName();
}

function kcw_gallery_BuildUid($cat, $name, $dirtime, $type) {
    $uid = $cat . '.' . $name . '.' . dechex($dirtime) . '.' . $type; 
    $uid = str_replace(' ', '-', $uid);
    return $uid;
}
//Construct the list of filesystem gallerys
function kcw_gallery_BuildFilesystemListData() {
    $oldgallery = kcw_gallery_BuildOldGalleryListData(kcw_gallery_RootFolder());
    for ($i = 0;$i < count($oldgallery);$i++) {
        $category = $oldgallery[$i]["category"];
        $name = $oldgallery[$i]["name"];

        $path = null;
        if ($category != 'top') $path = kcw_gallery_RootFolder() . '/' . $category . '/' . $name;
        else $path = kcw_gallery_RootFolder() . '/' . $name;

        $dirtime = $oldgallery[$i]["created"];
        $oldgallery[$i]["uid"] = kcw_gallery_BuildUid($category, $name, $dirtime, "file");
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
    $forumsgallery = kcw_gallery_BuildForumGalleryListData();
    for ($i = 0;$i < count($forumsgallery);$i++) {
        $category = $forumsgallery[$i]["category"];
        $name = $forumsgallery[$i]["name"];
        $time = $forumsgallery[$i]["created"];

        $forumsgallery[$i]["uid"] = kcw_gallery_BuildUid($category, $name, $time, "topic");
    }
    return $forumsgallery;
}
function kcw_gallery_BuildForumsGalleryData($guid) {
    $list = kcw_gallery_BuildForumsListData();
    $gallery = NULL;
    foreach ($list as $item) {
        if ($item["uid"] == $guid) {
            $gallery = kcw_Gallery_BuildForumGalleryData($item);
            break;
        }
    }
    return $gallery;

    return kcw_Gallery_BuildForumGalleryData($guid);
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
        $status["files"] = 0;
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

function kcw_gallery_GetSingleListData($file, $callback) {
    $list = null;
    if (!file_exists($file)) {
        //Build the list data
        $list = $callback();
        //Cache
        kcw_gallery_Cache($file, $list);
    } else {
        //Read
        $list = kcw_gallery_GetCacheDataJSON($file);
    }
    return $list;
}

//Delete invalid list cache failes
function kcw_gallery_ValidateListCache() {
    //$list_file = kcw_gallery_GetCacheFile("list");
    //$status = kcw_gallery_GetListStatusData();
    //if (file_exists($list_file)) unlink($list_file);
}

function kcw_gallery_BuildMultiCache($cache_name, $other_caches, $other_callbacks) {
    $main_cache = kcw_gallery_GetCacheFile($cache_name);
    $main_data = array();

    if (!file_exists($main_cache)) {
        for ($i = 0; $i < count($other_caches); $i++) {
            $cache_file = kcw_gallery_GetCacheFile($other_caches[$i]);
            
            $data = array();
            if (!file_exists($cache_file)) {
                $data = kcw_gallery_GetSingleListData($cache_file, $other_callbacks[$i]);
            } else {
                $data = kcw_gallery_GetCacheDataJSON($cache_file);
            }

            $main_data = array_merge($main_data, $data);
        }

        kcw_gallery_Cache($main_cache, $main_data);
    } else {
        $main_data = kcw_gallery_GetCacheDataJSON($main_cache);
    }

    return $main_data;
}

//Return the complete and UP TO DATE list of galleries
function kcw_gallery_GetListData() {
    kcw_gallery_ValidateListCache();
    $list = kcw_gallery_BuildMultiCache("list", ["files-list",                          "forums-list"], 
                                                ["kcw_gallery_BuildFilesystemListData", "kcw_gallery_BuildForumsListData"]);

    return $list;
}

function kcw_gallery_GetGalleryData($guid) {
    $g_file = kcw_gallery_GetCacheFile($guid);
    $gallery_data = NULL;

    if (!file_exists($g_file)) {
        $ext = substr($guid, strrpos($guid, "."));
        if ($ext == ".topic") {
            $gallery_data = kcw_gallery_BuildForumsGalleryData($guid);
        } else if ($ext == ".file") {
            $gallery_data = kcw_gallery_BuildFilesystemGalleryData($guid);
        }

        if ($gallery_data != NULL) kcw_gallery_Cache($g_file, $gallery_data);
    } else {
        $gallery_data = kcw_gallery_GetCacheDataJSON($g_file);
    }
    return $gallery_data;
}
?>