<?php

include_once "file-helpers.php";

//Construct the gallery data list array given the folder data
function kcw_gallery_GetOldGalleryListData($folderdata) {    
    $data = array();
    for($i = 0;$i < count($folderdata);$i++) {
        $tmpd = kcw_gallery_GetFoldersWithFiles($folderdata[$i]);
        if ($tmpd != NULL) {
            foreach ($tmpd as $d) {
                $cat = $d["category"]; $name = $d["name"];
                $d["friendly_name"] = kcw_gallery_FilterName($cat . " / " . $name); 
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
function kcw_gallery_GetOldGalleryData($files) {
    $data = array();
    $data["images"] = array();
    foreach ($files as $file) {
        $f = array();
        $f["name"] = kcw_gallery_GetFileName($file);
        $exif = kcw_gallery_GetExifData($file);
        $f["taken"] = $exif["taken"];
        $data["images"][] = $f;
    }

    return $data;
}
//Filter a name so it reads well
function kcw_gallery_FilterName($gallery_name) {
    $name = str_replace("-", " ", $gallery_name);
    $name = ucwords($name);
    return $name;
}
//Return data for the given gallery folder
function kcw_gallery_BuildOldGalleryData($gallery, $rootdir, $baseurl) {
    $relativepath = '/' . $gallery["category"] . '/' . $gallery["name"] . '/';
    $baseurl .= $relativepath;
    $folder = $rootdir . $relativepath;

    $files = kcw_gallery_GetFolderData($folder, true)["files"];
    $data = kcw_gallery_GetOldGalleryData($files);
    //Not positive this is working
    //$data["images"] = kcw_gallery_SortFilesByTakenTime($data["images"]);
    $data["uid"] = $gallery["uid"];
    $data["name"] = kcw_gallery_FilterName($gallery["category"] . ' / ' . $gallery["name"]);
    $data["baseurl"] = $baseurl;
    $data["thumbsurl"] = $baseurl . 'thumbs/';
    $data["basedir"] = $folder;
    $data["thumbsdir"] = $folder . 'thumbs/';;
    return $data;
}

?>