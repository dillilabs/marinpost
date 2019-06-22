/**
 * Redactor plugin for supporting 'add image' button via existing s3 image picker
 */

if (!RedactorPlugins) var RedactorPlugins = {};

RedactorPlugins.addimage = function () {
    return {
        init: function () {
            var modal = $('#image-modal-2');
            const thisObj = this;
            modal.on('dialogclose', function (event) {
                var url = $('#selected-img-url').val();
                thisObj.insert.html("<img src='" + url + "'>");
                thisObj.code.sync();
            });

            var button = this.button.addAfter('underline', 'image', 'Add Image');
            this.button.addCallback(button, this.addimage.show);
        },
        show: function () {
            var modal = $('#image-modal-2');
            modal.dialog('open');
        }
    };
};
