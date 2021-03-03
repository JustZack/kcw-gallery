<?php

include_once "file-helpers.php";

//Construct the gallery data list array given the folder data
function kcw_gallery_GetOldGalleryListData($folderdata) {    
    $data = array();
    for($i = 0;$i < count($folderdata);$i++) {
        $tmpd = kcw_gallery_GetFoldersWithFiles($folderdata[$i]);
        if ($tmpd != NULL) {
            foreach ($tmpd as $d) {
                $data[] = $d;
            }
        }
    }
    return $data;
}
//Return a listing the old gallery data
function kcw_gallery_BuildOldGalleryListData($root) {
    $folderdata = kcw_gallery_GetFolderData($root);
    $data = kcw_gallery_GetOldGalleryListData($folderdata);
    return $data;
}

//Construct the gallery data for the given gallery files
function kcw_gallery_GetOldGalleryData($baseurl, $files) {
    $data = array();
    $data["images"] = array();
    foreach ($files as $file) {
        $f = array();
        $f["name"] = kcw_gallery_GetFileName($file);
        $f["taken"] = kcw_gallery_GetExifData($file)["taken"];
        $data["images"][] = $f;
    }
    $data["baserurl"] = $baseurl;
    return $data;
}
//Return data for the given gallery folder
function kcw_gallery_BuildOldGalleryData($gallery, $rootdir, $baseurl) {
    $relativepath = '/' . $gallery["category"] . '/' . $gallery["name"] . '/';
    $baseurl .= $relativepath;
    $folder = $rootdir . $relativepath;

    $files = kcw_gallery_GetFolderData($folder, true)["files"];
    $data = kcw_gallery_GetOldGalleryData($baseurl, $files);
    //Not positive this is working
    $data["images"] = kcw_gallery_SortFilesByTakenTime($data["images"]);
    $data["uid"] = $gallery["uid"];
    $data["name"] = $gallery["category"] . ' / ' . $gallery["name"];
    return $data;
}

?>