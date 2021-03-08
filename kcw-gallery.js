jQuery(document).ready(function() {
    jQuery("ul.kcw-gallery-list li").on('click', function() {
        var guid = jQuery(this).data('id');
        GetGalleryData(guid, 1);
    });
    jQuery("a.kcw-gallery-list-home").on('click', function() {
        GetGalleryList();
    });

    function StoreGalleryData(data) {
        //Generate the build cache
        if (kcw_gallery.gallery == undefined
            || kcw_gallery.uid != data.uid) { 
               kcw_gallery.gallery = {};
               kcw_gallery.gallery.total = data.total;
               kcw_gallery.gallery.per_page = data.per_page;
               kcw_gallery.gallery.uid = data.uid;
               kcw_gallery.gallery.start = data.start;
               kcw_gallery.gallery.end = data.end;
               kcw_gallery.gallery.baseurl = data.baseurl;
               kcw_gallery.gallery.thumbsurl = data.thumbsurl;

               kcw_gallery.gallery.pages = [];
        }
        //var key = "p" + parseInt(data.page);
        kcw_gallery.gallery.pages[data.page-1] = data.images;
    }

    function BuildThumbnail(thumbsurl, imgurl, imgname) {
        var img = imgurl + imgname;

        var filename =  imgname.substring(0, imgname.lastIndexOf("."));
        var thumb = thumbsurl + filename + ".jpg";
        return "<li data-src='" + img + "'><img src='" + thumb + "'></li>";
    }

    function DisplayGalleryData(data) {
        StoreGalleryData(data);

        var gal =  kcw_gallery.gallery;
        var page = gal.pages[data.page-1];
        //Do the display stuff
        jQuery("ul.kcw-gallery-thumbs").empty();
        for (var i = 0;i < gal.pages[data.page-1].length;i++) {
            var elem = BuildThumbnail(gal.thumbsurl, gal.baseurl, page[i].name);
            jQuery("ul.kcw-gallery-thumbs").append(elem);
        }
    }
    function GetGalleryData(guid, gpage) {
        var index = "p" + gpage;
        if (kcw_gallery.gallery != undefined 
         && kcw_gallery.gallery[index] != undefined 
         && kcw_gallery.gallery[index].uid == guid)
            return kcw_gallery.gallery[index];
        else
            return ApiCall("", guid, DisplayGalleryData);
    }

    function DisplayGalleryList(data) {
        kcw_gallery.list = data;

        //Do the list display stuff
    }   
    function GetGalleryList() {
        if (kcw_gallery.list != undefined) 
            return kcw_gallery.list;
        else 
            return ApiCall("list", "", DisplayGalleryList);
    }

    var api_url = kcw_gallery.api_url;
    function ApiCall(endpoint, paremeter_string, then) {
        var url = api_url + endpoint + paremeter_string;
        jQuery.get(url, then).done(function() {
        }).fail(function() {
        }).always(function() {
        });
    }
});