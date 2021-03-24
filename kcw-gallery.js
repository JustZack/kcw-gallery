jQuery(document).ready(function() {
    /*
        Event Handlers
    */
    jQuery("ul.kcw-gallery-list").on('click', 'li', function() {
        if (!isLoading) {
            var guid = jQuery(this).data('id');

            ShowGalleryPage(guid, 1)
        }
    });
    jQuery("a.kcw-gallery-list-home").on('click', function() {
        var page = 1;
        if (kcw_gallery.list != undefined) page = kcw_gallery.list.current;
        
        ShowGalleryListPage(page);
    });
    jQuery("ul.kcw-gallery-pagination").on('click', 'li', function() {
        if (!isLoading) {
            var page = jQuery(this).data('page');
            if (page != undefined) {
                if (ListActive) ShowGalleryListPage(page);
                else ShowGalleryPage(kcw_gallery.gallery.uid, page);
            }
        }
    });

    /*
    Functions dealing with displaying paging links
    */
    function BuildPagingLink(j, current, elippsis) {
        var li_elem = "<li data-page='" + (j+1) + "'>";
        li_elem += "<a";
        if (current) li_elem += " class='current_page'";
        li_elem += ">";
        if (elippsis) li_elem += "...";
        else li_elem += ""+(j+1);
        li_elem += "</a></li>";
        return li_elem;
    }

    var ListActive = true;
    function DisplayPagingLinks(toPage) {
        var num_pages = Math.floor(toPage.total / toPage.per_page) + 1;
        var current = toPage.current - 1;

        jQuery("ul.pagination-top").empty();
        jQuery("ul.pagination-bottom").empty();

        var max_visible_pages = 6;
        var show_pages = [];
        //If there are more pages in this gallery than should be shown
        if (num_pages > max_visible_pages) {
            //Show the first 2 pages
            show_pages.push(0, 1);
            var show_last = true;
            //Show 10 pages around the current page
            var start = current - 1; var end = current + 1;
            //if the first or second page is selected, offset the show pages
            if (start < 2) {
                start += 2 - start;
                end += 2 - start;;
            }
            if (end > num_pages - 1) {
                end = num_pages - 1;
                start -= num_pages - 1;
                show_last = false;
            }
            
            for (var i = start;i <= end;i++) {
                show_pages.push(i);
            }

            //Show the last two pages
            if (show_last) show_pages.push(num_pages - 2, num_pages - 1);
        } else {
            for (var i = 0;i < num_pages;i++) {
                show_pages.push(i);
            }
        }

        last = 0;
        show_pages.forEach(function(i) {
            var elem = "";
            var isCurrent = i == current;
            var doEllipsis = false;
            if (last + 1 < i) {
                var mid = last + Math.floor(.5 * (i - last));
                
                if (mid + 1 == i) doEllipsis = false;
                else doEllipsis = true;

                elem += BuildPagingLink(mid, isCurrent, doEllipsis);
            }

            doEllipsis = false;
            elem += BuildPagingLink(i, isCurrent, doEllipsis);

            jQuery("ul.pagination-top").append(elem);
            jQuery("ul.pagination-bottom").append(elem);
            last = i;
        });
    }

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
                kcw_gallery.gallery.friendly_name = data.friendly_name;
                kcw_gallery.gallery.baseurl = data.baseurl;
                kcw_gallery.gallery.thumbsurl = data.thumbsurl;

                kcw_gallery.gallery.pages = [];
                console.log("init gallery cache for " + kcw_gallery.gallery.uid);
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

    function DisplayGalleryData(gpage) {
        
        var gal =  kcw_gallery.gallery;
        var page = gal.pages[gpage];

        ListActive = false;
        DisplayPagingLinks(gal);

        jQuery("a.kcw-gallery-list-home").text("List Page " + kcw_gallery.list.current);

        jQuery("div.kcw-gallery-title").text(gal.friendly_name);

        //Do the display stuff
        jQuery("ul.kcw-gallery-thumbs").empty();
        for (var i = 0;i < gal.pages[gpage].length;i++) {
            var elem = BuildThumbnail(gal.thumbsurl, gal.baseurl, page[i].name);
            jQuery("ul.kcw-gallery-thumbs").append(elem);
        }

        SetQueryParameters();



        jQuery("div.kcw-gallery-list-container").css({display: "none"});
        jQuery("div.kcw-gallery-list-container").animate({opacity: 0}, function (){
            FinishActionFor("div.kcw-gallery-display");
        });
    }

    function ShowGalleryPage_callback(data) {
        console.log("Updating gallery cache");
        StoreGalleryData(data);
        DisplayGalleryData(kcw_gallery.gallery.current-1);
    }

    function ShowGalleryPage(guid, gpage) {
        jQuery("div.kcw-gallery-display").css({display: "block"});
        ShowLoadingGif();
        //If no gallery is cached, the requested gallery differs from the cache, or the current gallery page does not exist
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


    /*
        Functions dealing with displaying the gallery list
    */
    function StoreListData(data) {
        //Generate the cache
        if (kcw_gallery.list == undefined || kcw_gallery.list.pages == undefined) {
               kcw_gallery.list = {};
               kcw_gallery.list.total = data.total;
               kcw_gallery.list.per_page = data.per_page;
               kcw_gallery.list.pages = [];
               console.log("init list cache");
        }
        kcw_gallery.list.current = data.page;
        kcw_gallery.list.pages[data.page-1] = data.items;
    }
    function BuildListItem(name, cat, guid, numfiles) {
        var html = "<li data-id='" + guid + "'>" +
                    "<div class='kcw-gallery-list-name-wrapper'>" +
                    "<a class='kcw-gallery-list-title'>" + name + "</a> ";
        if (cat != null) html += "<a class='kcw-gallery-list-category'>" + cat + "</a>";
            html += "</div><span>" + numfiles + "</span>" +
                    "<span class='dashicons dashicons-images-alt2'></span>" +
                    "</li>";
        return html;
    }
    function DisplayGalleryList(lpage) {
        var list = kcw_gallery.list;

        ListActive = true;
        DisplayPagingLinks(list);

        //Do the list display stuff
        jQuery("ul.kcw-gallery-list").empty();
        for (var i = 0;i < list.pages[lpage].length;i++) {
            var item = list.pages[lpage][i];
            if (item.visibility == "visible") {
                var cat = null;
                if (item.category != null) cat = item.nice_category;
                var elem = BuildListItem(item.nice_name, cat, item.uid, item.files);
                jQuery("ul.kcw-gallery-list").append(elem);
            }
        }

        SetQueryParameters(true);

        jQuery("div.kcw-gallery-display").css({display: "none"});
        jQuery("div.kcw-gallery-display").animate({opacity: 0}, function() {
            FinishActionFor("div.kcw-gallery-list-container");
        });
    }
    function ShowGalleryListPage_callback(data) {
        console.log("Updating list cache");
        StoreListData(data);
        DisplayGalleryList(kcw_gallery.list.current-1);

    }
    function ShowGalleryListPage(lpage) {
        jQuery("div.kcw-gallery-list-container").css({display: "block"});
        ShowLoadingGif();

        if (kcw_gallery.list == undefined || kcw_gallery.list.pages == undefined
         || kcw_gallery.list.pages[lpage-1] == undefined) {
            ApiCall("list", "/"+lpage, ShowGalleryListPage_callback);
         } else {
            kcw_gallery.list.current = lpage;
            DisplayGalleryList(lpage-1);
         }
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

    var isLoading = false;
    var loadingTimeout = null;
    //Display the loading gif on the given element
    function ShowLoadingGif() {
        isLoading = true;

        var pos = {};
        
        var lw = jQuery("div.kcw-gallery-loading-wrapper").outerWidth();
        var lh = jQuery("div.kcw-gallery-loading-wrapper").outerHeight();

        pos.top = jQuery(window).height() / 2;
        pos.top -= lw/2;

        pos.left = jQuery(window).width() / 2;
        pos.left -= lh/2;

        console.log(pos);

        jQuery("div.kcw-gallery-loading-wrapper").css({top: pos.top, left: pos.left});
        jQuery("div.kcw-gallery-loading-wrapper").animate({opacity: 1});

        loadingTimeout = setTimeout(function(){opacity: 0;
            jQuery("p.kcw-gallery-loading-status").text("Please Wait...");
            jQuery("p.kcw-gallery-loading-status").css({display: "block"});
            jQuery("p.kcw-gallery-loading-status").animate({opacity: 1}, 600);
            loadingTimeout = setTimeout(function(){
                jQuery("p.kcw-gallery-loading-status").animate({opacity: 0}, function() {
                    jQuery(this).text("Generating Thumbnails...");
                    jQuery(this).animate({opacity: 1});
                });
            }, 5000);
        }, 3000);
    }
    //Hide the loading gif
    function HideLoadingGif(){
        //Stop any loading timeouts from firing
        clearTimeout(loadingTimeout);

        isLoading = false;
        jQuery("div.kcw-gallery-loading-wrapper").animate({opacity: 0}, function() {
            jQuery(this).css({top: "-999px", left: "-999px"});
            jQuery("p.kcw-gallery-loading-status").text("");
            jQuery("p.kcw-gallery-loading-status").css({display: "none", opacity: 0});
        });
    }
    //Finish an action by scrolling to the active element and hiding the loading gif
    function FinishActionFor(elem) {
        var offset = 0;
        jQuery("html, body").animate({scrollTop: offset}, 400);

        HideLoadingGif();

        jQuery(elem).animate({opacity: 1}, 100);
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
        var list_page = getQueryStringParam("lpage");

        //Set current gallery list page if needed
        if (kcw_gallery.list == null) kcw_gallery.list = {};
        if (list_page != null) {
            kcw_gallery.list.current = parseInt(list_page);
        } else {
            kcw_gallery.list.current = 1;
        }

        //Display the correct paging links
        if (gallery_guid != null) {
            ListActive = false;
            DisplayPagingLinks(kcw_gallery.gallery);
            jQuery("a.kcw-gallery-list-home span.kcw-gallery-list-home-name").text("Gallery List Page " + kcw_gallery.list.current);
            jQuery("div.kcw-gallery-display").animate({opacity: 1});
        } else {
            ListActive = true;
            DisplayPagingLinks(kcw_gallery.list);
            jQuery("div.kcw-gallery-list-container").animate({opacity: 1});

        } 

        //Set other vars
        if (gallery_page == null) gallery_page = 1;
        if (search == null) search = "";
    }
    //Set variables into the query string
    function SetQueryParameters(exclude_gallery) {
        if (kcw_gallery.gallery == undefined || exclude_gallery != undefined) {
            var list_page = kcw_gallery.list.current;
            updateQueryStringParam("lpage", list_page);
            removeQueryStringParam("guid");
            removeQueryStringParam("gpage");
        } else {
            var gallery_guid = kcw_gallery.gallery.uid;
            var gallery_page = kcw_gallery.gallery.current;
            updateQueryStringParam("guid", gallery_guid);
            updateQueryStringParam("gpage", gallery_page);
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