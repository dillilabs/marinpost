(function($) {
    $.fn.stripeForm  = function(options) {
        var config = $.extend({}, $.fn.stripeForm.defaults, options);

        return this.each(function() {
            var form = $(this);

            //------------------------------------------------------------------
            // Selectors
            //------------------------------------------------------------------

            var planSelect     = form.find('input[name=plan]');
            var requiredFields = form.find('input.required, select.required');
            var submitButton   = form.find('input[type=submit]');
            var errors         = form.find('ul.errors');
            var spinner        = form.find('img.spinner');
    
            //------------------------------------------------------------------
            // Functions
            //------------------------------------------------------------------

            // Convert address_line1 to Address Line 1
            var properCase = function(str) {
                return str.replace(/[-_]/g, ' ').replace(/\w\S*/g, function(txt) {
                    return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
                });
            };

            var disableSubmitAndShowSpinner = function() {
                submitButton.prop('disabled', true);
                spinner.show();
            };

            var hideSpinnerAndEnableSubmit = function() {
                spinner.hide();
                submitButton.prop('disabled', false);
            };

            var handleStripe = function(status, response) {
              if (response.error) {
                errors.append('<li>'+ response.error.message +'</li>');
                hideSpinnerAndEnableSubmit();

              } else {
                var token = response.id;
                form.append($('<input type="hidden" name="stripeToken" />').val(token));

                if (config.ajaxSubmit) {
                    ajaxSubmit(form);
                } else {
                    // normal HTTP submit
                    form.get(0).submit();
                }

                return false;
              }
            };

            var ajaxSubmit = function() {
                var data = form.serialize();
                var fields = form.find('input[type=text]');
                var success = form.find('img.success');
                var card = form.find('.current-card');

                errors.empty();
                disableSubmitAndShowSpinner();

                $.post('/', data, function(data) {
                    hideSpinnerAndEnableSubmit();

                    if (data.error) {
                        errors.append('<li>' + data.error + '</li>');
                    } else {
                        fields.val('');
                        success.fadeIn(function() { success.fadeOut(); });
                        card.text(data.card);
                    }
                });
            };

            //------------------------------------------------------------------
            // Events
            //------------------------------------------------------------------

            form.submit(function(e) {
                var valid = true;

                errors.empty();

                if (config.requirePlan && planSelect.filter(':checked').length == 0) {
                    valid = false;
                    errors.append('<li>Subscription Plan is required</li>');
                }

                requiredFields.each(function(i, field) {
                    var field = $(field);

                    if (field.val().length == 0) {
                        valid = false;
                        errors.append('<li>'+properCase(field.attr('data-stripe'))+' is required</li>');
                    }
                });

                if (valid) {
                    disableSubmitAndShowSpinner();
                    Stripe.card.createToken(form, handleStripe);
                }

                return false;
            });
        });
    };

    $.fn.stripeForm.defaults = {
        ajaxSubmit: false,
        requirePlan: true,
    };
}(jQuery));
