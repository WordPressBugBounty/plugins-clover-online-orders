
/* For fast Clover connection */
window.addEventListener('message', (event) => {
    if (event.data.source === 'soo') {
        mooConnectWithClover(event.data.api_key);
    }

    if (event.data.source === 'reports') {
        console.log(event.data.pageHeight);
        jQuery("#mooFrameReports").parent().height(event.data.pageHeight);
    }


});

//Fast Save for the settings using only cmd + s

document.addEventListener("keydown", function(e) {
    if ((window.navigator.platform.match("Mac") ? e.metaKey : e.ctrlKey)  && e.keyCode == 83) {
        e.preventDefault();
        //Get The active form and save it
        var allForms = document.getElementsByTagName("form");
        var activeForm = Array.from(allForms).filter(s => {
            if(s.parentElement.style.display === 'block'){
               return true;
            }
        });
        if(activeForm.length === 1){
            if ( activeForm[0].name === 'mooStoreSettings' || activeForm[0].name === 'mooCheckoutSettings'){
                mooSaveChanges(e,activeForm[0]);
            }
            if ( activeForm[0].name === 'mooDeliveryAreas'){
                mooSaveDeliveryAreas(e,activeForm[0]);
            }

        }
    }
}, false);

jQuery(document).ready(function($){
    window.moo_loading = '<svg xmlns="http://www.w3.org/2000/svg" width="44px" height="44px" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid" class="uil-default"><rect x="0" y="0" width="100" height="100" fill="none" class="bk"></rect><rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff" transform="rotate(0 50 50) translate(0 -30)">  <animate attributeName="opacity" from="1" to="0" dur="1s" begin="0s" repeatCount="indefinite"></animate></rect><rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff" transform="rotate(30 50 50) translate(0 -30)">  <animate attributeName="opacity" from="1" to="0" dur="1s" begin="0.08333333333333333s" repeatCount="indefinite"></animate></rect><rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff" transform="rotate(60 50 50) translate(0 -30)">  <animate attributeName="opacity" from="1" to="0" dur="1s" begin="0.16666666666666666s" repeatCount="indefinite"></animate></rect><rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff" transform="rotate(90 50 50) translate(0 -30)">  <animate attributeName="opacity" from="1" to="0" dur="1s" begin="0.25s" repeatCount="indefinite"></animate></rect><rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff" transform="rotate(120 50 50) translate(0 -30)">  <animate attributeName="opacity" from="1" to="0" dur="1s" begin="0.3333333333333333s" repeatCount="indefinite"></animate></rect><rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff" transform="rotate(150 50 50) translate(0 -30)">  <animate attributeName="opacity" from="1" to="0" dur="1s" begin="0.4166666666666667s" repeatCount="indefinite"></animate></rect><rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff" transform="rotate(180 50 50) translate(0 -30)">  <animate attributeName="opacity" from="1" to="0" dur="1s" begin="0.5s" repeatCount="indefinite"></animate></rect><rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff" transform="rotate(210 50 50) translate(0 -30)">  <animate attributeName="opacity" from="1" to="0" dur="1s" begin="0.5833333333333334s" repeatCount="indefinite"></animate></rect><rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff" transform="rotate(240 50 50) translate(0 -30)">  <animate attributeName="opacity" from="1" to="0" dur="1s" begin="0.6666666666666666s" repeatCount="indefinite"></animate></rect><rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff" transform="rotate(270 50 50) translate(0 -30)">  <animate attributeName="opacity" from="1" to="0" dur="1s" begin="0.75s" repeatCount="indefinite"></animate></rect><rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff" transform="rotate(300 50 50) translate(0 -30)">  <animate attributeName="opacity" from="1" to="0" dur="1s" begin="0.8333333333333334s" repeatCount="indefinite"></animate></rect><rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff" transform="rotate(330 50 50) translate(0 -30)">  <animate attributeName="opacity" from="1" to="0" dur="1s" begin="0.9166666666666666s" repeatCount="indefinite"></animate></rect></svg>';
    window.moo_first_time = true;
    window.moo_nb_allItems =0;
    window.customHoursForCategoriesUpdated = false;
    window.customHoursForOrderTypesUpdated = false;
    window.moo_RestUrl = moo_params.moo_RestUrl;

    if(typeof $.magnificPopup !== 'undefined'){
        $('.moo-open-popupItems').magnificPopup({
            type:'inline',
            closeBtnInside: true,
            midClick: true // Allow opening popup on middle mouse click. Always set it to true if you don't provide alternative source in href.
        });
    }

    $("#show_menu").on("click",function(e) {
        e.preventDefault();
        $("#MooPanel_main>#menu_for_mobile>ul").toggle();
    });
    // check if we are on settings page
    if(document.getElementById("MooPanel") !== null) {
        moo_Update_stats();
        Moo_GetOrderTypes();
        Moo_SetupCategoriesSection();

        $("div.faq_question").on("click",function() {
            var clicked = $(this);
            // Get next element to current element
            clicked = clicked.next();
            // Show or hide the next element
            clicked.toggle();
        });

        //update height of panel

        var sideBarHeight = jQuery("#MooPanel_sidebar").height();
        if(sideBarHeight>658){
            jQuery("#MooPanel_main").css("min-height",(sideBarHeight));
        }


        if($('#moo_progressbar_container').length == 1)  {
            window.bar = new ProgressBar.Line('#moo_progressbar_container', {
                strokeWidth: 4,
                easing: 'easeInOut',
                duration: 1400,
                color: '#496F4E',
                trailColor: '#eee',
                trailWidth: 1,
                svgStyle: {width: '100%', height: '100%'},
                text: {
                    style: {
                        // Text color.
                        // Default: same as stroke color (options.color)
                        color: '#999',
                        position: 'absolute',
                        right: '0',
                        top: '30px',
                        padding: 0,
                        margin: 0,
                        transform: null
                    },
                    autoStyleContainer: false
                },
                from: {color: '#FFEA82'},
                to: {color: '#ED6A5A'}
            });
        }


        /* --- Modifier Group --- */

        $('.moo_ModifierGroup input').bind('click.sortable mousedown.sortable',function(ev){
            ev.target.focus();
        });
        $('.sub-group input').bind('click.sortable mousedown.sortable',function(ev){
            ev.target.focus();
        });
        //Drag and drop
        $("#sortable").sortable({
            stop: function(event, ui) {
                var tabNew = new Array();
                var i = 0;

                jQuery("#sortable tr").each(function(i, el){
                    tabNew[i] = jQuery(this).attr("data-cat-id");
                    i++;
                });
                jQuery.post(moo_params.ajaxurl,{'action':'moo_new_order_categories','newtable':tabNew},function(data){
                    //console.log(data);
                })
            }
        });

        $("#orderCategory").sortable({
            stop: function(event, ui) {
                var tabNew = new Array();
                var i = 0;
                jQuery("#orderCategory .category-item").each(function (i, el) {
                    tabNew[i] = jQuery(this).attr("cat-id-mobil");
                    i++;
                });
                jQuery.post(moo_params.ajaxurl,{'action':'moo_new_order_categories','newtable':tabNew},function(data){
                    //console.log(data);
                })
            }
        });

        $(".moo_listItem").sortable({
            stop: function(event, ui) {
                var category = jQuery(this).attr("id-cat");
                var tabNew = new Array();
                var i = 0;
                jQuery(".moo_listItem li.cat"+category).each(function(i, el){
                    tabNew[i] = jQuery(this).attr("uuid_item");
                    i++;
                });
                jQuery.post(moo_params.ajaxurl,{'uuid':category,'action':'moo_reorder_items','newtable':tabNew},function(data){
                    console.log(data);
                });
            }
        });

        /*
        $(".sub-group").sortable({
            stop: function(event, ui) {
                var group = jQuery(this).attr("GM");
                var tabNew = new Array();
                var i = 0;
                jQuery(".moo_ModifierGroup .list-GModifier_"+group).each(function (i, el) {
                    tabNew[i] = jQuery(this).attr("group-id");
                    i++;
                });
                //var NB = tabNew.length;
                jQuery.post(moo_params.ajaxurl,{'action':'moo_new_order_modifier','group_id':group,'newtable':tabNew},function(data){
                    console.log(data);
                })
            }
        });

        $(".moo_ModifierGroup").sortable({
            stop: function(event, ui) {
                var tabNew = new Array();
                var i = 0;
                jQuery(".moo_ModifierGroup .list-group").each(function (i, el) {
                    tabNew[i] = jQuery(this).attr("group-id");
                    i++;
                });
                jQuery.post(moo_params.ajaxurl,{'action':'moo_new_order_group_modifier','newtable':tabNew},function(data){
                    console.log(data);
                })
            }
        });
        */
    }

   /* Remove loader*/
    $('body').addClass('loaded');
    //Force removing loader after 10 seconds
    setTimeout(function(){ $('body').addClass('loaded') }, 3);

    if($('.moo-color-field')) {
        $('.moo-color-field').wpColorPicker();
    }

    $('#CouponExpiryDate').datepicker({
        dateFormat: 'mm-dd-yy'
    });

    $('#CouponStartDate').datepicker({
        dateFormat: 'mm-dd-yy'
    });

    if ( window.location.hash != "" ) {
        switch (window.location.hash) {
            case "#apikey":
                tab_clicked(1);
                break;
            case "#inventory":
                tab_clicked(2);
                break;
            case "#ordertypes":
                tab_clicked(3);
                break;
            case "#announcements":
                tab_clicked(4);
                break;
            case "#categories":
                tab_clicked(5);
                break;
            case "#modifiergroups":
                tab_clicked(6);
                break;
            case "#checkout":
                tab_clicked(7);
                break;
            case "#store":
                tab_clicked(8);
                break;
            case "#delivery":
                tab_clicked(9);
                break;
            case "#help":
                tab_clicked(10);
                break;
            case "#export":
                tab_clicked(11);
                break;
            case "#custom-hours":
                tab_clicked(12);
                break;
        }
    }

    //check the api key
    if($("#moo-checking-section")){
        if($("#moo-checking-section").css("display") === "block") {
            mooCheckApiKeyOnLoading();
        }
    }
    if($("#mooAutoSyncCheking")){
        if($("#mooAutoSyncCheking").css("display") === "block"){
            mooCheckAutoSyncStatus();
        }
    }
});


/* --- Modifier Group --- */
function sooStartReOrderModifiers(){

    jQuery(".moo_ModifierGroup").sortable({
        stop: function(event, ui) {
            var tabNew = [];
            var i = 0;
            jQuery(".moo_ModifierGroup .list-group").each(function (i, el) {
                tabNew[i] = jQuery(this).attr("group-id");
                i++;
            });
            jQuery.post(moo_params.ajaxurl,{'action':'moo_new_order_group_modifier','newtable':tabNew},function(data){
                console.log(data);
            })
        }
    });

    jQuery(".sub-group").sortable({
        stop: function(event, ui) {
            var group = jQuery(this).attr("GM");
            var tabNew = [];
            var i = 0;
            jQuery(".moo_ModifierGroup .list-GModifier_"+group).each(function (i, el) {
                tabNew[i] = jQuery(this).attr("group-id");
                i++;
            });
            //var NB = tabNew.length;
            jQuery.post(moo_params.ajaxurl,{'action':'moo_new_order_modifier','group_id':group,'newtable':tabNew},function(data){
                console.log(data);
            })
        }
    });

    //Change cursor
    jQuery("ul.moo_ModifierGroup li").css('cursor','move');
    jQuery(".bar-group").show();

    jQuery("#MooPanel_tabContent6 div.show_group").hide();
    jQuery("#MooPanel_tabContent6 div.saved_new_name,#MooPanel_tabContent6 div.edit_modifer_name").hide();

    //Hide Start and Show finish
    jQuery("#sooStartReOrderModifiers").hide();
    jQuery("#sooFinishReOrderModifiers").show();

    //Enable

    jQuery(".moo_ModifierGroup").sortable("enable")
    jQuery(".sub-group").sortable("enable")

}
function sooFinishReOrderModifiers(){
    jQuery(".moo_ModifierGroup").sortable("disable")
    jQuery(".sub-group").sortable("disable")
    //Change cursor
    jQuery("ul.moo_ModifierGroup li").css('cursor','');
    jQuery(".bar-group").hide();

    jQuery("#MooPanel_tabContent6 div.show_group").show();
    jQuery("#MooPanel_tabContent6 div.saved_new_name,#MooPanel_tabContent6 div.edit_modifer_name").show();

    //Shoz Start and Hide finish
    jQuery("#sooFinishReOrderModifiers").hide();
    jQuery("#sooStartReOrderModifiers").show();
}
function edit_name_GGroup(event,id){
    event.preventDefault();
    jQuery("#label_"+id+" .getname").css("display","none");
    jQuery("#label_"+id+" .change-name").css("display","block");
}
function editModifierGroup(event, uuid){
    event.preventDefault();
    swal({
        title: 'Please wait ..',
        showConfirmButton: false
    });

    jQuery.get(moo_RestUrl+"moo-clover/v1/inventory/modifier-groups/"+uuid, function (response) {
        if(response.data !== null) {
            const form = getModifierGroupPopupHtmlContent(response.data);
            Swal.fire({
                title: '',
                html: form,
                showCancelButton: true,
                cancelButtonText : "Cancel",
                confirmButtonText: "Save Changes",
                showLoaderOnConfirm: true,
                preConfirm:  () => {
                    try {
                        const originalGroup = response.data;
                        let preSelectedModifiers = [];
                        const groupName = Swal.getPopup().querySelector('#sooGroupName').value
                        const form = Swal.getPopup().querySelector('#editModifierGroup')
                        const data = jQuery(form).serializeArray();
                        if (groupName === ''){
                            Swal.showValidationMessage(
                                'The Modifier Group name is required'
                            )
                            return;
                        }
                        //Get Selected Modifiers
                        if (data){
                            data.forEach(elm=>{
                                if(elm.name === "modifier"){
                                    preSelectedModifiers.push(elm.value)
                                }
                            })
                        }
                        //Check Min and Max
                        const maxAllowed = parseInt(originalGroup.max);
                        if (!isNaN(maxAllowed) && preSelectedModifiers.length > maxAllowed){
                            Swal.showValidationMessage(
                                'You can choose only up to '+maxAllowed+" modifiers"
                            )
                            return;
                        }
                        //Save Data
                        const payload = {
                            name:groupName,
                            selectedModifiers:preSelectedModifiers
                        }
                        return fetch(moo_RestUrl+"moo-clover/v1/inventory/modifier-groups/"+uuid,{
                            method: "POST",
                            mode: "cors",
                            cache: "no-cache",
                            credentials: "same-origin",
                            headers: {
                                "Content-Type": "application/json",
                                "X-WP-Nonce": moo_params.nonce
                            },
                            redirect: "follow",
                            referrerPolicy: "no-referrer",
                            body: JSON.stringify(payload),
                        })
                            .then(response => {
                                return {'status':'success'};
                            }).then(data => {
                                if(data.message){
                                    throw new Error(data.message)
                                } else {
                                    if (data === false){
                                        throw new Error(mooObjectL10n.anErrorOccurred)
                                    }
                                    return data;
                                }
                            })
                            .catch(error => {
                                Swal.showValidationMessage(
                                    error
                                )
                            })
                    } catch (e){
                        console.log(e)
                    }

                },
                allowOutsideClick: false
            }).then(function (data) {
                if(data.value?.status === "success" ) {
                    swal({
                        title:"Update Completed",
                        type:"success"
                    });
                }
            }, function (data) {
                if (data.dismiss === 'cancel') {
                    Swal.close();
                }
            });
        } else {
            swal({
                title: "Error",
                text: 'An error has occurred, try again',
                type: "error",
                confirmButtonText: "ok"
            });
        }
    }).fail(function (data) {
        swal({
            title: "Error",
            text: 'An error has occurred, try again',
            type: "error",
            confirmButtonText: "ok"
        });
    });
}
function getModifierGroupPopupHtmlContent(data){
    let html = '';
    const form = document.createElement('div');
    const minMaxInfo = sooModifierGroupRequirement(data.min,data.max);
    html += '<div class="moo-EditModifierGroupContainer" style="text-align: left;">';
    html += '<form id="editModifierGroup">';
    html += '<div class="moo-EditModifierGroupName" style="font-weight: bold;">';
    html += '<div style="padding: 10px 0px 5px 0px;">Edit the Name</div>';
    html += '<div class="moo-form-group">';
    if(data.alternateName){
        html += `<input style="width: 100%" type="text" value="${data.alternateName}" class="moo-form-control" name="sooGroupName" id="sooGroupName">`;
    } else {
        html += `<input style="width: 100%" type="text" value="${data.name}" class="moo-form-control" name="sooGroupName" id="sooGroupName">`;
    }
    html += '</div>';
    html += '</div>';
    html += '<div class="moo-EditModifierGroupName" style="font-weight: bold;padding: 10px 0px 5px 0px;">';
    html += 'Choose the PreSelected Modifiers';
    html += '</div>';
    html += '<div class="moo-modifiersPanel">';
    if(minMaxInfo !== '') {
        html += `<div style="padding-bottom: 10px;font-size: small;" class="EditModifierGroupTitle">Limits : ${minMaxInfo}</div>`;
    }
    html += `<div class="mooModifierGroup"><div id="mooModifiers-wrapper-for-${data.uuid}" class="mooModifiers-wrapper mooModifiers-wrapper-for-${data.uuid}">`;
    for(var j=0;j<data.modifiers.length;j++) {
        html += sooModifierLineInModifierGroup(data.modifiers[j],data.uuid,data.min,data.max);
    }
    html += '</div></div></form></div>';
    form.innerHTML = html;
    return form;
}
function sooModifierGroupRequirement(min_required,max_allowd) {
    var html ='';
    if (min_required !== null){
        min_required = parseInt(min_required);
    }
    if (max_allowd !== null){
        max_allowd = parseInt(max_allowd);
    }

    if(min_required === 1  && max_allowd === 1) {
        html += 'Only one option is allowed';
    } else {
        if(min_required != null && max_allowd != null && max_allowd === min_required ) {
            html += ' (' + mooObjectL10n.mustChoose + ' ' + min_required + ' ' + mooObjectL10n.options + ')';
        } else {
            if(min_required != null && max_allowd != null && min_required >= 1 &&  max_allowd > 1) {
                html +=' ('+mooObjectL10n.mustChooseBetween +' '+min_required +' & '+max_allowd+' '+ mooObjectL10n.options + ')';
            } else {
                if(min_required != null && min_required === 1)
                    html +=' ('+mooObjectL10n.mustChooseAtLeastOneOption+')';

                if(min_required != null && min_required > 1)
                    html +=' ('+mooObjectL10n.mustChooseAtLeast+' '+min_required +' '+mooObjectL10n.options+')';

                if(max_allowd != null && max_allowd > 1)
                    html +=' ('+mooObjectL10n.selectUpTo+' '+max_allowd +' '+mooObjectL10n.options+')';

                if(max_allowd != null && max_allowd === 1)
                    html +=' ('+mooObjectL10n.selectOneOption+')';
            }
        }
    }
    return html;
}
function sooModifierLineInModifierGroup(modifier,modifierG_uuid,min,max) {
    var modifier_price = parseFloat(modifier.price);
    modifier_price = modifier_price/100;
    var uuid =  modifier.uuid;
    var html='';

    if (modifier.is_pre_selected === "1"){
        html += '<div id="sooModifierLine-'+uuid+'" data-group-uuid="'+modifierG_uuid+'" class="moo-row sooModifierLine mooModifier-checked">'+
            '<div class="sooModifierLineOverlayer" onclick="mooClickOnModifierLine(event,\''+uuid+'\',\''+min+'\',\''+max+'\')"></div>'+
            '<div class="moo-col-lg-1 moo-col-md-1 moo-col-sm-1 moo-col-xs-1">';
        html += '<input role="checkbox" name="modifier" value="'+uuid+'" class="mooModifierCheckbox" type="checkbox" onchange="mooChangeModifierLine(event,\''+uuid+'\',\''+min+'\',\''+max+'\')" checked>';
    } else {
        html += '<div id="sooModifierLine-'+uuid+'" data-group-uuid="'+modifierG_uuid+'" class="moo-row sooModifierLine">'+
            '<div class="sooModifierLineOverlayer" onclick="mooClickOnModifierLine(event,\''+uuid+'\',\''+min+'\',\''+max+'\')"></div>'+
            '<div class="moo-col-lg-1 moo-col-md-1 moo-col-sm-1 moo-col-xs-1">';
        html += '<input role="checkbox" name="modifier" value="'+uuid+'" class="mooModifierCheckbox" type="checkbox" onchange="mooChangeModifierLine(event,\''+uuid+'\',\''+min+'\',\''+max+'\')">';
    }
    html += '</div>'+
        '<div class="moo-col-lg-8 moo-col-md-8 moo-col-sm-8 moo-col-xs-8 mooModifier-name"  tabindex="0">'+modifier.name+'</div>'+
        '<div class="moo-col-lg-2 moo-col-md-2 moo-col-sm-2 moo-col-xs-2 mooModifier-price" tabindex="0">'+((modifier_price>0)?'$'+modifier_price.toFixed(2):'')+'</div>';
    html += '</div>';

    return html;
}
function validerChangeNameGG(event,id){
    event.preventDefault();
    var newName = jQuery("#newName_"+id).val();
    //
    jQuery("#label_"+id+" .getname").css("display","block");
    jQuery("#label_"+id+" .change-name").css("display","none");
    jQuery("#label_"+id+" .getname").text(newName);
    jQuery.post(moo_params.ajaxurl,{'action':'moo_change_modifiergroup_name',"mg_uuid":id,"mg_name":newName}, function (data) {
        //console.log(data);
        }
    );
}
function annulerChangeNameGG(event,id) {
    event.preventDefault();
    jQuery("#label_"+id+" .getname").css("display","block");
    jQuery("#label_"+id+" .change-name").css("display","none");
}
function show_sub(event,id){
    event.preventDefault();
    jQuery('#detail_group_'+id).slideToggle('fast', function() {
        if (jQuery(this).is(':visible')) {
            jQuery("#plus_"+id).attr('src',moo_params.plugin_url+'/public/img/substract.png');
        } else {
            jQuery("#plus_"+id).attr('src',moo_params.plugin_url+'/public/img/add.png');
        }
    });
    //jQuery('#detail_group_'+id).slideToggle();
}
function edit_name_GModifer(event,id){
    event.preventDefault();
    jQuery("#label_"+id+" .getname").css("display","none");
    jQuery("#label_"+id+" .change-name-modifier").css("display","block");
}
function validerChangeNameModifier(event,id){
    event.preventDefault();
    var newName = jQuery("#newName_"+id).val();
    //
    jQuery("#label_"+id+" .getname").css("display","block");
    jQuery("#label_"+id+" .change-name-modifier").css("display","none");
    jQuery("#label_"+id+" .getname").text(newName);
    jQuery.post(moo_params.ajaxurl,{'action':'moo_change_modifier_name',"m_uuid":id,"m_name":newName}, function (data) {
            //console.log(data);
        }
    );
}
function annulerChangeNameModifier(event,id,name){
    event.preventDefault();
    jQuery("#label_"+id+" .getname").css("display","block");
    jQuery("#label_"+id+" .change-name-modifier").css("display","none");
}
/* --- Modifier Group --- */
function vald_change_name(event,uuid,v) {
    event.preventDefault();
    var name = jQuery("#name_"+uuid).val();
    var newname = jQuery("#newName"+uuid).val();
    if ( v=="D" ){jQuery("td#name_"+uuid).html(newname);}
    else{
        jQuery("#newName"+uuid).prop('disabled', true);
        jQuery("#bt_MV_"+uuid).remove();
        jQuery("#bt_MA_"+uuid).remove();
    }
    jQuery.post(moo_params.ajaxurl,{'action':'moo_change_name_category','newName':newname,"id_cat":uuid}, function(response){
        if(response == 1){
           // console.log(response);
        }
        else{
            jQuery("td#name_"+uuid).html(name_cat);
        }
    });
}


function annuler_change_name(event,uuid,v,lastname){
    event.preventDefault();
    if (v == "D"){jQuery("td#name_"+uuid).html(name_cat);}
    else{
        jQuery("#newName"+uuid).val(lastname);
        jQuery("#newName"+uuid).prop('disabled', true);
        jQuery("#bt_MV_"+uuid).remove();
        jQuery("#bt_MA_"+uuid).remove();
    }
}

function MooChangeM_Status(uuid)
{
    var mg_status = jQuery('#myonoffswitch_'+uuid).prop('checked');
    jQuery.post(moo_params.ajaxurl,{'action':'moo_update_modifier_status',"mg_uuid":uuid,"mg_status":mg_status}, function (data) {
            console.log(data);
        }
    );
}

function delete_img_category(event,uuid,responsive){
    event.preventDefault();
    var image = "";
    if(responsive == 'D'){
        tr_new(uuid,image);
    } else {
        img_row(uuid,image)
    }
    jQuery.post(moo_params.ajaxurl,{'action':'moo_delete_img_category',"uuid":uuid}, function(data){
     if (data == 1) {
         //console.log(data);
     }
     });
}

function img_row(uuid,img){
    var html="<label>Operation</label>";
    html +='<div class="bt bt-upload">';
    html +='<a href="#" onclick="uploader_image_category(event,&quot;'+uuid+'&quot;,&quot;M&quot;)">';
    html +="<img src='"+moo_params.plugin_url+"public/img/upload.png' style='width: 20px;'>";
    html +='</a>';
    html +='</div>';
    html +='<div class="bt bt-edit">';
    html +='<a href="#" onclick="edit_name_mobil(event,&quot;'+uuid+'&quot;)">';
    html +="<img src='"+moo_params.plugin_url+"public/img/edit.png' style='width: 20px;'>";
    html +='</a>';
    html +='</div>';
    if(img == ""){
        jQuery("#id_img_M_"+uuid).html("<label>Pecture</label><img src='"+moo_params.plugin_url+"/public/img/no-image.png' style='width: 50px;'>")
        jQuery("#id_bt_M"+uuid).html(html);
    }
    else{
        html +='<div class="bt bt-delete">';
        html +='<a href="#" onclick="delete_img_category(event,&quot;'+uuid+'&quot;,&quot;M&quot;)">';
        html +="<img src='"+moo_params.plugin_url+"public/img/delete.png' style='width: 20px;'>";
        html +='</a>';
        html +='</div>';
        jQuery("#id_img_M_"+uuid).html("<label>Pecture</label><img src='"+img+"' style='width: 50px;'>");
        jQuery("#id_bt_M"+uuid).html(html);
    }
}
function edit_name_mobil(event,uuid){
    event.preventDefault();
    var name = jQuery("#newName"+uuid).val();
    var htmlC = "";
    htmlC +='<a href="#" class="vald-change-name" onclick="vald_change_name(event,&quot;'+uuid+'&quot;,&quot;M&quot;)" id="bt_MV_'+uuid+'">';
    htmlC +="<img src='"+moo_params.plugin_url+"/public/img/valider.png'  alt='Validate change' style='width: 18px;'>";
    htmlC +="</a>";
    htmlC +='<a href="#" class="annuler-change-name" onclick="annuler_change_name(event,&quot;'+uuid+'&quot;,&quot;M&quot;,&quot;'+name+'&quot;)" id="bt_MA_'+uuid+'">';
    htmlC +="<img src='"+moo_params.plugin_url+"/public/img/annuler.png' alt='annuler change' style='width: 18px;margin-left: 2px;'>";
    htmlC +="</a>";
    jQuery("#bt_MV_"+uuid).remove();
    jQuery("#bt_MA_"+uuid).remove();
    jQuery("#newName"+uuid).prop('disabled', false);
    jQuery("#name_cat_Mobil"+uuid).append(htmlC);
}

// add parametre ,image,name,visibility
function tr_new(uuid,img){
    var nameCat = jQuery("td#name_"+uuid).text();
    var visib_cat = jQuery(".visib_cat"+uuid).is(":checked")? true : false;
    var input_check = "";
    if (visib_cat == true){
        input_check = '<input type="checkbox" name="onoffswitch[]" id="myonoffswitch_Visibility_'+uuid+'" class="moo-onoffswitch-checkbox visib_cat'+uuid+'" onclick="visibility_cat(&quot;'+uuid+'&quot;)" checked>';
    }
    else{
        input_check = '<input type="checkbox" name="onoffswitch[]" id="myonoffswitch_Visibility_'+uuid+'" class="moo-onoffswitch-checkbox visib_cat'+uuid+'" onclick="visibility_cat(&quot;'+uuid+'&quot;)">';
    }
    var cont_html = "";
    cont_html +="<td style='display:none;'><span id='id_cat'>"+uuid+"</span></td>";
    cont_html +="<td class='img-cat'' style='width: 50px;' id='"+uuid+"'>";
    if(img == ""){
        cont_html +="<img src='"+moo_params.plugin_url+"/public/img/no-image.png' style='width: 50px;'>";
    }
    else {
        cont_html +="<img src='"+img+"' style='width: 50px;'>";
    }
    cont_html +="</td>";
    cont_html +="<td class='name_cat' id='name_"+uuid+"'>";
    cont_html +=nameCat;
    cont_html +="</td>";
    cont_html +="<td class='show-cat'>";
    cont_html +='<div class="moo-onoffswitch" title="Visibility Category">';
    cont_html +=input_check;
    cont_html +='<label class="moo-onoffswitch-label" for="myonoffswitch_Visibility_'+uuid+'"><span class="moo-onoffswitch-inner"></span>';
    cont_html +='<span class="moo-onoffswitch-switch"></span>';
    cont_html +='</label>';
    cont_html +="</div>";
    cont_html +="</td>";
    if (img == ""){
        cont_html +="<td class='bt-cat'>";
        cont_html +='<a href="#" onclick="uploader_image_category(event,&quot;'+uuid+'&quot;,&quot;D&quot;)" title="Uploader Image">';
        cont_html +="<img src='"+moo_params.plugin_url+"/public/img/upload.png'>";
        cont_html +="</a>";
        cont_html +="</td>";
        cont_html +="<td colspan='2' class='bt-cat'>";
    cont_html +="<a href='#' class='edit_name' title='Edite Name'>";
    cont_html +="<img src='"+moo_params.plugin_url+"/public/img/edit.png' alt='Edite Name' >";
    cont_html +="</a>";
    cont_html +="</td>";
    }
    else{
        cont_html +="<td class='bt-cat'>";
        cont_html +='<a href="#" onclick="uploader_image_category(event,&quot;'+uuid+'&quot;,&quot;D&quot;)" title="Change Image">';
        cont_html +="<img src='"+moo_params.plugin_url+"/public/img/upload.png'>";
        cont_html +="</a>";
        cont_html +="</td>";
        cont_html +="<td class='bt-cat'>";
        cont_html +="<a href='#' class='edit_name' title='Edite Name'>";
        cont_html +="<img src='"+moo_params.plugin_url+"/public/img/edit.png'>";
        cont_html +="</a>";
        cont_html +="</td>";
        cont_html +="<td class='bt-cat'>";
        cont_html +='<a href="#" onclick="delete_img_category(event,&quot;'+uuid+'&quot;,&quot;D&quot;)" title="Delete Image">'
        cont_html +="<img src='"+moo_params.plugin_url+"/public/img/delete.png'>";
        cont_html +="</a>";
        cont_html +="</td>";
    }
    cont_html +="<td class='items_cat'>";
    cont_html +="<a href='#detailCat"+uuid+"' class='moo-open-popupItems'>";
    cont_html +="<img src='"+moo_params.plugin_url+"/public/img/plusIT.png' style='width: 20px;'>";
    cont_html +="</a>";
    cont_html +="</td>";
    jQuery("tr#row_id_"+uuid).html(cont_html);
    if(typeof jQuery.magnificPopup !== 'undefined' ) {
        jQuery('.moo-open-popupItems').magnificPopup({
            type:'inline',
            midClick: true // Allow opening popup on middle mouse click. Always set it to true if you don't provide alternative source in href.
        });
    }

    jQuery("tr#row_id_"+uuid).html(cont_html);
}
function visibility_cat(uuid) {
    var check = jQuery(".visib_cat"+uuid).is(":checked")? true : false;
    jQuery.post(moo_params.ajaxurl,{'action':'moo_update_visiblite_category','visiblite':check,"id_cat":uuid}, function(response){
        //console.log(response);
    });
}
function visibility_cat_mobile(uuid) {
    var check = jQuery("#visib"+uuid).is(":checked")? true : false;
    jQuery.post(moo_params.ajaxurl,{'action':'moo_update_visiblite_category','visiblite':check,"id_cat":uuid}, function(response){
        //console.log(response);
    });
}
function tab_clicked(tab)
{
    var Nb_Tabs=12; // Number for tabs
    for(var i=1;i<=Nb_Tabs;i++) {
        jQuery('#MooPanel_tabContent'+i).hide();
        jQuery('#MooPanel_tab'+i).removeClass("MooPanel_Selected");
    }
    jQuery('#MooPanel_tabContent'+tab).show();
    jQuery('#MooPanel_tab'+tab).addClass("MooPanel_Selected");

    if(tab === 9 &&  window.moo_first_time === true) {
        moo_getLatLongforMapDa();
        if(typeof moo_merchantAddress !== "undefined"){
            window.moo_first_time = false;
        }
    }
    //refresh when clicking on categories
    if(tab === 5){
        if(window.customHoursForCategoriesUpdated)  {
            Moo_CustomHoursForCategories();
        }
    }
    //refresh when clicking on ordertypes
    if(tab === 3){
        if( window.customHoursForOrderTypesUpdated ) {
            Moo_RefreshOrderTypesSection();
        }
    }
    if(tab === 12){
        window.customHoursForCategoriesUpdated = true;
        window.customHoursForOrderTypesUpdated = true;

       // var iframe = document.getElementById('mooFrameCustomHours');
       // iframe.src = iframe.src;

    }
    jQuery("#menu_for_mobile ul").toggle();

}
function MooPanel_ImportItems(event)
{
    event.preventDefault();
    jQuery('#MooPanelSectionImport').html(window.moo_loading);
    jQuery('#MooPanelSectionImportItems').html('');
    Moo_ImportLabels();
}
function MooPanel_RefreshPage(event)
{
    event.preventDefault();
    window.location.reload();
}
var flag_key_not_found = false;

function Moo_ImportCategories() {
    jQuery.post(moo_params.ajaxurl,{'action':'moo_import_categories'}, function (data) {
            if(data.status === 'Success') {
                jQuery('#MooPanelSectionImportCategories').append(data.data);
            } else {
                jQuery('#MooPanelSectionImport').append("Error when importing the categories, please try again");
            }
    }).done(function () {
        setTimeout(function (){
            jQuery('#MooPanelSectionImport').html("All of your data was successfully imported from Your Clover POS. Please click refresh button below or re-upload this page"+'<br/> ');
            jQuery('#MooPanelSectionImportItems').html('');
            jQuery('#MooPanelSectionImportCategories').html('');
            jQuery('#MooPanelButtonImport').html('<a href="#" onclick="MooPanel_RefreshPage(event)" class="button button-secondary" style="margin-bottom: 35px;" >Refresh</a>');
        },2000);

        moo_Update_stats();
        Moo_GetOrderTypes();
        Moo_SetupCategoriesSection();

    }).fail(function (response) {
        console.log(response);
        jQuery('#MooPanelSectionImport').html("An error has occurred, please re-import again or refresh the page and try again"+'<br/> ');
        jQuery('#MooPanelSectionImportItems').html('');
        jQuery('#MooPanelSectionImportCategories').html('');
        jQuery('#MooPanelButtonImport').html('<a href="#" onclick="MooPanel_RefreshPage(event)" class="button button-secondary" style="margin-bottom: 35px;" >Refresh</a>  <a href="#" onclick="MooPanel_ImportItems(event)" class="button button-secondary" style="margin-bottom: 35px;">Re-Import</a>');
    });
}

function Moo_ImportLabels() {
    jQuery.post(moo_params.ajaxurl,{'action':'moo_import_labels'}, function (data) {
        if(data.status === 'Success') {
            if(data.data === "Please verify your Key in page settings") {
                flag_key_not_found = true;
                jQuery('#MooPanelSectionImport').html('Please verify your API Key<br/> ');
            } else {
                flag_key_not_found = false;
                jQuery('#MooPanelSectionImport').append('<br/> '+data.data);
            }
        } else {
            jQuery('#MooPanelSectionImport').append('<br/> '+"Error when importing the label, please try again");
        }
    }).done(function () {
        Moo_ImportTaxes();
    }).fail(function (response) {
        console.log(response);
        jQuery('#MooPanelSectionImport').html("An error has occurred, please re-import again or refresh the page and try again"+'<br/> ');
        jQuery('#MooPanelSectionImportItems').html('');
        jQuery('#MooPanelButtonImport').html('<a href="#" onclick="MooPanel_RefreshPage(event)" class="button button-secondary" style="margin-bottom: 35px;" >Refresh</a>  <a href="#" onclick="MooPanel_ImportItems(event)" class="button button-secondary" style="margin-bottom: 35px;">Re-Import</a>');
    });
}
function Moo_ImportTaxes() {
    if(!flag_key_not_found) {
        jQuery.post(moo_params.ajaxurl,{'action':'moo_import_taxes'}, function (data) {
            if(data.status ==='Success')
                jQuery('#MooPanelSectionImport').append('<br/> '+data.data);
            else
                jQuery('#MooPanelSectionImport').append('<br/> '+"Error when importing the tax rates, please try again");
        }).done(function () {
            Moo_ImportItems();
        }).fail(function (response) {
            console.log(response);
            jQuery('#MooPanelSectionImport').html("An error has occurred, please re-import again or refresh the page and try again"+'<br/> ');
            jQuery('#MooPanelSectionImportItems').html('');
            jQuery('#MooPanelButtonImport').html('<a href="#" onclick="MooPanel_RefreshPage(event)" class="button button-secondary" style="margin-bottom: 35px;" >Refresh</a>  <a href="#" onclick="MooPanel_ImportItems(event)" class="button button-secondary" style="margin-bottom: 35px;">Re-Import</a>');
        });
    }

}
function Moo_ImportItems() {
    Moo_ImportItemsV2(0);
}
function Moo_ImportItemsV2(page) {
    var received = 0;
    var productsNb = 0;
    jQuery.post(moo_params.ajaxurl,{'action':'moo_import_items_v2','page':page}, function (data) {
            received = data.received;
            productsNb = data.currentNb;
    }).done(function () {
        if(received > 0) {
            jQuery('#MooPanelSectionImportItems').html(productsNb + " Items imported"+'<br/> ');
            Moo_ImportItemsV2(page+1);
        } else {
            Moo_ImportCategories();
        }
    }).fail(function (response) {
        jQuery('#MooPanelSectionImport').html("An error has occurred, please re-import again or refresh the page and try again"+'<br/> ');
        jQuery('#MooPanelSectionImportItems').html('');
        jQuery('#MooPanelButtonImport').html('<a href="#" onclick="MooPanel_RefreshPage(event)" class="button button-secondary" style="margin-bottom: 35px;" >Refresh</a>  <a href="#" onclick="MooPanel_ImportItems(event)" class="button button-secondary" style="margin-bottom: 35px;">Re-Import</a>');
    });
}

function Moo_GetOrderTypes(uuid = null){
    if( document.querySelector('#MooOrderTypesContent') != null )
    {
        document.querySelector('#MooOrderTypesContent').innerHTML  ="<div style='text-align: center'>Loading your ordertypes, please wait...</div>";
        jQuery.post(moo_params.ajaxurl,{'action':'moo_getAllOrderTypes'}, function (data) {
            if(data.status == 'success') {
                var orderTypes = {};
                try {
                    orderTypes = JSON.parse(data.data);
                } catch (e) {
                    console.log("Parsing error: orderTypes");
                }
                var html='<p>Drag and drop to rearrange</p><ul style="margin-bottom: 10px">';
                if(orderTypes.length>0) {
                    for(var i=0;i<orderTypes.length;i++) {
                        var $ot = orderTypes[i];
                        if($ot.label == "") continue;
                        if ($ot.status==1){
                            html += '<li class="moo_orderType enabled" ot_uuid="'+$ot.ot_uuid+'">';
                        }
                        else{
                            html += '<li class="moo_orderType disabled" ot_uuid="'+$ot.ot_uuid+'">';
                        }

                        html +='<div class="moo_item_order">';
                        html +='<span style="float: left">';
                        html += $ot.label ;
                        html +='</span>';
                        if ($ot.status==1){
                            html += '<span style="margin-left: 10px"><img src="'+moo_params.plugin_url+"/public/img/bullet_ball_green.png"+'"/></span>';
                        }
                        else{
                            html += '<span style="margin-left: 10px"><img src="'+moo_params.plugin_url+"/public/img/bullet_ball_glass_grey.png"+'"/></span>';
                        }
                        html +='<span style="float: right;font-size: 12px;" id="top-bt-'+$ot.ot_uuid+'">';
                        //html += '<a href="#" onclick="moo_showOrderTypeDetails(event,&quot;'+$ot.ot_uuid+'&quot;)">Edit</a> | <a href="#" title="Delete this order types from the wordpress Database" onclick="Moo_deleteOrderType(event,&quot;'+$ot.ot_uuid+'&quot;)">DELETE</a>';
                        html += `<a href="#" onclick="moo_showOrderTypeDetails(event,'${$ot.ot_uuid}')">Edit</a> | `;
                        html += `<a href="#" title="Delete this order types from the wordpress Database" onclick="Moo_deleteOrderType(event,'${$ot.ot_uuid}')">DELETE</a>`;
                        html +='</span>';
                        html += '</div>';
                        if(uuid && uuid===$ot.ot_uuid) {
                            html +='<div class="Detail_OrderType" id="detail_'+$ot.ot_uuid+'" style="display: block">';
                        } else {
                            html +='<div class="Detail_OrderType" id="detail_'+$ot.ot_uuid+'">';
                        }

                        html +='<div class="champ_order name_order"><span class="label_Torder">Order Type Name </span><input type="text" id="label_'+$ot.ot_uuid+'" value="'+$ot.label+'" style="width: 160px;"></div>';
                        //enable/disable
                        html +='<div class="champ_order IsEnabled_order"><span class="label_Torder">Disable / Enabled </span>';

                        html +='<div class="moo-onoffswitch" title="Enable or disable this order type">';

                        if ($ot.status==1){
                            html += '<input type="checkbox" name="onoffswitch[]" id="select_En_'+$ot.ot_uuid+'" class="moo-onoffswitch-checkbox" checked>';
                        }
                        else{
                            html += '<input type="checkbox" name="onoffswitch[]" id="select_En_'+$ot.ot_uuid+'" class="moo-onoffswitch-checkbox" >';
                        }
                        html +='<label class="moo-onoffswitch-label" for="select_En_'+$ot.ot_uuid+'"><span class="moo-onoffswitch-inner"></span>';
                        html +='<span class="moo-onoffswitch-switch"></span>';
                        html +='</label>';
                        html +="</div>";
                        html += '</div>';
                        //Min amount
                        html +='<div class="champ_order Taxable_order"><span class="label_Torder">Minimum Amount </span>';
                        html +='<input type="text" id="minAmount_'+$ot.ot_uuid+'" value="'+$ot.minAmount+'" style="width: 160px;">';
                        html += '</div>';
                        //Max amount
                        html +='<div class="champ_order Taxable_order"><span class="label_Torder">Maximum Amount </span>';
                        html +='<input type="text" id="maxAmount_'+$ot.ot_uuid+'" value="'+$ot.maxAmount+'" style="width: 160px;">';
                        html += '</div>';
                        //show Taxable
                        html +='<div class="champ_order Taxable_order"><span class="label_Torder">Taxable </span>';
                        html +='<select style="width: 160px;" id="select_Tax_'+$ot.ot_uuid+'">';
                        html +='<option value="1" '+(($ot.taxable==1)?"selected":"")+'>Yes</option>';
                        html +='<option value="0" '+(($ot.taxable==0)?"selected":"")+'>No</option>';
                        html +='</select>';
                        html += '</div>';


                        //Delivery Order
                        html +='<div class="champ_order type_order"><span class="label_Torder">Delivery Order </span>';
                        html +='<select style="width: 160px;" id="select_type_'+$ot.ot_uuid+'">';
                        html +='<option value="1" '+(($ot.show_sa==1)?"selected":"")+'>Yes</option>';
                        html +='<option value="0" '+(($ot.show_sa==0)?"selected":"")+'>No</option>';
                        html +='</select>';
                        html +='<br/><small>Selecting Yes Will require customers to provide their delivery address</small>';
                        html += '</div>';


                        //Use Coupons
                        html +='<div class="champ_order IsEnabled_order"><span class="label_Torder">Allow Coupons </span>';
                        html +='<div class="moo-onoffswitch" title="Enable or disable this coupons">';

                        if ($ot.use_coupons==1){
                            html += '<input type="checkbox" name="onoffswitch[]" id="useCoupons_'+$ot.ot_uuid+'" class="moo-onoffswitch-checkbox" checked>';
                        }
                        else{
                            html += '<input type="checkbox" name="onoffswitch[]" id="useCoupons_'+$ot.ot_uuid+'" class="moo-onoffswitch-checkbox" >';
                        }
                        html +='<label class="moo-onoffswitch-label" for="useCoupons_'+$ot.ot_uuid+'"><span class="moo-onoffswitch-inner"></span>';
                        html +='<span class="moo-onoffswitch-switch"></span>';
                        html +='</label>';
                        html +="</div>";
                        html += '</div>';

                        //Allow Scheduled Orders
                        html +='<div class="champ_order IsEnabled_order"><span class="label_Torder">Allow Scheduled Orders </span>';
                        html +='<div class="moo-onoffswitch" title="Allow or not scheduled orders">';

                        if ( $ot.allow_sc_order == 1 ){
                            html += '<input type="checkbox" name="onoffswitch[]" id="allowScOrders_'+$ot.ot_uuid+'" class="moo-onoffswitch-checkbox" checked>';
                        }
                        else{
                            html += '<input type="checkbox" name="onoffswitch[]" id="allowScOrders_'+$ot.ot_uuid+'" class="moo-onoffswitch-checkbox" >';
                        }
                        html +='<label class="moo-onoffswitch-label" for="allowScOrders_'+$ot.ot_uuid+'"><span class="moo-onoffswitch-inner"></span>';
                        html +='<span class="moo-onoffswitch-switch"></span>';
                        html +='</label>';
                        html +="</div>";
                        html += '</div>';

                        //Allow Service Fee
                        html +='<div class="champ_order IsEnabled_order"><span class="label_Torder">Allow Service Fee </span>';
                        html +='<div class="moo-onoffswitch" title="Add The Default Service Fee To This Order Type">';

                        if (
                            $ot.allow_service_fee === 1 ||
                            $ot.allow_service_fee === "1" ||
                            $ot.allow_service_fee === true ||
                            $ot.allow_service_fee === "true"
                        ){
                            html += '<input type="checkbox" name="onoffswitch[]" id="allowServiceFee_'+$ot.ot_uuid+'" class="moo-onoffswitch-checkbox" checked>';
                        } else {
                            html += '<input type="checkbox" name="onoffswitch[]" id="allowServiceFee_'+$ot.ot_uuid+'" class="moo-onoffswitch-checkbox" >';
                        }
                        html +='<label class="moo-onoffswitch-label" for="allowServiceFee_'+$ot.ot_uuid+'"><span class="moo-onoffswitch-inner"></span>';
                        html +='<span class="moo-onoffswitch-switch"></span>';
                        html +='</label>';
                        html +="</div>";
                        html += '</div>';

                        //Limit Availability time
                        html +='<div class="champ_order type_order"><span class="label_Torder">Ordering Hours</span>';
                        html +='<select name="" id="availabilityCustomTime_'+$ot.ot_uuid+'" >' +
                            '<option value="">Default Clover Business Hours</option>'+
                            '<optgroup label="Choose Order Type Hours">' ;
                        //Add the Custom Hours to the select
                        if(moo_custom_hours_for_ot){
                            Object.keys(moo_custom_hours_for_ot).forEach(function (key) {
                                html +=  '<option value="'+key+'"' ;
                                if($ot.custom_hours!== null && $ot.custom_hours === key) {
                                    html += 'selected' ;
                                }
                                html +=   '>'+moo_custom_hours_for_ot[key]+'</option>';
                            });
                        }


                        html +=  '</optgroup>';
                        html +=  '</select>' ;
                        html +='</div>';
                        //Custom message
                        html +='<div class="champ_order IsEnabled_order">Custom message to display when ordering method is not available (maximum 200 characters)';
                        html +='<div><textarea style="width: 100%;" cols="100" rows="5" id="moo_ot_customMessage_'+$ot.ot_uuid+'">'+$ot.custom_message+'</textarea></div>';
                        html += '</div>';
                        //Buttons
                        html +='<div class="bt_update_order" style="float: right;">';
                        html +='<a class="button" style="min-width: 70px !important;margin-right: 5px;" onclick="moo_saveOrderType(event,&quot;'+$ot.ot_uuid+'&quot;)">Save</a>';
                        html +='<a class="button" style="min-width: 70px !important;" onclick="Moo_GetOrderTypes()">Cancel</a>';
                        html += '</div>';
                        html += '</div>';
                        html += '</li>';
                    }
                } else {
                    html = "<div class='normal_text' >You don't have any OrderTypes,<br/> please import your data by clicking on <b>Import / Sync Inventory</b></div>";
                }
                html += "</ul>";
                document.querySelector('#MooOrderTypesContent').innerHTML = html;
                window.customHoursForOrderTypesUpdated = false;
                makeOrderTypesSortable();
            } else {
                document.querySelector('#MooOrderTypesContent').innerHTML  ="<div style='text-align: center'>Please verify your API Key<br/></div>";
            }

        });
    }
}

function makeOrderTypesSortable() {
    jQuery("#MooOrderTypesContent ul").sortable({
        stop: function(event, ui) {
            var tabNew = new Array();
            var i = 0;
            jQuery("#MooOrderTypesContent ul li").each(function(i, el){
                tabNew[i] = jQuery(this).attr("ot_uuid");
                i++;
            });
            jQuery.post(moo_params.ajaxurl,{'action':'moo_reorder_ordertypes','newtable':tabNew},function(data){
                if(data===false)
                    swal('Sort Order Not Changed, please contact us to fix that');
            })
        }
    });
}

function Moo_RefreshOrderTypesSection(){
    if( document.querySelector('#MooOrderTypesContent') != null )
    {
        document.querySelector('#MooOrderTypesContent').innerHTML  ="<div style='text-align: center'>Loading your ordertypes, please wait...</div>";
    }
    if(moo_RestUrl.indexOf("?rest_route") !== -1 ){
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/ordertypes_hours/&_wpnonce='+ moo_params.nonce;
    } else {
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/ordertypes_hours/?_wpnonce='+ moo_params.nonce;
    }
    jQuery.get(endpoint, function (data) {
        if(data.status === "success"){
            moo_custom_hours_for_ot= data.data;
        } else {
            console.log(moo_custom_hours_for_ot);
        }
        Moo_GetOrderTypes();
    }).fail(function (response) {
        console.log(response);
        Moo_GetOrderTypes();
    });

}
function Moo_SetupCategoriesSection( uuid = null ){
    if( document.querySelector('.moo-categories-section') !== null ) {
        document.querySelector('#MooPanel_tabContent5 .moo-categories-section').innerHTML =  "Loading  your categories,  please wait..."

        jQuery(".moo-categories-section").show();
        jQuery("#moo-categories-edit-section").hide().html('');
        jQuery("#moo-btn-backtocategories").hide();
        jQuery("#moo-btn-reordercategories").show();

        if(moo_RestUrl.indexOf("?rest_route") !== -1){
            var endpoint = moo_RestUrl+'moo-clover/v2/dash/categories&_wpnonce='+ moo_params.nonce;
        } else {
            var endpoint = moo_RestUrl+'moo-clover/v2/dash/categories?_wpnonce='+ moo_params.nonce;

        }
        jQuery.get(endpoint, function (response) {
            if(response.status === 'success') {
                var categories = response.data;
                var html ='';
                if(categories.length>0) {
                    for(var i=0;i<categories.length;i++) {
                        var category = categories[i];
                        html += '<div class="moo-row moo-category-row" id="moo-category-row-'+category.uuid+'" onmouseenter="mooShowQuicklinks(this)" onmouseleave="mooHideQuicklinks(this)" >';
                        html += '<div class="moo-col-md-2">';
                        if(category.image_url === null){
                            html += '<div class="moo-category-image moo-cat-no-img">';
                            html +='<span class="moo-category-image-delete-btn" onclick="mooDeleteCategoryImage(event,\''+category.uuid+'\')"><img alt="remove the  image" src="'+ moo_params.plugin_url+"/public/img/close.png"  +'"/></span>';
                            html += '<img src="'+moo_params.plugin_url+"/public/img/noImg3.png"+'" class="moo-category-img-'+category.uuid+'"/>';
                        } else {
                            html += '<div class="moo-category-image">';
                            html +='<span class="moo-category-image-delete-btn" onclick="mooDeleteCategoryImage(event,\''+category.uuid+'\')"><img alt="remove the  image" src="'+ moo_params.plugin_url+"/public/img/close.png"  +'"/></span>';
                            html += '<img src="'+ category.image_url  +'" class="moo-category-img-'+category.uuid+'"/>';
                        }
                        html += '<div class="moo-category-image-PicSelector" onclick="mooUploadImageForCategory(event,\''+category.uuid+'\')">' +
                            '<div class="moo-category-image-label">' +
                            'Update'+
                            '</div>'+
                            '</div>';
                        html += '</div>';
                        html += '</div>';
                        html += '<div class="moo-col-md-8 moo-category-info">';

                        html += '<div class="moo-category-uuid">'+ category.uuid +'</div>';

                        if(category.alternate_name !== null && category.alternate_name !== "") {
                            html += '<div class="moo-category-title">'+ category.alternate_name +'</div>';
                        } else {
                            html += '<div class="moo-category-title">'+ category.name +'</div>';
                        }


                       // html += '<div class="moo-category-uuid">XLAIHZ354155</div>';
                       // html += '<div class="moo-category-title">Dozen bagels dunkin donuts</div>';

                        html += '<div class="moo-category-description">';
                        if( category.description !== null){
                            html += category.description;
                        } else {
                           // html += "Our Real Special marinara sauce with fresh tomatoes baked on our signature thin crust, baked to a perfect crisp. We create food we’re proud to serve and deliver it fast, with a smile.";
                            html += "";
                        }
                        html +='</div>';
                        //html += '<div class="moo-category-quicklinks"><a href="">Edit Name or Description</a></div>';
                        html += '<div class="moo-category-quicklinks"></div>';
                        html += '</div>';
                        html += '<div class="moo-col-md-2 moo-category-actions-container">';
                        html += '<div class="moo-category-actions">' +
                            '<div class="soo-onoffswitch">' +
                            '   <input type="checkbox" name="onoffswitch[]" id="myonoffswitch_Visibility_'+ category.uuid +'" class="soo-onoffswitch-checkbox visib_cat'+ category.uuid +'" onclick="visibility_cat(\''+ category.uuid +'\')" ';
                            if(category.show_by_default === "1") {
                                html += 'checked' ;
                            }
                            html +=  '>' +
                            '       <label class="soo-onoffswitch-label" for="myonoffswitch_Visibility_'+ category.uuid +'"><span class="soo-onoffswitch-inner"></span>' +
                            '       <span class="soo-onoffswitch-switch"></span>' +
                            '       </label>' +
                            '   </div>';
                        html += '<div class="moo-category-action-edit" onclick="Moo_SetupEditCategorySection(event,\''+ category.uuid +'\')">' +
                                '<img src="'+ moo_params.plugin_url +'/public/img/settings.png" />'+
                                '</div>';
                        html += '</div>';
                        html += '</div>';
                        html += '</div>';
                    }
                } else{
                    html += "<div class='normal_text' >You don't have any category,<br/> please import your data by clicking on <b>Import Inventory</b></div>";
                }

                document.querySelector('#MooPanel_tabContent5 .moo-categories-section').innerHTML = html;
                //to refresh categories section only if custom hours clicked
                window.customHoursForCategoriesUpdated = false;

                if(uuid){
                    var cat_row_id = '#moo-category-row-'+uuid;
                    var box = document.querySelector('#MooPanel_main');
                    var targetElm = document.querySelector(cat_row_id);
                    if(targetElm){
                        scrollToElm( box, targetElm , 1 );
                    }
                }
                swal.close();
            } else {
                var html = "<div class='normal_text' >We cannot get your categories, Please verify your API Key and your wordpress settings, if you are using HTTPS and in your settings you have HTTP</div>";
                document.querySelector('#MooPanel_tabContent5 .moo-categories-section').innerHTML = html;
                swal.close();
            }
        }).fail(function () {
            var html = "<div class='normal_text' >We cannot get your categories, Please verify your API Key and your wordpress settings, if you are using HTTPS and in your settings you have HTTP</div>";
            document.querySelector('#MooPanel_tabContent5 .moo-categories-section').innerHTML = html;
            swal.close();
        });
    }
}
function Moo_SetupReorderCategoriesSection(event){
    event.preventDefault();

    if( document.querySelector('.moo-categories-section') !== null )
    {
        jQuery(".moo-categories-section").hide();
        jQuery("#moo-categories-edit-section").show().html('');
        document.querySelector('#MooPanel_tabContent5 #moo-categories-edit-section').innerHTML = "Loading your categories...";

        if(moo_RestUrl.indexOf("?rest_route") !== -1){
            var endpoint = moo_RestUrl+'moo-clover/v2/dash/categories&_wpnonce='+ moo_params.nonce;
        } else {
            var endpoint = moo_RestUrl+'moo-clover/v2/dash/categories?_wpnonce='+ moo_params.nonce;
        }

        jQuery.get(endpoint, function (response) {
            if(response.status === 'success') {
                var categories = response.data;
                var html  = '<div class="moo-row moo-goback-row">';
                html += '<div class="moo-goback-icon" onclick="Moo_SetupCategoriesSection()"><img src="'+moo_params.plugin_url+'/public/img/back.png"/></div><div onclick="Moo_SetupCategoriesSection()" class="moo-goback-text">Back</div>';
                html += '</div>';
                if(categories.length>0) {
                    html += '<div class="moo-row moo-reorder-category-row">';
                    for(var i=0;i<categories.length;i++) {
                        var category = categories[i];
                        html += '<div class="moo-reorder-category moo-reorder-category-title" catid="'+category.uuid+'">';
                        if(category.alternate_name !== null && category.alternate_name !== ""){
                            html +=  category.alternate_name;
                        } else {
                            html +=  category.name;
                        }

                        html +=  '</div>';
                    }
                } else{
                    html += "<div class='normal_text' >You don't have any category,<br/> please import your categories by clicking on <b>Import inventory</b></div>";

                }
                html += '</div>';

                document.querySelector('#MooPanel_tabContent5 #moo-categories-edit-section').innerHTML = html;

                jQuery(".moo-reorder-category-row").sortable({
                    stop: function(event, ui) {
                        var newSortOrder = new Array();
                        jQuery(".moo-reorder-category-row .moo-reorder-category ").each(function (i, el) {
                            newSortOrder[i] = jQuery(this).attr("catid");
                        });
                        jQuery.post(moo_params.ajaxurl,{'action':'moo_new_order_categories','newtable':newSortOrder},function(data){
                            console.log(data);
                        })
                    }
                });
            }
            else
                document.querySelector('#MooPanel_tabContent5 #moo-categories-edit-section').innerHTML  ="<div style='text-align: center'>Please verify your API Key<br/></div>";

        }).fail(function () {
            swal({
                title:"An error has occurred",
                text:"Please try again or contact us",
                type:"error"
            });

        });
    }
}
function Moo_RefeshEditCategorySection(event,uuid) {
    if(event){
        event.preventDefault();
    }
    if(moo_RestUrl.indexOf("?rest_route") !== -1 ){
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/categories_hours/&_wpnonce='+ moo_params.nonce;
    } else {
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/categories_hours/?_wpnonce='+ moo_params.nonce;
    }
    jQuery.get(endpoint, function (data) {
        if(data.status === "success"){
            moo_custom_hours = data.data;
        } else {
           // console.log(moo_custom_hours);
        }
        Moo_SetupEditCategorySection(event,uuid);
    }).fail(function (response) {
        console.log(response);
        Moo_SetupEditCategorySection(event,uuid);
    });
}
function Moo_CustomHoursForCategories() {
    if( document.querySelector('.moo-categories-section') !== null ) {
        jQuery(".moo-categories-section").show();
        jQuery("#moo-categories-edit-section").hide().html('');
        jQuery("#moo-btn-backtocategories").hide();
        jQuery("#moo-btn-reordercategories").show();
        document.querySelector('#MooPanel_tabContent5 .moo-categories-section').innerHTML = "Loading your categories please wait";
    }
    if(moo_RestUrl.indexOf("?rest_route") !== -1){
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/categories_hours/&_wpnonce='+ moo_params.nonce;
    } else {
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/categories_hours/?_wpnonce='+ moo_params.nonce;
    }
    jQuery.get(endpoint, function (data) {
        if(data.status === "success"){
            moo_custom_hours = data.data;
        } else {
            console.log(moo_custom_hours);
        }
        Moo_SetupCategoriesSection();
    }).fail(function (response) {
        console.log(response);
        Moo_SetupCategoriesSection();
    });
}

function moo_click_on_limitTime( uuid ) {
    if(moo_RestUrl.indexOf("?rest_route") !== -1){
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/category/'+uuid+'/time&_wpnonce='+ moo_params.nonce;
    } else {
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/category/'+uuid+'/time?_wpnonce='+ moo_params.nonce;
    }

    var check = jQuery("#myonoffswitch_limit_time_"+uuid).is(":checked")? true : false;
    if(check){
        jQuery("#moo-av-time-for-"+uuid).slideDown("slow");
        // send changes to server
        jQuery.post(endpoint,{"status":"custom"});
    } else {
        jQuery("#moo-av-time-for-"+uuid).slideUp("slow");
        // send changes to server
        jQuery.post(endpoint,{"status":"all"});
    }
}
function moo_EditCategoryTimeAvailability(event, uuid) {
    if(event) {
        event.preventDefault();
    }
    var cat_time_status = jQuery("#myonoffswitch_limit_time_"+uuid).is(":checked")? "custom" : "all";
    var cat_time = jQuery("#moo-category-availability-time-" + uuid).val();
    if(cat_time === ""){
        swal({
            title:"No Time selected",
            text:"Please choose a time or add a new one",
            type:"error"
        });
        return;
    }
    if(moo_RestUrl.indexOf("?rest_route") !== -1){
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/category/'+uuid+'/time&_wpnonce='+ moo_params.nonce;
    } else {
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/category/'+uuid+'/time?_wpnonce='+ moo_params.nonce;
    }

    mooShowWaitMessage();
    jQuery.post(endpoint,{"status":cat_time_status,"hour":cat_time}, function (response) {

        if(response.status === "success" ) {
            swal({
                title:"The category updated",
                type:"success"
            });
        } else {
            swal({
                title:"No changes detected",
                text:"Choose new Custom Hours and press save",
                type:"error"
            });
            console.log(response);
        }
    }).fail(function () {
        swal({
            title:"An error has occurred",
            text:"Please try again or contact us",
            type:"error"
        });
        mooHideWaitMessage();
    });
}

function Moo_SetupEditCategorySection(event, uuid) {
    if(event) {
        event.preventDefault();
    }
    if( document.querySelector('.moo-categories-section') !== null ) {
        jQuery(".moo-categories-section").hide();
        jQuery("#moo-categories-edit-section").show().html('');
        document.querySelector('#MooPanel_tabContent5 #moo-categories-edit-section').innerHTML  ="<div>Loading the details please wait...<br/></div>";

        if(moo_RestUrl.indexOf("?rest_route") !== -1){
            var endpoint = moo_RestUrl+'moo-clover/v2/dash/category/'+uuid+'&_wpnonce='+ moo_params.nonce;
        } else {
            var endpoint = moo_RestUrl+'moo-clover/v2/dash/category/'+uuid+'?_wpnonce='+ moo_params.nonce;
        }

        jQuery.get(endpoint, function (data) {
            if(data.uuid !== undefined ) {
                var category = data;
                var html  = '<div class="moo-row moo-goback-row">';
                    html += '<div class="moo-goback-icon" onclick="Moo_SetupCategoriesSection(\''+category.uuid+'\')"><img src="'+moo_params.plugin_url+'/public/img/back.png"/></div><div onclick="Moo_SetupCategoriesSection(\''+category.uuid+'\')" class="moo-goback-text">Back</div>';
                    html += '<div class="mooHelpRefreshLinks">' +
                            '<a href="#" onclick="Moo_RefeshEditCategorySection(event,\''+ category.uuid +'\')">Refresh</a>'+
                            ' | <a href="https://docs.zaytech.com" target="_blank">Help</a>'+
                            '</div>';
                    html += '</div>';

                // Edit section
                html += '<div class="moo-row moo-category-row">';
                html += '<div class="moo-col-md-2">';

                if(category.image_url === null){
                    html += '<div class="moo-category-image moo-cat-no-img">';
                    html +='<span class="moo-category-image-delete-btn" onclick="mooDeleteCategoryImage(event,\''+category.uuid+'\')"><img alt="remove the  image" src="'+ moo_params.plugin_url+"/public/img/close.png"  +'"/></span>';
                    html += '<img src="'+moo_params.plugin_url+'/public/img/noImg3.png" class="moo-category-img-'+category.uuid+'"/>';
                } else {
                    html += '<div class="moo-category-image">';
                    html += '<span class="moo-category-image-delete-btn" onclick="mooDeleteCategoryImage(event,\''+category.uuid+'\')"><img alt="remove the  image" src="'+ moo_params.plugin_url+"/public/img/close.png"  +'"/></span>';
                    html += '<img src="'+ category.image_url  +'" class="moo-category-img-'+category.uuid+'"/>';
                }
                html += '<div class="moo-category-image-PicSelector" onclick="mooUploadImageForCategory(event,\''+category.uuid+'\')">' +
                    '<div class="moo-category-image-label">' +
                    'Update'+
                    '</div>'+
                    '</div>';
                html += '</div>';
                html += '</div>';
                html += '<div class="moo-col-md-8 moo-category-info">';
                html += '<div class="moo-input-title">Name</div>';
                if(category.alternate_name !== null && category.alternate_name !== "" && category.alternate_name !== category.name){
                    html += '<div class="moo-category-title"><input class="moo-category-title-input" type="text" value="'+ category.alternate_name.replace(/"/g, '&quot;') +'" />';
                    html += "<div class='moo-category-title-cloverName'> Name on Clover : "+ category.name.replace(/"/g, '&quot;') +'</div></div>';
                } else {
                    html += '<div class="moo-category-title"><input class="moo-category-title-input" type="text" value="'+ category.name.replace(/"/g, '&quot;') +'" /></div>';
                }

               // html += '<div class="moo-category-title"><input class="moo-category-title-input" type="text" value="Dozen bagels dunkin donuts" /></div>';
                html += '<div class="moo-input-title">Description</div>';
                html += '<div class="moo-category-description"><textarea class="moo-category-description-input">';
                if( category.description !== null){
                    html += category.description;
                } else {
                   // html += "Our Real Special marinara sauce with fresh tomatoes baked on our signature thin crust, baked to a perfect crisp. We create food we’re proud to serve and deliver it fast, with a smile.";
                    html += "";
                }
                html +='</textarea></div>';
                //Custom Hours section
                html += '<div class="moo-category-custom-hours">';
                html += '<div class="moo-input-title">Ordering Hours</div>';

                html +='<select name="" id="availabilityCustomTime_'+category.uuid+'" >' +
                    '<option value="">Default Clover Business Hours</option>'+
                    '<optgroup label="Choose Categories Hours">' ;
                    //Add the Custom Hours to the select
                    if(moo_custom_hours){
                        Object.keys(moo_custom_hours).forEach(function (key) {
                            html +=  '<option value="'+key+'"' ;
                            if(category.custom_hours !== null && category.custom_hours === key && category.time_availability === "custom") {
                                html += 'selected' ;
                            }
                            html +=   '>'+moo_custom_hours[key]+'</option>';
                        });
                    }
                html +=  '</optgroup>';
                html +=  '</select>' ;

                html += '</div>';

                html += '</div>';
                html += '<div class="moo-col-md-2 moo-category-actions-container">';
                html += '<div class="moo-category-actions">' ;
                html += '<div class="moo-category-action-edit" onclick="Moo_EditCategory(event,\''+category.uuid+'\')">' +
                    '<div class="moo-category-action-edit-button-save">Save</div>'+
                    '</div>';
                html += '</div>';
                html += '</div>';
                html += '</div>';

                // Category hours section
                var htmlOld = "";
                htmlOld += '<div class="moo-row moo-category-row moo-category-hours-row">';
                htmlOld += '<div class="moo-col-md-12  moo-category-hours-row-buttons">' +
                    '</div>';
                htmlOld += '<div class="moo-col-md-10 moo-category-info">';
                htmlOld += '<div class="moo-category-timing-title">Use custom hours for this category</div>';
                htmlOld += '</div>';
                htmlOld += '<div class="moo-col-md-2 moo-category-actions-container">';
                htmlOld += '<div class="moo-category-actions">' +
                    '<div class="moo-onoffswitch">' +
                    '   <input type="checkbox" name="onoffswitch[]" id="myonoffswitch_limit_time_'+ category.uuid +'" class="moo-onoffswitch-checkbox cat_'+ category.uuid +'" onclick="moo_click_on_limitTime(\''+ category.uuid +'\')" ';
                if(category.time_availability === "custom") {
                    htmlOld += 'checked' ;
                }
                htmlOld +=  '>' +
                    '       <label class="moo-onoffswitch-label" for="myonoffswitch_limit_time_'+ category.uuid +'"><span class="moo-onoffswitch-inner"></span>' +
                    '       <span class="moo-onoffswitch-switch"></span>' +
                    '       </label>' +
                    '   </div>';
                htmlOld += '</div>';
                htmlOld += '</div>';
                htmlOld += '</div>';
                // Choose hours
                htmlOld += '<div class="moo-category-timing-content" id="moo-av-time-for-'+category.uuid+'">';
                htmlOld += '<div class="moo-col-md-12  moo-category-timing-header"></div>';
                htmlOld += '<div class="moo-col-md-8  moo-category-timing-body">' +
                    '<div class="moo-col-md-4">Choose hours</div> '  +
                    '<div class="moo-col-md-8"> <select name="" id="moo-category-availability-time-'+category.uuid+'" >' +
                    '<option value="">Select</option>';

                //Add the Custom Hours to the select
                if(moo_custom_hours){
                    Object.keys(moo_custom_hours).forEach(function (key) {
                        htmlOld +=  '<option value="'+key+'"' ;
                        if(category.custom_hours!== null && category.custom_hours === key) {
                            htmlOld += 'selected' ;
                        }
                        htmlOld +=   '>'+moo_custom_hours[key]+'</option>';
                    });
                }


                htmlOld +=  '</select>' +
                    '</div>' +
                    '</div>';
                htmlOld += '<div class="moo-col-md-3  moo-category-timing-footer"><div class="moo-category-save-time-button" onclick="moo_EditCategoryTimeAvailability(event, \''+category.uuid+'\')">Save</div></div>';
                htmlOld += '</div>';


                // Sync section
                html += '<div class="moo-row moo-category-sync-row">';
                html += '<div class="moo-col-md-9 moo-category-sync-col1">';
                html += '   <div class="moo-category-sync-line1">You can rearrange items by dragging and dropping.</div>';
                html += '   <div class="moo-category-sync-line2">If you don\'t see all items, click “Sync” to sync this category with your Clover Inventory</div>';
                html += '   <div class="moo-category-sync-line3">To make changes to items. <a href="admin.php?page=moo_items&category='+category.uuid+'" >Select Items images / description</a></div>';
                html += '</div>';
                html += '<div class="moo-col-md-3 moo-category-sync-col2" onclick="mooSyncOneCategory(\''+category.uuid+'\')"><div class="moo-category-sync-button-sync">Sync</div></div>';
                html += '</div>';

                // Reorder items section
                html += '<div class="moo-row moo-reorder-category-items-row">';
                if(category.items !== undefined && category.items.length>0) {
                    for(var i=0;i<category.items.length;i++) {
                        var item = category.items[i];
                        if(item.visible === 0){
                            html += '<div class="moo-reorder-category-title moo-reorder-category-hidden" item_uuid="'+item.uuid+'" category_uuid="'+category.uuid+'">' +
                                item.name +
                                '</div>';
                        } else {
                            html += '<div class="moo-reorder-category-title" item_uuid="'+item.uuid+'" category_uuid="'+category.uuid+'">' +
                                item.name +
                                '</div>';
                        }

                    }
                }

                html += '</div>';

                document.querySelector('#MooPanel_tabContent5 #moo-categories-edit-section').innerHTML = html;

                jQuery(".moo-reorder-category-items-row").sortable({
                    stop: function(event, ui) {
                        var newSortOrder = new Array();
                        var category = null;
                        jQuery(".moo-reorder-category-items-row .moo-reorder-category-title ").each(function (i, el) {
                            newSortOrder[i] = jQuery(this).attr("item_uuid");
                            category = jQuery(this).attr("category_uuid");
                        });
                        jQuery.post(moo_params.ajaxurl,{
                            'action':'moo_reorder_items',
                            'newtable':newSortOrder,
                            'uuid':category,
                        },function(data){
                          //  console.log(data);
                        })
                    }
                });

                if(category.time_availability === "custom"){
                    jQuery("#moo-av-time-for-"+uuid).slideDown("slow");
                } else  {
                    jQuery("#moo-av-time-for-"+uuid).slideUp("slow");
                }

            } else {
                document.querySelector('#MooPanel_tabContent5 #moo-categories-edit-section').innerHTML  ="<div style='text-align: center'>Please verify your API Key<br/></div>";
            }

        }).fail(function () {
            swal({
                title:"An error has occurred",
                text:"Please try again or contact us",
                type:"error"
            });
            jQuery(".moo-categories-section").show();
            jQuery("#moo-categories-edit-section").hide();

        });
    }
}

function Moo_EditCategory(event,uuid) {
    if(event) {
        event.preventDefault();
    }
    var cat_newName = jQuery(".moo-category-title-input").val();
    var cat_newDescription = jQuery(".moo-category-description-input").val();
    var cat_newCustomHours = jQuery("#availabilityCustomTime_"+uuid).val();

    if(moo_RestUrl.indexOf("?rest_route") !== -1){
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/category/'+uuid+'&_wpnonce='+ moo_params.nonce;
    } else {
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/category/'+uuid+'?_wpnonce='+ moo_params.nonce;
    }
    mooShowWaitMessage();
    jQuery.post(endpoint,{
        "cat_name":cat_newName,
        "cat_description":cat_newDescription,
        "cat_customHours":cat_newCustomHours,
    }, function (response) {
        if(response.status === "success" ) {
            swal({
                title:"Update Completed",
                type:"success"
            });
        } else {
            swal({
                title:"No changes detected",
                text:"Adding images does not require pressing save",
                type:"error"
            });
        }
    }).fail(function () {
        swal({
            title:"An error has occurred",
            text:"Please try again or contact us",
            type:"error"
        });
        mooHideWaitMessage();
    });
}

function mooShowQuicklinks(element){
    jQuery(".moo-category-quicklinks",element).show();
}
function mooHideQuicklinks(element){
    jQuery(".moo-category-quicklinks",element).hide();
}
function moo_Update_stats() {
    jQuery.post(moo_params.ajaxurl,{'action':'moo_get_stats'}, function (data) {
            if(data.status === 'Success'){
                window.moo_nb_allItems = data.products;
                jQuery({someValue: 0}).animate({someValue: data.products}, {
                    duration: 5000,
                    easing:'swing',
                    step: function() {jQuery('#MooPanelStats_Products').html(Math.round(this.someValue));}
                });
                jQuery({someValue: 0}).animate({someValue: data.cats}, {
                    duration: 3000,
                    easing:'swing',
                    step: function() {jQuery('#MooPanelStats_Cats').html(Math.round(this.someValue));}
                });
                jQuery({someValue: 0}).animate({someValue: data.labels}, {
                    duration: 3000,
                    easing:'swing',
                    step: function() {jQuery('#MooPanelStats_Labels').html(Math.round(this.someValue));}
                });
                jQuery({someValue: 0}).animate({someValue: data.taxes}, {
                    duration: 3000,
                    easing:'swing',
                    step: function() {jQuery('#MooPanelStats_Taxes').html(Math.round(this.someValue));}
                });
                setTimeout(function(){

                    jQuery('#MooPanelStats_Products').html(data.products);
                    jQuery('#MooPanelStats_Cats').html(data.cats);
                    jQuery('#MooPanelStats_Labels').html(data.labels);
                    jQuery('#MooPanelStats_Taxes').html(data.taxes);

                },5000);
            }

        }
    );
}

function MooChangeOT_Status(uuid) {
    var ot_status = jQuery('#myonoffswitch_'+uuid).prop('checked');
    jQuery.post(moo_params.ajaxurl,{'action':'moo_update_ot_status',"ot_uuid":uuid,"ot_status":ot_status}, function (data) {
           console.log(data);
        }
    );
}
function MooChangeOT_Status_Mobile(uuid) {
    var ot_status = jQuery('#myonoffswitch_mobile_'+uuid).prop('checked');
    jQuery.post(moo_params.ajaxurl,{'action':'moo_update_ot_status',"ot_uuid":uuid,"ot_status":ot_status}, function (data) {
            console.log(data);
        }
    );
}
function moo_addordertype(e)
{
    e.preventDefault();

    var label   = document.querySelector('#Moo_AddOT_label').value;
    var taxable = document.querySelector('#Moo_AddOT_taxable_oui').checked ;
    var show_sa = jQuery("#Moo_AddOT_delivery_oui").prop("checked");
    var minAmount = document.querySelector('#Moo_AddOT_minAmount').value;
    var nonce = document.querySelector('#Moo_AddOT_nonce').value;
   // var maxAmount = document.querySelector('#Moo_AddOT_maxAmount').value;
    if(label === "")
        swal("Error","Please enter a label for your order Type","error");
    else
    {
        jQuery('#Moo_AddOT_loading').html(window.moo_loading);
        jQuery('#Moo_AddOT_btn').hide();

        jQuery.post(moo_params.ajaxurl,{
            'action':'moo_add_ot',
            "nonce":nonce,
            "label":label,
            "taxable":taxable,
            "minAmount":minAmount,
            "show_sa":show_sa
        }, function (data) {
            if(data.status === 'success')
            {
                if(data.message === '401 Unauthorized')
                    jQuery('#Moo_AddOT_loading').html('Verify your API key');
                else {
                    swal({
                        title:"Order type added",
                        type:'success'

                    });
                    Moo_GetOrderTypes();
                    jQuery('#Moo_AddOT_loading').html('');
                    jQuery('#Moo_AddOT_btn').show();
                }

            } else {
                jQuery('#Moo_AddOT_loading').html('Verify your API key');
            }
         }).fail(function () {
            jQuery('#Moo_AddOT_loading').html('');
            jQuery('#Moo_AddOT_btn').show();
        });
    }

}
function Moo_deleteOrderType(e,uuid)
{
    e.preventDefault();
    swal({
        text: 'Please confirm that you want delete this order type',
        type: 'warning',
        showCancelButton: true,
        showLoaderOnConfirm: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!',
        preConfirm: function(data) {
            return new Promise(function (resolve, reject) {
                jQuery.post(moo_params.ajaxurl,{
                    'action':'moo_delete_ot',
                    'nonce':moo_params.nonce,
                    "uuid":uuid
                }, function (data) {
                    if(data !== null && data.status === 'success') {
                        resolve(true);
                    } else {
                        reject(false);
                    }
                }).fail(function ( data ) {
                    reject(false);
                });
            });
        }
    }).then(function (result) {
        if(result.value) {
            Moo_GetOrderTypes();

            swal({
                title:"Order type deleted",
                type:'success'
            });

        } else {
            if(!result.dismiss) {
                swal({
                    title:"Order type not deleted, try again",
                    type:'error'

                });
            }
        }
    });
}

function MooSendFeedBack(e)
{
    e.preventDefault();
    var msg   =  jQuery("#Moofeedback").val();
    var email =  jQuery("#MoofeedbackEmail").val();
    var name  =  jQuery("#MoofeedBackFullName").val();
    var bname =  jQuery("#MoofeedBackBusinessName").val();
    var phone =  jQuery("#MoofeedBackPhone").val();
    var website =  jQuery("#MoofeedBackWebsiteName").val();

    if(msg === '') {
        swal("Error","Please enter your message","error");
    } else {
        if(email === '') {
            swal("Error","Please enter your email, so we can contact you again","error");
        } else {
            var data = {
                'name':name,
                'bname':bname,
                'message':msg,
                'email':email,
                'phone':phone,
                'website':website
            };
            jQuery("#MooSendFeedBackBtn").text("Sending...").attr("onclick","event.preventDefault()");
            jQuery.post(moo_params.ajaxurl,{'action':'moo_send_feedback','data':data}, function (data) {
                if(data.status === "success"){
                    swal("Thank you","Your question has been sent. We will get back to you shortly","success");
                    jQuery("#Moofeedback").val("");
                    jQuery("#MooSendFeedBackBtn").text("Send").attr("onclick","MooSendFeedBack(event)")
                } else {
                    swal("Sorry","Your question hasn't been sent. Please try again or contact support@zaytech.com","error");
                    jQuery("#MooSendFeedBackBtn").text("Send").attr("onclick","MooSendFeedBack(event)");
                }
            }).fail(function (data) {
                swal("Sorry","Your question hasn't been sent. Please try again or contact support@zaytech.com","error");
                jQuery("#MooSendFeedBackBtn").text("Send").attr("onclick","MooSendFeedBack(event)");
            });
        }

    }
}
/* Modifiers Panel */

function Moo_changeModifierGroupName(uuid)
{
    var mg_name = jQuery('#Moo_ModifierGroupNewName_'+uuid).val();
    jQuery.post(moo_params.ajaxurl,{'action':'moo_change_modifiergroup_name',"mg_uuid":uuid,"mg_name":mg_name}, function (data) {
           jQuery('#Moo_ModifierGroupSaveName_'+uuid).show();
        }
    );
    setTimeout(function () {
        jQuery('#Moo_ModifierGroupSaveName_'+uuid).hide();
    }, 5000);

}
function Moo_changeModifierGroupName_Mobile(uuid)
{
    var mg_name = jQuery('#Moo_ModifierGroupNewName_mobile_'+uuid).val();
    jQuery.post(moo_params.ajaxurl,{'action':'moo_change_modifiergroup_name',"mg_uuid":uuid,"mg_name":mg_name}, function (data) {
            console.log(data);
            jQuery('#Moo_ModifierGroupSaveName_mobile_'+uuid).show();
        }
    );
    setTimeout(function () {
        jQuery('#Moo_ModifierGroupSaveName_mobile_'+uuid).hide();
    }, 5000);
}
function MooChangeModifier_Status(uuid) {
    var mg_status = jQuery('#myonoffswitch_'+uuid).prop('checked');
    jQuery.post(moo_params.ajaxurl,{'action':'moo_update_modifiergroup_status',"mg_uuid":uuid,"mg_status":mg_status}, function (data) {
            console.log(data);
        }
    );
}
function MooChangeModifier_Status_Mobile(uuid)
{
    var mg_status = jQuery('#myonoffswitch_mobile_'+uuid).prop('checked');
    jQuery.post(moo_params.ajaxurl,{'action':'moo_update_modifiergroup_status',"mg_uuid":uuid,"mg_status":mg_status}, function (data) {
            console.log(data);
        }
    );
}
/* Categories Panel */
function Moo_changeCategoryName(uuid)
{
    var cat_name = jQuery('#Moo_categoryNewName_'+uuid).val();
    if(cat_name != '')
        jQuery.post(moo_params.ajaxurl,{'action':'moo_change_category_name',"cat_uuid":uuid,"cat_name":cat_name}, function (data) {
                jQuery('#Moo_CategorySaveName_'+uuid).show();
            }
        );
        setTimeout(function () {
            jQuery('#Moo_CategorySaveName_'+uuid).hide();
        }, 5000);
}
function Moo_changeCategoryName_Mobile(uuid)
{
    var cat_name = jQuery('#Moo_categoryNewName_mobile_'+uuid).val();
    if(cat_name != '')
        jQuery.post(moo_params.ajaxurl,{'action':'moo_change_category_name',"cat_uuid":uuid,"cat_name":cat_name}, function (data) {
                jQuery('#Moo_CategorySaveName_mobile_'+uuid).show();
            }
        );
    setTimeout(function () {
        jQuery('#Moo_CategorySaveName_mobile_'+uuid).hide();
    }, 5000);
}
function MooChangeCategory_Status(uuid) {
    var cat_status = jQuery('#myonoffswitch_'+uuid).prop('checked');
    jQuery.post(moo_params.ajaxurl,{'action':'moo_update_category_status',"cat_uuid":uuid,"cat_status":cat_status}, function (data) {
            console.log(data);
        }
    );
}
function MooChangeOrderLater_Status() {
    var status = jQuery('#myonoffswitch_order_later').prop('checked');

    if(status)
        jQuery("#moo_orderLater_Details").slideDown();
    else
        jQuery("#moo_orderLater_Details").slideUp();
}
function mooShowMoreDetails(event,id) {
    var status = jQuery(event.target).prop('checked');
    if(status)
        jQuery(id).slideDown();
    else
        jQuery(id).slideUp();
}
function MooChangeCategory_Status_Mo(uuid)
{
    var cat_status = jQuery('#myonoffswitch_NoCategory_Mobile').prop('checked');
    jQuery.post(moo_params.ajaxurl,{'action':'moo_update_category_status',"cat_uuid":uuid,"cat_status":cat_status}, function (data) {
            console.log(data);
        }
    );
}
function MooShowCategoriesImages(id)
{
    var status = jQuery('#'+id).prop('checked');
    jQuery.post(moo_params.ajaxurl,{'action':'moo_update_category_images_status',"status":status}, function (data) {
            console.log(data);
        }
    );
}
function MooShowCategoriesImages_Mobile(id)
{
    var status = jQuery('#myonoffswitch_Visibility_Mobile').prop('checked');
    jQuery.post(moo_params.ajaxurl,{'action':'moo_update_category_images_status',"status":status}, function (data) {
            console.log(data);
        }
    );
}
function MooChangeCategory_Status_Mobile(uuid)
{
    var cat_status = jQuery('#myonoffswitch_mobile_'+uuid).prop('checked');
    jQuery.post(moo_params.ajaxurl,{'action':'moo_update_category_status',"cat_uuid":uuid,"cat_status":cat_status}, function (data) {
            console.log(data);
        }
    );
}
/* Start Upload Images Function */

var media_uploader  = null;
var moo_item_images = [];// {"image_url": "", "image_default": "", "image_enabled": ""}
var moo_item_options = [];// {"name": "", "min": "", "max": "","modifiers":[]}

var moo_category_images;

function uploader_image_category(event,id,responsive){
    event.preventDefault();
    media_uploader = wp.media({
        frame:    "post",
        state:    "insert",
        multiple: false
    });
    // insert image
    media_uploader.on("insert", function(){
        var json = media_uploader.state().get("selection").first().toJSON();
        var image_url = json.url;
        moo_category_images = image_url;
        moo_save_category_images(id,responsive);
    });
    media_uploader.open();
}
function mooUploadImageForCategory(event,uuid){
    event.preventDefault();
    media_uploader = wp.media({
        frame:    "post",
        state:    "insert",
        multiple: false
    });
    // on insert image
    media_uploader.on("insert", function(){
        var json = media_uploader.state().get("selection").first().toJSON();
        var image_url = json.url;
        moo_category_images = image_url;
        var body = {
                'action':'moo_save_category_image',
                'category_uuid':uuid,
                'image':image_url
        };


        var element = jQuery( ".moo-categories-section .moo-category-img-"+uuid );
        element.attr("src",image_url);
        element.parent().removeClass("moo-cat-no-img");

        jQuery.post(moo_params.ajaxurl,body,function(result){
            if (result !== 1) {
                console.log("Image not updated, technical issue or you've used the same image as before")
            }
        });
    });
    media_uploader.open();
}
function mooDeleteCategoryImage(event,uuid){
    event.preventDefault();
    swal({
        title: "Are you sure?",
        text: "Please confirm the suppression of this image",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#DD6B55",
        confirmButtonText: "Yes, detach it!",
        showLoaderOnConfirm: true,
        cancelButtonText: "No, cancel!",
        closeOnConfirm: false,
        closeOnCancel: false
    }).then(function(result){
        if (result.value) {
            //mooShowWaitMessage();
            var defaultImg = moo_params.plugin_url + "/public/img/noImg3.png";
            var element = jQuery( ".moo-categories-section .moo-category-img-"+uuid );
            element.attr("src",defaultImg);
            element.parent().addClass("moo-cat-no-img");
            jQuery.post(moo_params.ajaxurl,{'action':'moo_delete_img_category',"uuid":uuid}, function(data){
                if (data !== 1) {
                    swal("Error","Error when removing the image try again or contact the support team ","error");
                }
            });
        }
    });
}

function moo_save_category_images(uuid,response)
{
    if(Object.keys(moo_category_images).length>0)
    {
        image = moo_category_images;
        if(response == 'D'){
            tr_new(uuid,image);
        }
       else {
            img_row(uuid,image);
        }
        jQuery.post(moo_params.ajaxurl,{'action':'moo_save_category_image',"category_uuid":uuid,"image":moo_category_images},function(ret){
            if (ret == 1) {
                //console.log(ret);
            }
            else
                swal("Error","Error when saving your changes, please try again","error");

        });
    }
    else
    {
        history.back();
    }
}
function open_media_uploader_image() {
    media_uploader = wp.media({
        frame:    "post",
        state:    "insert",
        multiple: false
    });

    media_uploader.on("insert", function(){
        json = media_uploader.state().get("selection").first().toJSON();
        
        var image_url = json.url;
        var image_caption = json.caption;
        var image_title = json.title;
        moo_item_images.push({"image_url": image_url, "image_default": "1", "image_enabled": "1"});
        moo_display_item_images();

    });
    media_uploader.open();
}
function show_itemOptions(event,id){
    event.preventDefault();
    jQuery('#itemChoices_'+id).slideToggle('fast', function() {
        if (jQuery(this).is(':visible')) {
            jQuery("#plus_itemOption_"+id).attr('src',moo_params.plugin_url+'/public/img/substract.png');
        } else {
            jQuery("#plus_itemOption_"+id).attr('src',moo_params.plugin_url+'/public/img/add.png');
        }
    });
    //jQuery('#detail_group_'+id).slideToggle();
}
function moo_display_item_options() {
    jQuery('#moo_item_options').html('');
    if(moo_item_options.length>0){
        var html = "<h1>Options</h1>";
             html += "<ul class='moo_ModifierGroup ui-sortable'>";
        for(var i in moo_item_options){
            var option = moo_item_options[i];
            html += "<li class='list-group ui-sortable-handle' id='itemOption_"+i+"'>" +
                "<span class='show-detail-group'>" +
                '<a href="#" onclick="show_itemOptions(event,\''+i+'\')">' +
                '<img src="'+ moo_params.plugin_url+'/public/img/substract.png" id="plus_itemOption_'+i+'" style="width: 20px;">'+
                "</a>" +
                "</span>"+
                "<div class='label_name'><label>"+option.name+"</label></div>" +
                "<ul class='sub-group ui-sortable' id='itemChoices_"+i+"'>";
            for(j in option.modifiers){
                html +="<li>"+option.modifiers[j].name;
                if(option.modifiers[j].price !== 0){
                    var p = option.modifiers[j].price.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,");
                    html +=" (+$"+option.modifiers[j].price.toFixed(2)+") ";
                }
                html +="</li>";
            }


            html +="</ul>"+
                "</li>";
        }
        html += '</ul>';
        jQuery('#moo_item_options').append(html);
        jQuery('#moo_item_options').show();
    } else {

    }
}
function moo_display_item_images() {
    jQuery('#moo_itemimagesection').html('');
    for(var i in moo_item_images){
        var image = moo_item_images[i].image_url;
        var a1 = parseInt(moo_item_images[i].image_default);
        var b1 = parseInt(moo_item_images[i].image_enabled);
        var tag = "";
        var tag1 = "";
        if (a1 === 1) {
            tag = "<input id='image_default_id_"+i+"' onchange='moo_default_item_image("+i+")' type='radio' name='image_default' value='image_default' checked><label style='position: relative; top: -4px; right: -10px;' for='image_default_id_"+i+"'>Default Image</label>";
        } else {
            tag = "<input id='image_default_id_"+i+"' onchange='moo_default_item_image("+i+")' type='radio' name='image_default' value='image_default'><label style='position: relative; top: -4px; right: -10px;' for='image_default_id_"+i+"'>Default Image</label>";
        }
        if (b1 === 1) {
            tag1 = "<input id='image_enabled_id_"+i+"' onchange='moo_enable_item_image("+i+")' type='checkbox' name='image_enabled"+i+"' value='image_enabled' checked><label style='position: relative; top: -4px; right: -10px;' for='image_enabled_id_"+i+"'>Image Enabled</label>";
        } else {
            tag1 = "<input id='image_enabled_id_"+i+"' onchange='moo_enable_item_image("+i+")' type='checkbox' name='image_enabled"+i+"' value='image_enabled'><label style='position: relative; top: -4px; right: -10px;' for='image_enabled_id_"+i+"'>Image Enabled</label>";
        }
        /*var html = '<table style="margin: 0 auto;">'+
                    '<tr><td rowspan="3"><img height="200" width="300" src="'+image+'" alt=""></td>'+
                    '<td><a href="#" onclick="moo_delete_item_images(\''+i+'\')">Delete</a></td>'+
                    '<tr><td>'+tag+'</td></tr>'+
                    '<tr><td>'+tag1+'</td></tr></table>';*/

        var html = '<div class="image_item" style="width: 30%; display: inline-block; margin: 1%;">'+
                    '<img class="img-rounded img-thumbnail img-responsive image1" width="" src="'+image+'" alt="">'+
                    '<div class="image_options_holder"><div><a href="#" onclick="moo_delete_item_images(&quot;'+i+'&quot;)">Delete</a></div>'+
                    '<div style="margin-top: 4px;">'+tag+'</div>'+
                    '<div style="margin-top: 4px;">'+tag1+'</div></div></div>';
        jQuery('#moo_itemimagesection').append(html);

    }
}
function moo_delete_item_images(id) {
    delete(moo_item_images[id]);
    moo_display_item_images();
}
function moo_default_item_image(id) {
    jQuery("input[name=image_default]:checked");
    moo_item_images[id].image_default = "1";
    for (var i = 0; i < moo_item_images.length; i++) {
        if(i == id) continue;
        else moo_item_images[i].image_default = "0";
    }
}
function moo_enable_item_image(id) {
    var b = jQuery("input#image_enabled_id_"+id+"").is(':checked');
    if (b) {
        moo_item_images[id].image_enabled = "1";
        b = false;    
    } else {
        moo_item_images[id].image_enabled = "0";
        b = true;
    }
}

function moo_save_item_images(uuid) {
    mooShowWaitMessage();
    var description = jQuery('#moo_item_description').val();
    var flag = false;
    for(var i=0 in moo_item_images) {
        if (moo_item_images[i].image_default == "1" && flag == false) {
            flag = true;
            continue;
        }
        if(moo_item_images[i].image_default == "1" && flag == true) {
            moo_item_images[i].image_default = "0";
        }
    }
    var images = [];
    for(var i in moo_item_images) {
        images.push({"image_url": moo_item_images[i].image_url, 
            "image_default": moo_item_images[i].image_default, 
            "image_enabled": moo_item_images[i].image_enabled
        });
    }
    jQuery.post(moo_params.ajaxurl,{'action':'moo_save_items_with_images',"item_uuid":uuid,"description":description,"images":images}, function (data) {
        if(data.status == 'Success') {
            if(data.data == true) {
               var goBackLink = jQuery("#mooGoBackButton").attr("href");
               if(goBackLink) {
                   swal({
                       title: 'Your changes were saved',
                       type: 'success',
                       showCancelButton: true,
                       showLoaderOnConfirm: false,
                       confirmButtonColor: '#3085d6',
                       cancelButtonColor: '#d33',
                       confirmButtonText: 'Ok',
                       cancelButtonText: 'Go back to items'
                   }).then(function (data) {
                       if(data.dismiss) {
                           swal.close();
                           window.location.href = goBackLink;
                       }
                   });
               } else {
                   swal("Your changes were saved");
               }

            } else {
                swal("Error","Error when saving your changes, maybe your description is too long  please try again","error");
            }
        } else {
            swal("Error","Error when saving your changes, please try again","error");
        }
    }
    ).fail(function () {
        swal("Error","Error when saving your changes, please try again","error");
    });

}

function moo_get_item_with_images(uuid) {
    jQuery.post(moo_params.ajaxurl,{'action':'moo_get_items_with_images',"item_uuid":uuid}, function (data) {
        var items = data.data;
        moo_item_options= data.modifier_groups;
        for(var i in items ){
            var item = items[i];
            if(item._id) {
                var image_url = item.url;
                var image_default = item.is_default;
                var image_enabled = item.is_enabled;
                moo_item_images.push({"image_url": image_url, "image_default": image_default, "image_enabled": image_enabled});
            }
        }
        moo_display_item_images();
        moo_display_item_options();
    });
}
/*End upload Functions*/

function MooPanel_UpdateItems(event) {
    event.preventDefault();
    window.bar.animate(0.01);
    window.bar.setText('1 %');
    window.itemReceived = 0;
    moo_upadateItemsPerPage(0);
}
function MooPanel_UpdateCategories(event)
{
    event.preventDefault();
    window.bar.animate(0.01);
    window.bar.setText('1 %');

    jQuery.post(moo_params.ajaxurl,{'action':'moo_update_categories'}, function (data)
        {
            window.bar.animate(0.5);
            window.bar.setText('50 %');
        }
    ).done(function () {
            swal("Categories updated");
            window.bar.animate(1.0);
            window.bar.setText('100 %');

    });
}
function MooPanel_UpdateModifiers(event)
{
    event.preventDefault();
    window.bar.animate(0.01);
    window.bar.setText('1 %');
    jQuery.post(moo_params.ajaxurl,{'action':'moo_update_modifiers_groups'}, function (data)
        {
            window.bar.animate(0.5);
            window.bar.setText('50 %');
        }
    ).done(function () {
        jQuery.post(moo_params.ajaxurl,{'action':'moo_update_modifiers'}, function (data)
            {
                window.bar.animate(1.0);
                window.bar.setText('100 %');
            }
        ).done(function () {
            swal("Modifiers updated");
            window.bar.animate(1.0);
            window.bar.setText('100 %');

        });

    });
}
function MooPanel_UpdateOrderTypes(event)
{
    event.preventDefault();
    window.bar.animate(0.01);
    window.bar.setText('1 %');
    jQuery.post(moo_params.ajaxurl,{'action':'moo_update_order_types'}, function (data) {
            window.bar.animate(1.0);
            window.bar.setText('100 %');
    }).done(function () {
        Moo_GetOrderTypes();
        swal("Order Types updated");
        window.bar.animate(1.0);
        window.bar.setText('100 %');

    });


}
function MooPanel_UpdateTaxes(event)
{
    event.preventDefault();
    window.bar.animate(0.01);
    window.bar.setText('1 %');
    jQuery.post(moo_params.ajaxurl,{'action':'moo_update_taxes'}, function (data)
        {
            window.bar.animate(1.0);
            window.bar.setText('100 %');
        }
    ).done(function () {
        swal("Taxes updated");
        window.bar.animate(1.0);
        window.bar.setText('100 %');

    });


}
function moo_upadateItemsPerPage(page) {
    var received = 0;
    jQuery.post(moo_params.ajaxurl,{'action':'moo_update_items','page':page}, function (data)
    {
        received = data.received;
        var percent_loaded = data.received*100/window.moo_nb_allItems;

        if( percent_loaded === null )
            percent_loaded = 1;

        if (window.moo_nb_allItems !== 0 ) {
            window.bar.animate(bar.value()+percent_loaded/100);
        }
    }
    ).done(function () {
        if(received>0) {
            window.itemReceived += received;
            moo_upadateItemsPerPage(page+1);
            window.bar.setText(window.itemReceived+' items updated');
        } else {
            swal("Items updated");
            window.bar.animate(1.0);
            window.bar.setText('100 %');
            moo_Update_stats();

        }
    });
}

function moo_bussinessHours_Details(status) {
     if(status)
         jQuery('#moo_bussinessHours_Details').removeClass('moo_hidden');
    else
         jQuery('#moo_bussinessHours_Details').addClass('moo_hidden');

}
function moo_trackStock_details(status) {
     if(status)
         jQuery('#moo_trackStock_details').removeClass('moo_hidden');
    else
         jQuery('#moo_trackStock_details').addClass('moo_hidden');

}
function moo_showHideSection(id, show) {
     if(show) {
         jQuery(id).removeClass('moo_hidden');
     } else {
         jQuery(id).addClass('moo_hidden');
     }
}
function moo_filtrer_by_category(e)
{
    e.preventDefault();
    var catUuid = jQuery('#moo_cat_filter').val();
    if(catUuid && catUuid !== '') {
        document.location.href = 'admin.php?page=moo_items&category='+catUuid;
    } else {
        document.location.href = 'admin.php?page=moo_items';
    }
}
function moo_markItemAsFeatured(e,item_uuid) {
    e.preventDefault();
    swal({
        title: 'Please wait ..',
        showConfirmButton: false
    });
    const isFeatured = jQuery(e.target).data('isFeatured');
    jQuery.ajax({
        type: 'POST',
        url: moo_RestUrl+"moo-clover/v1/dashboard/featured_items",
        contentType: 'application/json; charset=UTF-8',
        beforeSend: function(jqXhr) {
            jqXhr.setRequestHeader('X-WP-Nonce', moo_params.nonce)
        },
        data: JSON.stringify({"itemUuid":item_uuid,"isFeatured":isFeatured})
    }).fail(function (data) {
        //Change butn text
        swal({
            title: "Error",
            text: 'An has occurred, please refresh the page or contact us',
            type: "error",
            confirmButtonText: "ok"
        });
    }).done(function (data) {
        if(data.status === "success"){
            if (isFeatured){
                jQuery('#sooMarkAsFeaturedLink-'+item_uuid).hide();
                jQuery('#sooMarkAsUnFeaturedLink-'+item_uuid).show();
                swal({
                    title: "Done!",
                    text: 'The item has been marked as featured item',
                    type: "success",
                    confirmButtonText: "ok"
                });
            } else {
                jQuery('#sooMarkAsFeaturedLink-'+item_uuid).show();
                jQuery('#sooMarkAsUnFeaturedLink-'+item_uuid).hide();
                swal({
                    title: "Done!",
                    text: 'The item has been marked as regular item',
                    type: "success",
                    confirmButtonText: "ok"
                });
            }
        } else {
            swal({
                title: "Error",
                text: 'Try again',
                type: "error",
                confirmButtonText: "ok"
            });
        }
    });
}
function moo_markItemAsOutOfStock(e,item_uuid) {
    e.preventDefault();
    swal({
        title: 'Please wait ..',
        showConfirmButton: false
    });
    const isOutOfStock = jQuery(e.target).data('isOutOfStock');
    jQuery.ajax({
        type: 'POST',
        url: moo_RestUrl+"moo-clover/v1/dashboard/out_of_stock_items",
        contentType: 'application/json; charset=UTF-8',
        beforeSend: function(jqXhr) {
            jqXhr.setRequestHeader('X-WP-Nonce', moo_params.nonce)
        },
        data: JSON.stringify({"itemUuid":item_uuid,"isOutOfStock":isOutOfStock})
    }).fail(function (data) {
        //Change butn text
        swal({
            title: "Error",
            text: 'An has occurred, please refresh the page or contact us',
            type: "error",
            confirmButtonText: "ok"
        });
    }).done(function (data) {
        if(data.status === "success"){
            if (isOutOfStock){
                jQuery('#sooOutOfStockItem-'+item_uuid).hide();
                jQuery('#sooInStockItem-'+item_uuid).show();
                jQuery('#outofstock-item-'+item_uuid).text("Yes");
                swal({
                    title: "Done!",
                    text: 'The item has been marked as out of stock',
                    type: "success",
                    confirmButtonText: "ok"
                });
            } else {
                jQuery('#sooOutOfStockItem-'+item_uuid).show();
                jQuery('#sooInStockItem-'+item_uuid).hide();
                jQuery('#outofstock-item-'+item_uuid).text("No");
                swal({
                    title: "Done!",
                    text: 'The item has been marked as in stock',
                    type: "success",
                    confirmButtonText: "ok"
                });
            }
        } else {
            swal({
                title: "Error",
                text: 'Try again',
                type: "error",
                confirmButtonText: "ok"
            });
        }
    });
}
function moo_changeItemVisibility(e,item_uuid) {
    e.preventDefault();
    swal({
        title: 'Please wait ..',
        showConfirmButton: false
    });
    const isVisible = jQuery(e.target).data('is-visible');
    jQuery.ajax({
        type: 'POST',
        url: moo_RestUrl+"moo-clover/v1/dashboard/showhide_items",
        contentType: 'application/json; charset=UTF-8',
        beforeSend: function(jqXhr) {
            jqXhr.setRequestHeader('X-WP-Nonce', moo_params.nonce)
        },
        data: JSON.stringify({"itemUuid":item_uuid,"visibility":!isVisible})
    }).fail(function (data) {
        //Change butn text
        swal({
            title: "Error",
            text: 'An has occurred, please refresh the page or contact us',
            type: "error",
            confirmButtonText: "ok"
        });
    }).done(function (data) {
        if(data.status === "success") {
            if (!isVisible){
                jQuery('#sooShowAnItem-'+item_uuid).hide();
                jQuery('#sooHideAnItem-'+item_uuid).show();
                jQuery('#visibility-item-'+item_uuid).text("No");
                swal({
                    title: "Done!",
                    text: 'The item has been marked as visible',
                    type: "success",
                    confirmButtonText: "ok"
                });
            } else {
                jQuery('#sooShowAnItem-'+item_uuid).show();
                jQuery('#sooHideAnItem-'+item_uuid).hide();
                jQuery('#visibility-item-'+item_uuid).text("Yes");
                swal({
                    title: "Done!",
                    text: 'The item has been marked as hidden',
                    type: "success",
                    confirmButtonText: "ok"
                });
            }
            jQuery('#soo-item-row-'+item_uuid).toggleClass( "item-hidden" );
        } else {
            swal({
                title: "Error",
                text: 'Try again',
                type: "error",
                confirmButtonText: "ok"
            });
        }
    });
}
function moo_editItemDescription(event, item_uuid, item_name) {
    event.preventDefault();
    var item_description = jQuery("#moo-itemTitleDesc-ItemUuid-"+item_uuid).text();
    if(item_description === ""){
        var title = 'Add Item Description';
        var ButtonText = 'Add';
    } else {
        var title = 'Update Item Description';
        var ButtonText = 'Update'
    }

    swal({
        title: item_name,
        input: 'textarea',
        inputValue: item_description,
        inputPlaceholder: "",
        showCancelButton: true,
        confirmButtonText: ButtonText,
        showLoaderOnConfirm: true,
        preConfirm: function (description) {
            return new Promise(function (resolve, reject) {
                if(description.length >= 255) {
                    reject('Text too long, You cannot add more than 255 char')
                } else {
                    jQuery.post(moo_params.ajaxurl,{'action':'moo_save_items_description',"item_uuid":item_uuid,"description":description}, function (data) {
                            if(data.status === 'success') {
                                if(data.data === true) {
                                    jQuery("#moo-itemTitleDesc-ItemUuid-"+item_uuid).text(description);
                                    resolve(true);
                                } else {
                                    reject('Error when saving your changes, please try again');
                                }
                            } else {
                                reject('Error when saving your changes, please try again');
                            }
                        }
                    ).fail(function ( data ) {
                        reject('Error when saving your changes, please try again');
                    });
                }
            })
        },
        allowOutsideClick: false
    }).then(function (data) {
        if( data.value ) {
            swal("The description was updated");
        }

    },function ( rejectionMessage ) {
        swal({
            text : rejectionMessage
        });
    });
}
function moo_editItemName(event, item_uuid,) {
    event.preventDefault();
    var currentName = jQuery("#item-name-section-for-"+item_uuid+">.moo-item-name>strong").text();
    var editInput = '<input name="newItemName" style="width: 50%" type="text" value="" id="newName-for-'+item_uuid+'"/>';
    editInput += "<span class='mooEditItemNameSaveButton' onclick='mooSaveItemCustomName(\""+item_uuid+"\")'><img src='"+ moo_params['plugin_img']+"/tick-circle.png' width='30px'/></span>";
    editInput += "<span class='mooEditItemNameCancelButton' onclick='mooCancelItemCustomName(\""+item_uuid+"\")'><img src='"+ moo_params['plugin_img']+"/close-circle.png' width='30px'/></span>";
    editInput += "<span class='mooOriginalItemName' style='display: none'>"+currentName+"</span>";
    //Create Input
    jQuery("#item-name-section-for-"+item_uuid+">.moo-item-name").html(editInput);

    //Add Input Value
    jQuery("#newName-for-"+item_uuid).val(currentName);

    //hide edit icon
    jQuery("#item-name-section-for-"+item_uuid+">img").hide();
    //When Enter Key is pressed
    jQuery('#newName-for-'+item_uuid).keypress(function(event) {
        if(event.keyCode === 13) {
            event.preventDefault(); // Stop the default behaviour
            //$('#loginBtn').click();
            mooSaveItemCustomName(item_uuid);
        }
    });
}
function mooSaveItemCustomName(item_uuid) {
    var newItemName =  jQuery('#newName-for-'+item_uuid).val();
    if (newItemName === ""){
        swal({
            title:"Name should not be empty",
            text:"Please enter the item name",
            type:"error"
        });
    } else {
        mooShowWaitMessage();
        if(moo_RestUrl.indexOf("?rest_route") !== -1){
            var endpoint = moo_RestUrl+'moo-clover/v2/dash/update_item_name&_wpnonce=' + moo_params.nonce ;
        } else {
            var endpoint = moo_RestUrl+'moo-clover/v2/dash/update_item_name?_wpnonce=' + moo_params.nonce ;
        }
        jQuery.post(endpoint,{
            "item_uuid":item_uuid,
            "name":newItemName,
        }, function (response) {
            if(response.status === "success"){
                //Show new Name
                jQuery("#item-name-section-for-"+item_uuid+">.moo-item-name").html("<strong>"+newItemName+"</strong>");
                //Show edit icon
                jQuery("#item-name-section-for-"+item_uuid+">img").show();
                mooHideWaitMessage();
            } else {
                swal({
                    title:"An error has occurred",
                    text: response.message ? response.message : "try again",
                    type:"error"
                });
            }
        }).fail(function () {
            swal({
                title:"An error has occurred",
                text:"try again",
                type:"error"
            });
        })
    }
}
function mooCancelItemCustomName(item_uuid, item_original_name) {
    var itemName = jQuery("#item-name-section-for-"+item_uuid+">.moo-item-name>.mooOriginalItemName").text();
    jQuery("#item-name-section-for-"+item_uuid+">.moo-item-name").html("<strong>"+itemName+"</strong>");
    //Show edit icon
    jQuery("#item-name-section-for-"+item_uuid+">img").show();
}
function moo_showOrderTypeDetails(e,id) {
    if (e !==  null)
        e.preventDefault();
    //jQuery('#detail_'+id).slideToggle( "slow" );
    jQuery('#detail_'+id).slideToggle('fast', function() {
        if (jQuery(this).is(':visible')) {
            jQuery("#top-bt-"+id).css('display','none');
        } else {
            jQuery("#top-bt-"+id).css('display','block');
        }
    });
}
function moo_saveOrderType(e,uuid) {
    e.preventDefault();
    mooShowWaitMessage();
    var name = jQuery('#label_'+ uuid).val();
    var isEnabled = jQuery('#select_En_' + uuid).prop('checked')?'1':'0';
    var useCoupons = jQuery('#useCoupons_' + uuid).prop('checked')?'1':'0';
    var allowScOrders = jQuery('#allowScOrders_' + uuid).prop('checked')?'1':'0';
    var allowServiceFee = jQuery('#allowServiceFee_' + uuid).prop('checked')?'1':'0';
    //var availabilityTime = jQuery('#availabilityTime_' + uuid).prop('checked')?'1':'0';
    var taxable = jQuery('#select_Tax_'+ uuid).val();
    var type = jQuery('#select_type_'+ uuid).val();
    var minAmount = jQuery('#minAmount_'+ uuid).val();
    var maxAmount = jQuery('#maxAmount_'+ uuid).val();
    var availabilityCustomTime = jQuery('#availabilityCustomTime_'+ uuid).val();
    var customMessage = jQuery('#moo_ot_customMessage_'+ uuid).val();
    if(customMessage.length>200){
        swal({
            text:'Custom message length must be least than 200 characters'
        });
        return;
    }
    var data = {
        "action":'moo_update_ordertype',
        "name":name,
        "enable":isEnabled,
        "availabilityTime":"",
        "taxable":taxable,
        "type":type,"uuid":uuid,
        "minAmount":minAmount,
        "maxAmount":maxAmount,
        "availabilityCustomTime":availabilityCustomTime,
        "useCoupons":useCoupons,
        "allowScOrders":allowScOrders,
        "allowServiceFee":allowServiceFee,
        "customMessage":customMessage,
    };
    jQuery.post(moo_params.ajaxurl,data, function (data) {
           // console.log(data);
            if(data && data.data == "1") {
                if(data.updated) {
                    swal({
                        text:'The order type "'+name+'" was updated'
                    });
                } else {
                    swal({
                        text:'The order type "'+name+'" was updated only locally, maybe you removed this order type from your account in Clover'
                    });
                }
                Moo_GetOrderTypes();
            } else {
                swal({
                    text:'The order type "'+name+'" was updated'
                });
            }
        }
    ).fail(function (data) {
        swal({
            text:'The order type "'+name+'" was removed from your account in Clover.com, You may need to re-create another one'
        });
    });

}
function moo_showCustomerMap(event,lat,lng)
{
    event.preventDefault();
    var location = {};
    location.lat = parseFloat(lat) ;
    location.lng = parseFloat(lng) ;

    jQuery('#mooCustomerMap').show();

    var map = new google.maps.Map(document.getElementById('mooCustomerMap'), {
        zoom: 15,
        center: location
    });

    var marker = new google.maps.Marker({
        position: location,
        map: map
    });

}
function mooDeleteCoupon(e)
{
    e.preventDefault();
    var url = jQuery(e.target).attr("href");
    swal({
        text: 'Please confirm that you want delete this coupon',
        type: 'warning',
        showCancelButton: true,
        showLoaderOnConfirm: false,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then(function (data) {
        if(!data.dismiss) {
            swal.close();
            window.location.href = url;
        }
    });
}

function moo_login2checkoutClicked(status)
{
    if(status === true) {
        jQuery(".moo_login2checkout").show();
    } else {
        jQuery(".moo_login2checkout").hide();
    }
}
function moo_click_on_textUnderSI(status)
{
    if(status === true) {
        jQuery(".moo_textUnderSI").show();
    } else {
        jQuery(".moo_textUnderSI").hide();
    }
}
function moo_click_on_textUnderTips(status)
{
    if(status === true) {
        jQuery(".moo_textUnderTips").show();
    } else {
        jQuery(".moo_textUnderTips").hide();
    }
}
function moo_couponsStatusClicked(status) {
    if(status === true) {
        jQuery("#moo_use_couponsapp").show();
    } else {
        jQuery("#moo_use_couponsapp").hide();
    }
}
function moo_saveCardsClicked(status)
{
    if(status==true) {
        jQuery(".moo_saveCardsClicked").show();
    } else {
        jQuery(".moo_saveCardsClicked").hide();
    }
}
function moo_createDefaultTipChooserSection() {
    var tips = jQuery("#MooTipsSelections").val();
    var defaultTips = jQuery("#MooTipsDefault").val();

    if(tips === "") {
        tips = [5,10,15,20];
    } else {
        tips = tips.split(",");
    }
    var html ="<option value =''>No Default Tip</option>";
    for(i  in tips){
        var tip = parseFloat(tips[i]);
        if(tip>0){
            if(tip === parseFloat(defaultTips)){
                html+= "<option value ='"+tip+"'selected>"+tip+"%</option>";
            } else {
                html+= "<option value ='"+tip+"'>"+tip+"%</option>";
            }
        }
    }
    jQuery("#MooTipsDefault").html(html);
}

/*
 * Clean the inventory, is about removing data that was removed on Clover from local db
 * @version 1.2.8
 */

function MooPanel_CleanInventory(e)
{
    e.preventDefault();
    swal.setDefaults({
        confirmButtonText: 'Next &rarr;',
        allowOutsideClick: false,
        showCancelButton: true,
        animation: false,
        progressSteps: ['0','1', '2', '3','4', '5', '6']
    });

    var steps = [
        {
            title: 'Clean Inventory',
            html: 'This may take several minutes depending on the size of your inventory. Only use this feature if you have made significant '+
                   'changes on Clover and you find it a hassle to manually hide them from the website. <br /> Click "Next" once it says "cleaned" '
        },
        {
            title: 'Order Types',
            html: '<p>Click "Start" to remove old order types that have been deleted from Clover yet still appears on the website.<br/> Click "Next" to move on to Tax Rates after it says Cleaned </p>'+
                  '<div id="mooClean_order_types"></div>'+
                  '<a href="" class="button button-secondary" onclick="mooClean(event,\'order_types\')">Start</a>'
        },
        {
            title: 'Taxes Rates',
            html: '<p>Click "Start" to remove old tax rates that have been deleted from Clover yet still appears on the website.<br/> Click "Next" to move on to Modifiers Groups after it says Cleaned </p>'+
                  '<div id="mooClean_tax_rates"></div>'+
                  '<a href="" class="button button-secondary" onclick="mooClean(event,\'tax_rates\')">Start</a>'
        },
        {
            title: 'Modifier Groups',
            html: '<p>Click "Start" to remove old Modifier Groups that have been deleted from Clover yet still appears on the website.<br/> Click "Next" to move on to Modifiers after it says Cleaned </p>'+
                  '<div id="mooClean_modifier_groups"></div>'+
                  '<a href="" class="button button-secondary" onclick="mooClean(event,\'modifier_groups\')">Start</a>'
        },
        {
            title: 'Modifiers',
            html: '<p>Click "Start" remove old Modifiers that have been deleted from Clover yet still appears on the website. This may take a few minutes.<br/> Click on "Next" to move on to Categories after it says Cleaned </p>'+
                  '<div id="mooClean_modifiers"></div>'+
                  '<a href="" class="button button-secondary" onclick="mooClean(event,\'modifiers\')">Start</a>'
        },
        {
            title: 'Categories',
            html: '<p>Click "Start" to remove old Categories that have been deleted from Clover yet still appears on the website. <br/> Click on "Next" to move on to Items after it says Cleaned '+
                  '<div id="mooClean_categories"></div>'+
                  '<a href="" class="button button-secondary" onclick="mooClean(event,\'categories\')">Start</a>'
        },
        {
            title: 'Items',
            html: '<p>Click "Start" to remove old items that have been deleted from Clover yet still appears on the website. '+
                    'This may take a few minutes. <br/> Click "Next" to finish and exit</p>'+
                  '<div id="mooClean_items"></div>'+
                  '<a href="" class="button button-secondary" onclick="mooClean(event,\'items\')">Start</a>'
        }
    ];

    swal.queue(steps).then(function (result) {
        if(!result.dismiss){
            swal.resetDefaults();
            swal({
                title: 'All Done!',
                html:
                    "The clean up process has been successfully completed. If you have added additional items to your Clover inventory, you will need to do a manual sync. Clean inventory feature only removes old items",
                confirmButtonText: 'ok'
            })
        }
    }, function () {
        swal.resetDefaults()
    })
}

/*
 * Repair the database issues
 * @version 1.5.8
 */

function sooRepairDatabase(e)
{
    e.preventDefault();
    swal({
        title: 'Repairing your database',
        text: 'Please wait...',
        showConfirmButton: false
    });
    jQuery.ajax({
        type: 'GET',
        url: moo_RestUrl+"moo-clover/v1/tools/repair_database",
        beforeSend: function(jqXhr) {
            jqXhr.setRequestHeader('X-WP-Nonce', moo_params.nonce)
        }
    }).fail(function (data) {
        //Change butn text
        swal({
            title: "Error",
            text: 'An has occurred, please refresh the page or contact us',
            type: "error",
            confirmButtonText: "ok"
        });
    }).done(function (data) {
        if(data.status === "success"){
            swal({
                title: "All Done!",
                text: 'Your database has been repaired.',
                type: "success",
                confirmButtonText: "ok"
            });
        } else {
            swal({
                title: "Error",
                text: 'Database repair was not completed successfully. Please verify your data or contact support if the issue persists',
                type: "error",
                confirmButtonText: "ok"
            });
        }
    });
}
/*
 * This function to Clean the inventory, we set teh typOfdate wich may take (order_tyes,tax_rates,categories,items..)
 * The default number of data per page is 10, then we send an other request using the recursive loop CleanByPage
 */
function mooClean(event,typeOfDate)
{
    event.preventDefault();
    jQuery(event.target).hide();
    var id = "#mooClean_"+typeOfDate;
    var pbar = new ProgressBar.Line(id, {
        strokeWidth: 4,
        easing: 'easeInOut',
        duration: 1400,
        color: '#496F4E',
        trailColor: '#eee',
        trailWidth: 1,
        svgStyle: {width: '100%', height: '100%'},
        text: {
            style: {
                // Text color.
                // Default: same as stroke color (options.color)
                color: '#999',
                right: '0',
                top: '30px',
                padding: 0,
                margin: 0,
                transform: null
            },
            autoStyleContainer: false
        },
        from: {color: '#FFEA82'},
        to: {color: '#ED6A5A'}
    });

    pbar.animate(0.1);
    //make the button grey
    jQuery(".swal2-popup .swal2-styled.swal2-confirm").css("background-color","#aaa");

    if(moo_RestUrl.indexOf("?rest_route") !== -1){
        var endpoint = moo_RestUrl+'moo-clover/v1/clean/'+typeOfDate+'/10/0&_wpnonce='+ moo_params.nonce;
    } else {
        var endpoint = moo_RestUrl+'moo-clover/v1/clean/'+typeOfDate+'/10/0?_wpnonce='+ moo_params.nonce;
    }
    jQuery.get(endpoint,function (data) {
        var nb = parseInt(data["nb_"+typeOfDate]);
        pbar.setText(nb +' '+typeOfDate.replace("_"," ")+' checked');
        if(! data.last_page) {
            mooCleanByPage(1,pbar,typeOfDate);
        } else {
            jQuery(id).html(nb +' '+typeOfDate.replace("_"," ")+' Cleaned, You can click on Next');
            jQuery(".swal2-popup .swal2-styled.swal2-confirm").css("background-color","#3085d6");
        }
    });


}
function mooCleanByPage(page,pbar,typeOfDate) {
    var id = "#mooClean_"+typeOfDate;

    if(page/10 <1) {
        pbar.animate(page/10);
    } else {
        pbar.animate(1.0);
    }
    if(moo_RestUrl.indexOf("?rest_route") !== -1){
        var endpoint = moo_RestUrl+'moo-clover/v1/clean/'+typeOfDate+'/10/'+page+'&_wpnonce='+ moo_params.nonce;
    } else {
        var endpoint = moo_RestUrl+'moo-clover/v1/clean/'+typeOfDate+'/10/'+page+'?_wpnonce='+ moo_params.nonce;
    }
    jQuery.get(endpoint, function (data) {
        var nb = (parseInt(data["nb_"+typeOfDate])+parseInt(page*10));
        pbar.setText( nb + ' ' + typeOfDate.replace("_"," ")+' checked');
        if(! data.last_page) {
            mooCleanByPage(page+1,pbar,typeOfDate);
        } else {
            jQuery(id).html(nb + ' ' + typeOfDate.replace("_"," ") +' Cleaned, You can click on Next');
            jQuery(".swal2-popup .swal2-styled.swal2-confirm").css("background-color","#3085d6");
        }
    });
}

function mooImportOneCategory(cat_id) {
    swal({
        html:
        '<div class="moo-msgPopup">Checking the items</div>' +
        '<img src="'+ moo_params['plugin_img']+'/loading.gif" class="moo-imgPopup" width="80px"/>',
        showConfirmButton: false
    });
    jQuery.get(moo_RestUrl+'moo-clover/v1/inventory/categories/'+cat_id,function (data) {
        if(data.status =='success'){
            swal({
                title:"Update Completed",
                text:"You may need to refresh the page to see the changes",
                type:"success"
            });
            if(typeof Moo_SetupEditCategorySection === 'function')
                Moo_SetupEditCategorySection(null,cat_id);
        } else {
            swal({
                title:"An error has occurred",
                text:"try again",
                type:"error"
            });
        }


    });
}
function mooSyncOneCategory(cat_id) {
    swal({
        html:
        '<div class="moo-msgPopup">Syncying the category with your Clover Inventory</div>' +
        '<img src="'+ moo_params['plugin_img']+'/loading.gif" class="moo-imgPopup" width="80px"/>',
        showConfirmButton: false
    });
    jQuery.get(moo_RestUrl+'moo-clover/v2/sync/update_category/'+cat_id,function (data) {
        if(typeof Moo_SetupEditCategorySection === 'function')
            Moo_SetupEditCategorySection(null,cat_id);

        swal({
            title:"Update Completed",
            text:"You may need to refresh the page to see the changes",
            type:"success"
        });

    });
}
function mooUpdateOneCategory( cat_id ) {
    swal({
        html:
        '<div class="moo-msgPopup">Checking the items</div>' +
        '<img src="'+ moo_params['plugin_img']+'/loading.gif" class="moo-imgPopup"/>',
        showConfirmButton: false
    });
    jQuery.get(moo_RestUrl+'moo-clover/v1/inventory/categories/'+cat_id,function (data) {
        if(data.status == 'success'){
            swal({
                title:"Successfully Updated",
               // text:"You have "+data.clover_nb_items+" in your Clover account, and "+data.nb_items+" in your website, don't forget to refresh the page",
                text:"You may need to refresh the page to see the changes",
                type:"success"
            });
        } else {
            swal({
                title:"An error has occurred",
                text:"try again",
                type:"error"
            });
        }
    });
}
function mooGetOpeningHours( event, sync = false ) {
    if(event){
        event.preventDefault();
    }
    swal.close();
    swal({
        title: 'Getting your hours, Please wait...',
        showConfirmButton: false
    });

    if(moo_RestUrl.indexOf("?rest_route") !== -1){
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/opening_hours&_wpnonce='+ moo_params.nonce;
    } else {
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/opening_hours?_wpnonce='+ moo_params.nonce;
    }
    if (sync){
        endpoint += '&sync=true';
    }

    jQuery.get(endpoint, function (data) {
        if(typeof data.days === "object") {
            var html = "<div class='mooOpeningHoursDashSection'>";
            Object.keys(data.days).forEach(key => {
                html += `<div class='mooOpeningHoursDashSectionRow'>`;
                html += `<div class='mooOpeningHoursDashSection-day'>${key}</div>`;
                if(typeof data.days[key] === 'string'){
                   html += `<div class='mooOpeningHoursDashSection-hours'>${data.days[key]}</div>`;
                } else {
                    Object.keys(data.days[key]).forEach(time => {
                        html += `<div class='mooOpeningHoursDashSection-hours'>${data.days[key][time]}</div>`;
                    })
                }
                html += "</div>";
            });
            if(data.timezone){
                html += `<div style="font-weight: 600;color: #174394;margin: 10px 0px 10px 0;">Your Timezone is : ${data.timezone}</div>`;
            }
            html += "<div class='mooOpeningHoursDashSmallText'>To change business hours, login to clover.com from computer, laptop, etc. Then go to account and setup then business information then find business hours.</div>";
            html += "</div>";
            swal({
                title:"Your Business Hours :",
                html: html,
                showCancelButton : true,
                cancelButtonText : "Close",
                confirmButtonText : "Sync Hours"
            }).then(data => {
                if (data.value){
                    mooGetOpeningHours(event, true)
                }
            });
        } else {
            swal({
                text:data
            });
        }
    });
}
function enableOrDisableOldCheckout() {
    const enable = jQuery('#sooOldCheckoutPage').is(":checked");
    if(enable){
        swal({
            title: 'Enabling the old Checkout page...',
            showConfirmButton: false
        });
    } else {
        swal({
            title: 'Please wait...',
            showConfirmButton: false
        });
    }

    let endpoint = moo_RestUrl+'moo-clover/v2/dash/enable-old-checkout';
    if(moo_RestUrl.indexOf("?rest_route") !== -1){
         endpoint += '&_wpnonce='+ moo_params.nonce;
    } else {
        endpoint += '?_wpnonce='+ moo_params.nonce;
    }
    var data = {
        "status":enable
    };

    jQuery.post(endpoint, data, function (response) {
        if(response.status){
            if(enable){
                swal({
                    type:"success",
                    title:"Old Checkout Page is Enabled",
                    text:"Checkout beta enabled, Google Pay and DoorDash Drive Integration is available with this feature reach out to support@zaytech.com for instructions on how to get started."
                });
                jQuery('#sooAdditionalPaymentMethods').hide();
                // Show Google Recaptcha
            } else {
                swal({
                    type:"success",
                    title:"Old Checkout Page is Disabled",
                    text:"Checkout beta enabled, Google Pay and DoorDash Drive Integration is available with this feature reach out to support@zaytech.com for instructions on how to get started."
                });
                jQuery('#sooAdditionalPaymentMethods').show();
                // Hide Google Recaptcha
            }
        } else {
            jQuery('#sooOldCheckoutPage').prop('checked',!enable)
            swal({
                type:"error",
                text:'Error Occurred: Please Refresh and Retry'
            });
        }

    }).fail(function (response) {
        jQuery('#sooOldCheckoutPage').prop('checked',!enable)
        swal({
            text: 'Error Occurred: Please Refresh and Retry'
        });
    });
}

function expandSection(element) {
    jQuery(element).parent().toggleClass("MooPanelItemExpanded")
}
function expandAllSections(element) {
    if(jQuery(element).text() ==='[ Collapse All ]'){
        var parent = jQuery(element).parent();
        jQuery("#"+parent.attr("id")+" .MooPanelItem").each(function (i, el) {
            jQuery(el).removeClass("MooPanelItemExpanded")
        });
        jQuery(element).text("[ Expand All ]");
    } else {
        var parent = jQuery(element).parent();
        jQuery("#"+parent.attr("id")+" .MooPanelItem").each(function (i, el) {
            jQuery(el).addClass("MooPanelItemExpanded")
        });
        jQuery(element).text("[ Collapse All ]");
    }

}
function mooSaveChanges(event, element) {
    mooShowWaitMessage();
    event.preventDefault();
    jQuery('input[name=option_page]',element).remove();
    jQuery('input[name=action]',element).remove();
    jQuery('input[name=_wp_http_referer]',element).remove();
    jQuery('#_wpnonce',element).remove();
    var set =  jQuery('input[name=moo_settings]',element);
    var form = jQuery(element).serializeArray().map( function (val) {
            return {
                "name":val.name.substring(13).slice(0,-1),
                "value":val.value
            }}
    );
    jQuery.ajax({
        type: 'POST',
        url: moo_RestUrl+'moo-clover/v2/dash/save_settings',
        contentType: 'application/json; charset=UTF-8',
        beforeSend: function(jqXhr) {
            jqXhr.setRequestHeader('X-WP-Nonce', moo_params.nonce)
        },
        data: JSON.stringify(form)
    }).fail(function (data) {
        //Change butn text
        swal({ title: "Error", text: 'Settings not saved, please refresh the page or contact us',   type: "error",   confirmButtonText: "ok" });
    }).done(function (data) {
        if(data.status === "success"){
            swal({
                title: 'New settings were saved',
                type: "success",
                confirmButtonColor: "#0333dd",
                confirmButtonText: "Ok"
            });
        } else {
            swal({
                title: data.message,
                type: "error",
                confirmButtonText: "ok"
            });
        }
    });
    return false;
}
function mooSaveDeliveryAreas(event, el)
{
    var zones =  new Array();
    for (var i in moo_delivery_areas ) {
        var element = moo_delivery_areas[i];
        var zone = {};
        zone.id        = element.id;
        zone.name      = element.name;
        zone.minAmount = element.minAmount;
        zone.fee       = element.fee;
        zone.type      = element.type;
        zone.color     = element.color;
        zone.center    = null;
        zone.radius    = null;
        zone.path      = null;

        if(element.type == 'circle') {
            var radius = element.shape.getRadius();
            var center = element.shape.getCenter();
            zone.center = center;
            zone.radius = radius;
        } else {
            var vertices = element.shape.getPath();
            zone.path =  new Array();
            for (var i =0; i < vertices.getLength(); i++) {
                var xy = vertices.getAt(i);
                zone.path.push({lat:xy.lat(),lng:xy.lng()})
            }
        }

        if(typeof element.feeType != 'undefined' && element.feeType != null && element.feeType == "percent")
        //Select percent in Type
            zone.feeType = "percent";
        else
        //Select value in Type
            zone.feeType = "value";

        zones.push(zone);
    }
    var zones_txt = JSON.stringify(zones);
    jQuery('#moo_zones_json').val(zones_txt);
    mooSaveChanges(event, el);
}
function mooShowWaitMessage() {
    swal({
        title: 'Saving your changes ..',
        showConfirmButton: false
    });
}
function mooHideWaitMessage() {
    swal.close();
}

/**
 * Change the api key
 */
function mooUpdateApiKey() {

    swal({
        title: 'Please wait ..',
        text: 'Changing your API KEY and importing your new inventory',
        showConfirmButton: false,
        allowOutsideClick: false
    });

    var newApiKey = jQuery("#chang_api_key").val();
    if(moo_RestUrl.indexOf("?rest_route") !== -1){
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/update_api_key&_wpnonce='+ moo_params.nonce;
    } else {
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/update_api_key?_wpnonce='+ moo_params.nonce;
    }
    var data = {
      "api_key":newApiKey
    };
    jQuery.post(endpoint,data, function (response) {
        if(response.status){
            swal({
                type:"success",
                text:response.message
            });
            window.location.href = "admin.php?page=moo_index";
        } else {
            swal({
                type:"error",
                text:response.message
            });
        }

    }).fail(function (response) {
        swal({
            text: 'An error has occurred, please refresh the page  and try again'
        });
        console.log(response);
    });

}
function mooGetApikey(e) {
    e.preventDefault();
    mooShowWaitMessage();
    var newApiKey = jQuery("#new_api_key").val();
    if(newApiKey === ""){
        swal({
            type:"error",
            text:"Please enter your API KEY"
        });
        return;
    } else {
        mooSaveApikey(newApiKey);
    }
}

function mooSaveApikey(newApiKey) {

    if(moo_RestUrl.indexOf("?rest_route") !== -1){
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/save_apikey&_wpnonce='+ moo_params.nonce;
    } else {
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/save_apikey?_wpnonce='+ moo_params.nonce;
    }
    var data = {
      "api_key":newApiKey
    };
    jQuery.post(endpoint,data, function (response) {
        if(response.status === "success"){
            swal.close();
            //Put name and place on their places
            if(response.name){
                jQuery("#moo-keyValid-section .moo-merchant-name").text(response.name);
            }
            if(response.groupMerchantName){
                jQuery("#moo-keyValid-section .moo-merchant-bg").text(
                    "Loyalty Dashboard Branded App Account: "+response.groupMerchantName
                );
            }
            if(response.address){
                jQuery("#moo-keyValid-section .moo-merchant-address").text(response.address);
                moo_merchantAddress = response.address;

            }
            //Hide loading section and key section
            jQuery("#moo-checking-section").hide();
            jQuery("#moo-enterKey-section").hide();
            jQuery("#moo-error-section").hide();

            //show the section
            jQuery("#moo-keyValid-section").show();
            //recheck the autosync
            mooCheckAutoSyncStatus();
        } else {
            swal({
                type:"error",
                text:response.message
            });
        }

    }).fail(function (response) {
        swal({
            type:"error",
            text:"An error has occurred, please try again"
        });
    });

}
/* Scroll into div functions */

function scrollToElm(container, elm, duration){
    var pos = getRelativePos(elm);
    scrollTo( container, pos.top , duration);
}

function getRelativePos(elm){
    var pPos = elm.parentNode.getBoundingClientRect(), // parent pos
        cPos = elm.getBoundingClientRect(), // target pos
        pos = {};

    pos.top    = cPos.top    - pPos.top + elm.parentNode.scrollTop,
        pos.right  = cPos.right  - pPos.right,
        pos.bottom = cPos.bottom - pPos.bottom,
        pos.left   = cPos.left   - pPos.left;

    return pos;
}

function scrollTo(element, to, duration, onDone) {
    var start = element.scrollTop,
        change = to - start,
        startTime = performance.now(),
        val, now, elapsed, t;

    function animateScroll(){
        now = performance.now();
        elapsed = (now - startTime)/1000;
        t = (elapsed/duration);

        element.scrollTop = start + change * easeInOutQuad(t);
        if( t < 1 )
            window.requestAnimationFrame(animateScroll);
        else
            onDone && onDone();
    };

    animateScroll();
}

function easeInOutQuad(t){
    return t<.5 ? 2*t*t : -1+(4-2*t)*t
};

function  mooFilterModifiers(event) {
    var elem = jQuery(event.target);
    var word = elem.val();
    if(word!==""){
        jQuery("li.list-group>.label_name>.getname").each(function (index, element) {
            var text = jQuery(element).text().toUpperCase();
            if(text.indexOf(word.toUpperCase()) !== -1) {
                jQuery(element).parent().parent().show();
            }  else {
                jQuery(element).parent().parent().hide();
            }
        })
    } else {
        jQuery(".list-group").each(function (index, element) {
            jQuery(element).show();
        })
    }

}

function mooCheckApiKeyOnLoading() {

    if(moo_RestUrl.indexOf("?rest_route") !== -1){
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/check_apikey&_wpnonce='+ moo_params.nonce;
    } else {
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/check_apikey?_wpnonce='+ moo_params.nonce;
    }
    jQuery.get(endpoint, function (response) {

        if(response.status === "success") {

            //Fill the name
            if(response.name){
                jQuery("#moo-keyValid-section .moo-merchant-name").text(response.name);
            }
            //Fill the Group Name
            if(response.groupMerchantName){
                jQuery("#moo-keyValid-section .moo-merchant-bg").text(
                    "Loyalty Dashboard Branded App Account: "+response.groupMerchantName
                );
            }
            //Fill the Address
            if(response.address){
                jQuery("#moo-keyValid-section .moo-merchant-address").text(response.address);
                moo_merchantAddress = response.address;
            }
            //Show recaptcha
            if (response.blackoutStatus === "close") {
                jQuery("#sooBlackoutSection").show();
                if (response.blackoutStatusResponse){
                    if (response.blackoutStatusResponse.custom_message){
                        jQuery("#sooBlackoutSection .reasonSection").text('Reason : ' + response.blackoutStatusResponse.custom_message)
                    }
                    const fromTo = `From ${response.blackoutStatusResponse.start_time} to ${response.blackoutStatusResponse.end_time} (timezone: ${response.blackoutStatusResponse.timezone})`;
                    jQuery("#sooBlackoutSection .fromToSection").text(fromTo)

                }
            }
            //Show recaptcha
            if (response.brandedApp === false){
                jQuery("#sooGoogleReCAPTCHA").show();
            }

            //Check if Clover Hours already configured

            if (response.cloverOpeningHoursExist === false){
                jQuery("#scheduledOrdersSectionDisabled").show()
                jQuery("#scheduledOrdersSection").hide()
                jQuery("#sooCloverHoursSection").hide()
                jQuery("#sooCloverHoursNotFoundSection").show()
            }

            //Hide loading section
            jQuery("#moo-checking-section").hide();
            //show the section
            jQuery("#moo-keyValid-section").show();


        } else {
            if(response.status === "failed") {
                if(response.message === "The API KEY isn't valid"){
                    swal({
                        title:"An error has occurred",
                        text:"The API KEY isn't valid, please use a new one",
                        type:"error"
                    });
                    //Hide loading section
                    jQuery("#moo-checking-section").hide();
                    //show the section
                    jQuery("#moo-enterKey-section").show();

                    return;
                }
                if(response.message){
                    jQuery("#moo-error-section .moo-errorSection-message").text(response.message);
                    //Hide loading section
                    jQuery("#moo-checking-section").hide();
                    //show the section
                    jQuery("#moo-error-section").show();
                } else {
                    //Hide loading section
                    jQuery("#moo-checking-section").hide();
                    //show the section
                    jQuery("#moo-error-section").show();
                }
            }
        }

    }).fail(function (response) {
        if(response.status === "failed") {
            if(response.message){
                jQuery("#moo-error-section .moo-errorSection-message").text(response.message);
                //Hide loading section
                jQuery("#moo-checking-section").hide();
                //show the section
                jQuery("#moo-error-section").show();
            } else {
                //Hide loading section
                jQuery("#moo-checking-section").hide();
                //show the section
                jQuery("#moo-error-section").show();
            }
        }
    });
}
function mooCheckAutoSyncStatus() {
    if(moo_RestUrl.indexOf("?rest_route") !== -1){
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/autosync&_wpnonce='+ moo_params.nonce;
    } else {
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/autosync?_wpnonce='+ moo_params.nonce;
    }
    jQuery.get(endpoint, function (response) {
        //Hide loading section and key section
        jQuery("#mooAutoSyncCheking").hide();
        jQuery("#mooAutoSyncActivated").hide();
        jQuery("#mooAutoSyncDeactivated").hide();

        if(response.status === "enabled"){
            //show the enabled section
            jQuery("#mooAutoSyncActivated").show();
        } else {
            //show the disabled section
            jQuery("#mooAutoSyncDeactivated").show();
        }

    }).fail(function (response) {
        //Hide loading section and key section
        jQuery("#mooAutoSyncCheking").hide();
        jQuery("#mooAutoSyncActivated").hide();
        jQuery("#mooAutoSyncDeactivated").show();
    });
}
function mooChangeAutoSyncStatus(status) {
    mooShowWaitMessage();
    if(moo_RestUrl.indexOf("?rest_route") !== -1){
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/autosync&_wpnonce='+ moo_params.nonce;
    } else {
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/autosync?_wpnonce='+ moo_params.nonce;
    }
    jQuery.post(endpoint,{"status":status}, function (response) {
        mooHideWaitMessage();
        //Hide loading section and key section
        jQuery("#mooAutoSyncCheking").hide();
        jQuery("#mooAutoSyncActivated").hide();
        jQuery("#mooAutoSyncDeactivated").hide();

        if(response.status === "success"){
            if(status === "enabled"){
                //show the enabled section
                jQuery("#mooAutoSyncActivated").show();
            } else {
                //show the disabled section
                jQuery("#mooAutoSyncDeactivated").show();
            }
        } else {
            mooHideWaitMessage();
            jQuery("#mooAutoSyncCheking").show();
        }

    }).fail(function (response) {
        mooHideWaitMessage();
    });
}
function mooSeeDetailOfAutoSync(e) {
    if(e){
        e.preventDefault()
    }
    jQuery("#mooInventorySection").hide();
    jQuery("#mooAutoSyncDetailsSection").show();
    //SetupSection
    mooSetupAutoSyncDetailsSection(1);
}
function mooHideDetailOfAutoSync(e) {
    if(e){
        e.preventDefault()
    }
    jQuery("#mooInventorySection").show();
    jQuery("#mooAutoSyncDetailsSection").hide();
}
function mooSetupAutoSyncDetailsSection(page) {
    page  =  parseInt(page);
    if(page<1){
        page = 1;
    }
    var selector = "#MooPanel_tabContent2 #mooAutoSyncDetailsSection .mooAutoSyncDetailsSection";
    jQuery(selector).html("Loading the details please wait..");
    var html  = "";
    var finalHtml  = "";
    var htmlLines  = "";
    var beforePaginationHtml  = "";
    var paginationHtml  = "";
    var afterPaginationHtml  = "";
    let dataNames = {
        ITEM : [],
        MODIFIER : [],
        MODIFIER_GROUP : [],
        CATEGORY : [],
    }
    var items = [];
    var categories = [];
    var modifierGroups = [];
    var modifiers = [];

        html = '<div class="soo-body">';
        html += '<div class="moo-row">';

        htmlLines += '<div class="sync-header">';
        htmlLines += '<div class="moo-row">';
        htmlLines += '<div class="sync-header-row">';
        htmlLines += '   <div class="moo-col-md-3"></div>';
        htmlLines += '   <div class="moo-col-md-5"></div>';
        htmlLines += '   <div class="moo-col-md-1"></div>';
        htmlLines += '   <div class="moo-col-md-1"></div>';
        htmlLines += '   <div class="moo-col-md-2"></div>';
        htmlLines += '</div>';
        htmlLines += '</div>';
        htmlLines += '</div>';
        htmlLines += '<div class="sync-body"><div class="moo-row">';

        beforePaginationHtml += '<div class="moo-row">';
        beforePaginationHtml += '<div class="sync-pagination">';
        beforePaginationHtml += '<ul class="pagination">';

        afterPaginationHtml += '</ul>';
        afterPaginationHtml += '</div></div>';
        finalHtml += '</div></div></div>';
        finalHtml += '<p style="font-size: 11px">Any history older than 30 days will be gone</p>';

    if(moo_RestUrl.indexOf("?rest_route") !== -1){
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/autosync_details&_wpnonce=' + moo_params.nonce + "&page="+page;
    } else {
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/autosync_details?_wpnonce=' + moo_params.nonce + "&page="+page;
    }
    jQuery.get(endpoint, function (response) {
       if(response.data){
           if(response.data.length>0){
               for (var i  =0; i < response.data.length; i++){
                   var oneLine  = response.data[i];
                   var htmlLine = '';

                   if (oneLine.object_type === "ITEM") {
                       htmlLine += '<div class="sync-body-row">';
                       htmlLine += '<div class="moo-row">';
                       htmlLine += '<div class="moo-col-md-3 mooSyncItemUuid">'+oneLine.object_id+'</div>';
                       if (oneLine.response_code === "200"){
                           htmlLine += '<div class="moo-col-md-5">';
                           htmlLine += '<div class="sync-msg sync-msg-green">This item has been successfully updated.</div>';
                           htmlLine += '</div>';
                           htmlLine += '<div class="moo-col-md-1 status-alert">';
                           htmlLine += ' <a href="#"><img src="'+moo_params.plugin_url+'/public/img/green.png"></a>';
                           htmlLine += '<div class="alert">';
                           htmlLine += '<p>'+oneLine.response_body +'</p>';
                           htmlLine += ' </div>';
                           htmlLine += '</div>';
                       } else {
                           htmlLine += '<div class="moo-col-md-5">';
                           htmlLine += '<div class="sync-msg sync-msg-orange">An error has occurred when updating this item.</div>';
                           htmlLine += '</div>';
                           htmlLine += '<div class="moo-col-md-1 status-alert">';
                           htmlLine += ' <a href="#"><img src="'+moo_params.plugin_url+'/public/img/red.png"></a>';
                           htmlLine += '<div class="alert">';
                           htmlLine += '<p>'+oneLine.response_body +'</p>';
                           htmlLine += ' </div>';
                           htmlLine += '</div>';
                       }
                       htmlLine += '<div class="moo-col-md-1">';
                       htmlLine += '<a style="outline: none" href="#" class="btn-refresh"  onclick="mooAutoSyncDetailsSectionRefreshOneLine(event,\''+oneLine.object_id+'\',\''+oneLine.object_type+'\')"><img src="'+moo_params.plugin_url+'/public/img/load.png"></a>';
                       htmlLine += '</div>';
                       htmlLine += '<div class="moo-col-md-2">';
                       htmlLine += oneLine.created_at;
                       htmlLine += '</div>';
                       htmlLine += '</div>';
                       htmlLine += '</div>';
                   }

                   if (oneLine.object_type === "MODIFIER") {
                       htmlLine += '<div class="sync-body-row">';
                       htmlLine += '<div class="moo-row">';
                       htmlLine += '<div class="moo-col-md-3 mooSyncItemUuid">'+oneLine.object_id+'</div>';
                       if (oneLine.response_code === "200"){
                           htmlLine += '<div class="moo-col-md-5">';
                           htmlLine += '<div class="sync-msg sync-msg-green">This Modifier has been successfully updated.</div>';
                           htmlLine += '</div>';
                           htmlLine += '<div class="moo-col-md-1 status-alert">';
                           htmlLine += ' <a href="#"><img src="'+moo_params.plugin_url+'/public/img/green.png"></a>';
                           htmlLine += '</div>';
                       } else {
                           htmlLine += '<div class="moo-col-md-5">';
                           htmlLine += '<div class="sync-msg sync-msg-orange">An error has occurred when updating this Modifier.</div>';
                           htmlLine += '</div>';
                           htmlLine += '<div class="moo-col-md-1 status-alert">';
                           htmlLine += ' <a href="#"><img src="'+moo_params.plugin_url+'/public/img/red.png"></a>';
                           htmlLine += '<div class="alert">';
                           htmlLine += '<p>'+oneLine.response_body +'</p>';
                           htmlLine += ' </div>';
                           htmlLine += '</div>';
                       }
                       htmlLine += '<div class="moo-col-md-1">';
                       htmlLine += '<a href="#" class="btn-refresh"  onclick="mooAutoSyncDetailsSectionRefreshOneLine(event,\''+oneLine.object_id+'\',\''+oneLine.object_type+'\')"><img src="'+moo_params.plugin_url+'/public/img/load.png"></a>';
                       htmlLine += '</div>';
                       htmlLine += '<div class="moo-col-md-2">';
                       htmlLine += oneLine.created_at;
                       htmlLine += '</div>';
                       htmlLine += '</div>';
                       htmlLine += '</div>';
                   }

                   if (oneLine.object_type === "MODIFIER_GROUP") {
                       htmlLine += '<div class="sync-body-row">';
                       htmlLine += '<div class="moo-row">';
                       htmlLine += '<div class="moo-col-md-3 mooSyncItemUuid">'+oneLine.object_id+'</div>';
                       if (oneLine.response_code === "200"){
                           htmlLine += '<div class="moo-col-md-5">';
                           htmlLine += '<div class="sync-msg sync-msg-green">This Modifier Group has been successfully updated.</div>';
                           htmlLine += '</div>';
                           htmlLine += '<div class="moo-col-md-1 status-alert">';
                           htmlLine += ' <a href="#"><img src="'+moo_params.plugin_url+'/public/img/green.png"></a>';
                           htmlLine += '</div>';
                       } else {
                           htmlLine += '<div class="moo-col-md-5">';
                           htmlLine += '<div class="sync-msg sync-msg-orange">An error has occurred when updating this Modifier Group.</div>';
                           htmlLine += '</div>';
                           htmlLine += '<div class="moo-col-md-1 status-alert">';
                           htmlLine += ' <a href="#"><img src="'+moo_params.plugin_url+'/public/img/red.png"></a>';
                           htmlLine += '<div class="alert">';
                           htmlLine += '<p>'+oneLine.response_body +'</p>';
                           htmlLine += ' </div>';
                           htmlLine += '</div>';
                       }
                       htmlLine += '<div class="moo-col-md-1">';
                       htmlLine += '<a href="#" class="btn-refresh"  onclick="mooAutoSyncDetailsSectionRefreshOneLine(event,\''+oneLine.object_id+'\',\''+oneLine.object_type+'\')"><img src="'+moo_params.plugin_url+'/public/img/load.png"></a>';
                       htmlLine += '</div>';
                       htmlLine += '<div class="moo-col-md-2">';
                       htmlLine += oneLine.created_at;
                       htmlLine += '</div>';
                       htmlLine += '</div>';
                       htmlLine += '</div>';
                   }

                   if (oneLine.object_type === "CATEGORY") {
                       if (oneLine.response_body === '"Category Has Been Updated"'){
                           htmlLine += '<div class="sync-body-row">';
                           htmlLine += '<div class="moo-row">';
                           htmlLine += '<div class="moo-col-md-3 mooSyncItemUuid">'+oneLine.object_id+'</div>';
                           htmlLine += '<div class="moo-col-md-5">';
                           htmlLine += '<div class="sync-msg sync-msg-green">This Category has been successfully updated.</div>';
                           htmlLine += '</div>';
                           htmlLine += '<div class="moo-col-md-1 status-alert">';
                           htmlLine += ' <a href="#"><img src="'+moo_params.plugin_url+'/public/img/green.png"></a>';
                           htmlLine += '</div>';
                       } else {
                           htmlLine += '<div class="sync-body-row">';
                           htmlLine += '<div class="moo-row">';
                           htmlLine += '<div class="moo-col-md-3 mooSyncItemUuid">'+oneLine.object_id+'</div>';
                           htmlLine += '<div class="moo-col-md-5">';
                           htmlLine += '<div class="sync-msg sync-msg-orange">An error has occurred when updating this category.</div>';
                           htmlLine += '</div>';
                           htmlLine += '<div class="moo-col-md-1 status-alert">';
                           htmlLine += ' <a href="#"><img src="'+moo_params.plugin_url+'/public/img/green.png"></a>';
                           htmlLine += '</div>';
                       }


                       htmlLine += '<div class="moo-col-md-1">';
                       htmlLine += '<a href="#" class="btn-refresh"  onclick="mooAutoSyncDetailsSectionRefreshOneLine(event,\''+oneLine.object_id+'\',\''+oneLine.object_type+'\')"><img src="'+moo_params.plugin_url+'/public/img/load.png"></a>';
                       htmlLine += '</div>';
                       htmlLine += '<div class="moo-col-md-2">';
                       htmlLine += oneLine.created_at;
                       htmlLine += '</div>';
                       htmlLine += '</div>';
                       htmlLine += '</div>';
                   }


                   htmlLines  += htmlLine;

                   if(oneLine.object_type === "ITEM") {
                       if (!items[oneLine.object_id]) {
                           items.push(oneLine.object_id);
                       }
                   }

                   dataNames[oneLine.object_type].push(oneLine.object_id);
               }

               //Build Pagination HTML
               if(response.links.length > 1 && response.last_page>1) {

                   for (var i = 1; i < response.links.length; i++){
                       const link =  response.links[i];
                       const pageNumber = parseInt(link.label);
                       if ( ! isNaN(pageNumber) ){
                            if (link.active){
                                paginationHtml += '<li><a href="#" ><span style="background-color: #eff5fc">'+pageNumber+'</span></a></li>';
                            } else {
                                paginationHtml += '<li><a href="#" onclick="mooSetupAutoSyncDetailsSection('+ pageNumber +')"><span>'+pageNumber+'</span></a></li>';
                            }
                        } else {
                           if (link.label === "..."){
                               paginationHtml += '<li><span>'+link.label+'</span></li>';
                           } else {
                               if (link.url){
                                   paginationHtml += '<li><a href="#" onclick="mooSetupAutoSyncDetailsSection('+ (page+1) +')"><span>'+link.label+'</span></a></li>';
                               }
                           }
                        }
                   }
                   paginationHtml = beforePaginationHtml + paginationHtml + afterPaginationHtml;
               }

               var fullHtml  = html +paginationHtml +htmlLines+paginationHtml+finalHtml;
               jQuery(selector).html(fullHtml);
               mooAutoSyncDetailsSectionChangeItemsUuidByNames(items);
           } else {
               jQuery(selector).html("There are currently no auto sync requests.");
           }
       } else {
           jQuery(selector).html("There are currently no auto sync requests.");
       }

    }).fail(function (response) {
        jQuery(selector).html("An error has occurred, please click on refresh or contact us.");
    });
}
function mooAutoSyncDetailsSectionRefreshOneLine(event, uuid, type) {
    if(type === "ITEM"){
        jQuery(event.target).parent().addClass("refresh");
        if(moo_RestUrl.indexOf("?rest_route") !== -1){
            var endpoint = moo_RestUrl+'moo-clover/v2/sync/update_item/' + uuid ;
        } else {
            var endpoint = moo_RestUrl+'moo-clover/v2/sync/update_item/' + uuid ;
        }
        jQuery.get(endpoint, function (response) {
            jQuery(event.target).parent().removeClass("refresh");
            swal({"text":response})
        }).fail(function (response) {
            jQuery(event.target).parent().removeClass("refresh");
            swal({"text":response})
        });
    }
}
function mooAutoSyncDetailsSectionChangeItemsUuidByNames(items) {
    if(moo_RestUrl.indexOf("?rest_route") !== -1){
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/autosync_items_names&_wpnonce=' + moo_params.nonce ;
    } else {
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/autosync_items_names?_wpnonce=' + moo_params.nonce ;
    }
    jQuery.post(endpoint,{"items":items}, function (response) {
        if(response.status === "success"){
            var itemsNames = response.data;
            jQuery(".mooAutoSyncDetailsSection .mooSyncItemUuid").each(function (key, elm) {
                elm = jQuery(elm);
                if(elm){
                    itemUuid = elm.html();
                    if(itemsNames[itemUuid]){
                        elm.html(itemsNames[itemUuid])
                    }
                }
            })
        }
    });
}

function mooEditImageOnItemsPage(e, itemUuid, itemName) {
    e.preventDefault();
    console.log("Editing the img of the item" + itemUuid);
    //Get Item
    media_uploader = wp.media({
        title: 'Select an Image for "'+itemName+'" Placeholder',
        button: {
            text: 'Add Image'
        },
        library: {
            type: [  'image' ]
        },
        multiple: false
    });

    media_uploader.on("select", function(){
        var image = media_uploader.state().get("selection").first().toJSON();
        if (image.url){
            mooShowWaitMessage();
            //Change Item Img
            var images = [
                {"image_url": image.url, "image_default": 1, "image_enabled": 1}
            ];
            jQuery.post(moo_params.ajaxurl,{'action':'moo_save_items_with_images',"item_uuid":itemUuid,"description":'',"images":images}, function (data) {
                    if(data.status === 'Success') {
                        if(data.data === true) {
                            if (image.sizes && image.sizes.thumbnail && image.sizes.thumbnail.url) {
                                //Add image to the list of items
                                jQuery("#moo-item-img-"+itemUuid).attr('src',image.sizes.thumbnail.url);
                            } else {
                                //Add image to the list of items
                                jQuery("#moo-item-img-"+itemUuid).attr('src',image.url);
                            }
                            mooHideWaitMessage();
                        } else {
                            swal("Error","Error when saving your changes, please try again","error");
                        }
                    } else {
                        swal("Error","Error when saving your changes, please try again","error");
                    }
                }
            ).fail(function () {
                swal("Error","Error when saving your changes, please try again","error");
            });
        } else {
            swal("Error","Error when saving your changes, please try again","error");
        }

    });
    media_uploader.open();
}

function mooFastConnectWithClover() {
    var link = 'https://www.clover.com/oauth/v2/authorize?client_id=6MWGRRXJD5HMW&redirect_uri=https://api-v2.smartonlineorders.com/v2/auth/clover-login/fast';
    window.open(link, '_blank');
}
function mooConnectWithClover(apiKey) {
    mooShowWaitMessage();
    mooSaveApikey(apiKey);
}


/* IMPORT EXPORT INVENTORY SCRIPT */

function moo_change_exportImport_tab(tab) {
    if (tab === 'export'){
        jQuery('#mooImportSection').hide();
        jQuery('#mooExportSection').show();
        jQuery('#mooExportSectionButton').addClass("btn_active");
        jQuery('#mooImportSectionButton').removeClass("btn_active");
    }
    if (tab === 'import'){
        jQuery('#mooImportSection').show();
        jQuery('#mooExportSection').hide();
        jQuery('#mooExportSectionButton').removeClass("btn_active");
        jQuery('#mooImportSectionButton').addClass("btn_active");
    }
}
/* Export DATA */
function mooExportInventory() {

    var checkedOptions = {};

    //Get the checked options
    document.getElementsByName('mooExportOptions').forEach(function(elem) {
        checkedOptions[elem.value] = elem.checked;
    });

    //Check if at least one options has been choosen
    if( Object.values(checkedOptions).every(value => value === false) ){
        swal({ title: "Error",
            text: 'You must choose at least one option',
            type: "error",
            confirmButtonText: "ok"
        });
        return;
    }

    //Show a waiting message
    mooShowWaitMessage();

    //Send the request
    jQuery.ajax({
        type: 'POST',
        url: moo_RestUrl+'moo-clover/v2/dash/export',
        contentType: 'application/json; charset=UTF-8',
        beforeSend: function(jqXhr) {
            jqXhr.setRequestHeader('X-WP-Nonce', moo_params.nonce)
        },
        data: JSON.stringify(checkedOptions)
    }).fail(function (data) {
        swal({ title: "Error",
            text: 'An error has occurred, please try again',
            type: "error",
            confirmButtonText: "ok"
        });
    }).done(function (data) {
        var filename = window.location.hostname ? window.location.hostname : "";
        filename += "-Inventory";
        swal.close();
        downloadObjectAsJson(data,filename);
    });
}
function downloadObjectAsJson(exportObj, exportName){
    var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(exportObj));
    var downloadAnchorNode = document.createElement('a');
    downloadAnchorNode.setAttribute("href",     dataStr);
    downloadAnchorNode.setAttribute("download", exportName + ".json");
    document.body.appendChild(downloadAnchorNode); // required for firefox
    downloadAnchorNode.click();
    downloadAnchorNode.remove();
}
/* -- IMPORT DATA -- */
function uploadingDefaultStyle() {
    document.getElementById("moo-drag-area").style.borderColor = '#193466';
    document.getElementById("uploadIcon").style.display = 'inline-block';
    document.getElementById("uploadingJsonIcon").style.display = 'none';
    document.getElementById('drop_title').innerHTML = `Drop your file here`;
    document.getElementById("drop_subTitle").style.display = 'inline-block';
    document.getElementById('progress-bar').style.display='none';
    document.getElementById('browse_file').style.display='block';
    document.getElementById('file_upload_inventory').value="";

}
function uploadingFileStyle() {
    document.getElementById("moo-drag-area").style.borderColor = '#000';
    document.getElementById("in-progress").style.background = '#193466';
    document.getElementById("uploadIcon").style.display = 'none';
    document.getElementById("uploadingJsonIcon").style.display = 'inline-block';
    document.getElementById('drop_title').innerHTML = `Uploading...`;
    document.getElementById("drop_subTitle").style.display = 'none';
    document.getElementById("progress-bar").style.display = 'inline-block'
    document.getElementById('browse_file').style.display='none';
    document.getElementById('try_again_import').style.display='none';
}
function uploadingMsg(msg) {
    document.getElementById('drop_title').innerHTML = msg;
}
function errorUploadingStyle() {
    document.getElementById('drop_title').innerHTML = "<spam style='color: #DF5C5C'>Failed!</spam>";
    document.getElementById("in-progress").style.background = '#DF5C5C';
    document.getElementById("try_again_import").style.display = 'inline-block';
    document.getElementById('browse_file').style.display='none';
}
function mooGetJsonFromText(str) {
    try {
        return JSON.parse(str);
    } catch (e) {
        return false;
    }
}
function mooClickOnBrowseButton() {
    document.getElementById('file_upload_inventory').click();
    return;
}
function uploadJsonData() {
    // reade file
    var fileSelector = document.getElementById('file_upload_inventory').files;
    var fr = new FileReader();
    fr.onload = function(){
        readContentFile(fr.result);
    }
    fr.readAsText(fileSelector[0]);
}
function readContentFile(fileTextContent) {
    var fileContent = mooGetJsonFromText(fileTextContent);
    if (fileContent) {
        if (
            fileContent.images ||
            fileContent.descriptions ||
            fileContent.modifiers ||
            fileContent.ordersTypes ||
            fileContent.settings
        ) {
            //Change style to uplading style
            uploadingFileStyle();
            jQuery("#mooImportSectionUpload").hide();

            // Clean local storage inventory images
            localStorage.removeItem('items');
            localStorage.removeItem('categories');
            localStorage.removeItem('inventoryImported');

            if (fileContent.descriptions){
                mooImportDescription(fileContent.descriptions);
            }

            if (fileContent.ordersTypes){
                jQuery("#mooImportSection>#mooImportResult>.mooOrdersTypes").show();
                mooImportOrdersTypes({
                    "ordersTypes":fileContent.ordersTypes
                });
            }
            if (fileContent.modifiers || fileContent.modifier_groups ){
                jQuery("#mooImportSection>#mooImportResult>.mooModifiers").show();
                mooImportModifiersAndGroups({
                    "modifiers":fileContent.modifiers,
                    "modifier_groups":fileContent.modifier_groups,
                });
            }

            if (fileContent.settings){
                jQuery("#mooImportSection>#mooImportResult>.mooSettings").show();
                mooImportSettings(fileContent.settings)
            }

            if (fileContent.images){

                if (fileContent.images.items && fileContent.images.items.length >0 ){
                    localStorage.setItem("items", JSON.stringify(fileContent.images.items));
                    jQuery("#mooImportSection>#mooImportResult>.mooItems").show();
                    mooImportItems(0);
                }

                if (fileContent.images.categories && fileContent.images.categories.length >0 ){
                    localStorage.setItem("categories", JSON.stringify(fileContent.images.categories));
                    jQuery("#mooImportSection>#mooImportResult>.mooCategories").show();
                    mooImportCategories(0)
                }
            }
        } else {
            swal("Error","Please double the check the uploaded file, it looks like it's exported from Smart Online Order","error");
            document.getElementById('file_upload_inventory').value="";
        }
    } else {
        swal("Error","The type of the uploaded file isn't supported yet, please upload a json file","error");
        document.getElementById('file_upload_inventory').value = "";
    }

}
// POST Descriptions FUNCTION

function mooImportDescription(descriptions) {
    // URL EndPoint
    if(moo_RestUrl.indexOf("?rest_route") !== -1){
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/import/descriptions&_wpnonce=' + moo_params.nonce ;
    } else {
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/import/descriptions?_wpnonce=' + moo_params.nonce ;
    }
    //Show upload result section
    jQuery("#mooImportSection>#mooImportResult>.mooDescriptions").show();

    jQuery.post(endpoint,JSON.stringify(descriptions), function (response) {
        if (response.status === true) {
            moveProgressTo("mooImportDescriptionsProgress",100);
            jQuery("#mooImportSection>#mooImportResult>.mooDescriptions>.text-upload>.moo-uploading-msg").text(
                "Descriptions imported successfully"
            );
        } else {
            jQuery("#mooImportSection>#mooImportResult>.mooDescriptions>.text-upload>.moo-uploading-msg").text(
                "Descriptions are not imported, an error has occurred"
            );
        }
    });
}
function mooImportOrdersTypes(data) {
    // URL EndPoint
    if(moo_RestUrl.indexOf("?rest_route") !== -1){
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/import/ordersTypes&_wpnonce=' + moo_params.nonce ;
    } else {
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/import/ordersTypes?_wpnonce=' + moo_params.nonce ;
    }
    //Show upload result section
    jQuery("#mooImportSection>#mooImportResult>.mooOrdersTypes").show();

    jQuery.post(endpoint,JSON.stringify(data), function (response) {
        if (response.status === true) {
            moveProgressTo("mooImportOrdersTypesProgress",100);
            jQuery("#mooImportSection>#mooImportResult>.mooOrdersTypes>.text-upload>.moo-uploading-msg").text(
                "Orders Types imported successfully"
            );
        } else {
            jQuery("#mooImportSection>#mooImportResult>.mooOrdersTypes>.text-upload>.moo-uploading-msg").text(
                "Orders Types are not imported, an error has occurred"
            );
        }
    });
}
function mooImportModifiersAndGroups(data) {
    // URL EndPoint
    if(moo_RestUrl.indexOf("?rest_route") !== -1){
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/import/modifiers&_wpnonce=' + moo_params.nonce ;
    } else {
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/import/modifiers?_wpnonce=' + moo_params.nonce ;
    }
    //Show upload result section
    jQuery("#mooImportSection>#mooImportResult>.mooModifiers").show();

    jQuery.post(endpoint,JSON.stringify(data), function (response) {
        if (response.status === true) {
            moveProgressTo("mooImportModifiersProgress",100);
            jQuery("#mooImportSection>#mooImportResult>.mooModifiers>.text-upload>.moo-uploading-msg").text(
                "Modifiers and Modifier Groups imported successfully"
            );
        } else {
            jQuery("#mooImportSection>#mooImportResult>.mooModifiers>.text-upload>.moo-uploading-msg").text(
                "Modifiers and Modifier Groups are not imported, an error has occurred"
            );
        }
    });
}
function mooImportSettings(settings) {
    //document.getElementById('drop_title').innerHTML = `Settings Uploading...`;
    // URL EndPoint
    if(moo_RestUrl.indexOf("?rest_route") !== -1){
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/import/settings&_wpnonce=' + moo_params.nonce ;
    } else {
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/import/settings?_wpnonce=' + moo_params.nonce ;
    }
    //Show upload result section
    jQuery("#mooImportSection>#mooImportResult>.mooSettings").show();

    jQuery.post(endpoint, JSON.stringify(settings) , function (response) {
        if (response.status === true) {
            moveProgressTo("mooImportSettingsProgress", 100);
            jQuery("#mooImportSection>#mooImportResult>.mooSettings>.text-upload>.moo-uploading-msg").text(
                "Settings imported successfully"
            );
        } else {
            jQuery("#mooImportSection>#mooImportResult>.mooSettings>.text-upload>.moo-uploading-msg").text(
                "Settings aren't imported successfully"
            );
        }
    });
}
function mooImportItems(index) {
    var items = JSON.parse(localStorage.getItem("items"));
    var nbItems = items.length;
    if (nbItems <= index){
        jQuery("#mooImportSection>#mooImportResult>.mooItems>.text-upload>.moo-uploading-msg").text(
            "Finished uploading your items"
        );
        moveProgressTo("mooImportItemsProgress", 100);
        return;
    }
    var item = items[index];
    delete(items);
    var percentage = Math.ceil(index * 100 / nbItems);
    var cloneImage;
    // Endpoint URL
    if(moo_RestUrl.indexOf("?rest_route") !== -1){
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/import/images&_wpnonce=' + moo_params.nonce ;
    } else {
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/import/images?_wpnonce=' + moo_params.nonce ;
    }

    if (moo_params.home_url && item.url.startsWith(moo_params.home_url)){
        cloneImage = false;
    } else {
        cloneImage = true;
    }
    jQuery.post(endpoint,JSON.stringify({
        "cloneImages":cloneImage,
        "items":[item]
    }),function (response) {
        if (response.status === 'success') {
            //Todo :: Success
            moveProgressTo("mooImportItemsProgress", percentage);
            mooImportItems(index+1);
        } else {
            //TODO : errors
            moveProgressTo("mooImportItemsProgress", percentage);
            console.log(response);
            mooImportItems(index+1);
        }
    });
}
function mooImportCategories(index) {
    var categories = JSON.parse(localStorage.getItem("categories"));
    var nbCategories = categories.length;
    if (nbCategories <= index){
        jQuery("#mooImportSection>#mooImportResult>.mooCategories>.text-upload>.moo-uploading-msg").text(
            "Finished uploading your categories"
        );
        moveProgressTo("mooImportCategoriesProgress", 100);
        return;
    }
    var category = categories[index];
    delete(categories);
    var percentage = Math.ceil(index * 100 / nbCategories);
    var cloneImage;
    // Endpoint URL
    if(moo_RestUrl.indexOf("?rest_route") !== -1){
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/import/images&_wpnonce=' + moo_params.nonce ;
    } else {
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/import/images?_wpnonce=' + moo_params.nonce ;
    }

    if (category.image_url){
        if (moo_params.home_url && category.image_url.startsWith(moo_params.home_url)){
            cloneImage = false;
        } else {
            cloneImage = true;
        }
    } else {
        cloneImage = false;
    }

    jQuery.post(endpoint,JSON.stringify({
        "cloneImages":cloneImage,
        "categories":[category]
    }),function (response) {
        if (response.status === 'success') {
            //Todo :: Success
            moveProgressTo("mooImportCategoriesProgress", percentage);
            mooImportCategories(index+1);
        } else {
            //TODO : errors
            moveProgressTo("mooImportCategoriesProgress", percentage);
            console.log(response);
            mooImportCategories(index+1);
        }
    });
}
function moveProgressTo(selector, progress) {
    var elem = document.getElementById(selector);
    elem.style.width = progress < 100 ? progress + "%" : '100%';
}


function postDescriptions(object,progress) {
    // URL EndPoint
    if(moo_RestUrl.indexOf("?rest_route") !== -1){
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/import/descriptions&_wpnonce=' + moo_params.nonce ;
    } else {
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/import/descriptions?_wpnonce=' + moo_params.nonce ;
    }
    // POST
    let objDesc = JSON.stringify(object['descriptions']);
    uploadingMsg("Uploading your descriptions...");
    jQuery.post(endpoint,objDesc, function (response) {
        if (response.status === true) {
            moveProgress(progress,100);
            if (object['settings']){
                postSettings(object,progress);
            } else if (object.images) {
                // POST Image
                if (object.images.categories.length > 0) {
                    postImages(object.images,'categories',1)
                }
                else if (object.images.items.length > 0){
                    postImages(object.images,'items',1)
                }
            } else {
                moveProgress(10,100);
            }
        } else {
            swal("Error","Error in the importation of the description content, Check the description content","error");
            document.getElementById('file_upload_inventory').value="";
        }
    });
}
function postSettings(object, progress) {
    //document.getElementById('drop_title').innerHTML = `Settings Uploading...`;
    // URL EndPoint
    if(moo_RestUrl.indexOf("?rest_route") !== -1){
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/import/settings&_wpnonce=' + moo_params.nonce ;
    } else {
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/import/settings?_wpnonce=' + moo_params.nonce ;
    }
    // POST
    let objSettings = JSON.stringify(object['settings']);
    uploadingMsg("Settings Uploading...");
    jQuery.post(endpoint,objSettings, function (response) {
        if (response.status === true) {
            if (object.images) {
                // POST Image
                moveProgress(progress,100);
                if (object.images.categories.length > 0) {
                    postImages(object.images,'categories',1)
                }
                else if (object.images.items.length > 0){
                    postImages(object.images,'items',1)
                } else {
                    moveProgress(progress,100);
                }
            } else {
                moveProgress(progress,100);
            }
        } else {
            swal("Error","Error in the importation of the Settings content, Check the Settings content","error");
            document.getElementById('file_upload_inventory').value="";
        }
    });
}
function postImages(object, objectName, progress) {
    uploadingMsg(objectName+' Uploading...');
    let nbrRowCat = object.categories.length;
    let nbrRowItems = object.items.length;
    let nbrRowInObject = nbrRowCat + nbrRowItems;
    let percentage = Math.floor(100/(nbrRowInObject/3));
    let objectDivided;
    let url_image;
    // Endpoint URL
    if(moo_RestUrl.indexOf("?rest_route") !== -1){
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/import/images&_wpnonce=' + moo_params.nonce ;
    } else {
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/import/images?_wpnonce=' + moo_params.nonce ;
    }
    // Clone IMAGE Status
    let url_website = moo_RestUrl.split('/').slice(2);
    if (objectName === 'categories') {
        url_image = object[objectName][0].image_url.split('/').slice(2);
    } else {
        url_image = object[objectName][0].url.split('/').slice(2);
    }
    let cloneImage = true;
    if (url_website[0] === url_image[0]) {
        cloneImage = false;
    }
    // Add Propriete ImageSettings to Image Object
    object.cloneImages = cloneImage;
    object.skipWhenImageExist = false;
    // Check number items
    if (object[objectName].length > 3){
        // Dividing object
        objectDivided = objectsDividing(object,objectName);
        postInventoryImages(objectDivided,1,progress,percentage, objectName, object);
    } else {
        // Post Images Normal
        let newObject = JSON.stringify({[objectName]:object[objectName],settings:imageSettings});
        jQuery.post(endpoint,newObject,function (response) {
            if (response.status === 'success') {
                if (objectName !== 'items') {
                    moveProgress(progress, progress + 40);
                    postImages(object,'items', progress);
                } else {
                    moveProgress(progress,100);
                }
            } else {
                swal("Error","Error in the importation of the images "+objectName+", Check the file content","error");
                document.getElementById('file_upload_inventory').value="";
            }
        });
    }
}
function postInventoryImages(object,line,progress,percentage, objectName,imgObject) {
    const bnrLine = Object.keys(object).length;
    let newProgress = +progress+percentage;
    // URL EndPoint
    if(moo_RestUrl.indexOf("?rest_route") !== -1){
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/import/images&_wpnonce=' + moo_params.nonce ;
    } else {
        var endpoint = moo_RestUrl+'moo-clover/v2/dash/import/images?_wpnonce=' + moo_params.nonce ;
    }
    // POST
    jQuery.post(endpoint,JSON.stringify(object[line]), function (response) {
        if (response.status === 'success') {
            moveProgress(progress, newProgress);
            // Saving in local storage
            addInventoryImportedToLocalStorage(object[line],objectName);
            if (line < bnrLine){
                postInventoryImages(object,line+1,newProgress,percentage, objectName,imgObject);
            } else if (objectName !== 'items') {
                postImages(imgObject,'items', newProgress);
            } else {
                moveProgress(progress, 100);
                uploadingMsg('Uploaded successfully');
                setInterval(uploadingDefaultStyle, 200);
            }
        } else {
            localStorage.setItem('progress',newProgress);
            errorUploadingStyle();
        }
    }).fail(function() {
        localStorage.setItem('progress',newProgress);
        errorUploadingStyle();
    });
}
function objectsDividing(objectDividing,objectName){
    let object = {};
    let objectImages = [];
    let objectCount = 0;
    let nbrRow = objectDividing[objectName].length;
    let imageSettings = objectDividing.settings;
    let nbrNewObject = 0;
    for (let i =0; i<nbrRow;i++){
        objectImages.push(objectDividing[objectName][i]);
        objectCount++;
        if (objectCount === 3){
            nbrNewObject++;
            object[nbrNewObject] = {[objectName]:objectImages,cloneImages:objectDividing.cloneImages,skipWhenImageExist:objectDividing.skipWhenImageExist};
            objectCount=0;
            objectImages=[];
        } else if (i === nbrRow-1){
            nbrNewObject++;
            object[nbrNewObject] = {[objectName]:objectImages,cloneImages:objectDividing.cloneImages,skipWhenImageExist:objectDividing.skipWhenImageExist};
        }
    }
    return object;
}

/* Progress Bar Moving */
function moveProgress(start,progress) {
    //let i = 1;
    var elem = document.getElementById("in-progress");
    var width = start;
    setInterval(frame, 10);
    function frame() {
        if (width < progress) {
            width++;
            elem.style.width = width + "%";
        } else if (width >= 100){
            //document.getElementById('drop_title').innerHTML = `Uploaded successfully`;
        }
    }
}
function addInventoryImportedToLocalStorage(images, objName) {
    if (localStorage.getItem('inventoryImported') !== null) {
        let importedList = localStorage.getItem('inventoryImported');
        let object = JSON.parse(importedList);
        if (object[objName]) {
            for (let i = 0; i < images[objName].length; i++){
                object[objName].push(images[objName][i]);
            }
        } else {
            object[objName] = images[objName];
        }
        localStorage.setItem("inventoryImported", JSON.stringify(object));
    } else {
        // create new local storage
        localStorage.setItem("inventoryImported", JSON.stringify(images));
    }
}
function checkIfExistInObjectImported() {
    let originList = localStorage.getItem('inventoryList');
    originList = JSON.parse(originList);
    let newList = {categories:[],items:[]};
    // check categories imported
    for (let i =0 ; i< originList.categories.length; i++){
        if (!isFound(originList.categories[i].uuid,'categories')) {
            newList.categories.push(originList.categories[i]);
        }
    }
    // check Items imported
    for (let i =0 ; i< originList.items.length; i++){
        if (!isFound(originList.items[i].uuid,'items')) {
            newList.items.push(originList.items[i]);
        }
    }
    return newList;
}
function isFound(uuid,elementName) {
    let importedList = localStorage.getItem('inventoryImported');
    importedList = JSON.parse(importedList);
    let isFound = false;
    if (importedList[elementName]){
        for (let i = 0; i<importedList[elementName].length;i++){
            if (importedList[elementName][i].uuid === uuid){
                isFound = true;
                break;
            }
        }
    }
    return isFound;
}
function reImportInventory() {
    let progress = localStorage.getItem('progress');
    uploadingFileStyle();
    let listTryAgain = checkIfExistInObjectImported();
    if (listTryAgain.categories.length>0) {
        postImages(listTryAgain,'categories',progress);
    } else if (listTryAgain.items.length>0) {
        //console.log(listTryAgain.items);
        postImages(listTryAgain,'items',progress);
    } else {
        moveProgress(progress,100);
    }

}