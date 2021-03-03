<?php

include_once "data-helpers.php";

//Return the path to the cache folder
function kcw_gallery_GetCacheFolder() {
    $cachedir = __DIR__ . DIRECTORY_SEPARATOR . "cache";
    return $cachedir;
}
//Return the path to a cache file
function kcw_gallery_GetCacheFile($type) {
    $cache = kcw_gallery_GetCacheFolder() . DIRECTORY_SEPARATOR . $type . ".json";
    return $cache;
}
//Return cache data given the filename
function kcw_gallery_GetCacheData($cachefilename) {
    return  file_get_contents($cachefilename);
}
//Return cache data as json given the filename
function kcw_gallery_GetCacheDataJSON($cachefilename) {
    return  json_decode(kcw_gallery_GetCacheData($cachefilename), true);
}
//Cache the given data to the specified cache type
function kcw_gallery_Cache($file, $data) {
    //Ensure the cache directory exists
    $cachedir = kcw_gallery_GetCacheFolder();
    if (!file_exists($cachedir) || !is_dir($cachedir)) {
        mkdir($cachedir);
    }

    //Write the cache file
    $data = json_encode($data);
    file_put_contents($file, $data);
    return $data;
}
//Delete a cache file
function kcw_gallery_DeleteCache($type) {
    $file = kcw_gallery_GetCacheFile($type);
    if (file_exists($file)) unlink($file);
}

?>