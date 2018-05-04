// Uploader form
var $uploader = undefined;

$(function(){

  // Binds file upload form
  $uploader = $('#file-uploader');
  // Click on upload button
  $uploader.on('click', '#upload', function(){
    $uploader.uploader('upload');
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
      var name = e.source;
      var progress = (e.uploaded / e.size) * 100;
      // Checks if matches a genome name
      if (title.match(/^genome(\d+)$/)) {
        $('#genome.section .alert').hide();
        $genomes.push($createFileRow(name, title, progress));
      }
      // Checks if matches an annotation name
      else if (title.match(/^annot(\d+)$/)) {
        $('#annot.section .alert').hide();
        $annots.push($createFileRow(name, title, progress));
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

$.fn.uploader = function(action = 'reset') {
  var $self = $(this);
  // Starts upload of a file
  if(action == 'upload') {
    var type = $self.find('#type').val();
    var file = $self.find('#file').prop('files')[0];

    // Defines container where to put new row
    var $section = undefined;
    if(type == 'genome') {
      $section = $('.section#genome');
    }
    else if(type == 'annot') {
      $section = $('.section#annot');
    }
    else {
      // Case type not valid
      return;
    }

    // Creates new title
    var title = undefined;
    var $last = $section.find('.file-row').last();
    if($last.length <= 0) {
      title = type + '0';
    } else {
      // Deletes everything except the number of the genome file
      var lastId = $last.attr('id').replace(/^[^\d]{0,}/i, '');
      // Increase by 1 the file name
      title = '' + type + (++lastId);
    }

    // Creates row
    var $row = $createFileRow(file.name, title);
    // Appends row to correct container
    $section.append($row);

    // Creates an uploader
    $row.find('.fileupload').fileupload({
      method: 'POST',
      dataType: 'json',
      url: Defaults.apiRoot + 'genome_configs/' + GenomeConfig.id + '/uploads/',
      maxChunkSize: 1000000, // 1MB
      add: function (e, data) {}
    })
    // Executed before sending a chunk
    .on('fileuploadchunksend', function (e, data) {
      // Retrieves data which will be sent to the server's API
      var formData = data.data;
      // Retreives previously returned data
      var file = $row.data('file');
      // Appends additional data
      formData.append('name', file ? file.name : '');
      formData.append('title', title);
    })
    // Executed when a chunk is returned
    .on('fileuploadchunkdone', function (e, data) {
      // Puts token into values sent to server
      var file = data.result.files[0];
      // Puts file into this row's data
      $row.data('file', file);
    })
    // Updates progress bar
    .on('fileuploadprogress', function(e, data){
      var progress = parseInt(data.loaded / data.total * 100, 10);
      $row.find('.progress-bar').css('width', progress + '%');
    })
    // Triggers file send
    .fileupload('send', {files: file});

    $self.uploader('reset');
  }
  // Resets values into form
  else if(action == 'reset') {
    $self.find('#type').val('');
    $self.find('#file').val('');
  }
}

/**
 * @function $createFileRow creates a new file row dom element
 * @return dom element
 */
 function $createFileRow(name, title, progress = 0) {
   // Creates row
   var $row = $('<div/>', {
     class: 'row file-row',
     id: title
   });

   // Adds hidden input field containing file to upload
   $row.append('<input class=\'fileupload\' type=\'file\' name=\'files[]\' style=\'display:none\' multipart/>');
   // File name
   $row.append('<div class=\'col-6\'>' + name + '</div>');
   // Upload progress
   $row.append(
    '<div class=\'col-6\'>' +
      '<div class=\'progress\'>' +
        '<div class=\'progress-bar\' role=\'progressbar\' style=\'width:' + progress + '%\' aria-valuemin=\'0\' aria-valuemax=\'100\'></div>' +
      '</div>' +
    '</div>'
   );

   return $row;
 }
