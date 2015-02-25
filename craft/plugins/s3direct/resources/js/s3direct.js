(function($) {
  var console = (window.console = window.console || {});
  if (!console['log']) {
    console['log'] = function() {};
  }

  $.fn.s3direct  = function(options) {
    var config = $.extend( {}, $.fn.s3direct.defaults, options );
    var uploadProgressBar = $(config.uploadProgressBarSelector);
    var updateIndexIndicator = $(config.updateIndexIndicatorSelector);

    $.each(config, function(k, v) {
      if (config[k].length == 0) {
        throw(k + ' is required.');
      }
    });

    return this.each(function() {
      var keyFor = function(fileName) {
        var subfolder = config.subfolder.length > 0 ? config.subfolder + '/' : '';

        return subfolder + config.currentUserId + '/' + fileName;
      };

      var fileUploadSubmit = function(e, data) {
        var file = data.files[0];

        data.formData = {
          key: keyFor(file.name),
           acl: 'public-read',
           policy: config.policy,
           signature: config.signature,
           AWSAccessKeyId: config.accessKey,
           'Content-Type': file.type,
        };

        if (config.debug) console.log('fileUploadSubmit()', data);
      };

      var addFileToSelectOptions = function(input, selectedFileId, file) {
        var selected = file.id == selectedFileId ? 'selected' : '';

        input.append('<option value="'+file.id+'" '+selected+'>'+file.filename+'</option>');
      };

      var updateFormInputs = function(files) {
        if (config.debug) console.log('updateFormInputs()', files);

        $(config.selectFilesSelector).each(function() {
          var input = $(this);
          var selectedFileId = input.children(':selected').val();

          input.children().remove().end().append('<option value=""></option>');

          $.each(files, function(index, file) {
            addFileToSelectOptions(input, selectedFileId, file);
          });
        });
      };

      var updateAssetsIndex = function(filenames) {
        var data = { 'sourceid': config.assetsSourceId };

        $.each(filenames, function(i, e) {
          var key = 'filenames['+i+']';
          data[key] = e;
        });

        if (config.debug) console.log('updateAssetsIndex()', filenames, data);

        updateIndexIndicator.show();

        $.ajax({
          url: config.updateAssetsIndexUrl,
          data: data,
          dataType: 'json',
          type: 'POST'
        }).done(function(data) {
          if (config.debug) console.log('updateAssetsIndex() done', data);
          updateFormInputs(data.files);
        }).fail(function(jqXHR, textStatus, errorThrown) {
          if (config.debug) console.log('updateAssetsIndex() fail', textStatus, errorThrown);
        }).always(function() {
          updateIndexIndicator.hide();
        });
      };

      var filenames = [];
      var uploadError = false;

      var fileUpload = $(this).fileupload({
        acceptFileTypes: config.acceptFileTypes,
        url: 'https://'+config.bucket+'.s3.amazonaws.com',
        dataType: 'json',
        start: function(e) {
          filenames = [];
          if (config.debug) console.log('fileupload() start', filenames);
        },
        progressall: function (e, data) {
          var progress = parseInt(data.loaded / data.total * 100, 10);
          uploadProgressBar.css('width', progress + '%');
        },
        done: function(e, data) {
          $.each(data.files, function(i, e) {
            filenames.push(e.name);
          });
          if (config.debug) console.log('fileupload() done', data, filenames);
        },
        fail: function(e, data) {
          uploadError = true;
          alert('Error uploading to ' + data.url);
          if (config.debug) console.log('fileupload() fail', data);
        },
        stop: function(e) {
          if (config.debug) console.log('fileupload() stop', filenames);
          if (!uploadError) {
            updateAssetsIndex(filenames);
          }
        }
      });
      
      fileUpload.bind('fileuploadsubmit', fileUploadSubmit);
      fileUpload.prop('disabled', !$.support.fileInput);
      fileUpload.parent().addClass( $.support.fileInput ? undefined : 'disabled');
    });
  };

  $.fn.s3direct.defaults = {
    policy: '',
    signature: '',
    accessKey: '',
    bucket: '',
    subfolder: '',
    currentUserId: '',
    assetsSourceId: '',
    updateAssetsIndexUrl: '/actions/s3Direct/updateAssetsIndex',
    uploadProgressBarSelector: '.progress-bar.s3direct',
    updateIndexIndicatorSelector: 'img.s3direct',
    selectFilesSelector: 'select.s3direct',
    acceptFileTypes: undefined,
    debug: false,
  };

}(jQuery));
