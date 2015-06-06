(function($) {
    $.fn.filters  = function(options) {
        var config = $.extend({}, $.fn.filters.defaults, options);
        var section = document.location.pathname.split('/')[1];

        return this.each(function() {
            var filteredContent = $(this);
            var toggleFilters = $('#filters fieldset h5');
            var filters = $(':checkbox.filter');
            var noFilters = $(':checkbox.all');
            var resetLink = $('.reset a');
            var dateFilterElement = $('div#date-filter');
            var dateFilter = '';
            var contentLengthThreshold = 20;
            var scrollPositionThreshold = 0.85;
            var isLoadingContent = false;
            var endOfContent = false;

            //----------
            // Functions
            //----------

            var initializeWithFilters = function(filterType) {
              var fieldset = $('#filters fieldset.'+filterType);
              var filters;

              fieldset.find('h5').click();

              if (filterType == 'date') {
                dateFilterElement.datepicker('setDate', config[filterType]);
                dateFilter = config[filterType];
              } else {
                fieldset.find('li > input#'+filterType+'-all').prop('checked', '');

                var filters = config[filterType].split(',');

                $.each(filters, function(_i, catId) {
                  fieldset.find('li > input#'+catId).prop('checked', 'checked');
                });
              }
            };

            var filterType = function(e) {
              if (e.is('.location')) {
                return '.location';
              } else if (e.is('.topic')) {
                return '.topic';
            //} else if (e.is('.author')) {
            //  return '.author';
              }
            };

            var activeFiltersOf = function(type) {
              return filters.filter('.'+type+':checked').map(function() { return this.value; }).get().join();
            };

            var activeFilters = function() {
              return {
                locations: activeFiltersOf('location'),
                topics: activeFiltersOf('topic'),
              //authors: activeFiltersOf('author'),
                date: dateFilter,
              };
            };

            var anyActiveFilters = function(selected) {
            //return (selected.locations.length || selected.topics.length || selected.authors.length || selected.date.length);
              return (selected.locations.length || selected.topics.length || selected.date.length);
            };

            var activeFilterUrlParams = function(selected) {
            //return 'locations='+selected.locations+'&topics='+selected.topics+'&authors='+selected.authors+'&date='+selected.date;
              return 'locations='+selected.locations+'&topics='+selected.topics+'&date='+selected.date;
            };

            var urlFor = function(section, activeFilters) {
              return '/filter?section=' + section + '&' + activeFilterUrlParams(activeFilters);
            };

            var disableFilters = function() {
              $(filters, noFilters).prop('disabled', true);
              dateFilterElement.datepicker('option', 'disabled', true);
              resetLink.addClass('disabled');
            };

            var enableFilters = function() {
              $(filters, noFilters).prop('disabled', false);
              dateFilterElement.datepicker('option', 'disabled', false);
              resetLink.removeClass('disabled');
            };

            var refreshViewsAndEnableFilters = function() {
              var selected = activeFilters();
              var url = urlFor(section, selected);

              filteredContent.load(url, enableFilters);

              if (anyActiveFilters(selected)) {
                resetLink.show();
              } else {
                resetLink.hide();
              }
            };

            var currentContentLength = function() {
              return $('.listing').length;
            };

            var loadMoreContent = function() {
              var selected = activeFilters();
              var url = urlFor(section, selected);
              var offset = currentContentLength();

              isLoadingContent = true;

              $.get(
                url + '&offset=' + offset,
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

            var resetDateFilter = function() {
              dateFilter = '';
              dateFilterElement.datepicker('setDate');
            };

            // -----------
            // Date picker
            // -----------

            dateFilterElement.datepicker({
              dateFormat: 'yy-mm-dd',
              onSelect: function() {
                dateFilter = this.value;
                refreshViewsAndEnableFilters();
              }
            });

            // ------
            // Events
            // ------

            toggleFilters.click(function() {
              var toggle = $(this);

              toggle.toggleClass('active').siblings('ul, .date-picker').slideToggle();
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
                filters.prop('checked', false);
                noFilters.prop('checked', true);
                resetDateFilter();
                refreshViewsAndEnableFilters();
              }

              e.preventDefault();
            });

            // append current filters to blog and notice detail links
            if (section == 'blog' || section == 'notices') {
              filteredContent.on('click', 'a.detail', function(e) {
                var selected = activeFilters();

                if (anyActiveFilters(selected)) {
                  document.location = this.href + '?' + activeFilterUrlParams(selected);
                }
              });
            }

            $(window).scroll(function() {
              var currentPosition = $(window).scrollTop() / ($(document).height() - $(window).height());
              var offset;

              if (isLoadingContent || endOfContent) return;

              if (currentPosition > scrollPositionThreshold) {
                loadMoreContent();
              }
            });

            // Initialize filters on page load
            if (config.locations.length || config.topics.length || config.date.length) {
                if (config.locations.length) {
                  initializeWithFilters('locations');
                }

                if (config.topics.length) {
                  initializeWithFilters('topics');
                }

                if (config.date.length) {
                  initializeWithFilters('date');
                }

                refreshViewsAndEnableFilters();
            }
        });
    };

    $.fn.filters.defaults = {
        locations: '',
        topics: '',
        date: '',
    };
}(jQuery));
