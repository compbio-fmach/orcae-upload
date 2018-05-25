/**
 * @function cretaeNewUploader
 * Creates new uploader where:
 *  name is not set (gets set when server responses)
 *  title is the last section uploader's title + 1 (e.g. if [last title] = genome2, then [new title] = genome3)
 *  file is the file currently set
 */
function createNewUploader() {
  // Defines data used to create new uploader and file which will be sent
  var file = $('#uploader-files').prop('files') ? $('#uploader-files').prop('files')[0] : undefined;
  var data = {};
  // Defines type
  data.type = $('#uploader-type').val();

  // Defines section where uploader will be appended
  var $section = undefined;
  if(data.type == 'genome') {
    // Defines section as genome section
    $section = $('#genome');
  }
  else if(data.type == 'annot') {
    // Defines section as annot section
    $section = $('#annot');
  }

  // VALIDATION
  // Removes error alerts from upoloader
  $('#uploader').siblings('.alert').remove();
  // Checks if file is set: if not, creates new alert
  if(!file) {
    $('#uploader').after($(
      '<div class=\'alert alert-danger\' role=\'alert\'>' +
        '<strong>Error! </strong>' +
        'Select the file to upload!' +
      '</div>'
    ));
    return;
  }
  // Checks selected type
  else if(!data.type.match(/^(genome|annot)$/)) {
    $('#uploader').after($(
      '<div class=\'alert alert-danger\' role=\'alert\'>' +
        '<strong>Error! </strong>' +
        'Select upload type!' +
      '</div>'
    ));
    return;
  }

  // Retrieves last uploader in section
  var $last = $section.find('.file-uploader').last();
  // Case last is not empty
  if($last.length > 0) {
    data.sort = $last.attr('id');
    // Replaces last digits with same digits + 1
    data.sort = parseInt(data.sort.split('-')[1]) + 1;
  } else {
    data.sort = 0;
  }

  // Sets other default values
  data.source = file.name;
  data.size = file.size;

  // Defines section where to append new uploader
  var uploader = new GenomeFileUploader(data);
  // Adds selectes files
  uploader.getElementFile().fileupload('add', {files: [file]});
  // Appends created row to section
  uploader.getElement().appendTo($section);
  // Starts file upload automatically
  uploader.start();

  return uploader.getElement();
}

// Creates a GenomeFileUploader istance
/**
 * @function GenomeFileUploader constructs a new GenomeFileUploader objects
 */
function GenomeFileUploader(data) {

  // Defines default data
  this.defaults = {
    stored_as: '',
    type: '',
    sort: 0,
    source: 'Choose file...',
    size: 0,
    file: undefined
  };

  // Creates file uploader dom element
  this.createElement = function() {
    // Creates element
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
    var data = this.data;
    var $element = this.getElement();

    // Updates id if it is different
    var id = data.type + '-' + data.sort;
    if($element.attr('id') != id) {
      $element.attr('id', id);
    }

    // Updates progress bar
    var current = typeof data.file != 'undefined' ? data.file.size : 0;
    var total = typeof data.size != 'undefined' ? data.size : 0;
    this.updateElementProgress(current, total);

    // Updates source
    var $source = $element.find('[name=\'files[]\']').next('label');
    if(this.source != $source.text()) {
      if(this.file) {
        this.source = this.file.name;
      }
      else {
        this.source = this.defaults.source;
      }
      // Updates source
      $source.text(this.source);
    }
  }

  // Updates this uploader's progress bar
  this.updateElementProgress = function(current, total) {
    var $element = this.getElement();
    var progress = this.getProgress(current, total);
    // console.log(progress);
    $element.find('.progress-bar').css('width', progress + '%');
  }

  // Returns upload progress
  this.getProgress = function(current, total) {
    console.log(current, total);
    // Calculates progress percentage
    return parseInt(current / total * 100, 10);
  }

  // Initializes file uploader dom elements events
  this.initElement = function() {
    var self = this;
    // Defines file input field
    var $file = this.getElementFile();
    // Initializes uploader on input field
    $file.fileupload({
      method: 'POST',
      dataType: 'json',
      // Generates url using data passed by the server
      url: Defaults.apiRoot + 'genome_configs/' + GenomeConfig.id + '/uploads/',
      maxChunkSize: parseInt(Defaults.chunkSize),
      // Handles when a file is added
      add: function (e, data) {
        // Initializes selected data
        self.request = data;
        // Uninitalizes xhr (will be initialized on upload start)
        self.xhr = undefined;
      }
    });

    // Binds every chunk submit
    $file.on('fileuploadchunksend', function (e, data) {
      var formData = data.data;
      formData.append('stored_as', self.data.stored_as);
      formData.append('sort', self.data.sort);
      formData.append('type', self.data.type);
      // DEBUG
      console.log(data);
      data.data = formData;
    });

    // Binds chunk uploaded correctly event
    $file.on('fileuploadchunkdone', function(e, data) {
      // Defines filename from returned file
      var file = data.result.files[0];
      // Updates name for other chunks
      self.data.stored_as = file.name;
      // Updates file
      self.data.file = file;
      // DEBUG
      console.log(file);
    });

    // Binds upload progress
    $file.on('fileuploadprogress', function(e, data){
      // Calculates current progress
      self.updateElementProgress(data.loaded, data.total);
    });

    // Binds click on start upload button
    self.$element.on('click', '.upload-start', function() {
      // Starts upload of given data
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
      ajax.done(function(){
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
  }

  // Start upload method
  this.start = function() {
    var self = this;
    // Checks if data and file have been set
    if(self.data && self.data.stored_as) {
      // Retrieves curren file info from server
      $.getJSON(Defaults.apiRoot + 'genome_configs/' + GenomeConfig.id + '/uploads/', {stored_as: this.data.stored_as}, function(result) {
        // File is not the file itself, but is the genome upload istance which contains file info
        var retrieved = result.file;
        var file = retrieved.file;

        // Updates file db reference
        self.data.file = file;

        // Updates request
        self.request.uploadedBytes = file && file.size;
        // Makes upload request
        self.xhr = self.request.submit();
      });
    } else {
      // Makes upload request to server
      self.xhr = self.request.submit();
    }
  }

  // Abort upload method
  this.stop = function() {
    if(this.xhr) {
      // console.log('XHR: ', this.xhr);
      this.xhr.abort();
    }
  }

  // Delete uploaded file method
  this.delete = function() {
    var self = this;
    // Stops current upload
    this.stop();
    // Sends deletion request
    return $.ajax({
      // Creates url to /orcae-upload/API/genome_configs/:id/uploads/:title/
      url: Defaults.apiRoot + 'genome_configs/' + GenomeConfig.id + '/uploads/?stored_as=' + this.data.stored_as,
      method: 'DELETE'
    });
  }

  // CONSTRUCTOR
  // Initializes attributes
  this.data = Object.assign(this.defaults, data);
  // Creates a new file uploader dom element
  this.createElement();
  // Initializes created element
  this.initElement();
  // Updates element with actual values
  this.updateElement();
}

// Page initialization
$(function(){
  // Change event of uploader file selector
  $('#uploader').on('change', '#uploader-files', function() {
    var fileName = ($(this).prop('files').length > 0) ? $(this).prop('files')[0].name : 'Choose file...';
    $(this).next('label').text(fileName);
  });
  // Click event on upload button
  $('#uploader').on('click', '#uploader-upload', function(){
    var $uploader = createNewUploader();
    // Deletes file name from file upload input field
    $('#uploader #uploader-files').next('label').text('Choose file...');
  });
  // Retrieves already uploaded files
  $.ajax({
    method: 'GET',
    dataType: 'json',
    url: Defaults.apiRoot + 'genome_configs/' + GenomeConfig.id + '/uploads/'
  }).done(function(data){
    // Sections
    var $genomes = new Array();
    var $annots = new Array();
    var $others = new Array();
    // Loops through every data returned from the server
    data.files.forEach(function(e, i){
      // Defines uploader object and his dom reference
      var uploader = new GenomeFileUploader(e);
      var $uploader = uploader.getElement();

      // Checks if matches a genome name
      if (e.type.match(/genome/)) {
        $('#genome.section .alert').hide();
        $genomes.push($uploader);
      }
      // Checks if matches an annotation name
      else if (e.type.match(/annot/)) {
        $('#annot.section .alert').hide();
        $annots.push($uploader);
      }
      // Checks if matches an 'others' name
      else if (e.type.match(/^other(\d+)$/)) {}

      // Updates uploader to match actual status
      uploader.updateElement();
    });

    // Appends rows
    $('#genome.section').append($genomes);
    $('#annot.section').append($annots);
    $('#others.section').append($others);

    // Shows container
    $('.main.container').fadeIn('normal');
  });

});
