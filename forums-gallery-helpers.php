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
    ($query);
    return kcw_gallery_Query($query);
}

function kcw_gallery_GetMediaInReply($reply_content) {
    $media = array();
    $toks["img"] = ["<img", "/>", 'src="', '"'];
    $toks["iframe"] = ["<iframe", "/>"];
    $toks["embed"] = ["[embed]", "[/embed]"];
    $start = 0;
    $end = -1;

    foreach ($toks as $tok) {
        
        if (strpos($reply_content, $tok[0], $start) > -1) {
            $start = strpos($reply_content, $tok[0], $start);
            $end = strpos($reply_content, $tok[1], $start) + strlen($tok[1]);
            $media_str = substr($reply_content, $start, $end - $start);
            //$srcpos = strpos($img_str, $tok[0]);
            //$srcend = strpos($img_str, $toks["src"][1], $srcpos+5);
            //$img_link = substr($img_str, $srcpos, $srcend - $srcpos);
            //$media[] = $img_link;
            $media[] = $media_str;
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
        $to_add = kcw_gallery_GetMediaInReply($reply["post_content"]);
        $images = array_merge($images, $to_add);
    }

    return $images;
}

function kcw_gallery_CountMediaIn($replies) {
    $media_count = 0;

    foreach ($replies as $reply) {
        $media_count += substr_count($reply["post_content"], "<img");
        $media_count += substr_count($reply["post_content"], "<iframe");
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

    //$data["baseurl"] = $baseurl;
    //$data["thumbsurl"] = $baseurl . 'thumbs/';
    //$data["basedir"] = $folder;
    //$data["thumbsdir"] = $folder . 'thumbs/';
    return $data;
}

?>