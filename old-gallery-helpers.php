    <?php

include_once "file-helpers.php";
include_once "formatting-helpers.php";

function kcw_gallery_StringEndsWith_h($str) {
    $hpos = strpos($str, "_h");
    $hposatend = $hpos == strlen($str) - 2;
    return ($hpos > -1 && $hposatend);
}
function kcw_gallery_GetGalleryVisibility($gal) {
    if (kcw_gallery_StringEndsWith_h($gal['name'])
     || kcw_gallery_StringEndsWith_h($gal['category'])) {
         return "hidden";
     } else {
        return "visible";
     }
}

function kcw_gallery_DetermineListItemData($directory) {
    $name = $directory["name"];
    $category = $directory["category"];

    if (isset($category))
        $directory["friendly_name"] = kcw_gallery_FilterName($category . " / " . $name);
    else
        $directory["friendly_name"] = kcw_gallery_FilterName($name);
    
    $directory["name"] = $name;
    $directory["nice_name"] = kcw_gallery_FilterName($name);

    $directory["category"] = $category;
    $directory["nice_category"] = kcw_gallery_FilterName($category);

    $directory["visibility"] = kcw_gallery_GetGalleryVisibility($directory);

    unset($directory["dirs"]);

    return $directory;
}

//Construct the gallery data list array given the folder data
function kcw_gallery_GetOldGalleryListData($folderdata) {    
    $data = array();
    //var_dump($folderdata);
    for($i = 0;$i < count($folderdata);$i++) {
        $tmpd;
        if ($folderdata[$i]["files"] > 0) {
            $d = $folderdata[$i];
            $data[] = kcw_gallery_DetermineListItemData($d);
        }
        else {
            $tmpd = kcw_gallery_GetFoldersWithFiles($folderdata[$i]);
            //var_dump($tmpd);
            if ($tmpd != NULL) {
                foreach ($tmpd as $d) {
                    if ($d != NULL) {
                        //if (!isset($d["category"])) var_dump($d);
                        $data[] = kcw_gallery_DetermineListItemData($d);
                    }
                }
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
function kcw_gallery_DetermineOldGalleryData($folderData) {
    $data = array();
    $data["images"] = array();

    //For every file 
    foreach ($folderData["files"] as $file) {
        $f = array();

        $f["name"] = kcw_gallery_GetFileName($file);
        $exif = kcw_gallery_GetExifData($file);
        $f["taken"] = $exif["taken"];
        $data["images"][] = $f;
    }

    return $data;
}

//Return data for the given gallery folder
function kcw_gallery_BuildOldGalleryData($gallery, $rootdir, $baseurl) {
    $relativepath = "";
    if ($gallery["category"] != "top") $relativepath = '/' . $gallery["category"];
    $relativepath .= '/' . $gallery["name"] . '/';

    $baseurl .= $relativepath;
    $folder = $rootdir . $relativepath;

    $folderData = kcw_gallery_GetFolderData($folder, true);
    $data = kcw_gallery_DetermineOldGalleryData($folderData);

    //Not positive this is working
    //$data["images"] = kcw_gallery_SortFilesByTakenTime($data["images"]);
    $data["uid"] = $gallery["uid"];
    $data["friendly_name"] = $gallery["friendly_name"];
    $data["visibility"] = $gallery["visibility"];
    $data["name"] = kcw_gallery_FilterName($gallery["name"]);
    
    $data["category"] = kcw_gallery_FilterName($gallery["category"]);

    $data["baseurl"] = $baseurl;
    $data["thumbsurl"] = $baseurl . 'thumbs/';
    $data["basedir"] = $folder;
    $data["thumbsdir"] = $folder . 'thumbs/';
    return $data;
}

?>