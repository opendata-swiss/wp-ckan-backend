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