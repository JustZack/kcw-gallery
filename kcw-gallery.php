<?php
/*
* Plugin Name:       KCW Gallery
* Description:       Provide a home for all KCW image uploads
* Version:           1.0.0
* Requires at least: 5.2
* Requires PHP:      7.2
* Author:            Zack Jones
*/


function kcw_gallery_GetHTML() {
    
}
function kcw_gallery_Init() {

}
add_shortcode("kcw-movies", 'kcw_gallery_Init');
?>