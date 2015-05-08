(function($) {
    $.fn.contact  = function(options) {
        return this.each(function() {
            var form = $(this);

            //-----------------------
            // Selectors
            //-----------------------

            var name = form.find('input[name=fromName]');
            var email = form.find('input[name=fromEmail]');
            var subject = form.find('select[name=subject]');
            var message = form.find('textarea[name=message]');

            //-----------------------
            // Functions
            //-----------------------

            var validField = function(field, label) {
                if (field.val().trim().length == 0) {
                    field.after('<ul class="errors"><li>'+label+' cannot be blank.</li></ul>');
                    return false;
                }

                return true;
            };

            // Email must be at least 5 chars and contain an @
            var validEmail = function(field) {
                var email = field.val();

                if (email.length < 5 || email.indexOf('@') < 1) {
                    field.after('<ul class="errors"><li>Email is not valid.</li></ul>');
                    return false;
                }

                return true;
            };

            //-----------------------
            // Hooks
            //-----------------------

            form.submit(function(e) {
                var validForm = true;
                
                form.find('ul.errors').remove();

                if (!validField(name, 'Name')) validForm = false;

                if (!validField(email, 'Email')) {
                    validForm = false;
                } else if (!validEmail(email)) {
                    validForm = false;
                }

                if (!validField(subject, 'Subject')) validForm = false;

                if (!validField(message, 'Message')) validForm = false;

                return validForm;
            });
        });
    };
}(jQuery));
