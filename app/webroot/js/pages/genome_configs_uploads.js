/**
 * @function createUploader
 * Creates a dom element which represents a file uploader
 * @return created dom element
 */
 function createUploader(name, title, file, source, progress = 0) {
   // Creates wireframe (which will be later modified)
   var $wireframe = $(
    '<div class=\'file-uploader\'>' +
      // First row: contains file input, upload, stop and delete buttons
      '<div class=\'row\'>' +
        '<div class=\'col-6\'>' +
          '<div class=\'custom-file\'>' +
            '<input type=\'file\' name=\'files[]\' class=\'custom-file-input form-control-sm upload-files\' multipart>' +
            '<label class=\'custom-file-label\'>Choose file...</label>' +
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
            '<div class=\'progress-bar\' role=\'progressbar\' style=\'width:' + progress + '%\' aria-valuemin=\'0\' aria-valuemax=\'100\'></div>' +
          '</div>' +
        '</div>' +
      '</div>' +
    '</div>'
   );

   // Adds data to wireframe
   $.data($wireframe.get(0), 'title', title);
   $.data($wireframe.get(0), 'name', name);
   $.data($wireframe.get(0), 'file', file);

   // Adds file name if file is set
   if(file) {
     $wireframe.find('[name=\'files[]\']').next('label').text(file.name);
   }
   // Adds file source if source is set
   // Used when uploader is created
   else if(source) {
     $wireframe.find('[name=\'files[]\']').next('label').text(source);
   }

   // Initializes uploader
   $wireframe.find('[name=\'files[]\']').fileupload({
     method: 'POST',
     dataType: 'json',
     url: Defaults.apiRoot + 'genome_configs/' + GenomeConfig.id + '/uploads/',
     maxChunkSize: 1000000, // 1MB
     add: function (e, data) {
       var xhr = undefined;
       // Binds upload start
       data.context = $wireframe.find('.upload-start').click(function(){
         xhr = data.submit();
       });
       // Binds abort upload
       data.context = $wireframe.find('.upload-stop').click(function(){
         xhr.abort();
       });
       // Binds delete
       data.context = $wireframe.find('.upload-delete').click(function(){
         // Aborts current upload
         if(xhr) xhr.abort();
         // Deletes current upload
       });
     }
   })
   // Executed before sending a chunk
   .on('fileuploadchunksend', function (e, data) {
     // Retrieves FormData which will be sent to the server's API
     var formData = data.data;
     // Appends additional data
     formData.append('name', $.data($wireframe.get(0), 'name'));
     formData.append('title', $.data($wireframe.get(0), 'title'));
   })
   // Executed when a chunk is returned
   .on('fileuploadchunkdone', function (e, data) {
     // Puts token into values sent to server
     var file = data.result.files[0];
     // Sets name if not alerady set (only first loop)
     if(!$.data($wireframe.get(0), 'name')) {
       $.data($wireframe.get(0), 'name', file.name);
     }
   })
   // Updates progress bar
   .on('fileuploadprogress', function(e, data){
     var progress = parseInt(data.loaded / data.total * 100, 10);
     $wireframe.find('.progress-bar').css('width', progress + '%');
   });

   // Adds selected file
   $wireframe.find('[name=\'files[]\']').fileupload('add', {files: new Array(file)});

   return $wireframe;
 }

/**
 * @function createUpload
 * Creates new uploader where:
 *  name is not set (gets set when server responses)
 *  title is the last section uploader's title + 1 (e.g. if [last title] = genome2, then [new title] = genome3)
 *  file is the file currently set
 */
function createNewUploader() {
  // Defines selected file type
  var type = $('#uploader-type').val();
  // Defines selected file (if any)
  var files = ($('#uploader-files').prop('files').length > 0) ? [$('#uploader-files').prop('files')[0]] : [];
  // Defines file name
  var source = (files.length > 0) ? files[0].name : '';
  // Initializes title
  var title = '';

  // Defines section where uploader will be appended
  var $section = undefined;
  if(type == 'genome') {
    // Defines section as genome section
    $section = $('#genome');
    // Defines first possible genome file title
    title = 'genome0';
  }
  else if(type == 'annot') {
    // Defines section as annot section
    $section = $('#annot');
    // Defines first possible annot file type
    title = 'annot0';
  }

  // VALIDATION
  // Removes error alerts from upoloader
  $('#uploader').siblings('.alert').remove();
  // Checks if file is set: if not, creates new alert
  if(!files.length > 0) {
    $('#uploader').after($(
      '<div class=\'alert alert-danger\' role=\'alert\'>' +
        '<strong>Error! </strong>' +
        'Select the file to upload!' +
      '</div>'
    ));
    return;
  }
  // Checks selected type
  else if(!type.match(/^(genome|annot)$/)) {
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
    title = $last.attr('id');
    // Replaces last digits with same digits + 1
    title = title.replace(/[\d]{0,}$/i, parseInt(title.replace(/^[^\d]{0,}/i, '')) + 1);
  }

  // Defines section where to append new uploader
  var uploader = new GenomeFileUploader(title, source, '', [], 0, (files.length > 0) ? files[0].size : 0);
  // Adds selectes files
  uploader.get('files').fileupload('add', {files: files});
  // Appends created row to section
  uploader.get().appendTo($section);
  // Starts file upload automatically
  uploader.start();

  return uploader.get();
}

// Creates a GenomeFileUploader istance
/**
 * @function GenomeFileUploader constructs a new GenomeFileUploader objects
 * @param title is the name of the file in a position. It allows to sort files
 * @param source is the name of the source file from which the file upload grab the data
 * @param name is the actual name of the file on the server, user for file upload (cannot initialize a new file upload)
 * @param files is a list of files. Can match a file input 'files' prop
 * @param uploaded states how many bytes have been uploaded for the current file upload
 * @param size is the size of the file when fully uploaded
 */
function GenomeFileUploader(title = '', source = '', name = '', files = null, uploaded = 0, size = 0) {
  // Initializes dom element associated with this file uploader
  this.$element = undefined;

  // Defines uploader data
  this.data = undefined;

  // Creates file uploader dom element
  this.create = function() {
    this.$element = $(
     '<div class=\'file-uploader\' id=\'' + this.title + '\'>' +
       // First row: contains file input, upload, stop and delete buttons
       '<div class=\'row\'>' +
         '<div class=\'col-6\'>' +
           '<div class=\'custom-file\'>' +
             '<input type=\'file\' name=\'files[]\' class=\'custom-file-input form-control-sm upload-files\' multipart>' +
             '<label class=\'custom-file-label\'>' + (this.source ? this.source : 'Choose file...') + '</label>' +
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
             '<div class=\'progress-bar\' role=\'progressbar\' style=\'width:' + parseInt(this.uploaded / this.size * 100, 10) + '%\' aria-valuemin=\'0\' aria-valuemax=\'100\'></div>' +
           '</div>' +
         '</div>' +
       '</div>' +
     '</div>'
    );
  }

  // Initializes file uploader dom elements events
  this.init = function() {
    var self = this;

    // Defines file input field
    var $file = self.get('files');

    // Initializes uploader on input field
    $file.fileupload({
      method: 'POST',
      dataType: 'json',
      // Generates url using data passed by the server
      url: Defaults.apiRoot + 'genome_configs/' + GenomeConfig.id + '/uploads/',
      maxChunkSize: 1000000, // 1MB
      // Handles when a file is added
      add: function (e, data) {
        // Initializes selected data
        self.data = data;
        // Uninitalizes xhr (will be initialized on upload start)
        self.xhr = undefined;
      }
    });

    // Binds click on start upload button
    self.$element.on('click', '.upload-start', function() {
      // Shows how many bytes have been already uploaded before upload starts
      console.log('Starting: ', {
        'uploaded': self.uploaded
      });
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

    // Binds every chunk submit
    $file.on('fileuploadchunksend', function (e, data) {
      var formData = data.data;
      formData.append('name', self.name);
      formData.append('title', self.title);


      console.log('Sent data: ', data);
    });

    // Binds chunk uploaded correctly event
    $file.on('fileuploadchunkdone', function(e, data) {
      // Defines filename from returned file
      var file = data.result.files[0];
      // Sets name for later chunks
      self.name = file.name;
      // Sets uploaded bytes
      self.uploaded = data.uploadedBytes;
      // Shows data returned by the server when chunk has been uploaded
      console.log('Chunk done: ', data);
    });

    // Binds upload progress
    $file.on('fileuploadprogress', function(e, data){
      // Calculates current progress
      var progress = parseInt(data.loaded / data.total * 100, 10);
      // Sets progress percentage to progress bar
      self.$element.find('.progress-bar').css('width', progress + '%');
    });
  }

  // Start upload method
  this.start = function() {
    var self = this;
    // Checks if data and file have been set
    if(this.data) {
      // Retrieves curren file info from server
      $.getJSON(Defaults.apiRoot + 'genome_configs/' + GenomeConfig.id + '/uploads/', {file: this.name}, function(result) {
        var file = result.file;
        self.data.uploadedBytes = file && file.size;

        console.log('UploadedBytes', result);

        self.xhr = self.data.submit();
      });
    }
  }

  // Abort upload method
  this.stop = function() {
    if(this.xhr) {
      console.log('XHR: ', this.xhr);
      this.xhr.abort();
    }
  }

  // Delete uploaded file method
  this.delete = function() {
    // Stops current upload
    this.stop();
    // Sends deletion request
    return $.ajax({
      // Creates url to /orcae-upload/API/genome_configs/:id/uploads/:title/
      'url': Defaults.apiRoot + 'genome_configs/' + GenomeConfig.id + '/uploads/' + title + '/',
      'method': 'DELETE'
    });
  }

  // Retrieves dom element
  this.get = function (filter = undefined) {
    var $rtn = this.$element;
    // Searcheds using find jquery method
    if(filter == 'files') {
      $rtn = $rtn.find('[name=\'files[]\']');
    }
    // Returns selected object
    return $rtn;
  }

  // Initializes attributes
  this.data = undefined;
  this.source = source;
  this.name = name;
  this.title = title;
  this.files = files;
  this.uploaded = uploaded;
  this.size = size;

  // Creates a new file uploader dom element
  this.create();
  // Initializes it
  this.init();
}

$(function(){

  // Change event of uploader file selector
  $('#uploader').on('change', '#uploader-files', function() {
    var fileName = ($(this).prop('files').length > 0) ? $(this).prop('files')[0].name : 'Choose file...';
    $(this).next('label').text(fileName);
  })

  // Click event on upload button
  $('#uploader').on('click', '#uploader-upload', function(){
    // Defines attributes to pass to
    // Creates uploader
    var $uploader = createNewUploader();
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
      // Defines variables used when a row is created
      var title = e.title;
      var source = e.source;
      var name = (e.uploaded < e.size) ? e.name : '';
      // Defines uploader object and his dom reference
      var uploader = new GenomeFileUploader(title, source, name, [], e.uploaded, e.size);
      var $uploader = uploader.get();

      // Checks if matches a genome name
      if (title.match(/^genome(\d+)$/)) {
        $('#genome.section .alert').hide();
        $genomes.push($uploader);
      }
      // Checks if matches an annotation name
      else if (title.match(/^annot(\d+)$/)) {
        $('#annot.section .alert').hide();
        $annots.push($uploader);
      }
      // Checks if matches an 'others' name
      else if (title.match(/^other(\d+)$/)) {}
    });

    // Appends rows
    $('#genome.section').append($genomes);
    $('#annot.section').append($annots);
    $('#others.section').append($others);

    // Shows container
    $('.main.container').fadeIn('normal');
  });

});
