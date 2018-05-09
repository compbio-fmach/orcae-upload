// Initializes GenomeConfig if not already set
if(typeof GenomeConfig == 'undefined') {
  var GenomeConfig = {};
}

// Loads form from GenomeConfig.data object
GenomeConfig.loadForm = function() {
  // Saves refernece to GenomeConfig data object
  var data = this.data ? this.data : {};
  // Initializes form values
  $('[name=\'type\']').each(function(){
    $(this).prop('checked', $(this).val() == data.type);
  });
  $('#species-name').val(data.species_name);
  $('#species-taxid').val(data.species_taxid);
  $('#species-5code').val(data.species_5code);
  $('#species-image').val('');
  $('#group-welcome').val(data.group_welcome);
  $('#group-description').val(data.group_description);
  $('#config-file-bogas, #config-file-species').each(function(){
    // Retrieves editor from jQuery element
    var editor = $(this).data('ace').editor.ace;
    // Defines value to set
    var value = ($(this).attr('id') == 'config-file-orcae') ? data.config_bogas : data.config_species;
    // Sets editor value
    editor.setValue(value, -1);
  });
}

// Serializes form values into GenomeConfig.data object
GenomeConfig.serializeForm = function() {
  // Creates data object which will overwrite the previous one
  this.data = {
    'species_name': $('#species-name').val(),
    'species_taxid': $('#species-taxid').val(),
    'species_5code': $('#species-5code').val(),
    'species_image': $('#species-image').prop('files')[0],
    'group_welcome': $('#group-welcome').val(),
    'group_description': $('#group-description').val(),
    'config_species': $('#config-file-species').data('ace').editor.ace.getValue(),
    'config_bogas': $('#config-file-bogas').data('ace').editor.ace.getValue()
  }
}

// Changes form if type changes
GenomeConfig.changeType = function() {
  // Retrieves current genome configuration type
  var type = this.data.type;
  // Handles 'update' type: hides some sctions
  if(type == 'update') {
    $('#group, #config-files').hide();
  }
  // Handles 'insert' type: shows every section
  else {
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
      that.data.forEach(function(e){
        // e is the current element in loop
        // e[0] is the name of the field
        // e[1] is the value of the field
        data.append(e[0], e[1]);
      });
      return data;
    }())
  });
}

// Creates a new validator, inheriting Validator constructor
var GenomeConfigValidator = (new function(){
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
* Document initialization
* Retreives genome config session from APIS
* Sets the current view using retrieved configuration
* The last thing that does is showing the form (after it is configured and loaded)
*/
$(function() {

  $('[name=\'type\']').on('click', function(){
    // Changes GenomeConfig type with actually checked field
    GenomeConfig.type = $(this).val();
    // DEBUG
    console.log('Actual Genome Configuration Type: ', GenomeConfig.type);
  });

  console.log(GenomeConfigValidator);

  /*
  // Initializes field validation on default fields
  $('#group-description, #group-welcome, #config-file-species, #config-file-orcae').on('keyup', function(){
    var value = $(this).val();
    var field = $(this).attr('name');
    var valid = validator.validate(field, value);

    // Graphical validation output
    $(this).validate(valid);
  });
  */

  /*
  // Initializes field validation for species fields
  $('#species-name, #species-taxid, #species-5code').off('keyup').on('keyup', function(){
    var $self = $(this);

    var value = $(this).val();
    var field = $(this).attr('name');

    validator.validateSpecies(
      field,
      value,
      // Standard validation (checks value before sending it to server)
      function(result, exit) {
        if(exit) {
          // Shows error immediately
          $self.validate(result)
        }
      },
      // Checks value against server
      function(result){
        $self.validate(result);
      });
  });
  */

  /*
  // Initializes YAML editors as ace editors
  $('#config-file-species, #config-file-orcae').each(function(){
    // Initializes ace editor
    var editor = ace.edit($(this).get(0));
    // Sets editors in .yaml mode
    editor.session.setMode('ace/mode/yaml');
    // Sets editor properties
    editor.setOptions({
        minLines: 30,
        maxLines: Infinity
    });

    // Defines the default file to retrieve
    var file = ($(this).attr('id') == 'config-file-orcae') ? 'config_bogas' : 'config_species';

    // Puts default data into editor
    // Default data is retrieved from editor
    getDefaults({'file': file}).done(function(data){
      if(!editor.getValue()) {
        editor.setValue(data, -1);
      }
    });
  });

  // $fields.filter('[name=\'type\']').on('click', changeGenomeConfigType);

  // Binds click on save button
  $('#save-genome-config').on('click', function(){
    // Initializes validation
    $('#alert-genome-config').html(
      '<div class=\'alert alert-secondary\' role=\'alert\'>Validating...</div>'
    );

    // Validates fields
    var serialized = serializeGenomeConfig();
    validator.validateAll(serialized, function(field, result, last){
      // Validates each field
      var $field = $fields.filter('[name=\'' + field + '\']');
      if($field) { $field.validate(result); }

      //If not last validation: exits
      if(last == undefined) { return; }

      // Case validation wrong
      if(!last) {
        // Error message
        $('#alert-genome-config').html(
          '<div class=\'alert alert-danger\' role=\'alert\'>' +
            '<strong>Error! </strong>' +
            'There are some errors which prevent save. Check out the form for more information.' +
          '</div>'
        );
        return;
      }

      // Case validation successful
      saveGenomeConfig(serialized)
        .done(function(data){
          $('#alert-genome-config').show().html(
            '<div class=\'alert alert-primary\' role=\'alert\'>' +
              '<strong>Success! </strong>' +
              'Data saved correctly!' +
            '</div>'
          );
        })
        .fail(function(data){
          $('#alert-genome-config').show().html(
            '<div class=\'alert alert-danger\' role=\'alert\'>' +
              '<strong>Error! </strong>' +
              'Something went wrong, data has not been saved!' +
            '</div>'
          );
        });
    });
  });

  // Retrieves Genome Configurations from database and puts them into form
  getGenomeConfig(GenomeConfig.id)
    .done(function(data){
      // Overwrites genome configuration
      GenomeConfig = data;
    })
    .fail(function(data){
      GenomeConfig.id = undefined;
    })
    .always(function(data){
      // States if currently loaded genome config already existed or not
      GenomeConfig.exists = (GenomeConfig.id != undefined);
      // Sets currently loaded genome config type
      GenomeConfig.type = (GenomeConfig.type && GenomeConfig.type.match(/^(insert|update)$/)) ? GenomeConfig.type : 'insert';

      // Loads data into form
      loadGenomeConfig(GenomeConfig);

      // Shows form
      $main.fadeIn('normal');
    });

  */

  $('.main.container').fadeIn('normal');
});

/**
* @function loadForm loads GenomeConfig data into form
* Retrieves values from ajax request using retrieveGenomeConfig
* Firstly: initializes DOM fields that requires special initialization
* Lastly: initializes common DOM fields
* @param data is the GenomeConfig istance returned by the server
* @return void
*/
function loadGenomeConfig(data) {
  // Copy data by value, in this way original data is not modified
  var data = Object.assign({}, data);

  if (data.config_orcae != undefined) {
    var $file = $fields.filter('[id=\'config-file-orcae\']');
    var editor = ace.edit($file.get(0));
    editor.setValue(data.config_orcae, -1);
    delete data.config_orcae ; // Deletes value from object (prevents value from being initialized lately)
  }

  // Initializes config_species.yaml editor's value
  if (data.config_species != undefined) {
    var $file = $fields.filter('[id=\'config-file-5code\']');
    var editor = ace.edit($file.get(0));
    editor.setValue(data.config_species, -1); // Sets editor's content (-1 sets for cursor position)
    delete data.config_species; // Deletes value from object (prevents value from being initialized lately)
  }

  // Initializes species image (passed as url to image)
  if(data.species_image != undefined) {
    var $image_input = $fields.filter('[name=\'species_image\']');
    var $image_preview = $main.find('#species-image-preview');
    // Initialize image field
    $image_preview.attr('src', data.species_image);
    delete data.species_image;
  }

  // Sets default session type if it is not set
  data.type = /^(update|insert)$/.test(data.type) ? data.type : 'insert';
  // Initializes session type
  $fields.filter('[name=\'type\']').each(function(){
    if($(this).val() == data.type) {
      $(this).prop('checked', true);
    } else {
      $(this).prop('checked', false);
    }
  });
  // Prevents session type field later inizialization
  delete data.type;

  // Almost every session attribute in DOM has the same name of session object's attribute
  for(var attr in data) {
    // sets value in DOM element bound to attribute's name
    $main.find('[name=\'' + attr + '\']').val(data[attr]);
  }
}

/**
* @function serializeGenomeConfig serializes values of session config page's form into FormData
* Serializes all in case type is 'insert'
* Serializes only species info in case of update
* @return data as FormData
*/
function serializeGenomeConfig() {
  // Data serialized to be returned
  var data = new Array();

  // Appends session type to data
  data.push({name: 'type', value: $('#session-type [name=\'type\']:checked').val()});

  $fields
    // Appends species info to data
    .filter('[name^=\'species_\']')
    // Appends grioup info to data
    .add('[name^=\'group_\']')
    .each(function(){
      data.push({name: $(this).attr('name'), value: $(this).val()});
    });

  // Appends YAML files configuration to data
  data.push({name: 'config_species', value: ace.edit($('#config-file-5code').get(0)).getValue()});
  data.push({name: 'config_orcae', value: ace.edit($('#config-file-orcae').get(0)).getValue()});

  // Appends image file to configuration data, if an image is set
  var species_image = $('#species [name=\'species_image\']').prop('files')[0];
  if(species_image) {
    data.push({name: 'species_image', value: species_image});
  }

  // Leaves only species fields if type is update
  if(GenomeConfig.type == 'update') {
    data = data.map(function(elem){
      if(elem.name == 'type' || elem.name.match(/^species_/)) {
        return elem;
      }
    });
  }

  console.log(data);

  // Returns array format
  return data;
}


/**
 * @function getGenomeConfig
 * Retreives genome config istance from server
 * @return ajax
 */
function getGenomeConfig(id) {
  return $.ajax({
    url: '../../API/genome_configs/' + id + '/',
    method: 'GET',
    dataType: 'json'
  });
}

/**
* @function saveSessionConfig saves form data against server API
* @return void
*/
function saveGenomeConfig(data) {
  // Creates a FormData object which will be sent through ajax
  var formData = new FormData();
  // Puts data values into FormData
  data.forEach(function(elem, i){
    formData.append(elem.name, elem.value);
  });
  // Sends data to /API/genome_configs/:id/ using POST method
  return $.ajax({
    url: '../../API/genome_configs/' + (GenomeConfig.id ? GenomeConfig.id + '/' : ''),
    method: 'POST',
    processData: false,
    contentType: false,
    data: formData,
    dataType: 'json'
  });
}

/**
* @function getSpecies searches for species into orcae_bogas.taxid table
* @param params is an array of {<param name>: <param value>} which is built as querystring befor sending it
* @return $.ajax object which implements .done(), .fali() and .always() methods
*/
function getSpecies(params) {
  return $.ajax({
    url: '../../API/species',
    method: 'GET',
    data: params,
    dataType: 'json'
  });
}

/**
* @function getDefaults retrieves default values from API
* @param params is an array of {<param name>: <param value>} which is built as querystring before sending it
* @return $.ajax object which implements .done(), .fali() and .always() methods
*/
function getDefaults(params) {
  return $.ajax({
    url: '../../API/defaults/',
    method: 'GET',
    data: params,
    dataType: 'json'
  });
}

/**
* @method previewImage of jQuery
* Previews an image in the current img element
* @param img is the image file to be previewed
* @param preview specifies if it is a preview (local image) or if the url refers to an image stored in the server
* @return void
*/
$.fn.previewImage = function(img, preview = true) {
  // Initializes img element where image must be displayed
  var $img = $(this);

  // Deletes preview class and changes src attribute of image to a remote location
  if(!preview) {
    $img.removeClass('preview');
    $img.attr('src', img);
    return;
  }

  // Adds .preview class to image
  $img.addClass('preview');
  // Modifies src value
  $img.attr('src', window.URL.createObjectURL(img));
}

/**
* @method previewFileName of jQuery
* Previews file name + extension into custom file form input
* @param preview is used to specify to remove file name previously displayed
* @return void
*/
$.fn.previewFileName = function(preview = true) {
  // Initializes input field
  var $input = $(this);

  // Reset deletes input value and file name
  if(!preview) {
    // Resets input value
    $input.val('');
    // Resets file name preview
    $input.next('.custom-file-label').removeClass('selected').html('Choose file');
    return;
  }

  // Retrieves file name
  var file = $input.val().split('\\').pop();
  // Puts file name into input label
  $input.next('.custom-file-label').addClass('selected').html(file);
}

/**
* @method validate of jQuery
* Allows to render a custom error
* @param valid is an error message in SessionConfigValidator style: [true|{<error/warning>: <message>}]
* If parameter is undefined, current object's field and value will be used
* Sets field error message
* @return void
*/
$.fn.validate = function(valid = undefined) {

  // Takes values by the current element
  var field = $(this).attr('name');
  var value = $(this).val();

  // Hold session paragraph reference
  var $parent = $(this).closest('.section-item');
  // Creates and hold message DOM element reference
  var $message = $('<small/>', {
    'class': 'validate-message',
    'text': ''
  });

  // Checks if param has already been set
  if(valid == undefined) {
    // Retrieves validation result (requires validator global variable to be set)
    valid = validator.validate(field, value);
  }

  // Deletes previously displayed error class on section paragraph
  $parent.removeClass('warning error');
  // Deletes previously displayed error message
  $(this).siblings('.validate-message').remove();

  // If field is valid exits after removing every message class
  if(valid === true) {
    return true;
  }
  // Case a warning has been returned
  else if(valid.warning != undefined) {
    $parent.addClass('warning'); // Adds warning
    $message.addClass('text-warning');
    $message.text(valid.warning);
    valid = 'warning';
  }
  // Case an error has been returned
  else if(valid.error != undefined) {
    $parent.addClass('error');
    $message.addClass('text-danger');
    $message.text(valid.error);
    valid = 'error';
  }
  // Case message only: shows it without error or warning class
  else if(typeof valid == 'string') {
    $message.text(valid);
    $message.addClass('text-muted');
    valid = true;
  }

  // Appends error message to input
  $(this).after($message);

  // Returns
  return valid;
}

/**
* @function clickSessionType changes the screen based on session's type value
* If type equals 'update', only species info must be shown
* If type equals 'insert', all form must be displayed
*/
function changeGenomeConfigType() {
  // Saves a reference to radio button
  var $rb = $(this);

  // Continues execution only if this is the checked radio button
  if(!$rb.prop('checked')) return;

  // Transition time: sets how much time must fadeIn/fadeOut take
  // If it executed before page content is displayed, it doesn't need animation
  // var tt = ($main.css('display') == 'none') ? 0 : 'slow';
  var tt = 0;

  var $sections = $main
    .find('.section#group')
    .add('.section#config-files');

  // Case 'update': displays only species information
  if($rb.val() == 'update') {
    // Updates session.type
    GenomeConfig.type = 'update';
    // Applies visible session type changes
    $sections.not('[style*=\'display: none\']').fadeOut(tt);
  }
  // Case 'insert': display all session's information
  else {
    // Updates session.type
    GenomeConfig.type = 'insert';
    // Applies visible session type changes
    $sections.filter('[style*=\'display: none\']').fadeIn(tt);
  }
}

/**
* @function changeSpeciesImage handle changes in selected session image
* Previews either image file name and image itself
* @return void
*/
function changeSpeciesImage() {
 // Reference to input file browser
 var $browser = $('#species-image-browser');
 // eference to image preview
 var $preview = $('#species-image-preview');
 // previews file name
 $browser.previewFileName();
 // previews image
 $preview.previewImage($browser.get(0).files[0]);
}

// Initialization of validator
function initValidator() {
  var validator = new Validator();

  // Defines errors
  validator.errors = {
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

  // Defines warnings (non-blocking errors)
  validator.warnings = {
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

  // Defines validation for species fields
  validator.validateSpecies = function(field, value, before, after) {
    var result = undefined;
    // Default validation first
    result = this.validate(field, value);
    // Defines if function exits after first callback
    var exit = (GenomeConfig.type != 'update' || result.error != undefined);
    // Callback on first results
    if(typeof before == 'function') {
      before(result, exit);
    }

    // Exits if current configuration is not in 'update mode'
    if(exit) { return; }

    // Ajax validation against server API
    // Defines species fields
    var species_name = $('#species-name').val();
    var species_taxid = $('#species-taxid').val();
    var species_5code = $('#species-5code').val();

    // Checks value against database
    getSpecies({
      'name': species_name,
      'taxid': species_taxid,
      '5code': species_5code
    }).done(function(data){
      if(data.length <= 0) {
        result = {'error': 'Does not match any species currently on Orcae'};
      } else {
        result = true;
      }
    }).fail(function(data){
      result = {'warning': 'Unable to check value against db'};
    }).always(function(data){
      console.log('DATA: ', data);
      if(typeof after == 'function') {
        after(result);
      }
    });
  }

  /**
  * @mathod validateAll validates every field of the form
  * Every field is validated asynchronously, using callbacks
  * @param fields is an array in format {name: <name>, value: <value>}
  * @return true if every field is valid
  * @return false if falidation found at least 1 invalid field
  */
  validator.validateAll = function(fields, callback) {
    var self = this;
    // Defines a counter of valid and invalid validation results, which sum must be equal to fields length when triggering callback
    var len = fields.length;
    var valid = 0;
    var invalid = 0;

    // Checks every field
    // Array.fn.forEach must be used to avoid closure issues
    fields.forEach(function(e, i) {
     // Checks if current element is defined
     if(!e) { return; }
     // Defines current field name
     var field = e.name;
     // Defines current field value
     var value = e.value;

     // Defines field validation callback
     var validateCb = function(result) {
       // If field is valid, increases valid fields counter
       if(result === true || result.error == undefined) {
         valid++;
       }
       // If field is not valid, increases invalid fields counter
       else {
         invalid++;
       }

       // Defines if it is the last executed callback
       var last = (len == valid + invalid) ? (valid == len) : undefined;

       // Executes the callback
       // First callback param is the field
       // Second callback param is the result of validation
       // Third callback param is undefined if it is not last iteration
       // is true if all validated fields didn't return error
       // is false if at least 1 validated field returned error
       callback(field, result, last);
     }

     // Species fields
     if(field.match(/^(species_(name|taxid|5code))$/)) {
       self.validateSpecies(
         field,
         value,
         function(result, exit){
           // First callback is executed only if the second will not be executed
           if(exit) {
             validateCb(result);
           }
         },
         function(result){
           validateCb(result);
         });
     }
     // Default fields
     else {
       // Calls callback on default validation
       validateCb(self.validate(field, value));
     }
    });
  }

  return validator;
}
