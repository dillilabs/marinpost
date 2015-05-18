(function($) {
    $.fn.filters  = function() {
        var section = document.location.pathname.split('/')[1];

        return this.each(function() {
            var filteredContent = $(this);
            var toggleFilters = $('#filters fieldset h5');
            var filters = $(':checkbox.filter');
            var noFilters = $(':checkbox.all');
            var resetLink = $('.reset a');
            var contentLengthThreshold = 20;
            var scrollPositionThreshold = 0.85;
            var isLoadingContent = false;
            var endOfContent = false;

            //----------
            // Functions
            //----------

            // TODO refactor
            var filterType = function(e) {
              if (e.is('.location')) {
                return '.location';
              } else if (e.is('.topic')) {
                return '.topic';
              } else if (e.is('.author')) {
                return '.author';
              }
            };

            var activeFiltersFor = function(type) {
              return filters.filter('.'+type+':checked').map(function() { return this.value; }).get().join();
            };

            var activeFilters = function() {
              return {
                location: activeFiltersFor('location'),
                topic: activeFiltersFor('topic'),
                author: activeFiltersFor('author'),
              };
            };

            var urlFor = function(section, filters) {
              return '/filter?section='+section+'&locations='+filters.location+'&topics='+filters.topic+'&authors='+filters.author;
            };

            var disableFilters = function() {
              $(filters, noFilters).prop('disabled', true);
              resetLink.addClass('disabled');
            };

            var enableFilters = function() {
              $(filters, noFilters).prop('disabled', false);
              resetLink.removeClass('disabled');
            };

            var refreshViewsAndEnableFilters = function() {
              var filters = activeFilters();
              var url = urlFor(section, filters);

              filteredContent.load(url, enableFilters);

              if (filters.location.length || filters.topic.length || filters.author.length) {
                resetLink.show();
              } else {
                resetLink.hide();
              }
            };

            var currentContentLength = function() {
              return $('.listing').length;
            };

            var loadMoreContent = function() {
              var filters = activeFilters();
              var url = urlFor(section, filters);
              var offset = currentContentLength();

              isLoadingContent = true;

              $.get(
                url+'&offset='+offset,
                function(data) {
                  if (data.length > contentLengthThreshold) {
                    filteredContent.append(data);
                  } else {
                    endOfContent = true;
                  }

                  isLoadingContent = false;
                }
              );
            };

            // uncheck any Locations "above" current
            var deselectParent = function(child) {
              var id = child.attr('data-parent');
              var parent;

              if (id) {
                if (parent = $('input[value='+id+']')) {
                  parent.prop('checked', false);

                  // recurse
                  deselectParent(parent);
                }
              }
            }

            // uncheck any Locations "below" current
            var deselectChildren = function(parent) {
              var ids = parent.attr('data-children');
              var children, child;

              if (ids) {
                children = ids.split(',');

                $.each(children, function(unused, id) {
                  if (child = $('input[value='+id+']')) {
                    child.prop('checked', false);

                    // recurse
                    deselectChildren(child);
                  }
                });
              }
            }

            //-------
            // Events
            //-------

            toggleFilters.click(function() {
              var toggle = $(this);

              toggle.toggleClass('active').siblings('ul').slideToggle();
            });

            filters.click(function() {
              var filter = $(this);
              var type = filterType(filter);
              var typeFilters;

              disableFilters();

              if (filter.is(':checked')) {
                noFilters.filter(type).prop('checked', false);

                // handle geographically hierarchical Locations
                if (type == '.location') {
                  // console.log('parent=' + filter.attr('data-parent'), ', children=' + filter.attr('data-children'));
                  deselectParent(filter);
                  deselectChildren(filter);
                }

              } else {
                typeFilters = filters.filter(type);
                noFilters.filter(type).prop('checked', !typeFilters.is(':checked'));

              }

              refreshViewsAndEnableFilters();
            });

            noFilters.click(function() {
              var noFilter = $(this);
              var type = filterType(noFilter);

              disableFilters();

              if (noFilter.is(':checked')) {
                filters.filter(type).prop('checked', false);
              }

              refreshViewsAndEnableFilters();
            });

            resetLink.click(function(e) {
              var link = $(this);

              if (!link.hasClass('disabled')) {
                // FIXME
                noFilters.click();
              }

              e.preventDefault();
            });

            $(window).scroll(function() {
              var currentPosition = $(window).scrollTop() / ($(document).height() - $(window).height());
              var offset;

              if (isLoadingContent || endOfContent) return;

              if (currentPosition > scrollPositionThreshold) {
                loadMoreContent();
              }
            });
        });
    };
}(jQuery));
