$(function() {
  var selectDate = $('div#select-letter-date');
  var resetLink = $('a#reset-letter-filter');
  var baseUrl = '/letters/';
  var dateFromUrl = document.location.pathname.match(/\/(\d{4}\/\d{2}\/\d{2})/);

  selectDate.datepicker({
    dateFormat: 'yy/mm/dd',
    defaultDate: dateFromUrl ? dateFromUrl[1] : 0,
    onSelect: function() {
      document.location = baseUrl + this.value;
    }
  });

  resetLink.click(function() {
    document.location = baseUrl;
  });
});
