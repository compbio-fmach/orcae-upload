// Initializes GenomeConfig if not already set
if(typeof GenomeConfig == 'undefined') {
  var GenomeConfig = {};
}

// Loads form from GenomeConfig.data object
GenomeConfig.loadForm = function() {
  // Saves refernece to GenomeConfig data object
  var data = this.data ? this.data : {};
  // Initalizes default type
  if(data.type != 'insert' && data.type != 'update') {
    data.type = 'insert';
  }
  // Initializes form values
  $('[name=\'type\']').each(function(){
    var checked = ($(this).val() == data.type);
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
    var editor = ace.edit(this);
    // Defines value to set
    var value = ($(this).attr('id') == 'config-file-bogas') ? data.config_bogas : data.config_species;
    // Sets editor value if not empty
    if(value) editor.setValue(value, -1);
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
}

// Changes form if type changes
GenomeConfig.changeType = function() {
  // Retrieves current genome configuration type
  var type = this.data.type;
  // Defines if type of current genome configuration is update or insert
  var update = (type == 'update');
  // Defines validator for configuration fields
  var insertValidator = GenomeConfig.insertValidator;
  var updateValidator = GenomeConfig.updateValidator;
  GenomeConfig.validator = update ? new updateValidator() : new insertValidator();
  // Handles 'update' type: hides some sctions
  if(update) {
    $('#group, #config-files').hide();
  }
  // Handles 'insert' type: shows every section
  else {
    $('#group, #config-files').show();
  }
  // Initializes suggestions for species name
  $('#species-name').autocomplete(update ? false : true);
  // Sets species type cirrectly
  $('[name=\'type\']').each(function(){
    // Sets checked only if value equals type
    $(this).attr('checked', $(this).val() == type);
  });
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
  var self = this;
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
      for(var name in self.data) {
        data.append(name, self.data[name]);
      }
      return data;
    }())
  });
}

// Creates a new custom validator, inheriting Validator constructor
GenomeConfig.commonValidator = function() {
  // Parent constructor
  Validator.call(this);

  // Validates every field
  this.validateAll = function() {
    // Defines a reference to validator object
    var self = this;
    // Retrieves fields which must be validated
    var $fields = $('#species-name, #species-taxid, #species-5code, #species-image, #group-welcome, #group-description');
    // Defines if is valid
    var isValid = true;
    // Loops through every field, executing validation
    $fields.each(function(){
      var $field = $(this);
      // Validates current field
      var currValid = self.validate($field.attr('name'), $field.val());
      // Validates the DOM field
      $field.validate(currValid);

      // Sets error if current field thorwn it
      if(currValid.error) {
        isValid = false;
      }
    });
    // Returns true only if no error has been found
    return isValid;
  }

  // Sets common validator errors
  this.errors = {
    'species_taxid': [{
        rule: /^\d{0,}$/,
        message: "Species taxonomy must contain only digits"
    }],
    'species_name': [
      {
        rule: /^.{0,50}$/,
        message: 'Species must be less than 50 chars long'
      },
      {
        rule: /^[a-zA-Z0-9\s]{0,}$/,
        message: 'Species name must contain only numbers, letters and spaces'
      },
      {
        rule: /^.{1,}$/,
        message: 'Species name must be set'
      }
    ],
    'species_5code': [
      {
        rule: /^.{0,5}$/,
        message: 'Species shortname must be less than 5 chars long'
      },
      {
        rule: /^[a-zA-Z0-9]{0,}$/,
        message: 'Species shortname must contain only numbers and letters'
      }
    ],
    'group_description': [{
      rule: /^.{0,255}$/,
      message: 'Group description must be less than 255 chars long'
    }]
  }

  // Sets common validator warnings
  this.warnings = {
    'species_taxid': {
      rule: /^\d{1,}$/,
      message: "Species NCBI taxonomy id should be set"
    },
    'species_5code': {
      rule: /^.{5}$/,
      message: "Species shortname should contain 5 chars"
    },
    'group_description': {
      rule: /^.{1,}$/,
      message: "Group description should be set"
    },
    'gorup_welcome': {
      rule: /^.{1,}$/,
      message: "Group welcome text should be set"
    }
  }
};

// Creates a validator for 'update' action
GenomeConfig.updateValidator = function() {
  GenomeConfig.commonValidator.call(this);

  // Reference to species avaiable
  var species = GenomeConfig.speciesAvaiable;

  // Retrieves species taxid current value
  function getSpeciesTaxid() {
    return $('#species-taxid').val();
  }

  // Retrieves species 5code current value
  function getSpecies5code() {
    return $('#species-5code').val();
  }

  // Retrieves species name current value
  function getSpeciesName() {
    return $('#species-name').val();
  }

  // Validates compliance between species taxid and 5code
  function validateCompliance(value) {
    var match = species.find(function(s) {
      var valid5code = s['5code'] == getSpecies5code();
      var validTaxid = s['NCBI_taxid'] == getSpeciesTaxid();
      return valid5code && validTaxid;
    });
    return match != undefined;
  }

  // Adds validation on taxid
  this.errors.species_taxid.push({
    rule: function(value) {
      // Checks if taxid is valid
      var match = species.find(function(s) {
        return s['NCBI_taxid'] == value;
      });
      return match != undefined;
    },
    message: 'Given taxid does not match any Orcae\'s species taxid'
  }, {
    rule: validateCompliance,
    message: 'Given taxid is not compliant with given 5code'
  });

  // Adds validation on
  this.errors.species_5code.push({
    rule: function(value) {
      // Checks if 5code matches any other species 5code
      var match = species.find(function(s) {
        return s['5code'] == value;
      });
      return match != undefined;
    },
    message: 'Given 5code does not match any Orcae\' species 5code'
  }, {
    rule: validateCompliance,
    mesage: 'Given 5code is not compliant with given taxid'
  });

};

// Creates a validator for 'insert' action
GenomeConfig.insertValidator = function() {
  GenomeConfig.commonValidator.call(this);

  // reference to Orcae's avaiable species
  var species = GenomeConfig.speciesAvaiable;

  // Adds validation on taxid
  this.errors.species_taxid.push({
    rule: function(value) {
      // Checks if taxid is valid
      var match = species.find(function(s) {
        return s['NCBI_taxid'] == value;
      });
      return !match;
    },
    message: 'Given taxid is already used'
  });

  // Adds validation on
  this.errors.species_5code.push({
    rule: function(value) {
      // Checks if 5code matches any other species 5code
      var match = species.find(function(s) {
        return s['5code'] == value;
      });
      return !match;
    },
    message: 'Given shortname is already used'
  });
};

// Retrieves species avaiable on Orcae currently
GenomeConfig.speciesAvaiable = (function() {
  // executes ajax call to species API
  retrieveSpecies()
    .done(function(data) {
      // Updtaes result with given species
      GenomeConfig.speciesAvaiable = data;
    });
  // Returns empty array
  return new Array();
}());

// Handles genome updates bound to current configuration id
GenomeConfig.checkUpdates = function() {
  // Mantains a reference to DOM element
  var $this = $('#save-genome-config');

  // Creates new genome updates handler
  var updates = new GenomeUpdates({
    // Time between requests
    pollingInterval: 3000,
    // Genome configuratiuon object
    genomeConfig: Object.assign({}, GenomeConfig.data)
  });

  // Handles genome editable
  var isUpdatable = function() {
    $this
      // Enables button
      .prop('disabled', false)
      // Updates button text
      .text('Save');
  }

  // Handles genome not editable
  var notUpdatable = function() {
    $this
      // Disables button
      .prop('disabled', true)
      // Updates button text
      .text('Orcae has already been updated with the current genome');
  }

  var isUpdating = function() {
    $this
      .prop('disabled', true)
      .text('Orcae is being updated with the current genome');
  }

  // Starts polling
  updates.polling({
    // Genome not already inserted into Orcae
    onUpdateEmpty: isUpdatable,
    // Genome aLready inserted into Orcae
    onUpdateSuccess: notUpdatable,
    // Genome not already inserted into Orcae
    onUpdateFailure: isUpdatable,
    // Currently under update
    onUpdateUpdating: isUpdating
  });
}

/**
 * @function retrieveSpecies
 * Makes an ajax request to /API/species/
 * @return ajax object
 */
function retrieveSpecies(params = {}) {
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
function retrieveDefaults(params = {}) {
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

 /**
  * @method $.fn.autocomplete
  * Opens a bootstrap dropdown under text input with suggestions
  * It is just a wrapper for $.fn.typeahead
  * @return void
  */
$.fn.autocomplete = function(destroy = false) {
  // Defines reference to current DOM element
  var $this = $(this);
  // Destroys autocomplete on this input field
  if(destroy) {
    $(this).typeahead('destroy');
    return;
  }
  // Cretaes typeahead over this input field
  $(this)
    .typeahead({
      autoSelect: false,
      // Defines data for autocomplete
      source: function(q, cb) {
        // Retrieves species previously saved
        var species = GenomeConfig.speciesAvaiable;
        // Returns species on callback
        cb(species);
      },
      // Handles user item selection
      updater: function (item) {
        // Sets species taxid
        $('#species-taxid').val(item['NCBI_taxid']);
        // Sets species 5code
        $('#species-5code').val(item['5code']);
        return item.organism;
      },
      // Handles which items must be returned as hints
      matcher: function (item) {
        var species = item.organism;
        if (species.toLowerCase().indexOf(this.query.trim().toLowerCase()) != -1) {
          return true;
        }
      },
      // Handles sorting
      sorter: function (items) {
          return items.sort(function(a, b) {
            // Compares species names
            a = a.organism;
            b = b.organism;
            if(a < b) return -1;
            else if(a > b) return 1;
            else return 0;
          });
      },
      highlighter: function (item) {
        var regex = new RegExp( '(' + this.query + ')', 'gi' );
        return item.organism.replace( regex, "<strong>$1</strong>" );
      }
    });
}

// Global variable which holds species
$(function() {

  // Initializes click on genome configuration type radio buttons
  $('[name=\'type\']').on('click', function(){
    // Changes GenomeConfig type with actually checked field
    GenomeConfig.data.type = $(this).val();
    GenomeConfig.changeType();
  });

  // Initializes .yaml files editors
  // Calls defaults API asynchronously
  $('#config-file-bogas, #config-file-species').each(function(){
    // Initializes ace editor
    var editor = ace.edit(this);
    // Sets editors in .yaml mode
    editor.session.setMode('ace/mode/yaml');
    // Sets editor properties
    editor.setOptions({
        minLines: 30,
        maxLines: Infinity
    });

    // Calls asynchronously defaults API
    // Defines which .yaml file is currently being configured
    var file = $(this).attr('id') == 'config-file-bogas' ? 'config_bogas' : 'config_species';
    // Makes AJAX call
    retrieveDefaults({file: file})
    // Case default file has been found
    .done(function(data){
      if(!editor.getValue()) {
        editor.setValue(data, -1);
      }
    });
  });

  /*
  // Initializes species avaiable on orcae
  $('#species-name').each(function(){
    // Defines a reference to current element
    var $this = $(this);
    // Retrieves species from species API
    retrieveSpecies()
      .done(function(species) {
        // Binds found array of species to species name element
        $this.data('species', species);
      });
  });
  */

  // Initializes validation
  $('#species-name, #group-welcome, #group-description').on('keyup', function(){
    // Retrieves validation result
    var valid = GenomeConfig.validator.validate($(this).attr('name'), $(this).val());
    $(this).validate(valid);
  });

  // Initilizes validation on species taxid and 5code
  $('#species-taxid, #species-5code').on('keyup', function(e) {
    // Defines which field has triggered keyup event
    var self = this;

    // Validates both fields
    $('#species-taxid, #species-5code').each(function(){
      // Double validation only on 'update'
      if(GenomeConfig.type != 'update' && self != this) return;
      // Validates field
      var result = GenomeConfig.validator.validate($(this).attr('name'), $(this).val());
      // Outputs results on dom elements
      $(this).validate(result);
    });
  });

  // Initilizes change image event: previews selected image
  $('#species-image').on('change', function(){
    // Retrieves image, if it is set
    var img = ($(this).prop('files') && $(this).prop('files')[0]) ? $(this).prop('files')[0] : false;
    // Other image validation rules
    // console.log(img);
    // Previews image, if set
    $('#species-image-preview').attr('src', img ? window.URL.createObjectURL(img) : '#');
  });

  // Initilizes click event on save button
  $('#save-genome-config').on('click', function(){
    // Empties message container
    var $messages = $('#messages').empty();
    // Defines a message which will shown to user
    var $msg = $('<div class="alert" role="alert"></div>');
    // Triggers form serialization
    GenomeConfig.serializeForm();
    // First of all, validates all fields locally
    if(GenomeConfig.validator.validateAll() !== true) {
      $messages.append(
        $msg
          .text('There are some errors, check the form then try again')
          .addClass('alert-danger')
      );
    }
    // Sends data to API
    GenomeConfig.send(GenomeConfig.data.id)
      // On success shows confirmation message
      .done(function(data){
        // Retrieves id from saved data
        GenomeConfig.data.id = data.id;
        // Updates history (page reload will get user back here)
        history.replaceState(null, null, './../' + data.id + '/');
        // Shows success alert
        $messages.append(
          $msg
            .html('<strong>Success! </strong>Data saved correctly!')
            .addClass('alert-primary')
        );

        // Handles species shortname (5code)
        GenomeConfig.data.species_5code = data.species_5code;
        $('#species-5code').val(GenomeConfig.data.species_5code).change();

        // Handles species image
        // Updates image preview
        $('#species-image-preview').attr('src', data.species_image);
        // Deletes previously set image
        $('#species-image').val('');
        // Checks image upload message
        if(data.validation.warnings.species_image) {
          // Retrieves warning message
          var msg = data.validation.warnings.species_image;
          // Validates field
          $('#species-image').validate({
            warning: msg
          });
        }
        // Case no warning on image: remover warning messages, if any
        else {
          $('#species-image').validate(true);
        }

        // Shows 'go-to-upload' button
        $('#go-to-uploads').show();
      })
      // On failure, shows any error message
      .fail(function(data) {
        /*
        // Defines errors as json messgages and html messages
        var errors = new Array();
        var $errors = new Array();
        // If response is in json format, it means there is a validation.errors array
        if(data.responseJSON) {
          errors = data.responseJSON.validation.errors;
        }
        // Catches generic error
        else {
          errors = [data];
        }
        // For every error, creates its html element
        for(var index in errors) {
          // Redefines attribute from index
          var attr = index.replace('_', ' ');
          $errors.push(
            '<div class=\'alert alert-danger\' role=\'alert\'>' +
              '<strong>Error on ' + attr + ': </strong>' +
              errors[index] +
            '</div>'
          );
        }
        */
      });
  });

  // Initializes save button status (checks if there are ongoing updates)
  $('#save-genome-config').each(function() {
    GenomeConfig.checkUpdates();
  });

  // Initializes form data
  GenomeConfig.retrieve(GenomeConfig.data.id)
    .done(function(data){
      // Puts retrieved data into current genome configuration data
      GenomeConfig.data = Object.assign(GenomeConfig.data, data);
      // Shows go-to-upload button
      $('#go-to-uploads').show();
    })
    .fail(function(data) {
      GenomeConfig.data.id = undefined;
    })
    .always(function(data){
      // Loads retrieved data, if any
      GenomeConfig.loadForm();
      // Triggers interface change
      GenomeConfig.changeType();
      // Shows container when initialization ends
      $('.main.container').fadeIn('normal');
    });
});
