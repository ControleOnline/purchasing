define('purchasing-default', ['jquery', 'core'], function (jQuery, core) {
    var purchasing = {};
    purchasing.bind = function (selector) {
        jQuery(selector).find('.stepwizard-row a').click(function () {
            jQuery(this).parent().parent().find('.stepwizard-step').removeClass('active');
            jQuery(this).parent().addClass('active');
        });
        jQuery(selector).find('[name=choose-origin]:checked').trigger('change');
        jQuery(selector).find('[name=choose-destination]:checked').trigger('change');

        jQuery(selector).find('#btn-quote-details').click(function () {
            var container = '#quote-details-container';
            var url = '/sales/quote-details.html';
            jQuery.ajax({
                beforeSend: function (xhr) {

                },
                url: core.format.normalizeUrl(url),
                data: {
                    quote: jQuery(container).data('quote')
                },
                method: 'GET',
                success: function (data, textStatus, jqXHR) {
                    jQuery(container).html(jQuery('<div/>').html(data).find(container).html());
                    core.bind(container);
                }
            });
        });
    };
    return purchasing;
});
