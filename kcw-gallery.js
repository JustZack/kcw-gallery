jQuery(document).ready(function() {
    /*
        Event Handlers
    */
    jQuery("ul.kcw-gallery-list").on('click', 'li', function() {
        var guid = jQuery(this).data('id');
        ShowGalleryPage(guid, 1)
        //DoGalleryDisplay(guid, 1);
    });
    jQuery("a.kcw-gallery-list-home").on('click', function() {
        ShowGalleryListPage(1)
    });
    jQuery("ul.kcw-gallery-pagination").on('click', 'li', function() {
        ShowGalleryPage(kcw_gallery.gallery.uid, jQuery(this).data('page'));
    });

    /*
        Functions dealing with displaying a gallery
    */
    function StoreGalleryData(data) {
        //Generate the cache
        if (kcw_gallery.gallery == undefined
            || kcw_gallery.gallery.uid !== data.uid) { 
               kcw_gallery.gallery = {};
               kcw_gallery.gallery.total = data.total;
               kcw_gallery.gallery.per_page = data.per_page;
               kcw_gallery.gallery.uid = data.uid;
               kcw_gallery.gallery.name = data.name;
               kcw_gallery.gallery.baseurl = data.baseurl;
               kcw_gallery.gallery.thumbsurl = data.thumbsurl;

               kcw_gallery.gallery.pages = [];
               console.log("init gallery cache");
        }
        kcw_gallery.gallery.current = data.page;
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

    function DisplayPagingLinks(gal) {
        var num_pages = Math.floor(gal.total / gal.per_page) + 1;
        //var num_pages = Math.floor(gal.total / 3);

        jQuery("ul.pagination-top").empty();
        jQuery("ul.pagination-bottom").empty();

        for (var i = 0;i < num_pages;i++) {
            elem = "<li data-page='" + (i+1) + "'>";
            elem += "<a";
            
            if (i == gal.current-1) elem += " class='current_page'"
            elem += ">" + (i+1) + "</a></li>";

            jQuery("ul.pagination-top").append(elem);
            jQuery("ul.pagination-bottom").append(elem);
        }

    }

    function DisplayGalleryData(gpage) {
        
        var gal =  kcw_gallery.gallery;
        var page = gal.pages[gpage];


        jQuery("div.kcw-gallery-list-container").animate({opacity: 0}, function (){
            jQuery("div.kcw-gallery-list-container").css({display: "none"});
        });

        DisplayPagingLinks(gal);

        jQuery("div.kcw-gallery-title").text(gal.name);

        //Do the display stuff
        jQuery("ul.kcw-gallery-thumbs").empty();
        for (var i = 0;i < gal.pages[gpage].length;i++) {
            var elem = BuildThumbnail(gal.thumbsurl, gal.baseurl, page[i].name);
            jQuery("ul.kcw-gallery-thumbs").append(elem);
        }

        SetQueryParameters();

        jQuery("div.kcw-gallery-display").animate({opacity: 1});
    }

    function ShowGalleryPage_callback(data) {
        console.log("Updating gallery cache");
        StoreGalleryData(data);
        DisplayGalleryData(kcw_gallery.gallery.current-1);
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
            kcw_gallery.gallery.current = gpage;
            DisplayGalleryData(gpage-1);
        }
    }

    function ShowGalleryListPage(lpage) {
        if (kcw_gallery.list == undefined
         || kcw_gallery.list.pages[lpage-1] == undefined) {
            ApiCall("list", "", ShowGalleryListPage_callback);
         } else {
            kcw_gallery.list.current = lpage;
            DisplayGalleryList(lpage-1);
         }
    }
    /*
        Functions dealing with displaying the gallery list
    */
    function StoreListData(data) {
        //Generate the cache
        if (kcw_gallery.list == undefined) {
               kcw_gallery.list = {};
               kcw_gallery.list.total = data.total;
               kcw_gallery.list.per_page = data.per_page;
               kcw_gallery.list.pages = [];
               console.log("init list cache");
        }
        kcw_gallery.list.current = data.page;
        kcw_gallery.list.pages[data.page-1] = data.items;
    }

    function ShowGalleryListPage_callback(data) {
        console.log("Updating list cache");
        StoreListData(data);
        DisplayGalleryList(kcw_gallery.list.current-1);

    }

    function BuildListItem(name, guid) {
        var html = "<li data-id='" + guid + "'>" +
                    "<a class='kcw-gallery-list-title'>" + name + "</a></li>";
        return html;
    }

    function DisplayGalleryList(lpage) {
        var list = kcw_gallery.list;

        //Do the list display stuff
        jQuery("ul.kcw-gallery-list").empty();
        for (var i = 0;i < list.pages[lpage].length;i++) {
            var item = list.pages[lpage][i];
            var elem = BuildListItem(item.name, item.uid);
            jQuery("ul.kcw-gallery-list").append(elem);
        }

        jQuery("div.kcw-gallery-list-container").animate({opacity: 1}, function (){
            jQuery("div.kcw-gallery-list-container").css({display: "block"});
        });
        jQuery("div.kcw-gallery-display").animate({opacity: 0});
    }

    //Perform an API call to the gallery
    var api_url = kcw_gallery.api_url;
    function ApiCall(endpoint, paremeter_string, then) {
        var url = api_url + endpoint + paremeter_string;
        console.log("REQUEST: " + url);
        jQuery.get(url, then).done(function() {
        }).fail(function() {
        }).always(function() {
        });
    }

    KCWGalleryInit();
    function KCWGalleryInit(){
        GetQueryStringParameters();
    }

    /*
        Functions dealing with editing the URL's query string
    */
    //Load variables from the query string
    function GetQueryStringParameters() {
        var gallery_guid = getQueryStringParam("guid");
        var gallery_page = getQueryStringParam("gpage");
        var search = getQueryStringParam("gsearch");
        
        if (gallery_page == null) gallery_page = 1;
        if (search == null) search = "";
        if (gallery_guid != null) DisplayPagingLinks(kcw_gallery.gallery);
    }
    //Set variables into the query string
    function SetQueryParameters() {
        if (kcw_gallery.gallery != undefined) {
            var gallery_guid = kcw_gallery.gallery.uid;
            var gallery_page = kcw_gallery.gallery.current;
            updateQueryStringParam("guid", gallery_guid);
            updateQueryStringParam("gpage", gallery_page);
        } else {
            removeQueryStringParam("guid");
            removeQueryStringParam("gpage");
        }
    }
    //Stolen from: https://stackoverflow.com/questions/901115/how-can-i-get-query-string-values-in-javascript
    function getQueryStringParam(name, url = window.location.href) {
        name = name.replace(/[\[\]]/g, '\\$&');
        var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
            results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, ' '));
    }
    //Stolen from: https://gist.github.com/excalq/2961415
    function updateQueryStringParam(key, value) {
        var baseUrl = [location.protocol, '//', location.host, location.pathname].join(''),
            urlQueryString = document.location.search,
            newParam = key + '=' + value,
            params = '?' + newParam;
    
        // If the "search" string exists, then build params from it
        if (urlQueryString) {
            keyRegex = new RegExp('([\?&])' + key + '[^&]*');
    
            // If param exists already, update it
            if (urlQueryString.match(keyRegex) !== null) {
                params = urlQueryString.replace(keyRegex, "$1" + newParam);
            } else { // Otherwise, add it to end of query string
                params = urlQueryString + '&' + newParam;
            }
        }
        window.history.replaceState({}, "", baseUrl + params);
    };
    //Stolen from: https://stackoverflow.com/questions/1634748/how-can-i-delete-a-query-string-parameter-in-javascript
    function removeQueryStringParam(parameter) {
        var url = document.location.href;
        var urlparts = url.split('?');
    
        if (urlparts.length >= 2) {
            var urlBase = urlparts.shift();
            var queryString = urlparts.join("?");
    
            var prefix = encodeURIComponent(parameter) + '=';
            var pars = queryString.split(/[&;]/g);
            for (var i = pars.length; i-- > 0;) {
                if (pars[i].lastIndexOf(prefix, 0) !== -1) {
                    pars.splice(i, 1);
                }
            }
    
            if (pars.length == 0) {
                url = urlBase;
            } else {
                url = urlBase + '?' + pars.join('&');
            }
    
            window.history.pushState('', document.title, url); // push the new url in address bar
        }
        return url;
    }
});