jQuery(document).ready(function(){
    window.moo_RestUrl = moo_params.moo_RestUrl;
    window.moo_theme_setings = [];
    window.moo_mg_setings = {};
    window.nb_items_in_cart = 0;
    window.header_height = (typeof window.header_height != 'undefined' && window.header_height != null)?window.header_height:0;
    window.categoriesTopMargin = window.header_height;
    window.phoneCategoriesTopMargin = window.header_height;
    if (sooStoreOptions && sooStoreOptions.themeSettings){
        window.moo_theme_setings = sooStoreOptions.themeSettings;
        window.nb_items_in_cart  = sooStoreOptions.nbItemsInCart;
        //Change the categories topMargin and width
        if(window.moo_theme_setings.onePage_categoriesTopMargin != null) {
            window.categoriesTopMargin = parseInt(window.moo_theme_setings.onePage_categoriesTopMargin);
        }
        if(window.moo_theme_setings.onePage_phoneCategoriesTopMargin != null) {
            window.phoneCategoriesTopMargin = parseInt(window.moo_theme_setings.onePage_phoneCategoriesTopMargin);
        }
        if(jQuery(window).width()< 992){
            window.categoriesTopMargin = window.phoneCategoriesTopMargin;
        }
        window.width  = 267;
        mooGetCategories(MooLoadBaseStructure);
    } else {
        /* Load the theme settings then draw the layout a get the categories with the first five items */
        jQuery.get(moo_RestUrl+"moo-clover/v1/theme_settings/onePage", function (data) {
            if(data != null && data.settings != null) {
                window.moo_theme_setings = data.settings;
                window.nb_items_in_cart  = data.nb_items;
                //Change the categories topMargin and width
                if(window.moo_theme_setings.onePage_categoriesTopMargin != null) {
                    window.categoriesTopMargin = parseInt(window.moo_theme_setings.onePage_categoriesTopMargin);
                }
                if(window.moo_theme_setings.onePage_phoneCategoriesTopMargin != null) {
                    window.phoneCategoriesTopMargin = parseInt(window.moo_theme_setings.onePage_phoneCategoriesTopMargin);
                }
                if(jQuery(window).width()< 992){
                    window.categoriesTopMargin = window.phoneCategoriesTopMargin;
                }
                window.width  = 267;
            }
        }).done(function () {
            mooGetCategories(MooLoadBaseStructure);
        });
    }


    if (sooStoreOptions && sooStoreOptions.modifiersSettings){
        window.moo_mg_setings = sooStoreOptions.modifiersSettings
    } else {
        /* Load the modifiers settings and save them on a window's variable */
        jQuery.get(moo_RestUrl+"moo-clover/v1/mg_settings", function (data) {
            if(data != null && data.settings != null) {
                window.moo_mg_setings = data.settings;
            }
        });
    }

    /* a listener when scrolling to fix the tha category section */
    jQuery(window).scroll(function(){
        if(!jQuery('#moo-onlineStore-items').offset()){
            return;
        }
        var container_top    = jQuery('#moo_OnlineStoreContainer').offset().top;
        var menu_height      = jQuery('#moo-menu-navigation').height();
        var firstCategoryPos = jQuery('#moo-onlineStore-items').offset().top;
       if(jQuery('#moo-onlineStore-items .moo-menu-category').last().offset()){
           var lastCategoryPos  = jQuery('#moo-onlineStore-items .moo-menu-category').last().offset().top + jQuery('#moo-onlineStore-items .moo-menu-category').last().height();
        } else {
           var lastCategoryPos  = jQuery('#moo-onlineStore-items .moo-menu-category').last().height();

        }
        var scrollTop = jQuery(window).scrollTop();
        if(jQuery('footer').offset()){
            var footerPos  = jQuery('footer').offset().top;
        } else {
            var footerPos  = 0;
        }
        if( jQuery(window).width() < 992 ) {
            if (scrollTop > firstCategoryPos ) {
                if(!jQuery(".moo-stick-to-content").hasClass('moo-mobile-fixed')){
                    jQuery(".moo-stick-to-content").addClass('moo-mobile-fixed').width('100%').css("top",window.categoriesTopMargin+'px');
                    mooExpColCatMenu();
                    jQuery(".moo-stick-to-content").attr('onclick', 'mooExpColCatMenu();');
                    jQuery(window).scrollTop(scrollTop-menu_height);
                    window.mooScrollAdded = true;
                }
                links = jQuery('ul.moo-nav li a');
                links.each(
                    function () {
                        var currentTop = jQuery(window).scrollTop();
                        var page = jQuery(this).attr('href');
                        var elemTop     = jQuery(page).offset().top;
                        var elemBottom     = elemTop + jQuery(page).height();
                        if((currentTop >= elemTop) &&(currentTop <= elemBottom))
                            jQuery('.moo-choose-category').html(jQuery(jQuery(this).attr('href')).find('.moo-title').text()+'<i class="fas fa-chevron-down" aria-hidden="true"></i>');
                    }
                );
            } else {
                jQuery(".moo-stick-to-content").removeClass('moo-fixed');
                jQuery(".moo-stick-to-content").removeClass('moo-mobile-fixed');
                jQuery(".moo-stick-to-content").removeAttr('onclick');
                jQuery('.moo-choose-category').html(mooObjectL10n.chooseACategory+'<i class="fas fa-chevron-down" aria-hidden="true"></i>');
                if(jQuery('.moo-nav').css('display') === 'none') {
                    mooExpColCatMenu();
                }
                if(window.mooScrollAdded) {
                    jQuery(window).scrollTop(container_top + jQuery('#moo-menu-navigation').height()+30);
                    window.mooScrollAdded = false;
                }
            }

        } else {
            if(footerPos>0){
                if (scrollTop > (container_top-header_height) && (scrollTop+menu_height) < footerPos ) {
                    jQuery(".moo-stick-to-content").addClass('moo-fixed').width(window.width).css("top",window.categoriesTopMargin+'px');
                   // jQuery(".moo-stick-to-content").css('margin-top',0);
                } else {
                    jQuery(".moo-stick-to-content").removeClass('moo-fixed');
                    jQuery(".moo-stick-to-content").removeClass('moo-mobile-fixed');
                    jQuery(".moo-stick-to-content").removeAttr('onclick');
                    jQuery('.moo-choose-category').html(mooObjectL10n.chooseACategory+'<i class="fas fa-chevron-down" aria-hidden="true"></i>');
                    if(jQuery('.moo-nav').css('display') === 'none') {
                        mooExpColCatMenu();
                    }
                }
            } else {
                if (scrollTop > (container_top-header_height)) {
                    jQuery(".moo-stick-to-content").addClass('moo-fixed').width(window.width).css("top",window.categoriesTopMargin+'px');
                } else {
                    jQuery(".moo-stick-to-content").removeClass('moo-fixed');
                    jQuery(".moo-stick-to-content").removeClass('moo-mobile-fixed');
                    jQuery(".moo-stick-to-content").removeAttr('onclick');
                    jQuery('.moo-choose-category').html(mooObjectL10n.chooseACategory+'<i class="fas fa-chevron-down" aria-hidden="true"></i>');
                    if(jQuery('.moo-nav').css('display') === 'none') {
                        mooExpColCatMenu();
                    }
                }
            }

        }
    });
});

function MooLoadBaseStructure(data, withButton) {
    var elm_id = '#moo_OnlineStoreContainer';
    var html = '<div class="moo-row">'+
        '<div  class="moo-is-sticky moo-new-icon" onclick="mooShowCart(event)">' +
        '<div class="moo-new-icon__count" id="moo-cartNbItems">'+((window.nb_items_in_cart>0)?window.nb_items_in_cart:'')+'</div>' +
        '<div class="moo-new-icon__cart">' +
        '</div></div>'+
        '<div id="MooLoadingSection" style="text-align: center;font-size: 20px;display:none">'+mooObjectL10n.loading+'</div>'+
        '</div>'+
        '<div class="moo-row">'+
        '<div class="moo-col-md-3" id="moo-onlineStore-categories">'+
        '</div>'+
        '<div class="moo-col-md-9" id="moo-onlineStore-items">'+
        '</div>'+
        '</div>';
    /* Adding the structure the html page */
    jQuery(elm_id).html(html);
    moo_renderCategories(data, withButton);
}

function MooSetLoading() {
    jQuery('#MooLoadingSection').show();
}

function MooCLickOnCategory(event,elm)
{
    event.preventDefault();
    var useAnimate = true;
    var category = jQuery(elm).attr('href');
    var categoryTop= jQuery(category).offset().top;
    var speed = 750;
    if( jQuery(window).width() < 992 ) {
        var menu_height   = jQuery('#moo-menu-navigation').height();
        if(jQuery("#moo-menu-navigation").hasClass("moo-mobile-fixed")){
            var pos = categoryTop  + 5;
            if(useAnimate){
                jQuery('html, body').animate( { scrollTop: pos }, speed ); // Go
            } else {
                window.scrollTo(0,pos);
            }
        } else {
            var diff = categoryTop - menu_height;
            if(diff < menu_height){
                jQuery(window).scrollTop(categoryTop + 5);
            } else {
                var pos = categoryTop - menu_height + 5;
                if(useAnimate){
                    jQuery('html, body').animate( { scrollTop: pos}, speed ); // Go
                } else {
                    window.scrollTo(0,pos);
                }
            }
        }
        jQuery(category).focus();
        return false;
    }  else {
        var pos = categoryTop + 2 + window.categoriesTopMargin ;
        if(useAnimate){
            jQuery('html, body').animate( { scrollTop: pos }, speed ); // Go
        } else {
            window.scrollTo(0,pos);
        }
        jQuery(category).focus();
        return false;
    }

}

//get all the categories of the store
function mooGetCategories(callback) {
    let endpoint = moo_RestUrl + "moo-clover/v1/categories?expand=";

    if(window.moo_theme_setings.onePage_show_more_button === 'off') {
        endpoint += "all_items";
    } else {
        endpoint += "five_items";
    }
    jQuery.get(endpoint, function (data) {
        if(data!=null && data.length>0) {
            // moo_renderCategories(data,true);
            callback(data,true);
        } else {
            var element = document.getElementById("moo_OnlineStoreContainer");
            var html     = '<div style="text-align: center;font-size: 20px;margin: 30px;">'+mooObjectL10n.noCategory+'</div>';
            jQuery(element).html(html);
            jQuery('#MooLoadingSection').hide();
        }
    });

}
//Render all categories to html element and insert it into the page
function moo_renderCategories($cats, withButton)
{
    var element = document.getElementById("moo-onlineStore-categories");
    var html     = '<nav id="moo-menu-navigation" class="moo-stick-to-content" tabindex="0" aria-label="List of categories" >';
        html     += '<div class="moo-choose-category" aria-label="Here you can choose a category of items">'+mooObjectL10n.chooseACategory+'</div>';
        html     += '<ul class="moo-nav moo-nav-menu moo-bg-dark moo-dark" role="menu">';

    for(var i in $cats){
        var category = $cats[i];
        if(typeof category !== 'object')
            continue;
        if(typeof attr_categories !== 'undefined' && attr_categories !== undefined && attr_categories !== null && typeof attr_categories === 'object') {
            if(attr_categories.indexOf(category.uuid.toUpperCase()) === -1){
                continue;
            }
        }
        if(category.items.length > 0 ) {
            html +='<li role="listitem"><a role="menuitem" href="#cat-'+category.uuid.toLowerCase()+'" onclick="MooCLickOnCategory(event,this)">'+category.name+'</a></li>';
            moo_renderItems(category,withButton);
        }

    }
    html    += "</ul></nav>";
    jQuery(element).html(html).promise().done(function() {
       window.width = jQuery('#moo_OnlineStoreContainer').width() - jQuery('.moo-menu-category').width();
       window.width = (jQuery('#moo_OnlineStoreContainer').width()+30) * 0.25;
       var cart_btn =  '<div class="moo-col-md-12" style="text-align: center;">'+
                       '<a href="#" class="moo-btn moo-btn-lg moo-btn-primary" onclick="mooShowCart(event)" tabindex="0" aria-label="View your cart" aria-haspopup="true">'+mooObjectL10n.viewCart+'</a>'+
                       '</div>';
       jQuery("#moo-onlineStore-items").append(cart_btn);
       jQuery('#MooLoadingSection').hide();

       var hash = window.location.hash;
       if (hash != "") {
            var top = (jQuery(hash).offset() != null)?jQuery(hash).offset().top:""; //Getting Y of target element
            window.scrollTo(0, top);
        }

    });
}

//Render items of the selected category to html element and insert it into the page
function moo_renderItems(category, withButton)
{
    var imageCatUrl = null;
    if(category.image_url !== null && category.image_url !== "") {
        imageCatUrl = category.image_url;
    }
    var element = document.getElementById("moo-onlineStore-items");
    var html    =   '<div id="cat-'+category.uuid.toLowerCase()+'" class="moo-menu-category" tabindex="0" aria-label="The category '+category.name+'">'+
                    '<div class="moo-menu-category-title">'+
                    '   <div class="moo-bg-image" style="background-image: url(&quot;'+((category.image_url!=null)?category.image_url:"")+'&quot;);"></div>'+
                    '   <div class="moo-title" tabindex="0" role="heading" aria-level="1">'+category.name+'</div>';
    if(category.description){
        html +='   <div class="moo-category-description" tabindex="0">'+category.description+'</div>';
    }
                html +='</div>'+
                    '<div class="moo-menu-category-content" id="moo-items-for-'+category.uuid.toLowerCase()+'">';

    for(var i in category.items){
        var item = category.items[i];
        if(typeof item != 'object')
            continue;
        var item_price = parseFloat(item.price);
            item_price = item_price/100;
            item_price = formatPrice(item_price.toFixed(2));
            if(item.price > 0 && item.price_type == "PER_UNIT")
                item_price += '/'+item.unit_name;

        html += '<div class="moo-menu-item moo-menu-list-item">'+
                ' <div class="moo-row">';
        if(item.image != null && item.image.url != null && item.image.url != "") {
            html += '    <div class="moo-col-lg-2 moo-col-md-2 moo-col-sm-2 moo-col-xs-12" role="img" tabindex="0" aria-label="The image of the item" >'+
                    '<a href="'+item.image.url+'" data-effect="mfp-zoom-in" tabindex="-1"><img alt="image of '+item.name+'" src="'+item.image.url+'" class="moo-img-responsive moo-image-zoom"></a>'+
                    '    </div>'+
                    '    <div class="moo-col-lg-6 moo-col-md-6 moo-col-sm-9 moo-col-xs-12">';
            if(item.description){
                html +=  '<div class="moo-item-name moo-item-name-bold" tabindex="0"><span>'+item.name+'</span></div>';
                html +=  '         <span class="moo-item-description moo-text-muted moo-text-sm" tabindex="0" >'+item.description+'</span>';
            } else {
                html +=  '<div class="moo-item-name moo-item-name-line-height" tabindex="0"><span>'+item.name+'</span></div>';

            }
            html += '    </div>';
        } else {
            html += '    <div class="moo-col-lg-8 moo-col-md-8 moo-col-sm-12 moo-col-xs-12">';
            if(item.description){
                html += '         <div class="moo-item-name moo-item-name-bold" tabindex="0" ><span>'+item.name+'</span></div>';
                html += '         <span class="moo-item-description moo-text-muted moo-text-sm" tabindex="0" >'+item.description+'</span>';
            } else {
                html += '         <div class="moo-item-name moo-item-name-line-height" tabindex="0" ><span>'+item.name+'</span></div>';
            }
            html += '    </div>';
        }
        if(parseFloat(item.price) == 0) {
            html += '    <div class="moo-col-lg-4 moo-col-md-4 moo-col-sm-12 moo-col-xs-12 moo-text-sm-right">'+
                '    <span></span>';
        } else {
            html += '    <div class="moo-col-lg-4 moo-col-md-4 moo-col-sm-12 moo-col-xs-12 moo-text-sm-right">'+
                '    <span class="moo-price" tabindex="0" aria-label="The item price is $'+item_price+'">$'+item_price+'</span>';
        }

        if(item.stockCount == "out_of_stock") {
            html += '<button class="moo-btn-sm moo-hvr-sweep-to-top" aria-label="The item '+item.name+' is out of stock">'+mooObjectL10n.outOfStock+'</button>';
        } else {
            if(category.available === false) {
                html += '<button class="moo-btn-sm moo-hvr-sweep-to-top" aria-label="The item '+item.name+' is not available yet">'+mooObjectL10n.notAvailableYet+'</button>';
            } else {
                //Checking the Qty window show/hide and add add to cart button
                if(window.moo_theme_setings.onePage_qtyWindow != null && window.moo_theme_setings.onePage_qtyWindow == "on") {
                    if(item.has_modifiers) {
                        if(window.moo_theme_setings.onePage_qtyWindowForModifiers != null && window.moo_theme_setings.onePage_qtyWindowForModifiers == "on") {
                            html += '<button class="moo-btn-sm moo-hvr-sweep-to-top" onclick="mooOpenQtyWindow(event,\''+item.uuid+'\',\''+item.stockCount+'\',moo_clickOnOrderBtnFIWM)" aria-label="Choose Qty & Options for the item '+item.name+'">'+mooObjectL10n.chooseOptionsAndQty+'</button>';
                        } else {
                            if(window.moo_mg_setings.qtyForAll)
                                html += '<button class="moo-btn-sm moo-hvr-sweep-to-top" onclick="moo_clickOnOrderBtnFIWM(event,\''+item.uuid+'\',1)" aria-label="Choose Options & Qty for the item '+item.name+'" >'+mooObjectL10n.chooseOptionsAndQty+'</button>';
                            else
                                html += '<button class="moo-btn-sm moo-hvr-sweep-to-top" onclick="moo_clickOnOrderBtnFIWM(event,\''+item.uuid+'\',1)" aria-label="Choose Options for the item '+item.name+'">'+mooObjectL10n.chooseOptions+'</button>';
                        }
                    } else {
                        html += '<button class="moo-btn-sm moo-hvr-sweep-to-top" onclick="mooOpenQtyWindow(event,\''+item.uuid+'\',\''+item.stockCount+'\',moo_clickOnOrderBtn)" aria-label="Add to cart the item '+item.name+'">'+mooObjectL10n.addToCart+'</button>';
                    }

                } else {
                    if(item.has_modifiers) {
                        if(window.moo_mg_setings.qtyForAll)
                            html += '<button class="moo-btn-sm moo-hvr-sweep-to-top" onclick="moo_clickOnOrderBtnFIWM(event,\''+item.uuid+'\',1)" aria-label="Choose Options & Qty for the item '+item.name+'">'+mooObjectL10n.chooseOptionsAndQty+'</button>';
                        else
                            html += '<button class="moo-btn-sm moo-hvr-sweep-to-top" onclick="moo_clickOnOrderBtnFIWM(event,\''+item.uuid+'\',1)" aria-label="Choose Options for the item '+item.name+'">'+mooObjectL10n.chooseOptions+'</button>';
                    } else {
                        html += '<button class="moo-btn-sm moo-hvr-sweep-to-top" onclick="moo_clickOnOrderBtn(event,\''+item.uuid+'\',1)" aria-label="Add to cart the item '+item.name+'">'+mooObjectL10n.addToCart+'</button>';
                    }

                }
            }
        }

        html += '</div>';
        if(item.has_modifiers)
            html += '<div class="moo-col-lg-12 moo-col-md-12 moo-col-sm-12 moo-col-xs-12 moo-modifiersContainer-for-'+item.uuid+'"></div>';
        html += '</div>'+
                '</div>';
    }
    category.name = category.name.replace("'","");
    category.name = category.name.replace('"',"");

    if(
        withButton &&
        category.items.length == 5 &&
        window.moo_theme_setings.onePage_show_more_button !== 'off'
    ) {
        html += '<div class="moo-menu-item moo-menu-list-item"><div class="moo-row moo-align-items-center"><a href="#" class="moo-bt-more moo-show-more" onclick="mooClickOnLoadMoreItems(event,\''+category.uuid+'\',\''+category.name+'\','+category.available+')" aria-label="show more items in the category '+category.name+'" > '+mooObjectL10n.showMore+' <i class="fas fa-chevron-down" aria-hidden="true" style=" display: block;"></i></a></div></div>';
    }
    html    += "</div>";

    jQuery(element).append(html).promise().then(function () {
        moo_ZoomOnImages();
    });
}

function mooClickOnLoadMoreItems(event,cat_id,cat_name,cat_available)
{
    event.preventDefault();
    jQuery(event.target).html(mooObjectL10n.loading);
    var html = '';
    /*
    swal({
        html:
        '<div class="moo-msgPopup" tabindex="-1" role="progressbar">Loading '+cat_name+'\'s items</div>' +
        '<img src="'+ moo_params['plugin_img']+'/loading.gif" class="moo-imgPopup"/>',
        showConfirmButton: false
    });
    */

    jQuery.get(moo_RestUrl+"moo-clover/v1/categories/"+cat_id+"/items", function (data) {
        if(data != null && data.items != null && data.items.length > 0)
        {
            var count = data.items.length;
            var html ='';
            for(var i in data.items){
                var item = data.items[i];
                if(typeof item != 'object')
                    continue;
                var item_price = parseFloat(item.price);
                item_price = item_price/100;
                item_price = item_price.toFixed(2);

                if(item.price > 0 && item.price_type == "PER_UNIT")
                    item_price += '/'+item.unit_name;

                html += '<div class="moo-menu-item moo-menu-list-item" >'+
                    ' <div class="moo-row">';

                if(item.image != null && item.image.url != null && item.image.url != "") {
                    html += '    <div class="moo-col-lg-2 moo-col-md-2 moo-col-sm-2 moo-col-xs-12" role="img" tabindex="0" aria-label="The image of the item" >'+
                        '<a href="'+item.image.url+'" data-effect="mfp-zoom-in" tabindex="-1"><img alt="image of '+item.name+'" src="'+item.image.url+'" class="moo-img-responsive moo-image-zoom"></a>'+
                        '    </div>'+
                        '    <div class="moo-col-lg-6 moo-col-md-6 moo-col-sm-9 moo-col-xs-12">';
                    if(item.description){
                        html +=  '<div class="moo-item-name moo-item-name-bold" tabindex="0"><span>'+item.name+'</span></div>';
                        html +=  '         <span class="moo-item-description moo-text-muted moo-text-sm" tabindex="0" >'+item.description+'</span>';
                    } else {
                        html +=  '<div class="moo-item-name moo-item-name-line-height" tabindex="0"><span>'+item.name+'</span></div>';

                    }
                    html += '    </div>';
                } else {
                    html += '    <div class="moo-col-lg-8 moo-col-md-8 moo-col-sm-12 moo-col-xs-12">';
                    if(item.description){
                        html += '         <div class="moo-item-name moo-item-name-bold" tabindex="0" ><span>'+item.name+'</span></div>';
                        html += '         <span class="moo-item-description moo-text-muted moo-text-sm" tabindex="0" >'+item.description+'</span>';
                    } else {
                        html += '         <div class="moo-item-name moo-item-name-line-height" tabindex="0" ><span>'+item.name+'</span></div>';

                    }
                    html += '    </div>';
                }
                if(parseFloat(item.price) == 0) {
                    html += '    <div class="moo-col-lg-4 moo-col-md-4 moo-col-sm-12 moo-col-xs-12 moo-text-sm-right">'+
                        '    <span></span>';
                } else {
                    html += '    <div class="moo-col-lg-4 moo-col-md-4 moo-col-sm-12 moo-col-xs-12 moo-text-sm-right">'+
                        '    <span class="moo-price" tabindex="0" aria-label="The item price is $'+item_price+'">$'+item_price+'</span>';
                }

                if(item.stockCount == "out_of_stock") {
                    html += '<button class="moo-btn-sm moo-hvr-sweep-to-top" aria-label="The item '+item.name+' is out of stock">'+mooObjectL10n.outOfStock+'</button>';
                } else {
                    if(cat_available === false) {
                        html += '<button class="moo-btn-sm moo-hvr-sweep-to-top" aria-label="The item '+item.name+' is not available yet">'+mooObjectL10n.notAvailableYet+'</button>';
                    } else {
                        //Checking the Qty window show/hide and add add to cart button
                        if(window.moo_theme_setings.onePage_qtyWindow != null && window.moo_theme_setings.onePage_qtyWindow == "on") {
                            if(item.has_modifiers) {
                                if(window.moo_theme_setings.onePage_qtyWindowForModifiers != null && window.moo_theme_setings.onePage_qtyWindowForModifiers == "on") {
                                    html += '<button class="moo-btn-sm moo-hvr-sweep-to-top" onclick="mooOpenQtyWindow(event,\''+item.uuid+'\',\''+item.stockCount+'\',moo_clickOnOrderBtnFIWM)" aria-label="Choose Qty & Options for the item '+item.name+'">'+mooObjectL10n.chooseOptionsAndQty+'</button>';
                                } else {
                                    if(window.moo_mg_setings.qtyForAll)
                                        html += '<button class="moo-btn-sm moo-hvr-sweep-to-top" onclick="moo_clickOnOrderBtnFIWM(event,\''+item.uuid+'\',1)" aria-label="Choose Options & Qty for the item '+item.name+'" >'+mooObjectL10n.chooseOptionsAndQty+'</button>';
                                    else
                                        html += '<button class="moo-btn-sm moo-hvr-sweep-to-top" onclick="moo_clickOnOrderBtnFIWM(event,\''+item.uuid+'\',1)" aria-label="Choose Options for the item '+item.name+'">'+mooObjectL10n.chooseOptions+'</button>';
                                }
                            } else {
                                html += '<button class="moo-btn-sm moo-hvr-sweep-to-top" onclick="mooOpenQtyWindow(event,\''+item.uuid+'\',\''+item.stockCount+'\',moo_clickOnOrderBtn)" aria-label="Add to cart the item '+item.name+'">'+mooObjectL10n.addToCart+'</button>';
                            }

                        } else {
                            if(item.has_modifiers) {
                                if(window.moo_mg_setings.qtyForAll)
                                    html += '<button class="moo-btn-sm moo-hvr-sweep-to-top" onclick="moo_clickOnOrderBtnFIWM(event,\''+item.uuid+'\',1)" aria-label="Choose Options & Qty for the item '+item.name+'">'+mooObjectL10n.chooseOptionsAndQty+'</button>';
                                else
                                    html += '<button class="moo-btn-sm moo-hvr-sweep-to-top" onclick="moo_clickOnOrderBtnFIWM(event,\''+item.uuid+'\',1)" aria-label="Choose Options for the item '+item.name+'">'+mooObjectL10n.chooseOptions+'</button>';
                            } else {
                                html += '<button class="moo-btn-sm moo-hvr-sweep-to-top" onclick="moo_clickOnOrderBtn(event,\''+item.uuid+'\',1)" aria-label="Add to cart the item '+item.name+'">'+mooObjectL10n.addToCart+'</button>';
                            }

                        }
                    }
                }

                html += '</div>';
                if(item.has_modifiers)
                    html += '<div class="moo-col-lg-12 moo-col-md-12 moo-col-sm-12 moo-col-xs-12 moo-modifiersContainer-for-'+item.uuid+'"></div>';
                html += '</div>'+
                    '</div>';

                if(!--count) {
                    jQuery("#moo-items-for-"+cat_id.toLowerCase()).html(html).promise().then(function () {
                        moo_ZoomOnImages();
                        jQuery("#"+cat_id.toLowerCase()).focus();
                    });
                    swal.close();
                }
            }
        } else {
            swal.close();
            var html     = mooObjectL10n.noItemsInCategory;
            jQuery("#moo-items-for-"+cat_id.toLowerCase()).html(html);
        }
    });
}
function mooOpenQtyWindow(event,item_id,stockCount,callback)
{
    event.preventDefault();
    var inputOptions = new Promise(function (resolve) {
        if(stockCount == "not_tracking_stock" ||  stockCount == "tracking_stock" ) {
            resolve({
                "1":"1","2":"2","3":"3","4":"4","5":"5","6":"6","7":"7","8":"8","9":"9","10":"10","custom":mooObjectL10n.customQuantity
            })
        } else {
            var options = {};
            var QtyMax = (parseInt(stockCount)>10)?10:parseInt(stockCount);
            var count = QtyMax;
            for(var $i = 1;$i<=QtyMax;$i++) {
                options[$i.toString()] = $i.toString();
                if(!--count) {
                    options["custom"] = mooObjectL10n.customQuantity;
                    resolve(options)
                }
            }
        }
    });
    swal({
        title: mooObjectL10n.selectTheQuantity,
        showLoaderOnConfirm: true,
        confirmButtonText: mooObjectL10n.add,
        cancelButtonText : mooObjectL10n.close,
        input: 'select',
        inputClass: 'moo-form-control',
        inputOptions: inputOptions,
        showCancelButton: true,
        preConfirm: function (value) {
            return new Promise(function (resolve, reject) {
                if(value=="custom")
                    mooOpenCustomQtyWindow(event,item_id,callback);
                else
                    callback(event,item_id,value);

            });
        }
    }).then(function () {},function (dismiss) {});
}
function mooOpenCustomQtyWindow(event,item_id,callback)
{
    swal({
        title: mooObjectL10n.enterTheQuantity,
        input: 'text',
        showCancelButton: true,
        showLoaderOnConfirm: true,
        inputValidator: function (value) {
            return new Promise(function (resolve, reject) {
                if (value != "" && parseInt(value)>0) {
                    callback(event,item_id,parseInt(value));
                } else {
                    reject(mooObjectL10n.writeNumber)
                }
            })
        }
    }).then(function () {},function () {})
}
//Click on order button for items without modifiers
function moo_clickOnOrderBtn(event,item_id,qty)
{
    var body = {
        item_uuid:item_id,
        item_qty:qty,
        item_modifiers:{}
    };
    /* Add to cart the item */
    jQuery.post(moo_RestUrl+"moo-clover/v1/cart", body,function (data) {
        if(data != null)
        {
            if(data.status == "error") {
                swal({
                    title:data.message,
                    type:"error"
                });
            } else {
                mooShowAddingItemResult(data);
            }
        }
        else
        {
            swal({
                title:mooObjectL10n.notAddedToCart,
                type:"error"
            });
        }
    }).fail(function ( data ) {
        swal({
            title:mooObjectL10n.notAddedToCart,
            text:mooObjectL10n.checkInternetConnection,
            type:"error"
        });
        console.log(data);
    }).done(function ( data ) {
        if(typeof data.nb_items != "undefined")
            jQuery("#moo-cartNbItems").text(data.nb_items)
    });

}
//Click on order button for an item with modifiers
function moo_clickOnOrderBtnFIWM(event,item_id,qty)
{
    event.preventDefault();
    //Change button content to loading
    var target = event.target;
    var old_text = jQuery(target).text();
    jQuery(target).text(mooObjectL10n.loadingOptions);

    jQuery.get(moo_RestUrl+"moo-clover/v1/items/"+item_id, function (data) {
        //Change butn text
        jQuery(target).text(old_text);

        if(data != null) {
            if(data.modifier_groups.length > 0) {
                if(typeof mooBuildModifiersPanel == "function") {
                    if(Object.keys(window.moo_mg_setings).length > 0) {
                        mooBuildModifiersPanel(data.modifier_groups,item_id,qty,window.moo_mg_setings);
                    } else {
                        mooBuildModifiersPanel(data.modifier_groups,item_id,qty,null);
                    }
                    swal.close();
                } else {
                    swal(mooObjectL10n.tryAgain,mooObjectL10n.checkInternetConnection,'error');
                }

            }
            else
                moo_clickOnOrderBtn(event,item_id,qty);
        } else {
            //Change button text
            jQuery(target).text(old_text);
            swal({
                title: mooObjectL10n.tryAgain,
                text: mooObjectL10n.cannotLoadItemOptions,
                type: "error",
                confirmButtonText: "ok"
            });
        }
    }).fail(function (data) {
        //Change button text
        jQuery(target).text(old_text);
        swal({
            title: mooObjectL10n.tryAgain,
            text: mooObjectL10n.cannotLoadItemOptions,
            type: "error",
            confirmButtonText: "ok"
        });
    });

}


/* Cart functions */
function mooShowCart(event) {
    if(typeof event != "undefined")
        event.preventDefault();

    var element = jQuery("#moo-panel-cart>.moo-panel-cart-container>.moo-panel-cart-content");
    var cart_element =jQuery("#moo-panel-cart>.moo-panel-cart-container>.moo-panel-cart-content") ;

    swal({
        html:
        '<div class="moo-msgPopup">'+ mooObjectL10n.loadingCart+'</div>' +
        '<img src="'+ moo_params['plugin_img']+'/loading.gif" class="moo-imgPopup"/>',
        showConfirmButton: false
    });

    var cart_html = '<div class="moo-row moo-cart-heading" tabindex="0" aria-label="Your cart details">'+
        '<div class="moo-col-lg-6 moo-col-md-6 moo-col-sm-5 moo-col-xs-5 moo-cart-line-itemName" tabindex="-1">'+ mooObjectL10n.item+'</div>'+
        '<div class="moo-col-lg-2 moo-col-md-2 moo-col-sm-2 moo-col-xs-2 moo-cart-line-itemQty" tabindex="-1">'+ mooObjectL10n.qty+'</div>'+
        '<div class="moo-col-lg-2 moo-col-md-2 moo-col-sm-3 moo-col-xs-3 moo-cart-line-itemPrice" tabindex="-1">'+ mooObjectL10n.subtotal+'</div>'+
        '<div class="moo-col-lg-2 moo-col-md-2 moo-col-sm-2 moo-col-xs-2 moo-cart-line-itemActions" tabindex="-1">'+ mooObjectL10n.edit+'</div>'+
        '</div>'+
        '<div class="moo-cart-container">';

    jQuery.get(moo_RestUrl+"moo-clover/v1/cart", function (data) {
        if(typeof data != 'undefined' && data != null) {
            cart_html += '<div class="moo-row moo-cart-content">';
            if(data.items != null && Object.keys(data.items).length>0) {
                jQuery.each(data.items,function(line_id,line) {
                    var price = parseFloat(line.item.price)/100;
                    var line_price = price * line.qty;

                    cart_html+='<div class="moo-row moo-cart-line"  >'+
                        '<div class="moo-col-lg-6 moo-col-md-6 moo-col-sm-5 moo-col-xs-5 moo-cart-line-itemName" tabindex="0">';
                    //check if cart line contain modifiers
                    if(line.modifiers.length > 0)
                    {
                        cart_html += line.item.name;
                        cart_html += '<div class="moo-cart-line-modifiers"  tabindex="0" aria-label="item selected options">';
                        for(var $j=0;$j<line.modifiers.length;$j++)
                        {
                            cart_html += ''+line.modifiers[$j].qty;
                            cart_html += '<span  tabindex="0">x '+line.modifiers[$j].name;
                            if(line.modifiers[$j].price>0) {
                                line_price += ((parseFloat(line.modifiers[$j].price)/100)*(parseInt(line.modifiers[$j].qty)))*line.qty;
                                cart_html += ' <span  style="color: #484848;">$'+(parseFloat(line.modifiers[$j].price)/100).toFixed(2)+"</span>";
                            }
                            cart_html += '</span><br/>';
                        }
                        cart_html += '</div>';

                    } else {
                        cart_html += line.item.name;
                    }
                    cart_html+='</div>';
                    cart_html+='<div class="moo-col-lg-2 moo-col-md-2 moo-col-sm-2 moo-col-xs-2  moo-cart-line-itemQty" aria-label="The quantity is '+line.qty+'" tabindex="0">'+line.qty+'</div>';
                    cart_html+= '<div class="moo-col-lg-2 moo-col-md-2 moo-col-sm-3 moo-col-xs-3  moo-cart-line-itemPrice" tabindex="0">$'+formatPrice(line_price.toFixed(2))+'</div>';
                    cart_html+= '<div class="moo-col-lg-2 moo-col-md-2 moo-col-sm-2 moo-col-xs-2  moo-cart-line-itemActions">';
                    if( ! window.moo_theme_setings
                        || ! window.moo_theme_setings.onePage_allowspecialinstructionforitems
                        ||  window.moo_theme_setings.onePage_allowspecialinstructionforitems === "on"
                    ){
                        cart_html+=  '<i  tabindex="0" role="button" aria-label="add or edit special instruction" style="cursor: pointer;margin-right: 10px;margin-left: 10px" class="fas fa-pencil-square" aria-hidden="true" onclick="mooUpdateSpecialInsinCart(\''+line_id+'\',\''+line.special_ins+'\')"></i>';
                    }

                    cart_html+=  '<i tabindex="0" role="button" aria-label="remove this item from your cart" style="cursor: pointer;margin-right: 10px;margin-left: 10px" class="fas fa-trash" aria-hidden="true" onclick="mooRemoveLineFromCart(\''+line_id+'\')"></i>';
                    cart_html+= '</div></div>';
                });
                cart_html += '</div>';
                //Set teh cart total
                if(data.totals !== null && data.totals !== false) {
                    cart_html +=' <div class="moo-row moo-cart-totals" tabindex="0" aria-label="Your cart totals">'+
                        '<div class="moo-row moo-cart-total moo-cart-total-subtotal">'+
                        '<div class="moo-col-lg-9 moo-col-md-9 moo-col-sm-7 moo-col-xs-7 moo-cart-total-label"  tabindex="0" aria-label="subtotal">'+ mooObjectL10n.subtotal+'</div>'+
                        '<div class="moo-col-lg-3 moo-col-md-3 moo-col-sm-5 moo-col-xs-5  moo-cart-total-price" tabindex="0">$'+mooOpformatCentPrice(data.totals.sub_total)+'</div>'+
                        '</div>';
                    if(data.totals.coupon_value > 0){
                        cart_html +='<div class="moo-row moo-cart-total moo-cart-total-subtotal" style="color: green;">'+
                            '<div class="moo-col-lg-9 moo-col-md-9 moo-col-sm-7 moo-col-xs-7 moo-cart-total-label"  tabindex="0" aria-label="coupon">' + data.totals.coupon_name + '</div>'+
                            '<div class="moo-col-lg-3 moo-col-md-3 moo-col-sm-5 moo-col-xs-5  moo-cart-total-price" tabindex="0">- $' + mooOpformatCentPrice(data.totals.coupon_value) + '</div>'+
                            '</div>';
                    }
                    cart_html += '<div class="moo-row moo-cart-total moo-cart-total-tax">'+
                        '<div class="moo-col-lg-9 moo-col-md-9 moo-col-sm-7 moo-col-xs-7 moo-cart-total-label"  tabindex="0" aria-label="taxes">'+ mooObjectL10n.tax +'</div>'+
                        '<div class="moo-col-lg-3 moo-col-md-3 moo-col-sm-5 moo-col-xs-5  moo-cart-total-price" tabindex="0" >$'+mooOpformatCentPrice(data.totals.total_of_taxes)+'</div>'+
                        '</div>'+
                        '<div class="moo-row moo-cart-total moo-cart-total-grandtotal">'+
                        '<div class="moo-col-lg-9 moo-col-md-9 moo-col-sm-6 moo-col-xs-6 moo-cart-total-label" tabindex="0" aria-label="total">'+ mooObjectL10n.total +'</div>'+
                        '<div class="moo-col-lg-3 moo-col-md-3 moo-col-sm-6 moo-col-xs-6 moo-cart-total-price" tabindex="0" >$'+mooOpformatCentPrice(data.totals.total)+'</div>'+
                        '</div>'+
                        '</div>'+
                        '<div class="moo-row" style="font-size: 11px;text-align: center;" tabindex="0">*'+ mooObjectL10n.quantityCanBeUpdated+'*</div>';

                }
                  //Set checkout btn
                //cart_html +='<div class="moo-row moo-cart-btns">'+
                   // '<a href="'+moo_CheckoutPage+'" class="moo-btn moo-btn-danger BtnCheckout">CHECKOUT</a>'+
                  //  '</div></div>';
                //element.html(cart_html);
                swal({
                    html:cart_html,
                    width: 700,
                    showCancelButton: true,
                    cancelButtonText : mooObjectL10n.close,
                    confirmButtonText : '<a href="'+moo_params.checkoutPage+'" style="color:#ffffff" tabindex="-1">'+ mooObjectL10n.checkout+'</a>'
                }).then(function (result) {
                    if(result.value)
                        window.location.href = moo_params.checkoutPage;
                });
            } else {

                cart_html +='<div class="moo-cart-empty" tabindex="0">'+ mooObjectL10n.cartEmpty+'</div> '+
                    '</div>';
                cart_html += '</div></div>';
               // element.html(cart_html);
                swal({
                    html:cart_html,
                    width: 700,
                    showConfirmButton: false,
                    showCancelButton: true,
                    cancelButtonText : mooObjectL10n.close
                });
            }
        } else {

            cart_html += '<div class="moo-row moo-cart-content">';
            cart_html +='<div class="moo-cart-empty">'+ mooObjectL10n.cartEmpty+'</div>'+
                '</div>';
            cart_html += '</div></div>';
           // element.html(cart_html);
            swal({
                html:cart_html,
                width: 700,
                showConfirmButton: false,
                showCancelButton: true,
                cancelButtonText : mooObjectL10n.close
            });

        }
    }).fail(function(data){
        cart_html +='<div class="moo-cart-empty">'+ mooObjectL10n.cannotLoadCart +'</div> '+
            '</div>';
       // element.html(cart_html);
        swal({
            html:cart_html,
            width: 700,
            showConfirmButton: false,
            showCancelButton: true,
            cancelButtonText : mooObjectL10n.close
        });
    });
}
function mooRemoveLineFromCart(line_id) {
    swal({
        title: mooObjectL10n.confirmItemDeletion,
        type: 'warning',
        showCancelButton: true,
        showLoaderOnConfirm: true,
        cancelButtonColor: '#d33',
        confirmButtonText: mooObjectL10n.yesDelete,
        cancelButtonText : mooObjectL10n.cancel,
        preConfirm: function () {
            return new Promise(function (resolve) {

                var body = {
                    line_id:line_id
                };
                /* Add to cart the item */
                jQuery.post(moo_RestUrl+"moo-clover/v1/cart/remove", body,function (data) {
                    if(data != null && data.status === 'success') {
                        resolve(true);
                    } else {
                        resolve(false);
                    }
                }).fail(function ( data ) {
                    resolve(false);
                }).done(function ( data ) {
                    if(typeof data.nb_items != "undefined")
                        jQuery("#moo-cartNbItems").text(data.nb_items)
                });
            })
        }
    }).then(function (data) {
        if(data.value) {
            swal({
                title:mooObjectL10n.deleted,
                type:'success'

            });
            mooShowCart();
        } else {
            if(data.dismiss !== "cancel"){
                swal({
                    title:mooObjectL10n.cannotDeleteItem,
                    type:'error'

                });
            } else {
                mooShowCart();
            }

        }

    }, function (dismiss) {
        // dismiss can be 'cancel', 'overlay',
        // 'close', and 'timer'
       //  if (dismiss === 'cancel') {
       // }
    })
}
function mooUpdateSpecialInsinCart(line_id,current_special_ins) {
    if(current_special_ins === ""){
        var title = mooObjectL10n.addSpecialInstructions;
        var ButtonText = mooObjectL10n.add;
    } else {
        var title = mooObjectL10n.updateSpecialInstructions;
        var ButtonText = mooObjectL10n.update
    }
    swal({
        title: title,
        input: 'textarea',
        inputValue: current_special_ins,
        inputPlaceholder: (window.moo_theme_setings.onePage_messageforspecialinstruction!==undefined)?window.moo_theme_setings.onePage_messageforspecialinstruction:"",
        showCancelButton: true,
        cancelButtonText : mooObjectL10n.close,
        confirmButtonText: ButtonText,
        showLoaderOnConfirm: true,
        preConfirm: function (special_ins) {
            return new Promise(function (resolve, reject) {
                if(special_ins.length>255) {
                    reject(mooObjectL10n.textTooLongMax250)
                } else {
                    var body = {
                        line_id:line_id,
                        special_ins : special_ins
                    };

                    jQuery.post(moo_RestUrl+"moo-clover/v1/cart/update", body,function (data) {
                        if(data != null && data.status === 'success') {
                            resolve(true);
                        } else {
                            resolve(false);
                        }
                    }).fail(function ( data ) {
                        resolve(false);
                    });
                }
            })
        },
        allowOutsideClick: false
    }).then(function (data) {
        if(data)
            mooShowCart();
        else
            swal({
                type: 'error',
                title: mooObjectL10n.notAdded,
                html: mooObjectL10n.specialInstructionsNotAdded
            })
    }, function (dismiss) {
        // dismiss can be 'cancel', 'overlay',
        // 'close', and 'timer'
         if (dismiss === 'cancel') {
             mooShowCart();
          }
    });

}
function mooUpdateSpecialInstructions(line_id) {
    swal({
        title: mooObjectL10n.addSpecialInstructions,
        input: 'textarea',
        inputPlaceholder: (window.moo_theme_setings.onePage_messageforspecialinstruction!==undefined)?window.moo_theme_setings.onePage_messageforspecialinstruction:"",
        showCancelButton: true,
        confirmButtonText: mooObjectL10n.add,
        cancelButtonText : mooObjectL10n.close,
        showLoaderOnConfirm: true,
        preConfirm: function (special_ins) {
            return new Promise(function (resolve, reject) {
                if(special_ins.length>255) {
                    reject(mooObjectL10n.textTooLongMax250)
                } else {
                    var body = {
                        line_id:line_id,
                        special_ins : special_ins
                    };

                    jQuery.post(moo_RestUrl+"moo-clover/v1/cart/update", body,function (data) {
                        if(data != null && data.status === 'success') {
                            resolve(true);
                        } else {
                            resolve(false);
                        }
                    }).fail(function ( data ) {
                        resolve(false);
                    });
                }
            })
        },
        allowOutsideClick: false
    }).then(function (data) {
        if(!data)
        {
            swal({
                type: 'error',
                title: mooObjectL10n.notAdded,
                html: mooObjectL10n.specialInstructionsNotAdded
            })
        }
    });

}
function mooExpColCatMenu() {

    jQuery(".moo-nav").toggle();

    if(jQuery(".moo-nav").css("display") === "block"){
        jQuery("#moo-menu-navigation").css("height",'100%');
    } else {
        jQuery("#moo-menu-navigation").css("height","auto");
    }

    if(jQuery('.moo-choose-category').find('i').hasClass('fa-chevron-down')){
        jQuery('.moo-choose-category').find('i').removeClass('fa-chevron-down');
        jQuery('.moo-choose-category').find('i').addClass('fa-times');
    }else{
        jQuery('.moo-choose-category').find('i').removeClass('fa-times');
        jQuery('.moo-choose-category').find('i').addClass('fa-chevron-down');
    }
}
function moo_ZoomOnImages() {

    //Zoom Images
    jQuery('.moo-image-zoom').on("click",function (event){
        if(event.isDefaultPrevented()) return;
        event.preventDefault();
        const imgSource = jQuery(event.target).attr('src');
        if ( imgSource ){
            swal({
                imageUrl: imgSource,
                confirmButtonText: 'Close',
                imageWidth: 1000,
                animation: true
            })
        }
    })
}
function formatPrice (p) {
    return p.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,")
}
function mooOpformatCentPrice (p) {
    p = p/100;
    return p.toFixed(2).toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,")
}

function mooShowAddingItemResult(data) {
    if(window.moo_theme_setings.onePage_askforspecialinstruction === "on"){
        swal({
            title:data.name,
            text:mooObjectL10n.addedToCart,
            type:"success",
            showCancelButton: true,
            confirmButtonText: mooObjectL10n.addSpecialInstructions,
            cancelButtonText: mooObjectL10n.noThanks,
            cancelButtonClass:'moo-green-background'
            //footer: '<a onclick="mooUpdateSpecialInsinCart(\''+data.line_id+'\',\'\')">Add special instruction</a>'
        }).then(function (result) {
            if(result.value) {
                mooUpdateSpecialInstructions(data.line_id);
            }
        });
    } else {
        swal({
            title:data.name,
            text:mooObjectL10n.addedToCart,
            timer:3000,
            type:"success"
        });
    }
}