/**
 * Inject CSRF token into jQuery POST requests.
 */
(function($) {
    $.fn.csrf  = function(options) {
        var config = $.extend({}, $.fn.csrf.defaults, options);

        return this.each(function() {
            $(document).ajaxSend(function(elm, xhr, s) {
                if (s.type == 'POST') {
                    if (!s.data) {
                        s.data = config.csrfTokenName+'='+config.csrfTokenValue;

                    } else if (typeof(s.data) == 'string') {
                        s.data += '&'+config.csrfTokenName+'='+config.csrfTokenValue;

                    } else {
                        s.data[config.csrfTokenName] = config.csrfTokenValue;
                    }
                }
            });
        });
    };

    $.fn.csrf.defaults = {
        csrfTokenName: null,
        csrfTokenValue: null
    };
}(jQuery));
