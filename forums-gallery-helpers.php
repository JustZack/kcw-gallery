<?php

include_once "formatting-helpers.php";

function kcw_gallery_Query($sql) {
    global $wpdb;
    $selection = $wpdb->get_results($sql, 'ARRAY_A');
    return $selection;
}

function kcw_gallery_QueryAllForums(){
    global $wpdb;
    $fields = "ID, post_author, post_date, post_type, post_name";
    $query = "select $fields from {$wpdb->posts} where post_type = 'forum'";
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
    $query = "select $fields from {$wpdb->posts} where post_type = 'topic'";
    return kcw_gallery_Query($query);
}

function kcw_gallery_QueryRepliesFor($topic_id) {
    global $wpdb;
    $fields = "ID, post_author, post_date, post_type, post_name, post_content";
    $select = "select $fields from {$wpdb->posts} ";
    $where = "where post_type in ('topic', 'reply') and (ID = '$topic_id' or post_parent = '$topic_id')";
    $query = $select . $where;
    return kcw_gallery_Query($query);
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
            $gallery_topics[] = $gtopic;
        }
    }

    return $gallery_topics;
}

function kcw_gallery_QueryGalleryTopic($topic_id) {
    $topic_replies = array();
    
    $replies = kcw_gallery_QueryRepliesFor($topic_id);
    foreach ($replies as $reply) $topic_replies[] = $reply;

    return $gallery_topics;
}

function kcw_gallery_BuildForumGalleryListData() {
    $galleries = kcw_gallery_QueryGalleryTopicList();
    
    $list_data = array();
    foreach ($galleries as $gallery) {
        $list_item = array();

        $name = $gallery["name"];
        $forum = $gallery["forum"];

        $list_item['name'] = $name;
        $list_item['category'] = $forum;
        $list_item['files'] = -1;
        $list_item['friendly_name'] = kcw_gallery_FilterName($forum . " / " . $name);
        $list_item['nice_name'] = kcw_gallery_FilterName($name);
        $list_item['nice_category'] = kcw_gallery_FilterName($forum);
        $list_item['visibility'] = 'visible';

        $list_data[] = $list_item;
    }
    return $list_data;
}

function kcw_Gallery_BuildForumGalleryData($topic_id) {
    $replies = kcw_gallery_QueryGalleryTopic($topic_id);

    return $replies;
}

?>