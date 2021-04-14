<?php

include_once "formatting-helpers.php";

function kcw_gallery_Query($sql) {
    global $wpdb;
    $selection = $wpdb->get_results($sql, 'ARRAY_A');
    return $selection;
}

//$kcw_gallery_WantedForums = "('', '', '')";
function kcw_gallery_QueryAllForums(){
    global $wpdb;
    $fields = "ID, post_author, post_date, post_type, post_name";
    $orderby = "order by post_date_gmt";
    $query = "select $fields from {$wpdb->posts} where post_type = 'forum' $orderby";
    return kcw_gallery_Query($query);
}

function kcw_gallery_QueryTopicsFor($forum_id) {
    global $wpdb;
    $fields = "post_id as ID, meta_value as forum_id";
    $select = "select $fields from {$wpdb->postmeta} ";
    $where = "where meta_key = '_bbp_forum_id' and meta_value = '$forum_id'";
    $query = $select . $where;
    $topics = kcw_gallery_Query($query);

    $fields = "ID, post_author, post_date, post_type, post_name";
    $orderby = "order by post_date_gmt";
    $query = "select $fields from {$wpdb->posts} where post_type = 'topic' $orderby";
    return kcw_gallery_Query($query);
}

//$kcw_gallery_WantedAuthors = "('', '', '')";
function kcw_gallery_QueryRepliesFor($topic_id) {
    global $wpdb;
    $fields = "ID, post_author, post_date_gmt, post_type, post_name, post_content";
    $select = "select $fields from {$wpdb->posts} ";
    $orderby = "order by post_date_gmt";
    $where = "where post_type in ('topic', 'reply') and (ID = '$topic_id' or post_parent = '$topic_id')";
    $where .= "and post_status = 'publish'";

    $query = $select . $where . $orderby;
    return kcw_gallery_Query($query);
}


function kcw_gallery_GetImagesInReply($reply_content) {
    $images = array();
    $stok = "<img"; $etok = "/>";
    $start = -1;
    $end = -1;

    do {
        $start = strpos($reply_content, $stok, $start+1); 
        $end = strpos($reply_content, $etok, $end+1);
        $images[] = substr($reply_content, $start, $end - $start);
    } while ($start);

    return $images;
}

function kcw_gallery_GetImagesIn($replies) {
    $images = array();

    foreach ($replies as $reply) {
        $to_add = kcw_gallery_GetImagesInReply($reply["post_content"]);
        $images = array_merge($images, $to_add);
    }

    return $images;
}

function kcw_gallery_CountImagesIn($replies) {
    $num_images = 0;

    foreach ($replies as $reply)
        $num_images += substr_count($reply["post_content"], "<img");

    return $num_images;
}

function kcw_gallery_QueryGalleryTopicList() {
    $gallery_topics = array();

    $forums = kcw_gallery_QueryAllForums();
    foreach ($forums as $forum) {
        $topics = kcw_gallery_QueryTopicsFor($forum['ID']);
        foreach ($topics as $topic) {
            $gtopic = array();
            $gtopic['name'] = $topic['post_name'];
            $gtopic['id'] = $topic['ID'];
            $gtopic['forum'] = $forum['post_name'];
            $gtopic['forum_id'] = $forum['ID'];
            $gtopic['created'] = strtotime($topic['post_date']);
            
            $replies = kcw_gallery_QueryRepliesFor($topic['ID']);
            $gtopic['images'] = kcw_gallery_CountImagesIn($replies);
            $gallery_topics[] = $gtopic;
        }
    }

    return $gallery_topics;
}

function kcw_gallery_QueryGalleryTopic($topic_id) {
    return kcw_gallery_QueryRepliesFor($topic_id);
}

function kcw_gallery_DetermineTopicData($replies) {
    return null;
}


function kcw_gallery_DetermineForumListItemData($gallery) {
    $list_item = array();

    $name = $gallery["name"];
    $forum = $gallery["forum"];

    $list_item['name'] = $name;
    $list_item['category'] = $forum;

    //Gonna require going through every reply / aggregate by sql
    $list_item['files'] = $gallery["images"];

    $list_item['friendly_name'] = kcw_gallery_FilterName($forum . " / " . $name);
    $list_item['nice_name'] = kcw_gallery_FilterName($name);
    $list_item['nice_category'] = kcw_gallery_FilterName($forum);
    
    //Post meta data?
    $list_item['visibility'] = 'visible';
    $list_item['created'] = $gallery['created'];

    return $list_item;
}

function kcw_gallery_BuildForumGalleryListData() {
    $galleries = kcw_gallery_QueryGalleryTopicList();
    
    $list_data = array();
    foreach ($galleries as $gallery) {
        $list_data[] = kcw_gallery_DetermineForumListItemData($gallery);
    }
    return $list_data;
}

function kcw_Gallery_BuildForumGalleryData($topic) {
    var_dump($topic);
    $replies = kcw_gallery_QueryGalleryTopic($topic);
    $images = kcw_gallery_GetImagesIn($replies);
    //var_dump($topic_id);
    //var_dump($replies);
    //var_dump($images);
    return $images;



    //$folderData = kcw_gallery_GetFolderData($folder, true);
    $data = kcw_gallery_DetermineOldGalleryData($folderData);

    //Not positive this is working
    //$data["images"] = kcw_gallery_SortFilesByTakenTime($data["images"]);
    $data["uid"] = $topic["uid"];
    $data["created"] = $topic["created"];
    $data["friendly_name"] = $topic["friendly_name"];
    $data["visibility"] = $topic["visibility"];
    $data["name"] = kcw_gallery_FilterName($topic["name"]);
    
    $data["category"] = kcw_gallery_FilterName($topic["category"]);

    $data["baseurl"] = $baseurl;
    $data["thumbsurl"] = $baseurl . 'thumbs/';
    $data["basedir"] = $folder;
    $data["thumbsdir"] = $folder . 'thumbs/';
    return $data;
}

?>