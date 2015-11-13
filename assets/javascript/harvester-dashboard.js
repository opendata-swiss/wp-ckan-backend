jQuery(document).ready(function($) {
    $( '.collapsible.collapsed' ).accordion({
        active: false,
        animate: false,
        collapsible: true,
        icons: { "header": "dashicons dashicons-arrow-down", "activeHeader": "dashicons dashicons-arrow-up" }
    });
    $( '.collapsible.open' ).accordion({
        animate: false,
        collapsible: true,
        icons: { "header": "dashicons dashicons-arrow-down", "activeHeader": "dashicons dashicons-arrow-up" }
    });
});