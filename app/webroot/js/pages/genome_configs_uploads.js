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
  var file = ($('#uploader-files').prop('files').length > 0) ? $('#uploader-files').prop('files')[0] : undefined;
  // Defines file name
  var name = '';
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
    title = $.data($last.get(0), 'title');
    // Replaces last digits with same digits + 1
    title = title.replace(/[\d]{0,}$/i, parseInt(title.replace(/^[^\d]{0,}/i, '')) + 1);
    // DEBUG
    // console.log('title: ', title);
  }

  // Defines section where to append new uploader
  var $uploader = createUploader(name, title, file);
  // Appends created row to section
  $uploader.appendTo($section);
  // Disables select file
  $uploader.find('[name=\'files[]\']').prop('disabled', true);
  // Disables upload buton
  $uploader.find('.upload-start').prop('disabled', true);

  return $uploader;
}

/**
 * @method deleteUpload removes an upload row
 * @return void
 */
function deleteUpload(title) {
  $.ajax({
    // Creates url to /orcae-upload/genome_configs/:id/uploads/:title/
    'url': Defaults.apiRoot + 'genome_configs/' + GenomeConfig.id + '/uploads/' + title + '/',
    method: 'DELETE'
  });
}

$(function(){

  // Change event of uploader file selector
  $('#uploader').on('change', '#uploader-files', function() {
    var fileName = ($(this).prop('files').length > 0) ? $(this).prop('files')[0].name : 'Choose file...';
    $(this).next('label').text(fileName);
  })

  // Click event on upload button
  $('#uploader').on('click', '#uploader-upload', function(){
    // Creates uploader
    var $uploader = createNewUploader();
    // Starts uploader upload
    $uploader.find('[name=\'files[]\']').fileupload({
      method: 'POST',
      dataType: 'json',
      url: Defaults.apiRoot + 'genome_configs/' + GenomeConfig.id + '/uploads/',
      maxChunkSize: 1000000, // 1MB
      add: function (e, data) {}
    })
    // Executed before sending a chunk
    .on('fileuploadchunksend', function (e, data) {
      // Retrieves FormData which will be sent to the server's API
      var formData = data.data;
      // Appends additional data
      formData.append('name', $.data($uploader.get(0), 'name'));
      formData.append('title', $.data($uploader.get(0), 'title'));
    })
    // Executed when a chunk is returned
    .on('fileuploadchunkdone', function (e, data) {
      // Puts token into values sent to server
      var file = data.result.files[0];
      // Sets name if not alerady set (only first loop)
      if(!$.data($uploader.get(0), 'name')) {
        $.data($uploader.get(0), 'name', file.name);
      }
    })
    // Updates progress bar
    .on('fileuploadprogress', function(e, data){
      var progress = parseInt(data.loaded / data.total * 100, 10);
      $uploader.find('.progress-bar').css('width', progress + '%');
    })
    // Triggers file send
    .fileupload('send', {files: $.data($uploader.get(0), 'file')});
  });

  // Click event on delete button
  $(document)
    .on('click', '.file-uploader .upload-delete', function(){
      var $uploader = $(this).closest('.file-uploader');
      deleteUpload($.data($uploader.get(0), 'title'));
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
      var progress = (e.uploaded / e.size) * 100;
      // Defines uploader object
      var $uploader = createUploader(undefined, title, undefined, source, progress);

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

      // Case updload has been completed: one can only delete it
      if(progress === 100) {
        $uploader.find('.upload-start, .upload-stop, .upload-files').prop('disabled', true);
      }
      // Case upload has not been completed
      else {
        $uploader.find('.upload-stop').prop('disabled', true);
      }
    });

    // Appends rows
    $('#genome.section').append($genomes);
    $('#annot.section').append($annots);
    $('#others.section').append($others);

    // Shows container
    $('.main.container').fadeIn('normal');
  });

});
