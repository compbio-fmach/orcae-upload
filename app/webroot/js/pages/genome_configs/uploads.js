/**
 * @function GenomeFileUploader constructs a new GenomeFileUploader objects
 */
function GenomeFileUploader(options) {
  // Defines a reference to current object
  var self = this;
  // Defines default data
  this.defaults = {
    // Default upload istance
    upload: {
      stored_as: '',
      type: '',
      sort: 0,
      source: '',
      size_current: 0,
      size_total: 0,
      file: undefined
    }
  };

  // Creates file uploader dom element
  this.createElement = function() {
    // Creates  DOM element
    this.$element = $(
     '<div class=\'file-uploader\' id=\'\'>' +
       // First row: contains file input, upload, stop and delete buttons
       '<div class=\'row\'>' +
         '<div class=\'col-6\'>' +
           '<div class=\'custom-file\'>' +
             '<input type=\'file\' name=\'files[]\' class=\'custom-file-input form-control-sm upload-files\' multipart>' +
             '<label class=\'custom-file-label\'></label>' +
           '</div>' +
         '</div>' +
         '<div class=\'col-2\'>' +
           '<button type=\'button\' class=\'btn btn-primary btn-block upload-start\'>Upload</button>' +
         '</div>' +
         '<div class=\'col-2\'>' +
           '<button type=\'button\' class=\'btn btn-outline-danger btn-block upload-stop\'>Stop</button>' +
         '</div>' +
         '<div class=\'col-2\'>' +
           '<button type=\'button\' class=\'btn btn-outline-danger btn-block upload-delete\'>Delete</button>' +
         '</div>' +
       '</div>' +
       // Second row: contains progress bar
       '<div class=\'row\'>' +
         '<div class=\'col-12\'>' +
           '<div class=\'progress\'>' +
             '<div class=\'progress-bar\' role=\'progressbar\' style=\'width:0%\' aria-valuemin=\'0\' aria-valuemax=\'100\'></div>' +
           '</div>' +
         '</div>' +
       '</div>' +
     '</div>'
    );
    // Adds element to data (allows to retrieve class from dom element)
    this.$element.data('GenomeFileUploader', this);
    // Updates values
    this.updateElement();
  }

  // Returns element created
  this.getElement = function() {
    return this.$element;
  }

  // Returns element file input
  this.getElementFile = function() {
    return this.getElement().find('input[type=\'file\']');
  }

  // Updates element with data
  this.updateElement = function() {
    var data = this.options.upload;
    var $element = this.getElement();
    // Retrieves file input
    var $file = this.getElementFile();
    // Updates id
    var id = data.type + '-' + data.sort;
    $element.attr('id', id);
    // Case file is set (file is actually a file)
    if(data.file) {
      // Appends file to file prop
      //$file.prop('files', $file.prop('files').push(data.file));
      // Defines source as file name
      data.source = data.file.name;
    }
    // Sets file input label
    $file.next('label').text(data.source);
    // Updates progress bar
    this.updateElementProgress();
    // Updates file types accepted
    var accept = '';
    switch(data.type) {
      case 'genome':
        accept = '.fasta';
        break;
      case 'annot':
        accept = '.gff3';
        break;
    }
    // Updates dom element
    $file.attr('accept', accept);
  }

  // Updates this uploader's progress bar
  this.updateElementProgress = function() {
    var $element = this.getElement();
    $element.find('.progress-bar').css('width', this.getProgress() + '%');
  }

  // Returns upload progress
  this.getProgress = function() {
    var upload = this.options.upload;
    // Calculates progress percentage
    return parseInt(upload.size_current / upload.size_total * 100, 10);
  }

  // Initializes file uploader
  this.initFileUploader = function() {
    // Defines a reference to itself due to scoping issues
    var self = this;
    // Defines file input field
    var $file = this.getElementFile();

    // Initializes uploader on input field
    $file.fileupload({
      method: 'POST',
      dataType: 'json',
      // Generates url using data passed by the server
      url: Defaults.apiRoot + 'genome_configs/' + GenomeConfig.id + '/uploads/',
      // Retrieves max chink size from default values given by server
      maxChunkSize: parseInt(Defaults.chunkSize),
      // Handles when a file is added
      add: function (e, data) {
        // Saves request made to server
        self.request = data;
        // Initializes xhr ongoing to server
        self.xhr = undefined;
      }
    });

    // Binds event on every chunk submitted
    $file.on('fileuploadchunksend', function (e, data) {
      var formData = data.data;
      var upload = self.options.upload;
      // Appends file data to formData sent to server
      formData.append('stored_as', upload.stored_as);
      formData.append('sort', upload.sort);
      formData.append('type', upload.type);
      data.data = formData;
    });

    // Binds chunk uploaded correctly event
    $file.on('fileuploadchunkdone', function(e, data) {
      var upload = self.options.upload;
      // Defines filename from returned file
      var file = data.result.shift();
      // Checks errors
      if(!file || file.error) {
        // Stops current upload
        self.stop();
      }
      // Gets file 'stored_as' value
      upload.stored_as = file.name;
    });

    // Binds upload progress
    $file.on('fileuploadprogress', function(e, data){
      var upload = self.options.upload;
      // Updates progress using local data
      upload.size_current = data.loaded;
      upload.size_total = data.total;
      self.updateElementProgress();
    });

    // Binds click on start upload button
    self.$element.on('click', '.upload-start', function() {
      // Reference to file input
      var $file = self.getElementFile();
      // Deletes error class, if any
      $file.removeClass('error');
      // Deletes previous error messages
      $file.siblings('.error-message').remove();

      // Defines an error message
      var $error = $('<small class="error-message"></small>');

      // Defines upload instance
      var upload = self.options.upload;

      // Case file is not set: shows error message
      if(!upload.file) {
        $file
          .addClass('error')
          .after($error.text('No file selected'));
        return;
      }
      // Case file has not the same name
      else if(upload.source != upload.file.name) {
        $file
          .addClass('error')
          .after($error.text('Selected file name and source does not match'));
        return;
      }

      // Adds file
      $file.fileupload('add', {files: new Array(self.options.upload.file)});
      // Starts upload of given file
      self.start();
    });

    // Binds click on stop upload button
    self.$element.on('click', '.upload-stop', function() {
      self.stop();
    });

    // Binds click on delete upload button
    self.$element.on('click', '.upload-delete', function() {
      // Retrieves ajax deletion request
      var ajax = self.delete();
      // Handles server response
      ajax.done(function(data){
        console.log(data);
        var $element = self.$element;
        var $section = self.$element.closest('.section');
        // Removes the selected element
        $element.remove();
        // Shows alert, if section is empty
        if($section.find('.file-uploader').length == 0) {
          $section.find('.alert').show();
        }
      });
    });

    // Handles file selected
    // console.log($file);
    $file.on('change', function(e) {
      var $file = self.getElementFile();
      // Deletes previous error message and style
      $file
        .removeClass('error')
        .siblings('.error-message').remove();

      // If it is uploading, stops execution
      self.stop();

      // Defines upload
      var upload = self.options.upload;
      // Defines selected file
      var selected = Array.from($(this).prop('files')).shift();
      // Defines a label
      var label = !selected ? upload.source : selected.name;
      // Sets new file
      self.options.upload.file = selected;
      // Updates labal dom element
      $file.next('label').text(label);
    });
  }

  // Start upload method
  this.start = function() {
    var self = this;
    // Defines reference to upload istance
    var upload = self.options.upload;

    // Defines if it is a new upload
    var isNew = !upload.stored_as;

    // Case request is not set: cannot upload
    if(!self.request) {
      return false;
    }

    // Case is not a new upload
    if(!isNew) {
      // Retrieves curren file info from server
      $.getJSON(
        // Url
        Defaults.apiRoot + 'genome_configs/' + GenomeConfig.id + '/uploads/',
        // Params into GET query
        {stored_as: this.options.upload.stored_as},
        // Response
        function(data) {
          // Checks if object and not an array
          var upload = (data instanceof Object) ? data : undefined;
          // Exits if upload is not an object
          if(!upload) return;

          // Retrieves file form genome upload instance
          var file = upload.file;
          // Updates request
          self.request.uploadedBytes = file && file.size;
          // Makes upload request
          self.xhr = self.request.submit();
      });
    }
    // Case it is a new upload
    else {
      // Makes upload request to server
      self.xhr = self.request.submit();
    }
  }

  // Abort upload method
  this.stop = function() {
    if(this.xhr) {
      // console.log('XHR: ', this.xhr);
      this.xhr.abort();
      this.xhr = null;
    }
  }

  // Delete uploaded file method
  this.delete = function() {
    // Stops current upload
    this.stop();
    // Sends deletion request
    return $.ajax({
      // Creates url to /orcae-upload/API/genome_configs/:id/uploads/:title/
      url: Defaults.apiRoot + 'genome_configs/' + GenomeConfig.id + '/uploads/?stored_as=' + this.options.upload.stored_as,
      method: 'DELETE'
    });
  }

  // CONSTRUCTOR
  // Initializes attributes
  this.options = Object.assign(this.defaults, options);
  // Creates a new file uploader dom element
  this.createElement();
  // Initializes uploader
  this.initFileUploader();
}

/**
 * @function cretaeNewUploader
 * Creates new uploader where:
 *  name is not set (gets set when server responses)
 *  title is the last section uploader's title + 1 (e.g. if [last title] = genome2, then [new title] = genome3)
 *  file is the file currently set
 */
function createNewUploader() {
  // Defines reference to upload form
  var $uploader = $('#uploader');
  // Removes previous error messages
  $uploader.find('.alert').remove();

  // Defines files to be uploaded
  var files = $('#uploader-files').prop('files') ? Array.from($('#uploader-files').prop('files')) : new Array();
  // Retrieves only valid files
  files = files.filter(function(file) {
    // Checks file name
    return /^(.*)(\.)(fasta|gff3)$/.test(file.name);
  });

  // Catches no file selected error
  if(files.length == 0) {
    // Shows error message
    $uploader.append($(
      '<div class=\'alert alert-danger\' role=\'alert\'>' +
        '<strong>Error: </strong>' +
        'No valid file has been selected' +
      '</div>'
    ));
    // Exits
    return false;
  }

  // Loops through every file for creating uploader istance
  files.forEach(function(file, i) {
    // Defines new upload istance
    var upload = {
      file: file,
      size_total: file.size,
      size_current: 0,
      sort: null,
      type: null
    }
    // Defines section where to append new file uploader
    var $section = $('.section');
    // Defines file type
    switch(file.name.replace(/(.*)(\.)/, '')) {
      case 'fasta':
        upload.type = 'genome';
        $section = $section.filter('#genome');
        break;
      case 'gff3':
        upload.type = 'annot';
        $section = $section.filter('#annot');
        break;
      default:
        upload.type = 'other';
        $section = $section.filter('#others');
        break;
    }
    // Retrieves uploaders already appended into section
    var $uploaders = $section.find('.file-uploader');
    // Case section is empty: sort is 0
    if($uploaders.length == 0) {
      upload.sort = 0;
    }
    // Case section not empty: retrieves last sort + 1
    else {
      upload.sort = $uploaders.last().attr('id');
      upload.sort = parseInt(upload.sort.replace(/(.*)(-)/, '')) + 1;
    }

    // Creates uploader
    var uploader = new GenomeFileUploader({
      // Sets genome upload instance
      upload: upload
    });

    // Retrieves uploader DOM element
    var $newUploader = uploader.getElement();
    // Retrieves file input
    var $newUploaderFile = uploader.getElementFile();
    // retrieves upload instance
    var upload = uploader.options.upload;

    // Deletes standard event on first upload sent
    $newUploaderFile.off('fileuploadchunkdone');
    // Creates new event on first chunk sent
    $newUploaderFile.on('fileuploadchunkdone', function(e, data, next) {
      // Retrieves first file returned
      var file = data.result.shift();
      // Case first chunk
      if(!upload.stored_as) {
        // Checks errors
        if(!file || file.error) {
          // Stops uploading
          uploader.stop();
          // Shows error message
          $uploader
            .append($(
              '<div class="alert alert-danger">' +
                '<strong>Error! </strong> Upload unavaiable while updating Orcae woth current genome' +
              '</div>'
            ));
          // Exits
          return;
        }
      }

      // Sets current genome upload instance file name
      upload.stored_as = file.name;
    });

    // Hides section's alert message
    $section.find('.alert').hide();
    // Appends uploader to his section
    $section.append($newUploader);
    // Starts upload
    $newUploader.find('.upload-start').click();
  });
}

/**
 * @function checkUpdateStatus
 * Checks status of last update
 * Makes polling to updates API
 */
function checkUpdateStatus(after = {}) {
  // Creates new genome update checker
  var updater = new GenomeUpdates({
    // Creates genome configuartion object
    genomeConfig: {id: GenomeConfig.id},
    // Defines polling interval
    pollingInterval: 3000
  });

  // Retrieves button
  var $btn = $('#update-button');
  // Defines functions which will be executed when result has been found
  var after = Object.assign({
    // Case no update has been executed yet
    onUpdateEmpty: function() {
      // Sets button status
      $btn
        .text('Update Orcae')
        .prop('disabled', false);
      // Removes class from conatiner
      $('.main.container').removeClass('updating');
    },
    // Case last update attempt failed
    onUpdateFailure: function(update) {
      // Calls onUpdateEmpty because has the same behavior
      this.onUpdateEmpty();
    },
    // Case last update was successfull
    onUpdateSuccess: function(update) {
      // Sets button status to disabled
      $btn
        .text('Orcae has already been updated with the current genome')
        .prop('disabled', true);
      // Adds class to conatiner
      $('.main.container')
        .removeClass('updating')
        .addClass('updated');
    },
    // Case still updating
    onUpdateUpdating: function(update) {
      // Sets button status to disabled, while updating
      $btn
        .text('Orcae is being updated with the current genome')
        .prop('disabled', true);
      // Adds class to container
      $('.main.container')
        .addClass('updating');
      return true;
    },
    // Case AJAX request failed: enables button
    onFailure: function() {
      // Same behavior of onUpdateEmpty
      this.onUpdateEmpty();
      // Shows an error message
      $('#alerts')
        .empty()
        .append($(
          '<div class="alert alert-danger">' +
            '<strong>Error: </strong>' +
            'Could not check updates' +
          '</div>'
        ));
    }
  }, after);

  // Starts polling
  updater.polling(after);
}

// Page initialization
$(function(){

  // Change event of uploader file selector
  $('#uploader')
    .on('change', '#uploader-files', function() {
      // Retrieves files selected
      var files = Array.from($(this).prop('files'));
      // Loops thorugh files to update the label
      files.forEach(function(file, index){
        // Substitutes files with file names
        files[index] = file.name;
      });
      // Defines a label
      var label = !files.length ? 'Choose files...' : files.join(', ');
      // Sets label into input field
      $(this).next('label').text(label);
    })
    // Click event on upload button
    .on('click', '#uploader-upload', function(){
      // Creates uploaders
      createNewUploader();
      // Deletes file name from file upload input field
      $('#uploader #uploader-files').next('label').text('Choose files...');
    });

  // Handles update
  $('#update-button')
    .on('click', function(e) {
      $.ajax({
        method: 'POST',
        dataType: 'json',
        url: Defaults.apiRoot + 'genome_configs/' + GenomeConfig.id + '/updates/',
      })
      // Handles update started
      .done(function(data){
        // Starts polling
        checkUpdateStatus();
      })
      // Handles update failure
      .fail(function(data){
        // Defines an array of html error messages
        var $errors = new Array();

        // Case no response json
        if(!data.responseJSON) {
          $errors.push($(
            '<div class="alert alert-danger" role="alert">' +
              '<strong>Error: </strong>' +
              'Could not update Orcae' +
            '</div>'
          ));
        }

        var json = data.responseJSON;
        // Case validation in update initialization
        if(json.validation && json.validation.init) {
          json.validation.init.forEach(function(error) {
            $errors.push($(
              '<div class="alert alert-danger" role="alert">' +
                '<strong>Error! </strong>' +
                error +
              '</div>'
            ));
          });
        }

        // Appends errors to container
        $('#alerts')
          .empty()
          .append($errors);

        // Enables update button
        $('#update-button')
          .text('Update')
          .prop('disabled', false);
      });
    });

  // Retrieves already uploaded files
  $.ajax({
    method: 'GET',
    // dataType: 'json',
    url: Defaults.apiRoot + 'genome_configs/' + GenomeConfig.id + '/uploads/'
  })
  .done(function(files){
    // Sections
    var $genomes = new Array();
    var $annots = new Array();
    var $others = new Array();
    // Loops through every data returned from the server
    files.forEach(function(upload, i){
      // Defines uploader object
      var uploader = new GenomeFileUploader({
        upload: Object.assign(upload, {
          size_total: upload.size,
          size_current: upload.file ? upload.file.size : 0,
          file: undefined
        })
      });
      // Defines uploader dom element
      var $uploader = uploader.getElement();
      // Checks if matches a genome name
      switch(upload.type) {
        case 'genome':
          $('#genome.section .alert').hide();
          $genomes.push($uploader);
          break;
        case 'annot':
          $('#annot.section .alert').hide();
          $annots.push($uploader);
          break;
        default: break;
      }
    });

    // Appends rows
    $('#genome.section').append($genomes);
    $('#annot.section').append($annots);
    $('#others.section').append($others);

    // Shows container
    $('.main.container').fadeIn('normal');
  });

  // Retrieves udpate status
  checkUpdateStatus();

});
