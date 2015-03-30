$(function() {
  var registerForm = $('form#register');
  var emailForm = $('form#update-email');

  registerForm.submit(function(e) {
    var email = $(this).find('input#email');
    var confirmEmail = $(this).find('input#confirm_email');

    confirmEmail.next('ul.errors').remove();

    if (confirmEmail.val() !== email.val()) {
      confirmEmail.after('<ul class="errors"><li>Does not match Email</li></ul>');
      e.preventDefault();
    }
  });

  emailForm.submit(function(e) {
    var email = $(this).find('input#email');
    var confirmEmail = $(this).find('input#confirm_email');
    var password = $(this).find('input#password');

    confirmEmail.next('ul.errors').remove();
    password.next('ul.errors').remove();

    if (confirmEmail.val() !== email.val()) {
      confirmEmail.after('<ul class="errors"><li>Does not match Email</li></ul>');
      e.preventDefault();
    }

    if (password.val().trim().length == 0) {
      password.after('<ul class="errors"><li>Current password is required.</li></ul>');
      e.preventDefault();
    }
  });
});
