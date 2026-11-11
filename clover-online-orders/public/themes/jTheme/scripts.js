/**
* Created by Mohammed EL BANYAOUI on 12/13/2017.
*/
jQuery(document).ready(function() {
    window.moo_RestUrl = moo_params.moo_RestUrl;
    window.moo_theme_setings = [];
    window.moo_mg_setings = {};
    window.nb_items_in_cart = 0;

    // Beta on: the badge on this page (#moo-cartNbItems, built in
    // MooLoadBaseStructure below) also needs to follow a second tab
    // adding/removing items -- SooCartStore.onChange() already fires on
    // the storage event, this just wires it to the badge. Flag off: a
    // no-op, nothing subscribes.
    SooCartBridge.onCartCountChange(function (n) {
        jQuery('#moo-cartNbItems').text(n > 0 ? n : '');
    });

    /* Load the theme settings then draw tha layout an get the categories with the first five items */
    jQuery.get(moo_RestUrl+"moo-clover/v1/theme_settings/jTheme", function (data) {
        if(data != null && data.settings != null) {
            window.moo_theme_setings = data.settings;
            window.nb_items_in_cart  = SooCartBridge.resolveNbItems(data.nb_items);
            window.moo_nbItemsPerLine = parseInt(window.moo_theme_setings.jTheme_nbItemsPerRow);
        }
    }).done(function () {
        //console.log(window.moo_theme_setings);
        //Check the hash to see if we will load one category
        var hash = window.location.hash;
        if (hash !== "") {
            var params = hash.split("-");
            // The hash is attacker-controlled: only a plain category id may go any further.
            var hashCatId = params.length === 2 ? mooSafeCatId(params[1]) : '';
            if(params[0] === '#cat' && hashCatId !== '') {
                MooLoadBaseStructure('#moo_OnlineStoreContainer',function () {
                    jQuery.get(moo_RestUrl+"moo-clover/v1/categories/"+hashCatId+"/items", function (data) {
                        if(data.items !== null) {
                            moo_renderItems(data);
                        } else {
                            MooHideLoading();
                            mooRenderNoItemsFound(hashCatId);
                        }

                    }).fail(function () {
                        MooHideLoading();
                        mooRenderNoItemsFound(hashCatId);
                    });
                });
                MooSetLoading();
            } else {
                MooLoadBaseStructure('#moo_OnlineStoreContainer',mooGetCategories);
                MooSetLoading();
            }

        } else {
            MooLoadBaseStructure('#moo_OnlineStoreContainer',mooGetCategories);
            MooSetLoading();
        }
    });

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

});
function MooLoadBaseStructure(elm_id,callback) {
    var html = '<div class="moo-row">'+
        '<div  class="moo-is-sticky moo-new-icon" onclick="mooShowCart(event)">' +
        '<div class="moo-new-icon__count" id="moo-cartNbItems">'+((window.nb_items_in_cart>0)?window.nb_items_in_cart:'')+'</div>' +
        '<div class="moo-new-icon__cart">' +
        '</div></div>'+
        '<div id="MooLoadingSection"></div>'+
        '</div>'+
        '<div class="moo-row">'+
        '<div id="moo-onlineStore-categories">'+
        '</div>'+
        '<div id="moo-onlineStore-items">'+
        '</div>'+
        '</div>';
    /* Adding the structure the the html page */
    jQuery(elm_id).html(html);
    callback();
}
function MooSetLoading() {
    jQuery('#MooLoadingSection').show();
}
function MooHideLoading() {
    jQuery('#MooLoadingSection').hide();
}

//get all the categories of the store
function mooGetCategories() {
    jQuery.get(moo_RestUrl+"moo-clover/v1/categories", function (data) {
        if(data!=null && data.length>0)
        {
            moo_renderCategories(data);
        }
        else
        {
            MooHideLoading();
            var element = document.getElementById("moo-onlineStore-categories");
            var html     = 'You don\'t have any category please import your inventory';
            jQuery(element).html(html);
        }
    });

}
function  moo_renderCategories($cats) {
    // the categories section in the DOM
    var compteur = 0;
    var lastCat = null;
    var element = document.getElementById("moo-onlineStore-categories");
    var nbItemsPerRow = 4;
    if( window.moo_theme_setings.jTheme_nbItemsPerRow !== undefined && window.moo_theme_setings.jTheme_nbItemsPerRow !== null && window.moo_theme_setings.jTheme_nbItemsPerRow !== "" ) {
        nbItemsPerRow = parseInt(window.moo_theme_setings.jTheme_nbItemsPerRow,10);
        if(nbItemsPerRow <= 0)
            nbItemsPerRow = 4;

    }
    var nbItemsPerRowCssCol = Math.floor((12/nbItemsPerRow));
    // Here your code that will convert the data to html
    var html = '<div class="moo-row">';
    for(var i in $cats){
        var category = $cats[i];

        if(category.uuid !== undefined ) {

            if(typeof attr_categories !== 'undefined' && attr_categories !== undefined && attr_categories !== null && typeof attr_categories === 'object') {
                if(attr_categories.indexOf(category.uuid.toUpperCase()) === -1){
                    continue;
                }
            }
            compteur++;
            lastCat = category;
            var imageCatUrl = moo_params['plugin_img']+'/noImg3.png';
            var imageCatUrl = moo_params['plugin_img']+'/moo_placeholder.png';
            if(category.image_url !== null && category.image_url !== "") {
                imageCatUrl = category.image_url;
            }
            if( i % nbItemsPerRow === 0) {
                html +='<div class="moo-col-xs-12 moo-col-sm-6 moo-col-md-'+nbItemsPerRowCssCol+'" style="clear: both">';
            } else {
                html +='<div class="moo-col-xs-12 moo-col-sm-6 moo-col-md-'+nbItemsPerRowCssCol+'">';
            }
            var catId = mooSafeCatId(category.uuid).toLowerCase();
            html +='<a class="link" href="#cat-'+ catId +'" id="cat-'+catId+'" data-cat-id="'+catId+'" onclick="MooClickOnCategory(event,this)">';
            html += '<div class="image-wrapper" style="background: url('+imageCatUrl+') no-repeat center;background-size:100%;"></div>';


            html +='<div class="cat-name-wrapper"><span class="moo-category-name">'+
                category.name+
                '</span></div></a></div>';
        }

    }
    html    += "</div>";
    if(compteur === 1) {
        if(lastCat) {
            var cat_id = lastCat.uuid;
        } else {
            // Then add theme to dom and do some changes after finsihed the rendriign
            jQuery(element).html(html).promise().done(function() {
                MooHideLoading();
            });
            return;
        }

        MooSetLoading();
        jQuery("#moo-onlineStore-categories").hide();
        jQuery.get(moo_RestUrl+"moo-clover/v1/categories/"+cat_id+"/items", function (data) {
            if(data.items !== null) {
                moo_renderItems(data);
            } else {
                MooHideLoading();
                mooRenderNoItemsFound(cat_id);
            }

        });
    } else {
        // Then add theme to dom and do some changes after finsihed the rendriign
        jQuery(element).html(html).promise().done(function() {
            MooHideLoading();
        });
    }

}

function MooClickOnCategory(event,elm)
{
    //event.preventDefault();
    var cat_id = jQuery(elm).attr('data-cat-id');
    MooSetLoading();
    jQuery("#moo-onlineStore-categories").hide();
    jQuery.get(moo_RestUrl+"moo-clover/v1/categories/"+cat_id+"/items", function (data) {
        if(data.items !== null) {
            moo_renderItems(data);
        } else {
            MooHideLoading();
            mooRenderNoItemsFound(cat_id);
        }

    });

}
function moo_renderItems(data) {
    // the categories section in the DOM
    var element = document.getElementById("moo-onlineStore-items");
    var nbItemsPerRow = 4;
    if( window.moo_theme_setings.jTheme_nbItemsPerRow !== undefined && window.moo_theme_setings.jTheme_nbItemsPerRow !== null && window.moo_theme_setings.jTheme_nbItemsPerRow !== "" ) {
        nbItemsPerRow = parseInt(window.moo_theme_setings.jTheme_nbItemsPerRow,10);
        if(nbItemsPerRow <= 0)
            nbItemsPerRow = 4;

    }
    var nbItemsPerRowCssCol = Math.floor((12/nbItemsPerRow));
    // Here your code that will convert the data to html
    var html = '<div class="moo-row">';
   html  += `<div class="moo-category-title">` +
        `<span class="moo-back-to-categories-icon" onclick="moo_backToCategories('${mooSafeCatId(data.uuid).toLowerCase()}')">`+
        `<img width="20px" src="${ moo_params['plugin_img']}/back-arrow.svg" alt="back to categories"/>`+
       `<h3>${data.name}</h3></span></div>`;

    if (data.description){
        html +='<div class="moo-category-description">'+data.description+'</div>';
    }

    for(var i in data.items){
        var item = data.items[i];
        if(typeof item =='object') {
            var item_price = parseFloat(item.price);
            item_price = item_price/100;
            item_price = item_price.toFixed(2);

            if(item.price > 0 && item.price_type === "PER_UNIT")
                item_price += '/'+item.unit_name;

            if(item.image !== null && item.image.url !== null && item.image.url !== "") {
                var itemimgUrl = item.image.url;
            } else {
                var itemimgUrl = moo_params['plugin_img']+'/moo_placeholder.png';
            }

                if( i % nbItemsPerRow === 0) {
                    html +='<div class="moo-col-xs-12 moo-col-sm-6 moo-col-md-'+nbItemsPerRowCssCol+'" style="clear: both">';
                } else {
                    html +='<div class="moo-col-xs-12 moo-col-sm-6 moo-col-md-'+nbItemsPerRowCssCol+'">';
                }
                html +='<a class="link" href="#item-'+item.uuid.toLowerCase()+'" data-item-id="'+item.uuid.toLowerCase()+'" onclick="MooClickOnItem(event,this)">';

                html += '<div class="image-wrapper" style="background: url('+itemimgUrl+') no-repeat center;background-size:100%;" onclick=""></div>';



            html +=   '<h2 class="title"><span class="brand "></span>'+
                '<span class="name">'+item.name+'</span>'+
                '</h2>'+
                '<div class="price-container clearfix">'+
                '<div class="price-box">'+
                '<span class="price">';

                if(parseFloat(item.price) === 0) {
                    html += '<span></span>';
                } else {
                    html += '<span>$'+item_price+'</span>';
                }
                 html += '</span></div></div>';
                if(window.moo_theme_setings.jTheme_showItemDescription !== undefined && window.moo_theme_setings.jTheme_showItemDescription !== null && window.moo_theme_setings.jTheme_showItemDescription === 'on' && item.description ) {
                    html += '<div class="moo_item_description">'+item.description+'</div>';
                }
                html += '<div class="btn-wrapper"><span class="moo-category-name">';
            if(data.available === false) {
                html += '<button class="osh-btn"><span class="label">Not Available Yet</span></button>';
            } else {
                if(item.stockCount === "out_of_stock") {
                    html += '<button class="osh-btn"><span class="label">OUT OF STOCK</span></button>';
                } else {
                    //Checking the Qty window show/hide and add add to cart button
                    if(window.moo_theme_setings.onePage_qtyWindow !== null && window.moo_theme_setings.jTheme_qtyWindow === "on") {
                        if(item.has_modifiers) {
                            if(window.moo_theme_setings.jTheme_qtyWindowForModifiers !== null && window.moo_theme_setings.jTheme_qtyWindowForModifiers === "on")
                                html += '<button class="osh-btn" onclick="mooOpenQtyWindow(event,\''+item.uuid+'\',\''+item.stockCount+'\',moo_clickOnOrderBtnFIWM)"><span class="label">Choose Qty & Options</span></button>';
                            else
                                html += '<button class="osh-btn" onclick="moo_clickOnOrderBtnFIWM(event,\''+item.uuid+'\',1)"><span class="label">Choose Options & Qty</span></button>';
                        } else {
                            html += '<button class="osh-btn" onclick="mooOpenQtyWindow(event,\''+item.uuid+'\',\''+item.stockCount+'\',moo_clickOnOrderBtn)"><span class="label">Add to cart</span></button>';
                        }

                    } else {
                        if(item.has_modifiers)
                            html += '<button class="osh-btn" onclick="moo_clickOnOrderBtnFIWM(event,\''+item.uuid+'\',1)"><span class="label"> Choose Options & Qty </span></button>';
                        else
                            html += '<button class="osh-btn" onclick="moo_clickOnOrderBtn(event,\''+item.uuid+'\',1)"><span class="label">Add to cart</span> </button>';

                    }

                }
            }


            html +='</span></div></a></div>';
        }
    }
    html += "</div>";
    if(data.uuid) {
        html    += '<div class="moo-back-to-categories"><button class="osh-btn" onclick="moo_backToCategories(\''+mooSafeCatId(data.uuid).toLowerCase()+'\')">' +
                '<span class="label">Back to Categories</span>'+
                '</button></div>';
    }
    // Then add theme to dom and do some changes after finsihed the rendriign
    jQuery(element).html(html).promise().done(function() {
        jQuery("#moo-onlineStore-items").show();
        MooHideLoading();
        //scroll to top #moo-onlineStore-items
        var top = (jQuery("#moo-onlineStore-items").offset() != null)?jQuery("#moo-onlineStore-items").offset().top:""; //Getting Y of target element
        window.scrollTo(0, top);
    });
}

function MooClickOnItem(event,elm) {
    event.preventDefault();
    var item_id = jQuery(elm).attr('data-item-id');
}
function moo_backToCategories(cat_id) {
    cat_id = mooSafeCatId(cat_id);
    window.location.hash = '';
    jQuery("#moo-onlineStore-items").hide();
    MooSetLoading();
    if(jQuery("#moo-onlineStore-categories").html()===''){
        mooGetCategories();
    } else {
        jQuery("#moo-onlineStore-categories").show();
        MooHideLoading();
    }
    var top = (cat_id !== '' && jQuery("#cat-"+cat_id).offset() != null)?jQuery("#cat-"+cat_id).offset().top:""; //Getting Y of target element
    window.scrollTo(0, top);
}
/* Category ids are Clover UUIDs; anything else (e.g. a crafted #cat- hash) is dropped */
function mooSafeCatId(cat_id) {
    cat_id = (cat_id === undefined || cat_id === null) ? '' : String(cat_id);
    return /^[A-Za-z0-9]+$/.test(cat_id) ? cat_id : '';
}
/* Built with DOM APIs, not an HTML string, so the id never lands inside inline JavaScript */
function mooRenderNoItemsFound(cat_id) {
    cat_id = mooSafeCatId(cat_id);
    var button = jQuery('<button class="osh-btn"><span class="label">Back to Categories</span></button>')
        .on('click', function () {
            moo_backToCategories(cat_id);
        });
    jQuery("#moo-onlineStore-items").empty().append('<h3>No item found</h3>', button);
}
function mooOpenQtyWindow(event,item_id,stockCount,callback) {
    // Beta on: no quantity prompt -- one unit is added and the drawer's
    // stepper takes it from there (SooCartBridge.addOneDirect).
    if (window.SooCartBridge && typeof SooCartBridge.addOneDirect === 'function' &&
        SooCartBridge.addOneDirect(event, item_id, stockCount, callback)) {
        return;
    }
    event.preventDefault();
    var inputOptions = new Promise(function (resolve) {
        if(stockCount == "not_tracking_stock" ||  stockCount == "tracking_stock" )
        {
            resolve({
                "1":"1","2":"2","3":"3","4":"4","5":"5","6":"6","7":"7","8":"8","9":"9","10":"10","custom":"Custom Quantity"
            })
        }
        else
        {
            var options = {};
            var QtyMax = (parseInt(stockCount)>10)?10:parseInt(stockCount);
            var count = QtyMax;
            for(var $i = 1;$i<=QtyMax;$i++)
            {
                options[$i.toString()] = $i.toString();
                if(!--count)
                {
                    options["custom"] = "Custom Quantity";
                    resolve(options)
                }
            }
        }
    });
    swal({
        title: mooObjectL10n.selectTheQuantity,
        showLoaderOnConfirm: true,
        confirmButtonText: "Add",
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
function mooOpenCustomQtyWindow(event,item_id,callback) {
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
function moo_clickOnOrderBtn(event,item_id,qty) {
    var body = {
        item_uuid:item_id,
        item_qty:qty,
        item_modifiers:{}
    };
    /* Add to cart the item */
    SooCartBridge.add(body, function (data) {
        if(data != null)
        {
            if(data.status == "error")
            {
                swal({
                    title:data.message,
                    type:"error"
                });
            }
            else
            {
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
        if(typeof data.nb_items != "undefined")
            jQuery("#moo-cartNbItems").text(data.nb_items)
    }, function ( data ) {
        swal({
            title:mooObjectL10n.notAddedToCart,
            text:mooObjectL10n.checkInternetConnection,
            type:"error"
        });
        console.log(data);
    });

}
//Click on order button for an item with modifiers
function moo_clickOnOrderBtnFIWM(event,item_id,qty) {
    event.preventDefault();
    //Change button content to loading
    var target = event.target;
    var old_text = jQuery(target).text();
    jQuery(target).text(mooObjectL10n.loadingOptions);

    jQuery.get(moo_RestUrl+"moo-clover/v1/items/"+item_id, function (data) {
        //Change butn text
        jQuery(target).text(old_text);

        if(data != null)
        {
            if(data.modifier_groups.length > 0)
            {
                if(typeof mooBuildModifiersPanel == "function")
                {
                    if(Object.keys(window.moo_mg_setings).length > 0)
                    {
                        if(window.moo_mg_setings.inlineDisplay && window.moo_mg_setings.inlineDisplay == true ) {
                            var h = '<div class="moo-col-lg-12 moo-col-md-12 moo-col-sm-12 moo-col-xs-12 moo-modifiersContainer-for-'+item_id+'"></div>';
                            jQuery("#moo-onlineStore-items").append(h);
                            jQuery("html, body").animate({
                                scrollTop: jQuery(".moo-modifiersContainer-for-"+item_id).offset().top
                            }, 600);
                        }
                        mooBuildModifiersPanel(data.modifier_groups,item_id,qty,window.moo_mg_setings);
                    }
                    else
                    {
                        mooBuildModifiersPanel(data.modifier_groups,item_id,qty);
                    }
                    swal.close();
                }
                else
                {
                    swal(mooObjectL10n.tryAgain,mooObjectL10n.refreshPageError,'error');
                }

            }
            else
                moo_clickOnOrderBtn(event,item_id,qty);
        }
        else
        {
            //Change button text
            jQuery(target).text(old_text);
            swal({ title: mooObjectL10n.error, text: mooObjectL10n.cannotLoadItemOptions,   type: "error",   confirmButtonText: "ok" });
        }
    }).fail(function (data) {
        //Change button text
        jQuery(target).text(old_text);
        swal({ title: mooObjectL10n.error, text: mooObjectL10n.cannotLoadItemOptions,   type: "error",   confirmButtonText: "ok" });
    });

}
function formatPrice (p) {
    return p.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,")
}
/* Cart functions */
function mooShowCart(event) {
    if(typeof event != "undefined")
        event.preventDefault();

    // Beta on: the browser cart (SooCartStore/localStorage) is the cart --
    // this theme's own GET v1/cart popup below reads the PHP-session cart,
    // which the beta never writes to, so it must not run. SooCartUI.open()
    // renders the same browser cart the badge is already counting.
    if(window.moo_params && moo_params.newCartEnabled) {
        if(window.SooCartUI) {
            SooCartUI.open();
        }
        return;
    }

    var element = jQuery("#moo-panel-cart>.moo-panel-cart-container>.moo-panel-cart-content");
    var cart_element =jQuery("#moo-panel-cart>.moo-panel-cart-container>.moo-panel-cart-content") ;

    swal({
        html:
        '<div class="moo-msgPopup">Loading your cart</div>' +
        '<img src="'+ moo_params['plugin_img']+'/loading.gif" class="moo-imgPopup"/>',
        showConfirmButton: false
    });

    var cart_html = '<div class="moo-row moo-cart-heading">'+
        '<div class="moo-col-lg-6 moo-col-md-6 moo-col-sm-5 moo-col-xs-5 moo-cart-line-itemName">ITEM</div>'+
        '<div class="moo-col-lg-2 moo-col-md-2 moo-col-sm-2 moo-col-xs-2 moo-cart-line-itemQty">QTY</div>'+
        '<div class="moo-col-lg-2 moo-col-md-2 moo-col-sm-3 moo-col-xs-3 moo-cart-line-itemPrice">SUB-TOTAL</div>'+
        '<div class="moo-col-lg-2 moo-col-md-2 moo-col-sm-2 moo-col-xs-2 moo-cart-line-itemActions">EDIT</div>'+
        '</div>'+
        '<div class="moo-cart-container">';

    jQuery.get(moo_RestUrl+"moo-clover/v1/cart", function (data) {
        if(typeof data != 'undefined' && data != null)
        {
            cart_html += '<div class="moo-row moo-cart-content">';
            if(data.items != null && Object.keys(data.items).length>0) {
                jQuery.each(data.items,function(line_id,line) {
                    var price = parseFloat(line.item.price)/100;
                    var line_price = price * line.qty;

                    cart_html+='<div class="moo-row moo-cart-line" >'+
                        '<div class="moo-col-lg-6 moo-col-md-6 moo-col-sm-5 moo-col-xs-5 moo-cart-line-itemName">';
                    //check if cart line contain modifiers
                    if(line.modifiers.length > 0)
                    {
                        cart_html += line.item.name;
                        cart_html += '<div class="moo-cart-line-modifiers">';
                        for(var $j=0;$j<line.modifiers.length;$j++)
                        {
                            cart_html += ''+line.modifiers[$j].qty;
                            cart_html += 'x '+line.modifiers[$j].name;
                            if(line.modifiers[$j].price>0)
                            {
                                line_price += ((parseFloat(line.modifiers[$j].price)/100)*(parseInt(line.modifiers[$j].qty)))*line.qty;
                                cart_html += ' <span style="color: #484848;">$'+(parseFloat(line.modifiers[$j].price)/100).toFixed(2)+"</span>";
                            }
                            cart_html += '<br/>';
                        }
                        cart_html += '</div>';

                    } else {
                        cart_html += line.item.name;
                    }
                    cart_html+='</div>';
                    cart_html+='<div class="moo-col-lg-2 moo-col-md-2 moo-col-sm-2 moo-col-xs-2  moo-cart-line-itemQty">'+line.qty+'</div>';
                    cart_html+= '<div class="moo-col-lg-2 moo-col-md-2 moo-col-sm-3 moo-col-xs-3  moo-cart-line-itemPrice">$'+formatPrice(line_price.toFixed(2))+'</div>';
                    cart_html+= '<div class="moo-col-lg-2 moo-col-md-2 moo-col-sm-2 moo-col-xs-2  moo-cart-line-itemActions">';
                    if( ! window.moo_theme_setings
                        || ! window.moo_theme_setings.jTheme_allowspecialinstructionforitems
                        ||  window.moo_theme_setings.jTheme_allowspecialinstructionforitems === "on"
                    ){
                        cart_html+=  '<i  tabindex="0" role="button" aria-label="add or edit special instruction" style="cursor: pointer;margin-right: 10px;margin-left: 10px" class="fas fa-pencil-square" aria-hidden="true" onclick="mooUpdateSpecialInsinCart(\''+line_id+'\',\''+line.special_ins+'\')"></i>';
                    }

                    cart_html+=  '<i tabindex="0" role="button" aria-label="remove this item from your cart" style="cursor: pointer;margin-right: 10px;margin-left: 10px" class="fas fa-trash" aria-hidden="true" onclick="mooRemoveLineFromCart(\''+line_id+'\')"></i>';
                    cart_html+= '</div></div>';
                });
                cart_html += '</div>';
                //Set teh cart total
                if(data.totals !== null && data.totals !== false)
                    cart_html +=' <div class="moo-row moo-cart-totals">'+
                        '<div class="moo-row moo-cart-total moo-cart-total-subtotal">'+
                        '<div class="moo-col-lg-9 moo-col-md-9 moo-col-sm-7 moo-col-xs-7 moo-cart-total-label">SUBTOTAL</div>'+
                        '<div class="moo-col-lg-3 moo-col-md-3 moo-col-sm-5 moo-col-xs-5  moo-cart-total-price">$'+mooformatCentPrice(data.totals.sub_total)+'</div>'+
                        '</div>'+
                        '<div class="moo-row moo-cart-total moo-cart-total-tax">'+
                        '<div class="moo-col-lg-9 moo-col-md-9 moo-col-sm-7 moo-col-xs-7 moo-cart-total-label">TAX</div>'+
                        '<div class="moo-col-lg-3 moo-col-md-3 moo-col-sm-5 moo-col-xs-5  moo-cart-total-price">$'+mooformatCentPrice(data.totals.total_of_taxes)+'</div>'+
                        '</div>'+
                        '<div class="moo-row moo-cart-total moo-cart-total-grandtotal">'+
                        '<div class="moo-col-lg-8 moo-col-md-8 moo-col-sm-6 moo-col-xs-6 moo-cart-total-label">TOTAL</div>'+
                        '<div class="moo-col-lg-4 moo-col-md-4 moo-col-sm-6 moo-col-xs-6 moo-cart-total-price">$'+mooformatCentPrice(data.totals.total)+'</div>'+
                        '</div>'+
                        '</div>'+
                        '<div class="moo-row" style="font-size: 11px;text-align: center;">*Quantity can be updated during checkout*</div>';

                swal({
                    html:cart_html,
                    width: 700,
                    showCancelButton: true,
                    cancelButtonText : 'Close',
                    confirmButtonText : '<a href="'+ moo_params.checkoutPage +'" style="color:#ffffff">CHECKOUT</a>'
                }).then(function (result) {
                    if(result.value){
                        window.location.href = moo_params.checkoutPage;

                    }
                });
            } else {

                cart_html +='<div class="moo-cart-empty">Your cart is empty</div> '+
                    '</div>';
                cart_html += '</div></div>';
                // element.html(cart_html);
                swal({
                    html:cart_html,
                    width: 700,
                    showConfirmButton: false,
                    showCancelButton: true,
                    cancelButtonText : 'Close'
                });
            }
        } else {

            cart_html += '<div class="moo-row moo-cart-content">';
            cart_html +='<div class="moo-cart-empty">Your cart is empty</div>'+
                '</div>';
            cart_html += '</div></div>';
            // element.html(cart_html);
            swal({
                html:cart_html,
                width: 700,
                showConfirmButton: false,
                showCancelButton: true,
                cancelButtonText : 'Close'
            });

        }
    }).fail(function(data){
        console.log('Fail to get the cart');
        cart_html +='<div class="moo-cart-empty">Error in loading your cart, please refresh the page</div> '+
            '</div>';
        // element.html(cart_html);
        swal({
            html:cart_html,
            width: 700,
            showConfirmButton: false,
            showCancelButton: true,
            cancelButtonText : 'Close'
        });
    });
}
function mooRemoveLineFromCart(line_id) {
    swal({
        title: mooObjectL10n.confirmItemDeletion,
        type: 'warning',
        showCancelButton: true,
        showLoaderOnConfirm: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: mooObjectL10n.yesDelete,
        preConfirm: function () {
            return new Promise(function (resolve) {

                var body = {
                    line_id:line_id
                };
                /* Add to cart the item */
                jQuery.post(moo_RestUrl+"moo-clover/v1/cart/remove", body,function (data) {
                    if(data != null && data.status == 'success') {
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
        },
    }).then(function (data) {
        if(data.value) {
            swal({
                title:"Deleted!",
                type:'success'

            });
        }
    }, function (dismiss) {
        // dismiss can be 'cancel', 'overlay',
        // 'close', and 'timer'
        //  if (dismiss === 'cancel') {
        // }
    })
}
function mooUpdateSpecialInsinCart(line_id,current_special_ins) {
    swal({
        title: mooObjectL10n.addSpecialInstructions,
        input: 'textarea',
        inputValue: current_special_ins,
        inputPlaceholder: mooObjectL10n.specialInstructionsHint,
        showCancelButton: true,
        confirmButtonText: 'Add',
        showLoaderOnConfirm: true,
        preConfirm: function (special_ins) {
            return new Promise(function (resolve, reject) {
                if(special_ins.length>255)
                {
                    reject(mooObjectL10n.textTooLongMax250)
                }
                else
                {
                    var body = {
                        line_id:line_id,
                        special_ins : special_ins
                    };

                    jQuery.post(moo_RestUrl+"moo-clover/v1/cart/update", body,function (data) {
                        if(data != null && data.status == 'success')
                        {
                            resolve(true);
                        }
                        else
                        {
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
        /* swal({
             type: 'success',
             title: 'Done',
             html: mooObjectL10n.specialInstructionsSubmitted
         })*/
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
function mooUpdateSpecialInstructions(line_id)
{
    swal({
        title: mooObjectL10n.addSpecialInstructions,
        input: 'textarea',
        inputPlaceholder: (window.moo_theme_setings.jTheme_messageforspecialinstruction!==undefined)?window.moo_theme_setings.jTheme_messageforspecialinstruction:"",
        showCancelButton: true,
        confirmButtonText: 'Add',
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
                        if(data != null && data.status == 'success')
                        {
                            resolve(true);
                        }
                        else
                        {
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
function mooShowAddingItemResult(data) {
    // Beta on: the drawer's own toast replaces the blocking SweetAlert.
    if (window.moo_params && moo_params.newCartEnabled && window.SooCartUI && typeof SooCartUI.notifyAdded === 'function') {
        if (typeof swal === 'function' && typeof swal.close === 'function') {
            swal.close();
        }
        SooCartUI.notifyAdded(data);
        return;
    }
    var title = (data.name != null) ? data.name : mooObjectL10n.item;
    // Beta on: this offer normally opens mooUpdateSpecialInstructions(data.line_id),
    // which POSTs moo-clover/v1/cart/update against the PHP-session cart's
    // line_id. SooCartBridge.add()'s synthetic response carries no
    // line_id (the browser cart mints its own, and there is no note-edit
    // affordance in the drawer yet), so the affordance is disabled here
    // rather than wired to an id that would silently update nothing.
    if(window.moo_theme_setings.jTheme_askforspecialinstruction === "on" && !(window.moo_params && moo_params.newCartEnabled)){
        swal({
            title:title,
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
            title:title,
            text:mooObjectL10n.addedToCart,
            timer:3000,
            type:"success"
        });
    }
}
