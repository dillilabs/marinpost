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
      var files = { names: [], titles: [], credits: [] };

      var kebabNameFor = function(originalFileName) {
        var fileParts = originalFileName.split('.');
        var fileExt = fileParts.length > 1 ? fileParts.pop() : false;
        var fileName = fileParts.join();

        fileName = fileName.replace(/\W+/g, '-');

        return fileExt ? fileName+'.'+fileExt : fileName;
      };

      var s3KeyFor = function(fileName) {
        var subfolder = config.subfolder.length > 0 ? config.subfolder + '/' : '';

        return subfolder + config.currentUserId + '/' + fileName;
      };

      var promptForString = function(strings, message) {
        var string = prompt(message);

        if (string) {
          string = string.trim();

          if (string.length) {
            strings.push(string);

            return true;
          } else {
            // recurse
            return promptForString();
          }
        }

        return false;
      };

      var fileUploadSubmit = function(e, data) {
        var file = data.files[0];

        files.names = [];
        files.names.push(kebabNameFor(file.name));

        files.credits = [];
        if (config.requireFileCredit) {
          if (!promptForString(files.credits, 'Image Credit')) {
            return false;
          }
        }

        files.titles = [];
        if (config.requireFileTitle) {
          if (!promptForString(files.titles, 'Document Title')) {
            return false;
          }
        } else {
          // Preserve original file name
          files.titles.push(file.name);
        }

        data.formData = {
          key: s3KeyFor(files.names[0]),
          acl: 'public-read',
          policy: config.policy,
          signature: config.signature,
          AWSAccessKeyId: config.accessKey,
          'Content-Type': file.type,
        };

        if (config.debug) console.log('fileUploadSubmit()', data);
      };

      var updateAssetsIndex = function(originalFileNames) {
        var data = { 'sourceId': config.assetsSourceId, 'imageTransform': config.imageTransform };

        $.each(originalFileNames, function(i, name) {
          var key = 'files['+i+']';

          data[key] = {
            name: files.names[0],
            title: files.titles[0],
            credit: files.credits[0]
          };
        });

        if (config.debug) console.log('updateAssetsIndex() before', originalFileNames, files, data);

        updateIndexIndicator.show();

        $.ajax({
          url: config.updateAssetsIndexUrl,
          data: data,
          dataType: 'json',
          type: 'POST'
        }).done(function(data) {
          if (config.debug) console.log('updateAssetsIndex() done', data);

          if (typeof config.onUpdateAssetsIndex == 'function') {
            if (config.debug) console.log('onUpdateAssetsIndex() begin', data.files);

            config.onUpdateAssetsIndex(data.files);

            if (config.debug) console.log('onUpdateAssetsIndex() end');
          }
        }).fail(function(jqXHR, textStatus, errorThrown) {
          if (config.debug) console.log('updateAssetsIndex() fail', textStatus, errorThrown);

        }).always(function() {
          updateIndexIndicator.hide();

        });
      };

      var originalFileNames = [];
      var uploadError = false;

      var fileUpload = $(this).fileupload({
        acceptFileTypes: config.acceptFileTypes,
        url: 'https://'+config.bucket+'.s3.amazonaws.com',
        dataType: 'json',
        singleFileUploads: true,
        start: function(e) {
          originalFileNames = [];

          if (config.debug) console.log('fileupload() start', originalFileNames);
        },
        progressall: function (e, data) {
          var progress = parseInt(data.loaded / data.total * 100, 10);

          uploadProgressBar.css('width', progress + '%');
        },
        done: function(e, data) {
          $.each(data.files, function(i, e) {
            originalFileNames.push(e.name);
          });

          setTimeout(function() {
            uploadProgressBar.css('width', 0);
          }, 1000);

          if (config.debug) console.log('fileupload() done', data, originalFileNames);
        },
        fail: function(e, data) {
          uploadError = true;
          alert('Error uploading to ' + data.url);

          if (config.debug) console.log('fileupload() fail', data);
        },
        stop: function(e) {
          if (config.debug) console.log('fileupload() stop', originalFileNames);

          if (!uploadError) {
            updateAssetsIndex(originalFileNames);
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
    acceptFileTypes: undefined,
    imageTransform: 'list',
    onUpdateAssetsIndex: false,
    requireFileCredit: false,
    requireFileTitle: false,
    debug: false,
  };

}(jQuery));
