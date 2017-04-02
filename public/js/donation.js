(function($) {
    $.fn.donation  = function(options) {
        var config = $.extend({}, $.fn.donation.defaults, options);

        return this.each(function() {
            var form = $(this);

            //------------------------------------------------------------------
            // Selectors
            //------------------------------------------------------------------

            var stripeFields   = form.find('input.required, select.required');
            var emailField     = form.find('input[name=email]');
            var amountField    = form.find('input[name=amount]');
            var amountCents    = 0;
            var submitButton   = form.find('input[type=submit]');
            var errors         = form.find('ul.errors');
            var spinner        = form.find('img.spinner');
    
            //------------------------------------------------------------------
            // Functions
            //------------------------------------------------------------------

            var validateAmount = function(amount) {
              amount = amount.replace(/\$/g, '').replace(/\,/g, '')

              amount = parseFloat(amount);

              if (isNaN(amount)) {
                errors.append('<li>Please enter a valid amount in USD ($).</li>');
                return false;

              }

              // Now convert to an integer for Stripe
              amount = amount * 100;
              amount = Math.round(amount);
              
              if (amount < config.minimumCents) {
                errors.append('<li>Donation amount must be at least $'+ (config.minimumCents / 100.0) +'.</li>');
                return false;

              }

              amountCents = amount;

              return true;
            }

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
                } else { // normal HTTP submit
                    form.get(0).submit();
                }

                return false;
              }
            };

            var ajaxSubmit = function() {
                var data = form.serialize();

                errors.empty();
                disableSubmitAndShowSpinner();

                $.post('/', data, function(data) {
                    hideSpinnerAndEnableSubmit();

                    if (data.error) {
                        errors.append('<li>' + data.error + '</li>');
                    } else {
                        form.parent().parent().html('<h3>Thank you for your donation!</h3>');
                    }
                });
            };

            //------------------------------------------------------------------
            // Events
            //------------------------------------------------------------------

            form.submit(function(e) {
                var valid = true;

                errors.empty();

                if (amountField.val().length == 0) {
                    errors.append('<li>Donation amount is required</li>');
                    valid = false;
                } else if (!validateAmount(amountField.val())) {
                    valid = false;
                }

                stripeFields.each(function(i, field) {
                    var field = $(field);

                    if (field.val().length == 0) {
                        errors.append('<li>'+properCase(field.attr('data-stripe'))+' is required</li>');
                        valid = false;
                    }
                });

                if (emailField.val().length == 0) {
                    errors.append('<li>Email Address is required</li>');
                    valid = false;
                }

                if (valid) {
                    disableSubmitAndShowSpinner();
                    amountField.val(amountCents);
                    Stripe.card.createToken(form, handleStripe);
                }

                return false;
            });
        });
    };

    $.fn.donation.defaults = {
        ajaxSubmit: true,
        minimumCents: 500,
    };
}(jQuery));
