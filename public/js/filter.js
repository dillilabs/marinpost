$(function() {
  var noFilters = $(':checkbox.all');
  var filters = $(':checkbox.filter');
  var filteredContent = $('#filtered-content');
  var section = document.location.pathname.split('/')[1];

  var activeFilters = function(type) {
    return filters.filter('.'+type+':checked').map(function() { return this.value; }).get().join();
  };

  var refreshViews = function() {
    filteredContent.load('/filter/'+section+'?locations='+activeFilters('location')+'&topics='+activeFilters('topic'));
  };

  var currentContentLength = function() {
    return $('.listing').length;
  };

  var moreContent = function() {
    $.get(
      '/more/'+section+'?locations='+activeFilters('location')+'&topics='+activeFilters('topic')+'&offset='+currentContentLength(),
      function(data) {
        filteredContent.append(data);
      }
    );
  };

  filters.click(function() {
    var filter = $(this);
    var type = filter.is('.location') ? '.location' : '.topic';
    var typeFilters;

    if (filter.is(':checked')) {
      noFilters.filter(type).prop('checked', false);
    } else {
      typeFilters = filters.filter(type);
      noFilters.filter(type).prop('checked', !typeFilters.is(':checked'));
    }

    refreshViews();
  });

  noFilters.click(function() {
    var noFilter = $(this);
    var type = noFilter.is('.location') ? '.location' : '.topic';

    if (noFilter.is(':checked')) {
      filters.filter(type).prop('checked', false);
    }

    refreshViews();
  });

  // FIXME activate on scroll
  $('#load-more-content').click(function(e) {
    e.preventDefault();
    moreContent();
  });
});
