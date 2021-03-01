<?php

define("KCW_OLD_GALLERY_ROOT", wp_get_upload_dir()["basedir"] . '/' . "Gallery");

$kcw_gallery_known_image_types = ["png", "jpg", "jpeg"];
//Verify that the given file is a supported image
function kcw_gallery_FileIsImage($file) {
    global $kcw_gallery_known_image_types;

    $dotpos = strrpos($file, '.');
    $ext = strtolower(substr($file, $dotpos + 1));
    foreach ($kcw_gallery_known_image_types as $type) 
        if ($ext == $type)
            return true;
    return false;
}
//Extract the filename from a path {
function kcw_gallery_GetFileName($file) {
    return kcw_gallery_GetFolderName($file);
}
//Extract the folder name from a path
function kcw_gallery_GetFolderName($folder) {
    //Determine where the last slash is
    $last_slash = strrpos($folder, '/');
    if ($last_slash + 1 == strlen($folder)) 
        $last_slash = strrpos($folder, '/', 1);
    //Split the name off the folder name
    $name = substr($folder,  $last_slash + 1);
    return $name;
}
//Get relevent information from the file
function kcw_gallery_GetFileDetails($file) {
    $data = array();
    $data["path"] = $file;

    //Make errors raise to warnings
    set_error_handler(function ($err_severity, $err_msg, $err_file, $err_line, array $err_context) {
        throw new ErrorException( $err_msg, 0, $err_severity, $err_file, $err_line );
    }, E_WARNING);

    try {
        $exif = exif_read_data($file);
        $data["taken"] = $exif["DateTimeOriginal"];
    } catch (Exception $e) {
        $data["taken"] = filemtime($file);
    }

    restore_error_handler();

    return $data;
}
//Get an object describing the gallery folders contents
function kcw_gallery_GetFolderData($folder) {
    //Build up the data array
    $data = array();
    $data["name"] = kcw_gallery_GetFolderName($folder);
    $data["path"] = $folder . '/';
    $data["dirs"] = array();
    $data["files"] = array();
    //Get all files in the directory
    $files = glob($folder . "/*");
    foreach($files as $file) {
        $name = kcw_gallery_GetFolderName($file);
        //Skip current and parrent dir aliases
        if ($name == '.' || $name == '..') continue;
        //If its a dir, get that folders information
        if (is_dir($file))
            $data['dirs'][] = kcw_gallery_GetFolderData($file);
        //Otherwise append the full path of the file to the files array
        else $data['files'][] = kcw_gallery_GetFileName($file);
    }
    return $data;
}
//Get the data contained in the discovered folder data
function kcw_gallery_GetGalleryData($folderdata, $parent = NULL) {
    $data = NULL;
    if (count($folderdata["dirs"]) > 0) {
        $data = array();
        //Build the parent dir name
        $nextparent = "";
        if ($parent != NULL) $nextparent = $parent . '/' . $folderdata["name"];
        else $nextparent = $folderdata["name"];
        //Iterate over the dirs in this folder data
        foreach($folderdata["dirs"] as $dir) {
            //Get the gallery data for this directory
            $dirdata = kcw_gallery_GetGalleryData($dir, $nextparent);
            //If any data was found in the dir (I.E. any files)
            if ($dirdata != NULL) {
                //If the parent ISNT null (I.E. Its the top level folder)
                if ($parent != NULL) $dirdata["category"] = $nextparent;
                //Init the dirs key if it isnt already
                if ($data["dirs"] == NULL) $data["dirs"] = array();
                //Add the gallery data to the data array
                $data["dirs"][] = $dirdata;
            }
        }
    }

    if (count($folderdata["files"]) > 0) {
        if ($data == NULL) $data = array();
        $data["name"] = $folderdata["name"];
        $data["path"] = $folderdata["path"];
        $data["files"] = array();
        //Get the file data for each file in this folder
        foreach ($folderdata["files"] as $file) {
            if (kcw_gallery_FileIsImage($file)) {
                $path = $folderdata["path"] . $file;
                $data["files"][] = kcw_gallery_GetFileDetails($path);
            }
        }
    }
    return $data;
}

?>