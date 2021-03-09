jQuery(document).ready(function() {
    jQuery("ul.kcw-gallery-list").on('click', 'li', function() {
        var guid = jQuery(this).data('id');
        DoGalleryDisplay(guid, 1);
    });
    jQuery("a.kcw-gallery-list-home").on('click', function() {
        DoGalleryListDisplay();
    });

    jQuery("ul.kcw-gallery-pagination").on('click', 'li', function() {
        console.log(jQuery(this).data('page'));
    });

    function StoreGalleryData(data) {
        //Generate the build cache
        if (kcw_gallery.gallery == undefined
            || kcw_gallery.uid != data.uid) { 
               kcw_gallery.gallery = {};
               kcw_gallery.gallery.total = data.total;
               kcw_gallery.gallery.per_page = data.per_page;
               kcw_gallery.gallery.uid = data.uid;
               kcw_gallery.gallery.name = data.name;
               kcw_gallery.gallery.start = data.start;
               kcw_gallery.gallery.end = data.end;
               kcw_gallery.gallery.baseurl = data.baseurl;
               kcw_gallery.gallery.thumbsurl = data.thumbsurl;

               kcw_gallery.gallery.pages = [];
        }
        
        kcw_gallery.gallery.pages[data.page-1] = data.images;
    }

    function BuildThumbnail(thumbsurl, imgurl, imgname) {
        var img = imgurl + imgname;

        var filename =  imgname.substring(0, imgname.lastIndexOf("."));
        var thumb = thumbsurl + filename + ".jpg";
        var html = "<li><a data-src='" + img + "'>" +
                    "<img width='" + 320 + "' height='" + 180 + "' src='" + thumb + "'>" +
                    "</a></li>";
        return html;
    }

    function DisplayPagingLinks(gal, currentpage) {
        var num_pages = Math.floor(gal.total / gal.per_page) + 1;
        //var num_pages = Math.floor(gal.total / 3);

        for (var i = 0;i < num_pages;i++) {
            elem = "<li data-page='" + i + "'>";
            elem += "<a";
            
            if (i == currentpage) elem += " class='current_page'"
            elem += ">" + (i+1) + "</a></li>";

            jQuery("ul.pagination-top").append(elem);
            jQuery("ul.pagination-bottom").append(elem);
        }

    }

    function DisplayGalleryData(data, page) {
        
        if (data != null) StoreGalleryData(data);

        var pagenum = 0;
        if (parseInt(page) > -1) pagenum = page;
        else pagenum = data.page-1;

        var gal =  kcw_gallery.gallery;
        var page = gal.pages[pagenum];

        jQuery("div.kcw-gallery-list-container").animate({opacity: 0}, function (){
            jQuery("div.kcw-gallery-list-container").css({display: "none"});
        });

        DisplayPagingLinks(gal, pagenum);

        jQuery("div.kcw-gallery-title").text(gal.name);

        //Do the display stuff
        jQuery("ul.kcw-gallery-thumbs").empty();
        for (var i = 0;i < gal.pages[pagenum].length;i++) {
            var elem = BuildThumbnail(gal.thumbsurl, gal.baseurl, page[i].name);
            jQuery("ul.kcw-gallery-thumbs").append(elem);
        }

        jQuery("div.kcw-gallery-display").animate({opacity: 1});
    }

    function DoGalleryDisplay(guid, gpage) {
        if (kcw_gallery.gallery != undefined 
         && kcw_gallery.gallery.pages[gpage-1] != undefined 
         && kcw_gallery.gallery.pages[gpage-1].uid == guid)
            return DisplayGalleryData(null, gpage);
        else
            return ApiCall("", guid, DisplayGalleryData);
    }

    function DisplayGalleryList(data) {
        if (data != null) kcw_gallery.list = data;
        //Do the list display stuff
        jQuery("div.kcw-gallery-list-container").animate({opacity: 1}, function (){
            jQuery("div.kcw-gallery-list-container").css({display: "block"});
        });
        jQuery("div.kcw-gallery-display").animate({opacity: 0});
    }

    function DoGalleryListDisplay() {
        if (kcw_gallery.list != undefined) 
            return DisplayGalleryList(null);
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