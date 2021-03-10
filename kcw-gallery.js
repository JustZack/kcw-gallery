jQuery(document).ready(function() {
    jQuery("ul.kcw-gallery-list").on('click', 'li', function() {
        var guid = jQuery(this).data('id');
        ShowGalleryPage(guid, 1)
        //DoGalleryDisplay(guid, 1);
    });
    jQuery("a.kcw-gallery-list-home").on('click', function() {
        DoGalleryListDisplay();
    });

    jQuery("ul.kcw-gallery-pagination").on('click', 'li', function() {
        ShowGalleryPage(kcw_gallery.gallery.uid, jQuery(this).data('page'));
    });

    function StoreGalleryData(data) {
        //Generate the cache
        if (kcw_gallery.gallery == undefined
            || kcw_gallery.gallery.uid !== data.uid) { 
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
               console.log("init cache");
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

        jQuery("ul.pagination-top").empty();
        jQuery("ul.pagination-bottom").empty();

        for (var i = 0;i < num_pages;i++) {
            elem = "<li data-page='" + (i+1) + "'>";
            elem += "<a";
            
            if (i == currentpage) elem += " class='current_page'"
            elem += ">" + (i+1) + "</a></li>";

            jQuery("ul.pagination-top").append(elem);
            jQuery("ul.pagination-bottom").append(elem);
        }

    }

    function DisplayGalleryData(gpage) {
        
        gpage--;
        var gal =  kcw_gallery.gallery;
        var page = gal.pages[gpage];


        jQuery("div.kcw-gallery-list-container").animate({opacity: 0}, function (){
            jQuery("div.kcw-gallery-list-container").css({display: "none"});
        });

        DisplayPagingLinks(gal, gpage);

        jQuery("div.kcw-gallery-title").text(gal.name);

        //Do the display stuff
        jQuery("ul.kcw-gallery-thumbs").empty();
        for (var i = 0;i < gal.pages[gpage].length;i++) {
            var elem = BuildThumbnail(gal.thumbsurl, gal.baseurl, page[i].name);
            jQuery("ul.kcw-gallery-thumbs").append(elem);
        }

        jQuery("div.kcw-gallery-display").animate({opacity: 1});
    }

    function ShowGalleryPage_callback(data) {
        console.log("Updating cache");
        StoreGalleryData(data);
        DisplayGalleryData(data.page);
    }

    function ShowGalleryPage(guid, gpage) {
        console.log(guid+"/"+gpage);
        //If no gallery is cached,
        //the requested gallery differs from the cache, or the current gallery page does not exist
        if (kcw_gallery.gallery == undefined 
         || kcw_gallery.gallery.uid != guid || kcw_gallery.gallery.pages[gpage-1] == undefined) {
            //Build / fetch the data
            ApiCall("", guid+"/"+gpage, ShowGalleryPage_callback);
        } else {
            //Use cached data
            DisplayGalleryData(gpage);
        }
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
        console.log("REQUEST: " + url);
        jQuery.get(url, then).done(function() {
        }).fail(function() {
        }).always(function() {
        });
    }
});