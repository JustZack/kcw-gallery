<?php

include_once "file-helpers.php";
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
    ($query);
    return kcw_gallery_Query($query);
}

function kcw_gallery_GetOriginalImageURL($image_url) {
    $site_url = site_url('');
    $site_url = substr($site_url, strpos($site_url, "://") + 3);
    //If the image pack contains our site url
    if (strpos($image_url, $site_url) > -1) {
        //Replace the default wordpress sizings with original path
        $image_url = preg_replace("/(-[\d]+x[\d]+)/", '', $image_url);
        //Replace the wordpress image cdn sizings with original path
        $image_url = preg_replace("/(\?.*w=[\d]+)/",  '', $image_url);
    }
    return $image_url;
}

function kcw_gallery_FilterMediaString($media_str, $tok) {
    //Extract the src link from img and iframe
    $srcpos = strpos($media_str, "src=", strlen($tok));
    $srcend = strpos($media_str, "\"", $srcpos + strlen($tok) + 1) - (strlen($tok) + 1);
    $link = substr($media_str, $srcpos + 5, $srcend - $srcpos);
    
    //Check if the source is an image type
    global $kcw_gallery_known_image_types;
    foreach ($kcw_gallery_known_image_types as $type)
        if (strpos($link, $type) > -1) {
            $link = kcw_gallery_GetOriginalImageURL($link);
        }
    
    return $link;
}

function kcw_gallery_GetMediaInReply($reply_content, $post_time) {
    $media = array();
    $toks["img"] = ["<img", "/>"];
    $toks["iframe"] = ["<iframe"];
    //$toks["embed"] = ["[embed]", "[/embed]"];
    $start = 0;
    $end = -1;

    foreach ($toks as $tok) {
        
        if (strpos($reply_content, $tok[0], $start) > -1) {
            $start = strpos($reply_content, $tok[0], $start);
            $end = strpos($reply_content, $tok[1], $start) + strlen($tok[1]);
            $media_str = substr($reply_content, $start, $end - $start);

            $item["name"] = kcw_gallery_FilterMediaString($media_str, $tok[0]);
            $item["type"] = substr($tok[0], 1, strlen($tok[0]) - 1);
            $item["taken"] = strtotime($post_time);

            $media[] = $item;
            $start = $end;
        } else {
            $start = -1;
        }
    }
    return $media;
}

function kcw_gallery_GetMediaIn($replies) {
    $images = array();

    foreach ($replies as $reply) {
        $to_add = kcw_gallery_GetMediaInReply($reply["post_content"], $reply["post_date_gmt"]);
        $images = array_merge($images, $to_add);
    }

    return $images;
}

function kcw_gallery_CountMediaIn($replies) {
    $media_count = 0;

    foreach ($replies as $reply) {
        $media_count += substr_count($reply["post_content"], "<img");
        $media_count += substr_count($reply["post_content"], "<iframe");
        //$media_count += substr_count($reply["post_content"], "[embed]");
    }

    return $media_count;
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
            $gtopic['images'] = kcw_gallery_CountMediaIn($replies);
            $gallery_topics[] = $gtopic;
        }
    }

    return $gallery_topics;
}

function kcw_gallery_QueryGalleryTopic($topic) {
    return kcw_gallery_QueryRepliesFor($topic["post_id"]);
}

function kcw_gallery_DetermineTopicData($replies) {
    return null;
}


function kcw_gallery_DetermineForumListItemData($gallery) {
    $list_item = array();

    $name = $gallery["name"];
    $forum = $gallery["forum"];
    
    //$list_item['type'] = "topic";
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
    
    $list_item['post_id'] = $gallery['id'];

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
    //var_dump($topic);
    $replies = kcw_gallery_QueryGalleryTopic($topic);
    $images = kcw_gallery_GetMediaIn($replies);

    //var_dump($images);

    $data["uid"] = $topic["uid"];
    $data['type'] = "topic";
    $data["created"] = $topic["created"];
    $data["friendly_name"] = $topic["friendly_name"];
    $data["visibility"] = $topic["visibility"];
    $data["name"] = kcw_gallery_FilterName($topic["name"]);
    $data["images"] = $images;
    $data["category"] = kcw_gallery_FilterName($topic["category"]);

    $data["baseurl"] = "{0}";
    $data["thumbsurl"] = "{0}?w=130";
    return $data;
}

?>