// TODO localize / inject api constant
var select2_options = {
    'ajax': {
        url: ogdConfig.CKAN_API_ENDPOINT + 'package_search',
        dataType: 'json',
        delay: 250,
        data: function(params) {
            return { q: params.term };
        },
        processResults: function(result, page) {
            console.log(result);
            return {
                results: jQuery.map(result.result.results, function (obj) {
                    obj.id = obj.name;
                    obj.text = obj.title.de;
                    obj.title = obj.title.de;
                    return obj;
                })
            };
        }
    },
    minimumInputLength: 3,
    placeholder: 'Datensatz suchen...',
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
    formatNoMatches: function() {
        return "Keine Treffer gefunden!";
    }
};

jQuery( document ).ready(function( $ ) {
    var fieldGroupId     = '_app-showcase-app_relations';
    var fieldGroupTable = $( document.getElementById( fieldGroupId + '_repeat' ) );
    fieldGroupTable
        .on( 'cmb2_add_row', function(event, row) {
            var name = $(row).find('.search-box')[0].name;
            console.log(row);
            // remove the previous select2 rendering, as CMB2 copies everything
            $("[name='" + name + "'] + .select2-container").remove();
            var new_select = $("[name='" + name + "'").select2(select2_options);
            // select empty value as original is copied
            new_select.val('').trigger('change');
        })
});