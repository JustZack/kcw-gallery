jQuery(document).ready(function() {
    jQuery("ul.kcw-gallery-list li").on('click', function() {
        var guid = jQuery(this).data('id');
        GetGalleryData(guid, 1);
    });
    jQuery("a.kcw-gallery-list-home").on('click', function() {
        GetGalleryList();
    });


    function DisplayGalleryData(data) {

        //Generate the build cache
        if (kcw_gallery_data.gallery == undefined
         || kcw_gallery_data.uid != data.uid) { 
            kcw_gallery_data.gallery = {};
            kcw_gallery_data.gallery.total = data.total;
            kcw_gallery_data.gallery.per_page = data.per_page;
            kcw_gallery_data.gallery.uid = data.uid;
            console.log(kcw_gallery_data.gallery);
        }

        var key = "p" + parseInt(data.page);
        kcw_gallery_data.gallery[key] = data.images;

        //Do the display stuff
        
    }
    function GetGalleryData(guid, gpage) {
        var index = "p" + gpage;
        if (kcw_gallery_data.gallery != undefined 
         && kcw_gallery_data.gallery[index] != undefined 
         && kcw_gallery_data.gallery[index].uid == guid)
            return kcw_gallery_data.gallery[index];
        else
            return ApiCall("", guid, DisplayGalleryData);
    }

    function DisplayGalleryList(data) {
        kcw_gallery_data.list = data;

        //Do the list display stuff
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