$(function() {
  var contributorRadioButtons = $('ul.contributors');

  if (document.location.pathname.match(/^\/about\/contributors/)) {
    contributorRadioButtons.show();
  }

  if (m = document.location.pathname.match(/(^\/about\/contributors\/\d+\/\S+)/)) {
    contributorRadioButtons.find('input[value$="'+m[1]+'"]').attr('checked', 'checked');
  }

  contributorRadioButtons.find('input').click(function(e) {
    e.preventDefault();
    document.location = this.value;
  });
});
