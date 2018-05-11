// Initializes GenomeConfig if not already set
if(typeof GenomeConfig == 'undefined') {
  var GenomeConfig = {};
}

// Loads form from GenomeConfig.data object
GenomeConfig.loadForm = function() {
  // Saves refernece to GenomeConfig data object
  var data = this.data ? this.data : {};
  // Initalizes default type
  if(data.type != 'insert' || data.type != 'update') {
    data.type = 'insert';
  }
  // Initializes form values
  $('[name=\'type\']').each(function(){
    var checked = ($(this).val() == data.type)
    // Stets checked if type matches
    $(this).prop('checked', checked);
    // Triggers click on checked radio button
    $(this).trigger('click');
  });
  $('#species-name').val(data.species_name);
  $('#species-taxid').val(data.species_taxid);
  $('#species-5code').val(data.species_5code);
  $('#group-welcome').val(data.group_welcome);
  $('#group-description').val(data.group_description);
  $('#species-image').val('');
  $('#species-image-preview').attr('src', data.species_image);
  $('#config-file-bogas, #config-file-species').each(function(){
    // Retrieves editor from jQuery element
    var editor = ace.edit($(this).get(0));
    // Defines value to set
    var value = ($(this).attr('id') == 'config-file-bogas') ? data.config_bogas : data.config_species;
    // Sets editor value
    editor.setValue(value ? value : '', -1);
  });
}

// Serializes form values into GenomeConfig.data object
GenomeConfig.serializeForm = function() {
  // Creates data object which will overwrite the previous one
  this.data = {
    'id': this.data.id,
    'type': $('[name=\'type\']:checked').val(),
    'species_name': $('#species-name').val(),
    'species_taxid': $('#species-taxid').val(),
    'species_5code': $('#species-5code').val(),
    'group_welcome': $('#group-welcome').val(),
    'group_description': $('#group-description').val(),
    'config_species': ace.edit($('#config-file-species').get(0)).getValue(),
    'config_bogas': ace.edit($('#config-file-bogas').get(0)).getValue()
  }

  // Serializes image value only if it is set
  if($('#species-image').prop('files') && $('#species-image').prop('files')[0]) {
    this.data.species_image = $('#species-image').prop('files')[0];
  }

  // DEBUG
  // console.log('Serialized data: ', this.data);
}

// Changes form if type changes
GenomeConfig.changeType = function() {
  // Retrieves current genome configuration type
  var type = this.data.type;
  // Handles 'update' type: hides some sctions
  if(type == 'update') {
    console.log('update')
    $('#group, #config-files').hide();
  }
  // Handles 'insert' type: shows every section
  else {
    console.log('insert');
    $('#group, #config-files').show();
  }
}

// Retrieves GenomeConfig from server's API
GenomeConfig.retrieve = function(id) {
  return $.ajax({
    // Creates url /API/genome_configs/:id/
    url: Defaults.apiRoot + 'genome_configs/' + (id ? id + '/' : ''),
    method: 'GET',
    dataType: 'json'
  });
}

// Sends GenomeConfig.data to server
GenomeConfig.send = function(id, method = 'POST') {
  // Mantains a reference to current object
  var that = this;
  // Returns ajax request handler
  return $.ajax({
    url: Defaults.apiRoot + 'genome_configs/' + (id ? id + '/' : ''),
    method: (method == 'PUT') ? 'PUT' : 'POST',
    dataType: 'json',
    // Parameters required for sending images
    processData: false,
    contentType: false,
    // Parses data from array to FormData
    data: (function(){
      var data = new FormData();
      for(var name in that.data) {
        data.append(name, that.data[name]);
      }
      return data;
    }())
  });
}

// Creates a new validator, inheriting Validator constructor
GenomeConfig.validator = (new function(){
  // Parent constructor
  Validator.call(this);

  // Sets this validator errors
  this.errors = {
    'species_taxid': {
      regex: /^\d{0,}$/,
      message: "Species taxonomy must contain only digits"
    },
    'species_name': [
      {
        regex: /^.{0,50}$/,
        message: 'Species must be less than 50 chars long'
      },
      {
        regex: /^[a-zA-Z0-9\s]{0,}$/,
        message: 'Species name must contain only numbers, letters and spaces'
      },
      {
        regex: /^.{1,}$/,
        message: 'Species name must be set'
      }
    ],
    'species_5code': [
      {
        regex: /^.{0,5}$/,
        message: 'Species shortname must be less than 5 chars long'
      },
      {
        regex: /^[a-zA-Z0-9]{0,}$/,
        message: 'Species shortname must contain only numbers and letters'
      }
    ],
    'group_description': {
      regex: /^.{0,255}$/,
      message: 'Group description must be less than 255 chars long'
    }
  }

  // Sets this validator warnings
  this.warnings = {
    'species_taxid': {
      regex: /^\d{1,}$/,
      message: "Species taxonomy id should be set"
    },
    'species_5code': {
      regex: /^.{5}$/,
      message: "Species shortname should contain 5 chars"
    },
    'group_description': {
      regex: /^.{1,}$/,
      message: "Group description should be set"
    },
    'gorup_welcome': {
      regex: /^.{1,}$/,
      message: "Group description should be set"
    }
  }
}());

/**
 * @function retrieveSpecies
 * Makes an ajax request to /API/species/
 * @return ajax object
 */
function retrieveSpecies(param) {
  return $.ajax({
    url: '../../API/species/',
    method: 'GET',
    data: params,
    dataType: 'json'
  });
}

/**
 * @function retrieveDefaults
 * Makes an ajax request to /API/defaults/
 * @return ajax object
 */
function retrieveDefaults(params) {
  return $.ajax({
    url: '../../API/defaults/',
    method: 'GET',
    data: params
  });
}

/**
 * @method $.fn.validate
 * Executed on a jQuery element, uses data to show validation results graphically
 * If errors have been found: red color
 * Else if warnings have been found: yellow/orange color
 * Else removes colors
 * @param result is an object which contains errors, warnings or nothing
 * @return void
 */
 $.fn.validate = function(result) {
   // Field container
   var $parent = $(this).closest('.section-item');
   // validation result message
   var $message = $('<small/>', {
     'class': 'validate-message',
     'text': ''
   });

   // Deletes previously displayed error class on section paragraph
   $parent.removeClass('warning error');
   // Deletes previously displayed error message
   $(this).siblings('.validate-message').remove();

   var parentClass = '';
   var messageClass = '';
   var messageText = '';
   if(result.error != undefined) {
     parentClass ='error';
     messageClass = 'text-danger';
     messageText = result.error;
   }
   else if(result.warning != undefined) {
     parentClass ='warning';
     messageClass = 'text-warning';
     messageText = result.warning;
   }
   else {
     // Returns without
     return;
   }

   // Modifies validation graphic results
   $parent.addClass(parentClass);
   $message.addClass(messageClass);
   $message.text(messageText);

   // Appends error message to input
   $(this).after($message);
 }

$(function() {
  // Initializes click on genome configuration type radio buttons
  $('[name=\'type\']').on('click', function(){
    // Changes GenomeConfig type with actually checked field
    GenomeConfig.data.type = $(this).val();
    // DEBUG
    // console.log('Actual Genome Configuration Type: ', GenomeConfig.data.type);
    GenomeConfig.changeType();
  });
  // Initializes config files editors
  $('#config-file-bogas, #config-file-species').each(function(){
    // Initializes ace editor
    var editor = ace.edit($(this).get(0));
    // Sets editors in .yaml mode
    editor.session.setMode('ace/mode/yaml');
    // Sets editor properties
    editor.setOptions({
        minLines: 30,
        maxLines: Infinity
    });
  });
  // Initializes validation on every validable field
  $('#species-name, #species-taxid, #species-5code, #group-welcome, #group-description').on('keyup', function(){
    // Retrieves validation result
    var valid = GenomeConfig.validator.validate($(this).attr('name'), $(this).val());
    // DEBUG
    // console.log(valid);
    $(this).validate(valid);
  });
  // Initilizes change image event: previews selected image
  $('#species-image').on('change', function(){
    // Retrieves image, if it is set
    var img = ($(this).prop('files') && $(this).prop('files')[0]) ? $(this).prop('files')[0] : '';
    // Previews image, if set
    if(img) {
      $('#species-image-preview').attr('src', window.URL.createObjectURL(img));
    }
    else {
      $('#species-image-preview').attr('src', '#');
    }
  });
  // Initilizes click event on save button
  $('#save-genome-config').on('click', function(){
    // Triggers form serialization
    GenomeConfig.serializeForm();
    // Sends data to API
    GenomeConfig.send(GenomeConfig.data.id)
      .done(function(data){
        // Retrieves id from saved data
        GenomeConfig.data.id = data.id;
        // Updates history (page reload will get user back here)
        history.replaceState(null, null, './../' + data.id + '/');
        // Shows success alert
        $('#alert-genome-config').html(
          '<div class=\'alert alert-primary\' role=\'alert\'>' +
            '<strong>Success! </strong>' +
            'Data saved correctly!' +
          '</div>'
        ).show();
        // Updates image preview
        $('#species-image-preview').attr('src', data.species_image);
        // Deletes previously set image
        $('#species-image').val('');
        // Shows 'go-to-upload' button
        $('#go-to-genome-uploads').show();
      })
      .fail(function(data){
        // Retrieves only firs error message
        var errorMsg = '';
        // If response is in json format, it means there is a validation.errors array
        if(data.responseJSON) {
          errors = data.responseJSON.validation.errors;
          errorMsg = errors[Object.keys(errors)[0]]
        }
        // Catch other error formats
        else {
          errorMsg = data;
        }
        // Shows failure alert
        $('#alert-genome-config').html(
          '<div class=\'alert alert-danger\' role=\'alert\'>' +
            '<strong>Error! </strong>' +
            errorMsg +
          '</div>'
        ).show();
      });
  });
  // Initializes form data
  GenomeConfig.retrieve(GenomeConfig.data.id)
    .done(function(data){
      // Puts retrieved data into current genome configuration data
      GenomeConfig.data = data;
      // Shows go-to-upload button
      $('#go-to-genome-uploads').show();
    })
    .fail(function(data){
      // Sets id as empty
      GenomeConfig.data.id = '';
      // Sets editor default values
      $('#config-file-bogas, #config-file-species').each(function(){
        var $self = $(this);
        retrieveDefaults({
          file: ($self.attr('id') == 'config-file-bogas') ? 'config_bogas' : 'config_species'
        }).done(function(data){
          ace.edit($self.get(0)).setValue(data, -1);
        });
      });
    })
    .always(function(data){
      // Loads retrieved data, if any
      GenomeConfig.loadForm();
      // Shows container when initialization ends
      $('.main.container').fadeIn('normal');
    });
});
