var select2_options = {
    'ajax': {
        url: datasetSearchConfig.CKAN_API_ENDPOINT + 'package_search',
        dataType: 'json',
        delay: 250,
        data: function(params) {
            return { q: params.term };
        },
        processResults: function(result, page) {
            return {
                results: jQuery.map(result.result.results, function (obj) {
                    obj.id = obj.name;
                    obj.text = obj.title[datasetSearchConfig.currentLanguage];
                    obj.title = obj.title[datasetSearchConfig.currentLanguage];
                    return obj;
                })
            };
        }
    },
    minimumInputLength: 3,
    placeholder: datasetSearchConfig.placeholder,
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
    },
};

jQuery( document ).ready(function( $ ) {
    $('.dataset_search_box').each(function(index) {
        // get parent repeatable group table for current dataset_search box
        var repeatableGroupTable = $( this ).closest('.cmb-repeatable-group');
        repeatableGroupTable
            .on('cmb2_add_row', function (event, row) {
                var name = $(row).find('.dataset_search_box')[0].name;
                // remove the previous select2 rendering, as CMB2 copies everything
                $("[name='" + name + "'] + .select2-container").remove();
                var new_select = $("[name='" + name + "'").select2(select2_options);
                // select empty value as original is copied
                new_select.val('').trigger('change');
            });
    });
});