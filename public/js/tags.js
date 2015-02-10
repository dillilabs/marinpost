(function($) {
    $.fn.tags  = function() {
        return this.each(function() {
            var type = this.id.split('-')[1];
            var tagGroupId = type == 'topic' ? 2 : 1;
            var findTagInput = $(this);
            var form = findTagInput.closest('form');
            var addNewTagLink = $('a#add-' + type + '-tag');
            var tags = $('#' + type + '-tags');

            var tagInput = function(value, label) {
                return '<label>' + '<input type="checkbox" name="fields[' + type + 'Tags][]" value="' + value + '" checked="checked"/> ' + label + '</label>';
            };

            var tagAlreadySelected = function(value) {
                var values = tags.find('input').map(function(i, e) { return e.value; } );
                return $.inArray(value, values) == 0;
            };

            var addTagToForm = function(value) {
                var label = findTagInput.val();
        
                if (value > 0) {
                    tags.append(tagInput(value, label));
                    findTagInput.val('');
                    addNewTagLink.hide();
                } else {
                    alert('Unable to create "' + label + '"');
                }
            };
        
            var createTagOnServer = function() {
                var data = {
                    groupId: tagGroupId,
                    title: findTagInput.val()
                };
        
                $.post(
                    '/actions/tags/createTag',
                    data,
                    function(data) {
                        addTagToForm(data.success ? data.id : 0);
                    });
            };
        
            findTagInput.keypress(function(e) {
                if (e.which == 13) {
                    e.preventDefault();
                }
            });
        
            findTagInput.autocomplete({
                minlength: 2,
                search: function(event, ui) {
                    var term = findTagInput.val();
                    term = term.replace(/^\s+/, '');
                    findTagInput.val(term);
                    return term.length > 0;
                },
                source: function(req, resp) {
                    var term = findTagInput.val();
        
                    $.getJSON(
                        '/' + type + 's/?term=' + term,
                        function(data) {
                            resp(data);
                            if (data.length == 0) {
                                addNewTagLink.show();
                            } else {
                                addNewTagLink.hide();
                            }
                        }
                    );
                },
                focus: function(event, ui) {
                    findTagInput.val(ui.item.label);
                    return false;
                },
                select: function(event, ui) {
                    var id;
        
                    if (! tagAlreadySelected(ui.item.value)) {
                        tags.append(tagInput(ui.item.value, ui.item.label));
                    }
        
                    this.value = '';
                    return false;
                },
                close: function(event, ui) {
                    if (findTagInput.val().length > 0) {
                        addNewTagLink.show();
                    }
                }
            });
        
            addNewTagLink.click(function(e) {
                e.preventDefault();
                createTagOnServer();
            });
        });
    };
}(jQuery));
