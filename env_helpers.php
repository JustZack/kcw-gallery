<?php

function kcw_gallery_IsDev() {
    $siteurl = site_url('');
    return (strpos($siteurl, "localhost") !== false);
}

function kcw_gallery_IsLive() {
    $siteurl = site_url('');
    return (strpos($siteurl, "kustomcoachwerks") !== false);
}

?>