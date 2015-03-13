$(function() {
  var selectDate = $('input#select-letter-date');
  var baseUrl = '/letters/';
  var toggleComments = $('#toggle-letter-comments');
  var comments = $('#letter-comments');

  selectDate.datepicker({
    dateFormat: 'yy/mm/dd',
    onSelect: function() {
      document.location = baseUrl + this.value;
    }
  });

  toggleComments.click(function() {
    comments.toggle();
    toggleComments.text((comments.is(':visible') ? 'Hide' : 'Show') + ' Comments');
  });
});
