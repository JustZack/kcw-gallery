<?php

include_once "env-helpers.php";
include_once "file-helpers.php";
include_once "formatting-helpers.php";

function kcw_gallery_Query($sql) {
    global $wpdb;
    $selection = $wpdb->get_results($sql, 'ARRAY_A');
    return $selection;
}

function kcw_gallery_AllowedAuthorIDs() {
    /* (Audrey, Gretchen, Franz, John, Pat) */
    return "(55, 56, 61, 52, 82)";
}
//Check if an author's posts are allowed to be used in the gallery
function kcw_gallery_IsAllowedAuthorID($id) {
    if (kcw_gallery_IsLive()) {
        return strpos(kcw_gallery_AllowedAuthorIDs(), "".$id) > -1;
    } else {
        return true;
    }
}

function kcw_gallery_AllowedForumIDs() {
    /* (Complete, Current) project forums */
    return "(205, 297)";
}
//Check if a forum id is allowed to be used in the gallery
function kcw_gallery_IsAllowedForumID($id) {
    if (kcw_gallery_IsLive()) {
        return strpos(kcw_gallery_AllowedForumIDs(), "".$id) > -1;
    } else {
        return true;
    }
}

//$kcw_gallery_WantedForums = "('', '', '')";
function kcw_gallery_QueryAllForums(){
    global $wpdb;
    $fields = "ID, post_author, post_date, post_type, post_name";
    $orderby = "order by post_date_gmt";
    $where = "where post_type = 'forum'";
    
    if (kcw_gallery_IsLive()) {
        $allowed_forums = kcw_gallery_AllowedForumIDs();
        $where .= " and ID in $allowed_forums";
    }

    $query = "select $fields from {$wpdb->posts} $where $orderby";
    return kcw_gallery_Query($query);
}

function kcw_gallery_QueryTopicsFor($forum_id) {
    global $wpdb;
    $fields = "ID, post_author, post_date, post_type, post_name";
    $orderby = "order by post_date_gmt";
    $where = "where post_type = 'topic' and post_parent = $forum_id";
    $query = "select $fields from {$wpdb->posts} $where $orderby";
    return kcw_gallery_Query($query);
}

//$kcw_gallery_WantedAuthors = "('', '', '')";
function kcw_gallery_QueryRepliesFor($topic_id) {
    global $wpdb;
    $fields = "ID, post_author, post_date_gmt, post_type, post_parent, post_name, post_content";
    $select = "select $fields from {$wpdb->posts} ";
    $where = "where post_type in ('topic', 'reply')";
    $where .= " and (ID = '$topic_id' or post_parent = '$topic_id')";
    $where .= " and post_status = 'publish'";
    $orderby = "order by post_date_gmt";
    
    if (kcw_gallery_IsLive()) {
        $allowed_authors = kcw_gallery_AllowedAuthorIDs();
        $where .= " and post_author in $allowed_authors";
    }
    
    $query = "$select $where $orderby";
    return kcw_gallery_Query($query);
}

function kcw_gallery_GetOriginalImageURL($image_url) {
    $site_url = site_url('');
    $site_url = substr($site_url, strpos($site_url, "://") + 3);
    //If the image url contains our site url
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
    $srcpos = strpos($media_str, "src=") + 5;
    $srcend = strpos($media_str, "\"", $srcpos);
    $link = substr($media_str, $srcpos, $srcend - $srcpos);
    if (strpos($link, "s.w.org/images/core/emoji/") !== FALSE) {
        return NULL;
    }
    $filtered_link = substr($link, 0, strpos($link, "?"));
    $ext = substr($link, strrpos($filtered_link, "."));
    //Check if the source is an image type
    global $kcw_gallery_known_image_types;
    foreach ($kcw_gallery_known_image_types as $type)
        if (strpos($ext, $type) > -1) {
            $link = kcw_gallery_GetOriginalImageURL($link);
        }
    
    return $link;
}

function kcw_gallery_GetMediaInReply($reply_content, $post_time) {
    $media = array();
    $toks["img"] = ["<img", "/>"];
    $toks["iframe"] = ["<iframe", "</iframe>"];
    //$toks["embed"] = ["[embed]", "[/embed]"];

    //$reply_content = kcw_gallery_FilterReply($reply_content);
    
    foreach ($toks as $tok) {
        $start = 0;
        $end = -1;
        do {
            $start = strpos($reply_content, $tok[0], $start);
            if ($start !== false) {
                $end = strpos($reply_content, $tok[1], $start) + strlen($tok[1]);
                $media_str = substr($reply_content, $start, $end - $start);

                $item["name"] = kcw_gallery_FilterMediaString($media_str, $tok[0]);
                if ($item["name"] !== NULL) {

                    $item["type"] = substr($tok[0], 1, strlen($tok[0]) - 1);
                    $item["taken"] = strtotime($post_time);
                    if ($item["type"] == "iframe") {
                        if (strpos($item["name"], "youtube") !== false) {
                            $id = substr($item["name"], strrpos($item["name"], "/") + 1);
                            $item["thumb"] = "https://i.ytimg.com/vi/$id/default.jpg";
                        }
                    }


                    $media[] = $item;
                }
                $start = $end;
            }
        } while ($start !== false);
    }
    return $media;
}

function kcw_gallery_GetMediaIn($replies) {
    $images = array();

    foreach ($replies as $reply) {
        $to_add = kcw_gallery_GetMediaInReply($reply["post_content"], $reply["post_date_gmt"]);
        for ($i = 0;$i < count($to_add);$i++) 
            //Get the url to the reply within the topic for this image
            $to_add[$i]["permalink"] = bbp_get_reply_url($reply["ID"]);
        $images = array_merge($images, $to_add);
    }

    return $images;
}

function kcw_gallery_CountMediaIn($replies) {
    $media_count = 0;

    foreach ($replies as $reply) {
        $media_count += substr_count($reply["post_content"], "<img");
        $media_count += substr_count($reply["post_content"], "youtu.be/");
        $media_count += substr_count($reply["post_content"], "youtube.com/");
        //$media_count += substr_count($reply["post_content"], "[embed]");
    }

    return $media_count;
}

function kcw_gallery_FilterReply($reply_content) {
    $new_reply = $reply_content;
    do {
        $quote = strpos($new_reply, "<blockquote");
        if ($quote !== false) {
            $quote_end = strpos($new_reply, "</blockquote>");
            $new_reply = substr($new_reply, 0, $quote);
            $new_reply .= substr($new_reply, $quote_end + strlen("</blockquote>"));
        }
    } while (strpos($new_reply, "<blockquote") !== false);

    return $new_reply;
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

            if ($gtopic['images'] > 0) $gallery_topics[] = $gtopic;
        }
    }

    return $gallery_topics;
}

function kcw_gallery_QueryGalleryTopic($topic) {
    return kcw_gallery_QueryRepliesFor($topic["post_id"]);
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
    //List is from oldest to newest, so reverse it to put newest at the top
    $list_data = array_reverse($list_data);
    return $list_data;
}

function kcw_gallery_BuildForumGalleryData($topic) {
    $replies = kcw_gallery_QueryGalleryTopic($topic);
    $images = kcw_gallery_GetMediaIn($replies);

    $data["uid"] = $topic["uid"];
    $data['type'] = "topic";
    $data["created"] = $topic["created"];
    $data["friendly_name"] = $topic["friendly_name"];
    $data["visibility"] = $topic["visibility"];
    $data["name"] = kcw_gallery_FilterName($topic["name"]);
    $data["images"] = $images;
    $data["category"] = kcw_gallery_FilterName($topic["category"]);
    $data["permalink"] = get_post_permalink($topic["post_id"]);
    $data["baseurl"] = "{0}";
    $data["thumbsurl"] = "{0}?w=130&ssl=1";
    return $data;
}

?>