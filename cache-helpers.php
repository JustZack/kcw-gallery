<?php

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

function kcw_gallery_InvalidateTopicCache($topic_id) {
    $forums_list_file = kcw_gallery_GetCacheFile("forums-list");
    
    if (file_exists($forums_list_file)) {
        $forums_list = kcw_gallery_GetCacheDataJSON($forums_list_file);
        $topic_cache_uid = -1;

        //Find the topic data in the list
        foreach ($forums_list as $forum) {
            if ($forum["post_id"] == "".$topic_id) {
                $topic_cache_uid = $forum["uid"];
                break;
            }
        }

        //Found the cache file
        if ($topic_cache_uid != -1) {
            kcw_gallery_DeleteCache($topic_cache_uid);
            kcw_gallery_DeleteCache("forums-list");
            kcw_gallery_DeleteCache("list");
        }
    }
    
}
?>