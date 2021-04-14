<?php

$kcw_gallery_known_image_types = ["png", "jpg", "jpeg", "gif"];
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

//Extract the filename from a path
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
function kcw_gallery_GetExifData($file) {
    $data = array();
    $data["path"] = $file;
    $data["taken"] = NULL;
    $exif = @exif_read_data($file, 'IFD0');
    
    if ($exif != NULL) $data["taken"] = strtotime($exif["DateTimeOriginal"]);
    else $data["taken"] = filemtime($file);
    return $data;
}

//Get all the files in the given folder
function kcw_gallery_GetFiles($folder) {
    return glob($folder . "/*");
}

//Get an object describing the gallery folders contents
function kcw_gallery_GetFolderData($folder, $show_files = false) {
    $files = kcw_gallery_GetFiles($folder);
    $data = array();
    if ($show_files) $data["files"] = array();

    foreach ($files as $file) {
/*        $name = kcw_gallery_GetFolderName($file);
        if ($name == '.' || $name == '..' || $name == 'thumbs') continue;
*/
        if (is_dir($file)) $data[] = kcw_gallery_GetFolderData_recursive($file);
        else if ($show_files && kcw_gallery_FileIsImage($file)) $data["files"][] = $file;
    }
    return $data;
}

//Check if the given folder name is invalid
function kcw_gallery_isFolderNameValid($name) {
    return !($name == '.' || $name == '..' || $name == 'thumbs');
}

//Recurse over the given folder to get all data within it
function kcw_gallery_GetFolderData_recursive($folder) {
    //Build up the data array
    $data = array();
    $data["name"] = kcw_gallery_GetFolderName($folder);
    //$data["path"] = $folder . '/';
    
    $data["files"] = 0;
    $data["dirs"] = array();

    //Get all files in the directory
    $files = kcw_gallery_GetFiles($folder);
    foreach($files as $file) {
        $name = kcw_gallery_GetFolderName($file);
        //Skip current, parent, and thumbnails dir 
        if (!kcw_gallery_isFolderNameValid($name)) continue;

        //If its a dir, get that folders information
        if (is_dir($file)) $data['dirs'][] = kcw_gallery_GetFolderData_recursive($file); 
        //Otherwise append the full path of the file to the files array
        else if (kcw_gallery_FileIsImage($file)) $data['files']++;
    }
    return $data;
}

function kcw_gallery_GetProjectsInDirectory($folderdata, $parent) {
    $data = array();

    if ($parent == NULL) $parent = $folderdata["name"];
    else                 $parent .= '/' . $folderdata["name"];

    foreach ($folderdata["dirs"] as $dir) {
        //var_dump($dir);
        $tmpd = kcw_gallery_GetFoldersWithFiles($dir, $parent);
        if ($tmpd != NULL) $data[] = $tmpd;
    }

    return $data;
}

function kcw_gallery_GetProject($folderdata, $parent) {
    $data = array();
    $data["name"] = $folderdata["name"];
    $data["category"] = $parent == NULL ? $data["name"] : $parent;
    $data["files"] = $folderdata["files"];
    
    return $data;
}

//Return only folders containing files
function kcw_gallery_GetFoldersWithFiles($folderdata, $parent = NULL) {
    $n_dirs = count($folderdata["dirs"]);
    $n_files = $folderdata["files"];
    $data = NULL;

    //More than one directory => projects in this category
    if ($n_dirs > 0) $data = kcw_gallery_GetProjectsInDirectory($folderdata, $parent);

    //More than one file => project directory
    if ($n_files > 0) $data = kcw_gallery_GetProject($folderdata, $parent);

    //No directories or files => skip
    //if (($n_files == 0 || $n_dirs == 0)) { echo 'a'; var_dump($data); echo 'b';}
    return $data;
}

//Sort files by date time
function kcw_gallery_SortFilesByTakenTime($files) {
    //Selection sort
    for ($i = 0;$i < count($files) - 1;$i++) {
        $mindate = strtotime($files[$i]["taken"]);
        $minj = $i;
        for ($j = $i + 1;$j < count($files);$j++) {
            $curdate = strtotime($files[$j]["taken"]);
            if ($curdate < $mindate) {
                $minj = $j;
                $mindate = $curdate;
            }

        }
        //Swap
        if ($minj != $i) {
            $tmp = $files[$i];
            $files[$i] = $files[$minj];
            $files[$minj] = $tmp;
        }
    }
    return $files;
}
?>