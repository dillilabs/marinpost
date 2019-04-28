(function($) {
    $.fn.subscription  = function(options) {
        var config = $.extend({}, $.fn.subscription.defaults, options);

        return this.each(function() {

            //------------------------------------------------------------------
            // Variables
            //------------------------------------------------------------------

            var createForm     = $('form.'+config.createFormClass);
            var planForm       = $('form.'+config.planFormClass);
            var paymentForm    = $('form.'+config.paymentFormClass);
            var filterForm     = $('form.'+config.filterFormClass);
            var frequencyForm  = $('form.'+config.frequencyFormClass);
            var contentForm    = $('form.'+config.contentFormClass);
            var suspendForm    = $('form.'+config.suspendFormClass);
            var cancelForm     = $('form.'+config.cancelFormClass);
            var reactivateForm = $('form.'+config.reactivateFormClass);

            var locationCheckboxes = filterForm.find('input[name="fields[subscriptionLocations][]"]');
            var topicCheckboxes    = filterForm.find('input[name="fields[subscriptionTopics][]"]');
            var allTopicsCheckbox  = filterForm.find('#all-topics');
            var authorCheckboxes   = filterForm.find('input[name="fields[subscriptionAuthors][]"]');
            var allAuthorsCheckbox = filterForm.find('#all-authors');
            var letterCheckbox     = filterForm.find('input[name="fields[subscriptionLetters][]"]');
            var customContent      = filterForm.find('fieldset.custom, fieldset.custom > .location-group, fieldset.custom > h5.custom, h4.custom-content-container-heading.custom');
            var contentRadio       = filterForm.find('input[name="fields[subscriptionContent]"]');

            var expirationDate = cancelForm.find('.expiration-date');

            var currentIssueLink = $('a.current-issue');

            //------------------------------------------------------------------
            // Functions
            //------------------------------------------------------------------

            // Uncheck all Locations "above" child Location.
            var deselectParentLocation = function(child) {
                var id = child.attr('data-parent');
                var parent;

                if (id) {
                    if (parent = $('input[value='+id+']')) {
                        parent.prop('checked', false);

                        // recurse
                        deselectParentLocation(parent);
                    }
                }
            }

            // Propagate (un)checked state to all Locations "below" parent Location.
            var propagateToChildLocations = function(parent) {
                var checkedState = parent.is(':checked');
                var ids = parent.attr('data-children');
                var children;

                if (ids) {
                    children = ids.split(',');

                    $.each(children, function(unused, id) {
                        var child = $('input[value='+id+']');

                        if (child.length > 0) {
                            child.prop('checked', checkedState);

                            // recurse
                            propagateToChildLocations(child);
                        }
                    });
                }
            }

            // Before form submission.
            var disableSubmitAndShowSpinner = function(form) {
                form.find('input[type=submit]').prop('disabled', true);
                form.find('img.spinner').show();
            };

            // After form submission.
            var hideSpinnerAndEnableSubmit = function(form) {
                form.find('img.spinner').hide();
                form.find('input[type=submit]').prop('disabled', false);
            };


            // Hook into submit
            var handleSubmit = function(form) {
                var errors = form.find('ul.errors');
                var data = form.serialize();
                var success = form.find('img.success');

                errors.empty();
                disableSubmitAndShowSpinner(form);

                $.post('/', data, function(data) {
                    if (data.errors) {
                        errors.append('<li>' + data.errors + '</li>');
                    } else {
                        success.fadeIn(function() { success.fadeOut(); });

                        if (data.expirationDate) {
                            // Plan form only:
                            // update text in Cancel form
                            expirationDate.text(data.expirationDate);
                        }
                    }

                    hideSpinnerAndEnableSubmit(form);
                });
            };

            //------------------------------------------------------------------
            // Create subscription form
            //------------------------------------------------------------------

            createForm.stripeForm();

            //------------------------------------------------------------------
            // Payment method form
            //------------------------------------------------------------------

            paymentForm.stripeForm({
                ajaxSubmit: true,
                requirePlan: false
            });

            //------------------------------------------------------------------
            // Content form
            //------------------------------------------------------------------

            contentRadio.click(function() {
                var disabled = this.value == 'default';

                locationCheckboxes
                  .add(allTopicsCheckbox)
                  .add(topicCheckboxes)
                  .add(allAuthorsCheckbox)
                  .add(topicCheckboxes)
                  .add(authorCheckboxes)
                  .add(letterCheckbox)
                  .attr('disabled', disabled);

                if (disabled) {
                  customContent.addClass('disabled');
                } else {
                  customContent.removeClass('disabled');
                }
            });

            locationCheckboxes.click(function() {
                var location = $(this);

                deselectParentLocation(location);
                propagateToChildLocations(location);
            });

            allTopicsCheckbox.click(function() {
                var checkedStatus = $(this).prop('checked');
                topicCheckboxes.prop('checked', checkedStatus);
            });

            topicCheckboxes.click(function() {
                allTopicsCheckbox.prop('checked', false);
            });

            allAuthorsCheckbox.click(function() {
                var checkedStatus = $(this).prop('checked');
                authorCheckboxes.prop('checked', checkedStatus);
            });

            authorCheckboxes.click(function() {
                allAuthorsCheckbox.prop('checked', false);
            });

            //------------------------------------------------------------------
            // Handle submit for plan, frequency, content and suspend forms
            //------------------------------------------------------------------

            planForm.add(frequencyForm).add(contentForm).add(suspendForm).submit(function(e) {
                e.preventDefault();

                handleSubmit($(this));
            });

            //------------------------------------------------------------------
            // Cancel form
            //------------------------------------------------------------------

            cancelForm.submit(function() {
                var message = "Select \"OK\" to terminate your subscription when it expires on:\n\n    " + expirationDate.text();

                if (confirm(message)) {
                    disableSubmitAndShowSpinner(cancelForm);
                    return true;
                }

                return false;
            });

            //------------------------------------------------------------------
            // Reactivate form
            //------------------------------------------------------------------

            reactivateForm.submit(function() {
                disableSubmitAndShowSpinner(reactivateForm);
                return true;
            });

            //------------------------------------------------------------------
            // Current issue link
            //------------------------------------------------------------------

            currentIssueLink.click(function(e) {
                $.get('/actions/mpSubscription/myCurrentIssue', function(data) {
                    var resp = $('.'+config.currentIssueClass+'-response');

                    resp.text(data.message).fadeIn(function() { resp.fadeOut(2000); });
                });

                e.preventDefault();

                return false;
            });
        });
    };

    $.fn.subscription.defaults = {
        cancelFormClass:     'cancel-form',
        contentFormClass:    'content-form',
        createFormClass:     'create-form',
        currentIssueClass:   'current-issue',
        filterFormClass:     'filter-form',
        frequencyFormClass:  'frequency-form',
        paymentFormClass:    'payment-form',
        planFormClass:       'plan-form',
        reactivateFormClass: 'reactivate-form',
        suspendFormClass:    'suspend-form'
    };
}(jQuery));
