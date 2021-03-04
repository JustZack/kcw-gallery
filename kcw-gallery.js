jQuery(document).ready(function() {
    jQuery("ul.kcw-gallery-list li").on('click', function() {
        var guid = jQuery(this).data('id');
        GetGalleryData(guid, 1);
    });
    jQuery("div.kcw-gallery-list-home").on('click', function() {
        console.log(GetGalleryList());
    });

    
    function DisplayGalleryData(data) {
        var key = "p" + parseInt(data.page);
        kcw_gallery_data.gallery = {};
        kcw_gallery_data.gallery[key] = data;
        console.log(kcw_gallery_data.gallery[key]);
    }
    function GetGalleryData(guid, gpage) {
        if (kcw_gallery_data.gallery != undefined) {
            if (kcw_gallery_data.gallery[gpage] != undefined) {
                return kcw_gallery_data.gallery[gpage];
            }
        }
        return ApiCall("", guid, DisplayGalleryData);
    }

    function DisplayGalleryList(data) {
        kcw_gallery_data.list = data;
    }   
    function GetGalleryList() {
        if (kcw_gallery_data.list != undefined) 
            return kcw_gallery_data.list;
        else 
            return ApiCall("list", "", DisplayGalleryList);
    }

    var api_url = kcw_gallery_data.api_url;
    function ApiCall(endpoint, paremeter_string, then) {
        var url = api_url + endpoint + paremeter_string;
        jQuery.get(url, then).done(function() {
        }).fail(function() {
        }).always(function() {
        });
    }
});