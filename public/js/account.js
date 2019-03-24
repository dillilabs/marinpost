$(function() {
  var registerForm = $('form#register');
  var emailForm = $('form#update-email');
  var passwordForm = $('form#update-password');
  var deleteForm = $('form#delete-account');
  var deleteEntryLinks = $('a.delete-entry');

  var clearErrors = function(field) {
    field.next('ul.errors').remove();
  };

  var isBlank = function(field) {
    return field.val().trim().length == 0;
  };

  var tooShort = function(field) {
    return field.val().trim().length < 8;
  }

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
    var populated = { email: true, password: true };

    clearErrors(email);
    clearErrors(confirmEmail);
    clearErrors(password);
    clearErrors(confirmPassword);

    if (isBlank(email)) {
      addError(email, 'Email Address is required.');
      populated.email = false;
      e.preventDefault();
    }

    if (populated.email && isBlank(confirmEmail)) {
      addError(confirmEmail, 'Email Address confirmation is required.');
      populated.email = false;
      e.preventDefault();
    }

    if (populated.email && notEqual(email, confirmEmail)) {
      addError(confirmEmail, 'Does not match Email Address');
      e.preventDefault();
    }

    if (isBlank(password)) {
      addError(password, 'Password is required');
      populated.password = false;
      e.preventDefault();
    }

    if (populated.password && isBlank(confirmPassword)) {
      addError(confirmPassword, 'Password confirmation is required');
      populated.password = false;
      e.preventDefault();
    }

    if (populated.password && notEqual(password, confirmPassword)) {
      addError(confirmPassword, 'Does not match New Password.');
      e.preventDefault();
    }

    if (populated.password && tooShort(password)) {
      addError(password, 'Must have at least 8 characters.');
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

    if (populated && isBlank(confirmEmail)) {
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

    if (populated && tooShort(newPassword)) {
      addError(newPassword, 'Must have at least 8 characters.');
      e.preventDefault();
    }

    if (isBlank(currentPassword)) {
      addError(currentPassword, 'Current Password is required for security.');
      e.preventDefault();
    }
  });

  deleteForm.submit(function(e) {
    if (!confirm('Are you sure?')) {
      e.preventDefault();
    }
  });

  deleteEntryLinks.click(function(e) {
    e.preventDefault();

    if (confirm('Are you sure?')) {
      document.location = $(this).attr('data-url');
    }
  });

  /**
   * auto loading upon scroll to bottom
   */
  $.fn.scrollingSectionContent  = function(options) {
    var config = $.extend({}, $.fn.scrollingSectionContent.defaults, options);

    return this.each(function() {
      var content = $(this);
      var scrollPositionThreshold = 0.85;
      var isLoadingContent = false;
      var contentLengthThreshold = 20;
      var endOfContent = false;

      //----------
      // Functions
      //----------

      var currentContentLength = function() {
        return $('.listing').length;
      };

      var loadMoreContent = function() {
        isLoadingContent = true;

        $.get(
          '/account/entries/section_entries',
          {
            section: config.section,
            offset: currentContentLength(), 
            limit: config.contentLimit
          },
          function(data) {
            if (data.length > contentLengthThreshold) {
                content.append(data);
            } else {
                endOfContent = true;
            }
            isLoadingContent = false;
          }
        );
      };

      //-------
      // Events
      //-------

      $(window).scroll(function() {
        var currentPosition = $(window).scrollTop() / ($(document).height() - $(window).height());

        if (isLoadingContent) return;

        if (currentPosition > scrollPositionThreshold) {
          if (!endOfContent) loadMoreContent();
        }
      });
      $.fn.scrollingSectionContent.defaults = {
        contentLimit: 10
      };
    });
  };
});