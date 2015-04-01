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
    var confirmEmail = $(this).find('input#confirmEmail');
    var password = $(this).find('input#password');
    var confirmPassword = $(this).find('input#confirmPassword');
    var populated = true;

    clearErrors(email);
    clearErrors(confirmEmail);
    clearErrors(password);
    clearErrors(confirmPassword);

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

    if (isBlank(password)) {
      addError(password, 'Password is required');
      populated = false;
      e.preventDefault();
    }

    if (isBlank(confirmPassword)) {
      addError(confirmPassword, 'Password confirmation is required');
      populated = false;
      e.preventDefault();
    }

    if (populated && notEqual(password, confirmPassword)) {
      addError(confirmPassword, 'Does not match New Password.');
      e.preventDefault();
    }
  });

  emailForm.submit(function(e) {
    var newEmail = $(this).find('input#email');
    var confirmEmail = $(this).find('input#confirmEmail');
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
      addError(confirmEmail, 'Does not match New Email Address.');
      e.preventDefault();
    }

    if (isBlank(currentPassword)) {
      addError(currentPassword, 'Current Password is required for security.');
      e.preventDefault();
    }
  });

  passwordForm.submit(function(e) {
    var newPassword = $(this).find('input#newPassword');
    var confirmPassword = $(this).find('input#confirmPassword');
    var currentPassword = $(this).find('input#password');
    var populated = true;

    clearErrors(newPassword);
    clearErrors(confirmPassword);
    clearErrors(currentPassword);

    if (isBlank(newPassword)) {
      addError(newPassword, 'New Password is required');
      populated = false;
      e.preventDefault();
    }

    if (isBlank(confirmPassword)) {
      addError(confirmPassword, 'New Password confirmation is required');
      populated = false;
      e.preventDefault();
    }

    if (populated && notEqual(newPassword, confirmPassword)) {
      addError(confirmPassword, 'Does not match New Password.');
      e.preventDefault();
    }

    if (isBlank(currentPassword)) {
      addError(currentPassword, 'Current Password is required for security.');
      e.preventDefault();
    }
  });
});
