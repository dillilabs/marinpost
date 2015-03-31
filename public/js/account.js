$(function() {
  var registerForm = $('form#register');
  var emailForm = $('form#update-email');
  var passwordForm = $('form#update-password');

  var clearErrors = function(field) {
    field.next('ul.errors').remove();
  };

  var isBlank = function(field) {
    return field.val().trim().length == 0;
  };

  var notEqual = function(field, otherField) {
    return field.val() !== otherField.val();
  }

  var addError = function(field, msg) {
    field.after('<ul class="errors"><li>'+msg+'</li></ul>');
  };

  registerForm.submit(function(e) {
    var email = $(this).find('input#email');
    var confirmEmail = $(this).find('input#confirm_email');
    var populated = true;

    clearErrors(email);
    clearErrors(confirmEmail);

    if (isBlank(email)) {
      addError(email, 'Email Address is required.');
      populated = false;
      e.preventDefault();
    }

    if (isBlank(confirmEmail)) {
      addError(confirmEmail, 'Email Address confirmation is required.');
      populated = false;
      e.preventDefault();
    }

    if (populated && notEqual(email, confirmEmail)) {
      addError(confirmEmail, 'Does not match Email Address');
      e.preventDefault();
    }
  });

  emailForm.submit(function(e) {
    var newEmail = $(this).find('input#email');
    var confirmEmail = $(this).find('input#confirm_email');
    var currentPassword = $(this).find('input#password');
    var populated = true;

    clearErrors(newEmail);
    clearErrors(confirmEmail);
    clearErrors(currentPassword);

    if (isBlank(newEmail)) {
      addError(newEmail, 'New Email Address is required.');
      populated = false;
      e.preventDefault();
    }

    if (isBlank(confirmEmail)) {
      addError(confirmEmail, 'Email Address confirmation is required.');
      populated = false;
      e.preventDefault();
    }

    if (populated && notEqual(newEmail, confirmEmail)) {
      addError(confirmEmail, 'Does not match New Email Address');
      e.preventDefault();
    }

    if (isBlank(currentPassword)) {
      addError(currentPassword, 'Current Password is required for security.');
      e.preventDefault();
    }
  });

  passwordForm.submit(function(e) {
    var newPassword = $(this).find('input#newPassword');
    var currentPassword = $(this).find('input#password');

    clearErrors(newPassword);
    clearErrors(currentPassword);

    if (isBlank(newPassword)) {
      addError(newPassword, 'New Password is required');
      e.preventDefault();
    }

    if (isBlank(currentPassword)) {
      addError(currentPassword, 'Current Password is required for security.');
      e.preventDefault();
    }
  });
});
