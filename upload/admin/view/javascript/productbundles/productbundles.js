var chosenProduct = "";
var chosenProductShow = "";
var chosenCategoryShow = "";
var Dialog;

// Remember Tab State
$(function() {
	$('#mainTabs a:first').tab('show'); // Select first tab
	$('#langtabs a:first').tab('show');
	if (window.localStorage && window.localStorage['currentTab']) {
		$('.mainMenuTabs a[href="'+window.localStorage['currentTab']+'"]').tab('show');
	}
	if (window.localStorage && window.localStorage['currentSubTab']) {
		$('a[href="'+window.localStorage['currentSubTab']+'"]').tab('show');
	}
	$('.fadeInOnLoad').css('visibility','visible');
	$('.mainMenuTabs a[data-toggle="tab"]').click(function() {
		if (window.localStorage) {
			window.localStorage['currentTab'] = $(this).attr('href');
		}
	});
	$('a[data-toggle="tab"]:not(.mainMenuTabs a[data-toggle="tab"], .langtabs a[data-toggle="tab"])').click(function() {
		if (window.localStorage) {
			window.localStorage['currentSubTab'] = $(this).attr('href');
		}
	});
});

// Show & Hide Tabs
$(function() {
    var $typeSelector = $('#Checker');
    var $toggleArea = $('.module__');
	var $toggleArea2 = $('#settingsTab');
	var $toggleArea3 = $('#bundlesTab');
	 if ($typeSelector.val() === 'yes') {
            $toggleArea.show(); 
			$toggleArea2.show();
			$toggleArea3.show();
        }
        else {
            $toggleArea.hide(); 
			$toggleArea2.hide();
			$toggleArea3.hide();
        }
    $typeSelector.change(function(){
        if ($typeSelector.val() === 'yes') {
            $toggleArea.show(300); 
			$toggleArea2.show(300);
			$toggleArea3.show(300);
        }
        else {
            $toggleArea.hide(300); 
			$toggleArea2.hide(300);
 			$toggleArea3.hide(300);
        }
    });
});

$(function() {
    var $typeSelector1 = $('#LinkChecker');
    var $toggleArea1 = $('#MainLinkOptions');
	 if ($typeSelector1.val() === 'yes') {
            $toggleArea1.show(); 
        }
        else {
            $toggleArea1.hide(); 
        }
    $typeSelector1.change(function(){
        if ($typeSelector1.val() === 'yes') {
            $toggleArea1.show(300); 
        }
        else {
            $toggleArea1.hide(300); 
        }
    });
});

//Show bundles
$('document').ready(function() {
    $.ajax({
        url: "index.php?route=" + modulePath + "/get_bundles&" + storeAddon + "&" + tokenAddon,
        type: 'get',
        dataType: 'html',
        success: function(data) { 
            $('.bundles-list').html(data);
            initializeProductFilterAutocomplete();
        }
    });
});

function addNewBundle() {
    $.ajax({
        url: "index.php?route=" + modulePath + "/add_bundle&" + tokenAddon + "&" + storeAddon + "&bundle_id=0",
        type: 'get',
        dataType: 'html',
        beforeSend: function() {
            $('.bundles-list').html(loadingPhase);
        },
        success: function(data) { 
            $('.bundles-list').html(data);
            initializeProductAutocomplete();
            initializeProductShowAutocomplete();
            initializeCategoryShowAutocomplete();
            initializeRemoveIconFunctionality();
            initializeDiscountCalculation();
            
            chosenProduct = "";
            chosenProductShow = "";
            
            $('.bundlePriceInput').trigger('keyup'); 
            
            $('.date').datetimepicker({
                pickTime: false
            });
            
            $('#langtabs-bundle a:first').tab('show');
            $('.bundle-description').summernote({
                height: 250,                 
                minHeight: null,             
                maxHeight: null,             
            });
        }
    });
}

function editBundle(bundle_id) {
    $.ajax({
        url: "index.php?route=" + modulePath + "/add_bundle&" + tokenAddon + "&" + storeAddon + "&bundle_id=" + bundle_id,
        type: 'get',
        dataType: 'html',
        beforeSend: function() {
            $('.bundles-list').html(loadingPhase);
        },
        success: function(data) { 
            $('.bundles-list').html(data);
            initializeProductAutocomplete();
            initializeProductShowAutocomplete();
            initializeCategoryShowAutocomplete();
            initializeRemoveIconFunctionality();
            initializeDiscountCalculation();
            
            chosenProduct = "";
            chosenProductShow = "";
            
            $('.bundlePriceInput').trigger('keyup'); 
            
            $('.date').datetimepicker({
                pickTime: false
            });
            
            $('#langtabs-bundle a:first').tab('show');
            $('.bundle-description').summernote({
                height: 250,                 
                minHeight: null,             
                maxHeight: null,             
            });
        }
    });
}

function deleteBundle(id) {
    if (id === undefined) {
        return false;
    }
    
    bootbox.confirm({
        title: text_delete,
        message: text_delete_a_confirmation,
        buttons: {
            cancel: {
                label: '<i class="fa fa-times"></i> ' + text_cancel
            },
            confirm: {
                label: '<i class="fa fa-check"></i> ' + text_confirm
            }
        },
        callback: function (result) {
            if (result) {
                $.ajax({
                    url: "index.php?route=" + modulePath + "/remove_bundle&" + tokenAddon + "&id=" + id,
                    type: 'get',
                    dataType: 'JSON',
                    beforeSend: function() {
                        $('.bundles-list').html(loadingPhase);
                    },
                    success: function(json) { 
                        if (json['success']) {
                            $('.bundles-list').load("index.php?route=" + modulePath + "/get_bundles&" + storeAddon + "&" + tokenAddon);
                        } else {
                            alert(json['error']);
                        }
                    }
                });
            } else {
                return true;
            }
        }
    });
}


function cancelBundle(selector) {
    $.ajax({
        url: "index.php?route=" + modulePath + "/get_bundles&" + storeAddon + "&" + tokenAddon,
        type: 'get',
        dataType: 'html',
        beforeSend: function() {
            $('.bundles-list').html(loadingPhase);
        },
        success: function(data) { 
            $('.bundles-list').html(data);
            initializeProductFilterAutocomplete();
        }
    });
}

function saveBundle(selector) {
    var status = false;
    
    var error_name = false;
    $('div.createBundleForm').find('input[name^="bundle[name]"]').each(function(index, key) { 
        if ($(key).val() == "") {
            error_name = true;
        }
    });
    
    var error_bundled_products = false;
    if ($('div.createBundleForm').find('input[name^="bundle[products]"]').length < 2) {
        error_bundled_products = true;
    }
    
    if (error_name || error_bundled_products) {
        Dialog = bootbox.alert({
            title: error_missing_data,
            message: text_error_missing_data
        });
    } else {
        
        $(".bundle-description").each(function(index, element) {
            try {
                var content = $(element).html($(element).code());
            } catch (err) {
                if (err.message.indexOf('is not a function') > -1) {
                    var content = $(element).html($(element).summernote('code'));
                }
            }
        });
        
        $.ajax({
            url: "index.php?route=" + modulePath + "/create_edit_bundle&" + tokenAddon + "&" + storeAddon,
            dataType: 'JSON',
            type: "POST",
            data: $('div.createBundleForm').find('input,select,textarea,checkbox').serialize(),
            beforeSend: function() {
                $('.bundles-list').html(loadingPhase);
            },
            success: function(data) {
                if (data['success']) {
                    message = data['message'];
                    
                    $.ajax({
                        url: "index.php?route=" + modulePath + "/get_bundles&" + storeAddon + "&" + tokenAddon,
                        type: 'get',
                        dataType: 'html',
                        beforeSend: function() {
                            $('.bundles-list').html(loadingPhase);
                        },
                        success: function(data) { 
                            $('.bundles-list').html(data);
                        }
                    });
                }

                if (data['error']) {

                }
            },
            complete: function() {
            }
        });  
    }
    
}

function removeProductFromBundle(selector, product_price) {
    $(selector).parent().addClass('remove-div');
    $(selector).parent().hide(500);
    
    // Updating the price of the bundle
    var totalProductsPrice = $('.total-products-price').html();
    if (totalProductsPrice.length == 0) totalProductsPrice = '0';
    var newBundlePrice = (parseFloat(totalProductsPrice) - parseFloat(product_price)).toFixed(2);
    $('.total-products-price').html(newBundlePrice);
    $('.bundlePriceInput').trigger('keyup');
    
    setTimeout(function() {
       $(selector).parent().remove(); 
    }, 500);
}

function addProductToBundle(selector) {
    var quantity = $('input[name=\'quantityInput\']').val();
    var textOption = '';
    var realPrice = 0;
    var html = '';

    if (chosenProduct != "") {
        if (chosenProduct['special'] == 0) { 
            realPrice = chosenProduct['price'] 
        } else {
            realPrice = chosenProduct['special']; 
        }
        
        if (chosenProduct['option']!="") {
            textOption = ' <i class="fa fa-tags" style="color:#ab9a87;font-size:13px;"></i> ';	
        }
        
        html += '<div class="col-sm-6 col-md-3 col-lg-2 hidden-div" style="display:none;">';
            html += '<input type="hidden" name="bundle[products][]" value="' + chosenProduct['value'] +'" />';    
            html += '<a onClick="removeProductFromBundle(this, \'' + realPrice + '\');" class="btn btn-xs btn-default pull-right" role="button"><i class="fa fa-times-circle" aria-hidden="true"></i></a>';
            html += '<div class="thumbnail">';
                html += '<img src="' + chosenProduct['image'] + '" alt="' + chosenProduct['label'] + '">';
                html += '<div class="caption text-center">';
                    html += '<h3>' + textOption + chosenProduct['label'] + '</h3>';
                    html += '<p>' + text_price + ': ' + realPrice + ' ' + currency + '</p>';
                html += '</div>';
            html += '</div>';
            html += '<div class="clearfix"></div>';
        html += '</div>';
        
        var totalProductsPrice = $('.total-products-price').html();
        if (totalProductsPrice.length == 0) totalProductsPrice = '0';
        
        for (i=0; i < quantity; i++) {
            // Updating the price of the bundle
            var newBundlePrice = (parseFloat(realPrice) + parseFloat(totalProductsPrice)).toFixed(2);
            $('.total-products-price').html(newBundlePrice);
            
            totalProductsPrice = newBundlePrice;
            if (totalProductsPrice.length == 0) totalProductsPrice = '0';
            
            $('.bundlePriceInput').trigger('keyup');
            
            // Adding the product in the bundle set
            $('.bundle-products-listbox').append(html);
            $('.bundle-products-listbox .hidden-div').show(500);
            $('.bundle-products-listbox .hidden-div').removeClass('hidden-div');
        }
        
        $('input[name=\'productInput\']').val('');
        $('input[name=\'quantityInput\']').val('1');
        chosenProduct = '';
    }
    
}

var initializeProductAutocomplete = function () {
	$('input[name=\'productInput\']').autocomplete({
		delay: 500,
		source: function(request, response) {
			$.ajax({
				url: 'index.php?route=' + modulePath + '/autocomplete_products&' + tokenAddon +'&' + storeAddon + '&filter_name=' +  encodeURIComponent(request) ,
				dataType: 'json',
				success: function(json) {		
					response($.map(json, function(item) {
						return {
							label: item['name'],
							value: item['product_id'],
							price: item['price'],
                            image: item['image'],
							special: item['special'],
							option: item['option']
						}
					}));
				}
			});
		}, 
		select: function(item) {
            chosenProduct = item;

            $(this).val(item['label']);	
		}
	});	
}

/*function addProductToShow(selector) {
    var html = '';

    if (chosenProductShow != "") {
        $('.label-product-' + chosenProductShow['value']).remove();
        
        html += '<span class="label label-lg label-primary label-product-' + chosenProductShow['value'] + ' hidden-div" style="display:none;">' + chosenProductShow['label'];
        html += ' <i class="fa fa-times-circle removeIcon"></i>';
        html += '<input type="hidden" name="bundle[products_show][]" value="' + chosenProductShow['value'] +'" />';    
        html += '</span>';
        
        $('.bundle-products-show-listbox').append(html);
        $('.bundle-products-show-listbox .hidden-div').show(500);
        $('.bundle-products-show-listbox .hidden-div').removeClass('hidden-div');
        
        $('input[name=\'productShowInput\']').val('');
        chosenProductShow = '';
    }
    
}*/

var initializeProductShowAutocomplete = function () {
	$('input[name=\'productShowInput\']').autocomplete({
		delay: 500,
		source: function(request, response) {
			$.ajax({
				url: 'index.php?route=' + modulePath + '/autocomplete_products&' + tokenAddon + '&' + storeAddon + '&filter_name=' +  encodeURIComponent(request) ,
				dataType: 'json',
				success: function(json) {		
					response($.map(json, function(item) {
						return {
							label: item['name'],
							value: item['product_id'],
							price: item['price'],
                            image: item['image'],
							special: item['special'],
							option: item['option']
						}
					}));
				}
			});
		}, 
		select: function(item) {
            chosenProductShow = item;
            
            var html = '';

            if (chosenProductShow != "") {
                $('.label-product-' + chosenProductShow['value']).remove();

                html += '<span class="label label-lg label-primary label-product-' + chosenProductShow['value'] + ' hidden-div" style="display:none;">' + chosenProductShow['label'];
                html += ' <i class="fa fa-times-circle removeIcon"></i>';
                html += '<input type="hidden" name="bundle[products_show][]" value="' + chosenProductShow['value'] +'" />';    
                html += '</span>';

                $('.bundle-products-show-listbox').append(html);
                $('.bundle-products-show-listbox .hidden-div').show(500);
                $('.bundle-products-show-listbox .hidden-div').removeClass('hidden-div');

                $('input[name=\'productShowInput\']').val('');
                chosenProductShow = '';
            }
            
            $(this).val('');	
		}
	});	
}

/*function addCategoryToShow(selector) {
    var html = '';

    if (chosenCategoryShow != "") {
        $('.label-category-' + chosenCategoryShow['value']).remove();
        
        html += '<span class="label label-lg label-primary label-category-' + chosenCategoryShow['value'] + ' hidden-div" style="display:none;">' + chosenCategoryShow['label'];
        html += ' <i class="fa fa-times-circle removeIcon"></i>';
        html += '<input type="hidden" name="bundle[categories_show][]" value="' + chosenCategoryShow['value'] +'" />';    
        html += '</span>';
        
        $('.bundle-categories-show-listbox').append(html);
        $('.bundle-categories-show-listbox .hidden-div').show(500);
        $('.bundle-categories-show-listbox .hidden-div').removeClass('hidden-div');
        
        $('input[name=\'categoryShowInput\']').val('');
        chosenCategoryShow = '';
    } 
}*/

var initializeCategoryShowAutocomplete = function () {
	$('input[name=\'categoryShowInput\']').autocomplete({
		delay: 500,
		source: function(request, response) {
			$.ajax({
				url: 'index.php?route=' + modulePath + '/autocomplete_categories&' + tokenAddon + '&' + storeAddon + '&filter_name=' +  encodeURIComponent(request),
				dataType: 'json',
				success: function(json) {		
					response($.map(json, function(item) {
						return {
							label: item['name'],
							value: item['category_id']
						}
					}));
				}
			});
		}, 
		select: function(item) {
			chosenCategoryShow = item;

            var html = '';

            if (chosenCategoryShow != "") {
                $('.label-category-' + chosenCategoryShow['value']).remove();

                html += '<span class="label label-lg label-primary label-category-' + chosenCategoryShow['value'] + ' hidden-div" style="display:none;">' + chosenCategoryShow['label'];
                html += ' <i class="fa fa-times-circle removeIcon"></i>';
                html += '<input type="hidden" name="bundle[categories_show][]" value="' + chosenCategoryShow['value'] +'" />';    
                html += '</span>';

                $('.bundle-categories-show-listbox').append(html);
                $('.bundle-categories-show-listbox .hidden-div').show(500);
                $('.bundle-categories-show-listbox .hidden-div').removeClass('hidden-div');

                $('input[name=\'categoryShowInput\']').val('');
                chosenCategoryShow = '';
            }

            $(this).val('');	
		}
	});
    
}

var initializeRemoveIconFunctionality = function () {
    $('.bundle-products-show-listbox').delegate('.removeIcon', 'click', function() {
        $(this).parent().remove();
    });
    
    $('.bundle-categories-show-listbox').delegate('.removeIcon', 'click', function() {
        $(this).parent().remove();
    });
}

var initializeDiscountCalculation = function () {
    $('.bundlePriceInput').on('keyup',function() {
        var totalProductsPrice = $('.total-products-price').html();
        var enteredDiscountPrice = $('.bundlePriceInput').val();
        var discountType = $('.discountType').val();
        var discountedPrice = '0';
        if (enteredDiscountPrice.length == 0) enteredDiscountPrice = '0';
        
        if (discountType == '2') { 
            if (parseFloat(totalProductsPrice)) {
                var enteredPercentage = enteredDiscountPrice/100;
                var discountValue = enteredPercentage*totalProductsPrice;

                discountedPrice = (parseFloat(totalProductsPrice) - parseFloat(discountValue)).toFixed(2);

                if (isNaN(discountedPrice)) {
                    discountedPrice = totalProductsPrice;
                }
                
                if (discountedPrice < 0) {
                    discountedPrice = parseFloat(0.0);
                }
                
                $('.total-bundle-price').html(parseFloat(discountedPrice).toFixed(2));
            }        
        } else {
            if (parseFloat(totalProductsPrice)) {
                discountedPrice = (parseFloat(totalProductsPrice) - parseFloat(enteredDiscountPrice)).toFixed(2);

                if (isNaN(discountedPrice)) {
                    discountedPrice = totalProductsPrice;
                }
                
                if (discountedPrice < 0) {
                    discountedPrice = parseFloat(0.0);
                }

                $('.total-bundle-price').html(parseFloat(discountedPrice).toFixed(2));
            }    
        }
        
    });
    
    $('.discountType').on('change', function() {
        $('.bundlePriceInput').trigger('keyup');        
    });
}

// Pagination Bundles
$('document').ready(function() {
   $('.bundles-list').delegate('.pagination a', 'click', (function(e){
        e.preventDefault();
        $.ajax({
            url: this.href,
            type: 'get',
            dataType: 'html',
            beforeSend: function() {
               $('.bundles-list').html('<p><h2 class="text-center">' + loadingPhase + '</h2></p>');
            },
            success: function(data) {				
                $('.bundles-list').html(data);
                initializeProductFilterAutocomplete();
            }
        });
    })); 
});

function filterData(selector) {
    var url = 'index.php?route=' + modulePath + '/get_bundles';

	var filter_name = $(selector + ' input[name=\'filter_name\']').val();

	if (filter_name) {
		url += '&filter_name=' + encodeURIComponent(filter_name);
	}

	var filter_product = $(selector + ' input[name=\'filter_product\']').val();

	if (filter_product) {
		url += '&filter_product=' + encodeURIComponent(filter_product);
	}
    
    var filter_product_id = $(selector + ' input[name=\'filter_product_id\']').val();

	if (filter_product_id) {
		url += '&filter_product_id=' + encodeURIComponent(filter_product_id);
	}

	var filter_discount = $(selector + ' input[name=\'filter_discount\']').val();

	if (filter_discount) {
		url += '&filter_discount=' + encodeURIComponent(filter_discount);
	}

	var filter_status = $(selector + ' select[name=\'filter_status\']').val();

	if (filter_status != '*') {
		url += '&filter_status=' + encodeURIComponent(filter_status);
	}

    var store_id = $(selector + ' input[name=\'store_id\']').val();

    if (store_id) {
        url += '&store_id=' + encodeURIComponent(store_id);
    }

    $.ajax({
        url: url + "&" + tokenAddon,
        type: 'get',
        dataType: 'html',
        beforeSend: function() {
            $('.bundles-list').html(loadingPhase);
        },
        success: function(data) { 
            $('.bundles-list').html(data);
            initializeProductFilterAutocomplete();
        }
    });
}

$(document).ready(function(e) {
    $('body').delegate('.filter-bundles input, .filter-bundles select', 'keydown', (function(e) {
        if (e.keyCode == 13) {
            filterData('.filter-bundles');
		}  
    })); 
    
     $('body').delegate('.filter-bundles input[name=\'filter_product\']', 'keyup', (function(e) {
        if ($(this).val() == "") {
            $('input[name=\'filter_product_id\']').val('');
		}  
    })); 
});

var initializeProductFilterAutocomplete = function () {
	$('input[name=\'filter_product\']').autocomplete({
		delay: 500,
		source: function(request, response) {
			$.ajax({
				url: 'index.php?route=' + modulePath + '/autocomplete_products&' + tokenAddon +'&' + storeAddon + '&filter_name=' +  encodeURIComponent(request) ,
				dataType: 'json',
				success: function(json) {		
					response($.map(json, function(item) {
						return {
							label: item['name'],
							value: item['product_id'],
						}
					}));
				}
			});
		}, 
		select: function(item) {
            chosenProduct = item;

            $(this).val(item['label']);	
            $('input[name=\'filter_product_id\']').val(item['value']);
		}
	});	
}
