$(function() {
  var noFilters = $(':checkbox.all');
  var filters = $(':checkbox.filter');
  var filteredContent = $('#filtered-content');
  var section = 'blog'; // TODO

  var activeFilters = function(type) {
    return filters.filter('.'+type+':checked').map(function() { return this.value; }).get().join();
  };

  var refreshViews = function() {
    filteredContent.load('/'+section+'/filter?locations='+activeFilters('location')+'&topics='+activeFilters('topic'));
  };

  var currentContentLength = function() {
    return $('.listing').length;
  };

  var moreContent = function() {
    $.get(
      '/'+section+'/more?locations='+activeFilters('location')+'&topics='+activeFilters('topic')+'&offset='+currentContentLength(),
      function(data) {
        filteredContent.append(data);
      }
    );
  };

  filters.click(function() {
    var filter = $(this);

    if (filter.is('.location')) {
      noFilters.filter('.location').attr('checked', false);
    } else {
      noFilters.filter('.topic').attr('checked', false);
    }

    refreshViews();
  });

  noFilters.click(function() {
    var noFilter = $(this);

    if (noFilter.is(':checked')) {
      if (noFilter.is('.location')) {
        filters.filter('.location').attr('checked', false);
      } else {
        filters.filter('.topic').attr('checked', false);
      }
    }

    refreshViews();
  });

  // FIXME activate on scroll
  $('#load-more-content').click(function(e) {
    e.preventDefault();
    moreContent();
  });
});
