var datasetSearchOptions = {
    'ajax': {
        url: baseConfig.datasetSearch.CKAN_API_ENDPOINT + 'package_search',
        dataType: 'json',
        delay: 250,
        data: function(params) {
            return { q: params.term };
        },
        processResults: function(result, page) {
            return {
                results: jQuery.map(result.result.results, function (obj) {
                    obj.id = obj.identifier;
                    obj.text = obj.title[baseConfig.datasetSearch.currentLanguage];
                    obj.title = obj.title[baseConfig.datasetSearch.currentLanguage];
                    return obj;
                })
            };
        }
    },
    minimumInputLength: 3,
    placeholder: baseConfig.datasetSearch.placeholder,
    allowClear: true,
    templateResult: function(dataset) {
        if (dataset.loading) return dataset.text;
        return "<div class='dataset-item'>" + dataset.text + "</div>";
    },
    templateSelection: function (dataset) {
        return dataset.text || dataset.text;
    },
    escapeMarkup: function(m) {
        return m;
    }
};

var mediatypeSearchOptions = {
    placeholder: baseConfig.mediatypeSearch.placeholder,
    allowClear: true
};

jQuery( document ).ready(function( $ ) {
    var rebuild_select2_box = function(element, boxClassName, options) {
        // get parent repeatable group table for current dataset_search box
        var repeatableGroupTable = $( element ).closest('.cmb-repeatable-group');
        repeatableGroupTable
            .on('cmb2_add_row', function (event, row) {
                var name = $(row).find('.' + boxClassName)[0].name;
                // remove the previous select2 rendering, as CMB2 copies everything
                $("[name='" + name + "'] + .select2-container").remove();
                var new_select = $("[name='" + name + "']").select2(options);
                // select empty value as original is copied
                new_select.val('').trigger('change');
            });
    };
    var rebuild_dataset_search_select2_box_cb = function() {
        rebuild_select2_box(this, 'dataset_search_box', datasetSearchOptions);
    };
    var rebuild_mediatype_search_select2_box_cb = function() {
        rebuild_select2_box(this, 'mediatype_search_box', mediatypeSearchOptions);
    };

    $('.dataset_search_box').each(rebuild_dataset_search_select2_box_cb);
    $('.mediatype_search_box').each(rebuild_mediatype_search_select2_box_cb);
});

jQuery( document ).ready(function( $ ) {
    //disable all delete buttons
    $('.cmb-remove-row button').attr("disabled","disabled");

    $('.cmb-remove-row').append('<p class="cmb2-metabox-description" style="align: right">' + baseConfig.datasetEdit.deleteText + '</p>');

    var code = "jQuery(this).siblings('button').prop('disabled', function(i, v) { return !v; });";
    $('.cmb-remove-row').prepend('<input type="checkbox" style="position: relative; top:5px; margin-bottom: 15px;" onchange="' + code + '"></input></span>');
});