jQuery(document).ready(function(){var r=jQuery("#be-filters");if(!r.length)return!0;var e=r.find(".advanced-filters .base");e.removeClass("base"),window.advancedGroupTemplate=e[0].outerHTML,e.remove(),r.on("click",".remove-advanced-filter",function(e){e.preventDefault();var t=jQuery(this).parents(".advanced-field"),a=t.find(".wpse-advanced-filters-field-selector").attr("name");beAddRowsFilter(a+"="),t.remove()}),jQuery("body").on("click","#be-filters .new-advanced-filter",function(e){e.preventDefault();var t=window.advancedGroupTemplate.replace(new RegExp(/\[\]/,"g"),"["+r.find(".advanced-filters .advanced-filters-list").children().length+"]"),a=r.find(".advanced-filters  .advanced-filters-list .advanced-field").last();a.length?a.after(t):r.find(".advanced-filters  .advanced-filters-list").prepend(t),setTimeout(function(){vgseInitSelect2(r.find(".advanced-filters  .advanced-filters-list .advanced-field select.select2").last())},1e3),r.find(".advanced-filters  .advanced-filters-list").children().show()}),r.find(".new-advanced-filter").last().trigger("click"),r.find(".advanced-filters-toggle").on("change",function(){jQuery(this).is(":checked")?r.find(".advanced-filters").show():r.find(".advanced-filters").hide()}),jQuery("body").on("change",".wpse-advanced-filters-operator-selector",function(){var e=jQuery(this).find("option:selected").data("value-field-type")||"";if(e){var t=jQuery(this).parents(".fields-wrap").find(".wpse-advanced-filters-value-selector"),a=t.attr("name");if(2===t.length){a=t.last().attr("name");t.last().remove()}var r=t.attr("class");t.replaceWith('<input type="'+e+'" class="'+r+'" name="'+a+'" />'),jQuery(this).parents(".fields-wrap").find(".wpse-advanced-filters-value-selector").trigger("change")}else{jQuery(this).parents(".fields-wrap").find('.wpse-advanced-filters-value-selector:first[type="number"]').attr("type","text");var s=jQuery(this).parents(".fields-wrap").find(".wpse-advanced-filters-field-selector").val();vgseInputToFormattedColumnField(s,jQuery(this).parents(".fields-wrap"),".wpse-advanced-filters-value-selector")}}),jQuery("body").on("change",".wpse-advanced-filters-field-selector",function(){var e=jQuery(this).val(),t=jQuery(this);if(!e||e&&t.data("last-formatted-key")&&e!==t.data("last-formatted-key")){var a=t.attr("name").replace("[key]","[value]");t.parents(".fields-wrap").find(".wpse-advanced-filters-value-selector:gt(0)").remove(),t.parents(".fields-wrap").find(".wpse-advanced-filters-value-selector").replaceWith('<input name="'+a+'" class="wpse-advanced-filters-value-selector" type="text" />'),t.data("last-formatted-key","")}if(e&&(t.parent().find("input.field-source").val(t.find("option:selected").data("source")),t.parent().find(".search-value-wrap input").val(""),vgseInputToFormattedColumnField(e,t.parents(".fields-wrap"),".wpse-advanced-filters-value-selector"),t.data("last-formatted-key",e),e)){var r=e;"date"===(void 0!==vgse_editor_settings.final_spreadsheet_columns_settings[r]?vgse_editor_settings.final_spreadsheet_columns_settings[r].value_type:"")?t.parents(".fields-wrap").find('.wpse-advanced-filters-operator-selector option[data-value-type="date"]').show():t.parents(".fields-wrap").find('.wpse-advanced-filters-operator-selector option[data-value-type="date"]').hide()}})}),jQuery(document).ready(function(){jQuery("body").on("click","[data-start-saved-search]",function(e){e.preventDefault();var t=jQuery(this).data("start-saved-search");vgseRemoveAllFilters(),jQuery("body").data("be-filters",""),beAddRowsFilter(t),vgseFilters.fillSearchForm(t),vgseReloadSpreadsheet()})}),jQuery(document).ready(function(){var r=jQuery("#be-filters");if(!r.length)return!0;var s=jQuery(".vg-toolbar .button-container.run_filters-container .toolbar-submenu");function n(e){return s.find("ul").filter(function(){return jQuery(this).data("key")===e})}function i(){1<s.find("input").length?s.find(".wpse-start-quick-search").show():s.find(".wpse-start-quick-search").hide(),s.find(".wpse-start-quick-search").appendTo(s)}function d(e,t){if(n(t).length)return!0;e.find(".select2").each(function(){jQuery(this).data("select2")&&jQuery(this).select2("destroy")});var a=e.clone(!0);s.append('<ul data-key="'+t+'"></ul>'),a.find(".wpse-advanced-filters-field-selector").length&&a.find(".wpse-advanced-filters-field-selector").val("_sku"),s.find("ul").last().append(a),vgseInitSelect2(e.find(".select2")),vgseInitSelect2(s.find("ul").last().find(".select2")),i()}r.find("li").each(function(){var e=jQuery(this),t=e.find("input,select,textarea").first();if(t.length){var a=t.attr("name");if(a&&"keyword"!==a){var r=-1<vgse_editor_settings.favorite_search_fields.indexOf(a)?"fa-star":"fa-star-o";e.append('<button class="wpse-favorite-search-field" type="button" data-key="'+a+'"><i class="fa '+r+'"></i></button>'),"fa-star"==r&&d(e,a)}}}),jQuery("body").on("click",".wpse-favorite-search-field",function(e){e.preventDefault();var t=jQuery(this);t.find(".fa-star").length?t.find("i").addClass("fa-star-o").removeClass("fa-star"):t.find("i").addClass("fa-star").removeClass("fa-star-o"),t.find(".fa-star").length?d(t.parents("li"),t.data("key")):function(e,t){n(t).remove(),i()}(t.parents("li"),t.data("key"));var a=[];r.find(".wpse-favorite-search-field .fa-star").each(function(){a.push(jQuery(this).parent().data("key"))}),jQuery.post(vgse_global_data.ajax_url,{action:"vgse_mark_search_fields_as_favorite",post_type:vgse_editor_settings.post_type,fields:a,nonce:vgse_editor_settings.nonce})})});