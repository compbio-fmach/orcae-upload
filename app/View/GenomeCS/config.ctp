<?php
/**
 * This view shows a form where users can create, update and read the selected genome configuration session
 * @param subtitle 'genome config session' is passed to layout
*/
// Defines subtitle
$this->assign('subtitle', "Genome config session");
// Defines custom css
$this->Html->css('sessions_config', array('inline' => false));
// Loads ace text editor library (used for yaml files configuration) from external cdn
$this->Html->script('https://cdnjs.cloudflare.com/ajax/libs/ace/1.3.3/ace.js', array('inline' => false));
?>

<!-- Top navbar -->
<?php
  // Outputs top navbar. Param page specifies which state navbar must have.
  echo $this->element('navbar.top', array('page' => 'sessions_config'));
?>

<!-- Page main content (not displayed by default, must be initialized and showed using javascript) -->
<div class="container main">

  <!-- set form in center column -->
  <div class="row">
    <div class="col-md-8 offset-md-2">

      <!-- defines if this is a new genome insertion or an update -->
      <form class="section" id="session">

        <!-- title -->
        <h4 class="section-title">Select an action</h4>

        <div class="section-input"  id="session-type">
          <!-- Insert new genome -->
          <label class="custom-radio"> Add new genome to ORCAE
            <input class="input" type="radio" name="type" value="insert" checked>
            <span class="checkmark"></span>
          </label>
          <!-- Update an existent genome -->
          <label class="custom-radio"> Update an already existent genome in ORCAE
            <input class="input" type="radio" name="type" value="update">
            <span class="checkmark"></span>
          </label>
        </div>

      </form>

      <!-- Species information section -->
      <form class="section" id="species">

        <!-- title -->
        <h4 class="section-title">Species information</h4>

        <div class="section-item">
          <label for="species-name">Species name</label>
          <input type="text" class="form-control" id="species-name" name="species_name" maxlength="50" placeholder="Species' name">
          <small class="text-muted">Insert the name of the species you want to upload genome files to</small>
        </div>

        <!-- Multiple columns on one row: uses bootstrap grid -->
        <div class="row">
          <!-- NCBI taxid input -->
          <div class="col-md-6 section-item">
            <label for="species-taxid">Species NCBI taxonomy id</label>
            <input type="text" class="form-control" id="species-taxid" name="species_taxid" placeholder="Species' NCBI taxid">
            <small class="text-muted">Set <a href="https://www.ncbi.nlm.nih.gov/taxonomy">NCBI taxonomy id</a> of the current species</small>
          </div>
          <!-- Shortname input -->
          <div class="col-md-6 section-item">
            <label for="species-5code">Species short name</label>
            <input type="text" class="form-control" id="species-5code" name="species_5code" maxlength="5" placeholder="Species' short name">
            <small class="text-muted">It is a 5 digits code which represents this species</small>
          </div>
        </div>

        <div class="row">
          <!-- image for species selection -->
          <div class="col-md-6 section-item">
            <!-- input label -->
            <label for="species-image">Species image</label>
            <!-- image preview -->
            <div class="thumbnail rounded">
              <!-- background image (just a grey-colored div) shown when no image has been set -->
              <div class="bg-secondary"></div>
              <!-- image shown only if an image is set -->
              <img class='species-image-preview' id="species-image-preview" src="#" alt="">
            </div>
            <!-- image selector -->
            <div class="custom-file">
              <input type="file" class="custom-file-input" id="species-image-browser" name="species_image" accept=".jpg">
              <label class="custom-file-label" for="species-image">Choose file</label>
            </div>
            <!-- Info text -->
            <small class="text-muted">Image will be stretched or cropped to fit 155x155 px.</small>
          </div>
        </div>

      </form>

      <!-- This section handles user admin rights, messages and group-related things -->
      <form class="section" id="group">

        <h4 class="section-title">Configure user group</h4>

        <div class="section-item">
          <label for="group-description">Group description</label>
          <textarea class="form-control" id="group-description" name="group_description" maxlength="255" placeholder=""></textarea>
          <small class="text-muted">Enter the description of the group</small>
        </div>

        <div class="section-item">
          <label for="group-welcome">Group welcome text</label>
          <textarea class="form-control" id="group-welcome" name="group_welcome" rows="5" placeholder=""></textarea>
          <small class="text-muted">Specify the text that will be displayed on the ORCAE homepage for your species</small>
        </div>

      </form>

      <!-- This section contains configuration files -->
      <div class="section" id="config-files">

        <h4 class="section-title">Create configuration files</h4>

        <!-- yaml editor for orcae_bogas.yaml section -->
        <div class="section-item">
          <label for="group-welcome">orcae_bogas configuration section (.yaml)</label>
          <div class="ace-editor" id="config-file-orcae"></div>
        </div>

        <!-- yaml editor for orcae_<species' shortname>.yaml -->
        <div class="section-item">
          <label for="group-welcome">orcae_&lt;species' shortname&gt; configuration file (.yaml)</label>
          <div class="ace-editor" id="config-file-5code"></div>
        </div>

      </div>

      <!-- Save changes button. Id does not need a section -->
      <div class="section">
        <button class="btn btn-primary btn-lg btn-block section-item" id="save-session" type="button">Save &check;</button>
      </div>

    </div>
  </div>

</div>

<!-- Bottom navbar -->
<?php
  // Outputs bottom navbar. Param page specifies which state navbar must have.
  echo $this->element('navbar.bottom', array('page' => 'sessions_config'));
?>

<!-- Scripts -->
<?php
  echo $this->Html->script('genomecs-validator');
?>

<!-- Page configuration scripts -->
<script type="text/javascript">

  // Web root path (required for calling APIs)
  var webroot = <?php echo json_encode($this->webroot); ?>;

  // GenomeCS object representing current Configuration Session
  var genomecs = {
    // Retrieves id from controller
    id: <?php echo json_encode($this->params['id']); ?>
  }

  // Mantains reference to mostly used DOM elements
  var $main = undefined;
  var $sections = undefined;
  var $fields = undefined;

  // Creates new istance of session config validator
  var validator = new Validator();

  /**
   * Document initialization
   * Retreives genome config session from APIS
   * Sets the current view using retrieved configuration
   * The last thing that does is showing the form (after it is configured and loaded)
   */
  $(document).ready(function() {

    // Defines main form container
    $main = $('.main.container');
    // Defines fields of form
    $fields = $main
      // Session type checkboxes
      .find('#session-type [name=\'type\']')
      // Species information fields
      .add('#species [name^=\'species_\']')
      // Group configuration fields
      .add('#group [name^=\'group_\']')
      // YAML file editors
      .add('#config-files [id^=\'config-file-\']');

    // Initializes session type field
    $fields.filter('[name=\'type\']').each(function(){
      $(this).on('click', function() {
        // Calls default action on session type radio button click
        clickSessionType.call(this);

        // Triggers species name validation on type change
        var $species_name = $fields.filter('[name=\'species_name\']');
        $species_name.trigger('keyup');
      });
    });

    // Initailizes species name
    $fields.filter('[name=\'species_name\']').each(function(){
      $(this).on('keyup', keyupSpeciesName);
    });

    // Initializes species taxid and species 5code
    $fields.filter('[name=\'species_taxid\'], [name=\'species_5code\']').each(function() {
      $(this).on('keyup', keyupSpecies);
    });

    // Initializes species image field
    $fields.filter('[name=\'species_image\']').each(function(){
      $(this).on('change', changeSpeciesImage);
    });

    // Initializes group fields
    $fields.filter('[name^=\'group_\']').each(function(){
      $(this).on('keyup', keyupGroup);
    });

    // Initializes YAML editors as ace editors
    $fields.filter('[id^=\'config-file-\']').each(function(){
      // Defines ace editor
      var editor = ace.edit($(this).get(0));
      // Sets editors in .yaml mode
      editor.session.setMode('ace/mode/yaml');
      // Sets editor graphic properties
      editor.setOptions({
          minLines: 30,
          maxLines: Infinity
      });

      // Defines the default file to retrieve
      if($(this).attr('id') == 'config-file-orcae') {
        var file = 'config_orcae.default.yaml';
      }
      else {
        var file = 'config_species.default.yaml';
      }

      // Puts default data into editor
      // Default data is retrieved from editor
      $.ajax({
        url: webroot + 'API/defaults/' + file,
        method: 'GET'
      }).done(function(data){
        if(!editor.getValue()) {
          editor.setValue(data, -1);
        }
      });
    });

    // Initializes save button event on click
    $('#save-session').on('click', clickSaveButton);

    // Retrieves GenomeCS from database and puts them into form
    $.ajax({
      url: webroot + 'API/genomecs/' + genomecs.id,
      method: 'GET',
      dataType: 'json'
    })
    .done(function(data){
      genomecs = data;
    })
    .fail(function(data){
      genomecs.id = undefined;
    })
    .always(function(data){
      // Loads data into form
      loadConfigSession(data);

      // If GenomeCS is set, triggers validation
      if(genomecs.id != undefined) {
        validateConfigSession(function(){});
      }

      // Shows form
      $main.fadeIn('slow');
    });
  });

  /**
   * @function loadForm loads Genomecs data into form
   * Retrieves values from ajax request using retrieveGenomeCS
   * Firstly: initializes DOM fields that requires special initialization
   * Lastly: initializes common DOM fields
   * @param data is the GenomeCS istance returned by the server
   * @return void
   */
  function loadConfigSession(data) {
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
   * @function serializeConfigSession serializes values of session config page's form into FormData
   * @return data as FormData
   */
  function serializeConfigSession() {
    // Data serialized to be returned
    var data = new FormData();

    // Appends session type to data
    data.append('type', $('#session-type [name=\'type\']:checked').val());

    $fields
      // Appends species info to data
      .filter('[name^=\'species_\']')
      // Appends grioup info to data
      .add('[name^=\'group_\']')
      .each(function(){
        data.append($(this).attr('name'), $(this).val());
      });

    // Appends YAML files configuration to data
    data.append('config_species', ace.edit($('#config-file-5code').get(0)).getValue());
    data.append('config_orcae', ace.edit($('#config-file-orcae').get(0)).getValue());

    // Appends image file to configuration data, if an image is set
    var species_image = $('#species [name=\'species_image\']').get(0).files[0];
    if(species_image) {
      data.append('species_image', species_image);
    } else {
      data.delete('species_image');
    }

    /*
    // DEBUG
    for (var value of data.values()) {
       console.log(value);
    }
    */

    return data;
  }

  /**
   * @function saveSessionConfig saves form data against server API
   * @return void
   */
  function saveConfigSession(data) {
    // Sends data to /API/sessions/:sid/config using POST method
    return $.ajax({
      url: webroot + 'API/genomecs/' + (genomecs.id ? genomecs.id : ''),
      method: 'POST',
      processData: false,
      contentType: false,
      data: data,
      dataType: 'json'
    });
  }

  /**
   * @function retrieveSpeciesInfo searches for species into orcae_bogas.taxid table
   * @param params is an array of {name: <param name>, value: <param value>} which is built as querystring befor sending it
   * @return $.ajax object which implements .done(), .faliure() and .always() methods
   */
  function retrieveSpecies(params) {
    return $.ajax({
      url: webroot + 'API/species',
      method: 'GET',
      data: params,
      dataType: 'json'
    });
  }

  /**
   * @method for jQuery element
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
   * @method for jQuery element
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
   * @method for jQuery element
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
    * @function validate validates a field
    * @param field states which field is validated
    * @param value specifies which valued is validated
    * @param callback is the callback executed on validation result
    * @return void
    */
   function validate(field, value, callback) {
     // Forces asynchronous execution of the validation
     // This way, every validatios works in the same way (no need to handle synchronous and asynchronous validation)
     return setTimeout(function(){
       // Validates the field locally
       var valid = validator.validate(field, value);
       // Executes callback passing validation results as parameter
       callback(valid);
     }, 0);
   }

   /**
    * @function validateSpeciesName validates asynchronously species name field
    * @param value is the species_name field value
    * @param callback is the callback executed on validation results
    * @param delay sets a delay between local and server validation
    * @return timeout which delays ajax request
    */
   function validateSpeciesName(value, callback, delay = 0) {
     // Defines object to be returned
     var delayed = undefined;

     // Default validation
     validate('species_name', value, function(result) {

       // Exits the function if local validation returned an error
       if(result !== true && result.error != undefined) {
         // Executes callback on local validation result
         callback(result);
         // Exits execution flow
         return delayed;
       }

       // Checks session type
       if(!genomecs || genomecs.type != 'update') {
         // Executes callback on local validation result
         callback(result);
         // Exits execution flow
         return delayed;
       }

       // Assigne timeout id to return value
       delayed = setTimeout(function() {
         // Validates species name congruency against database
         retrieveSpecies({name: value, limit: 1})
         // Ajax success callback
         .done(function(data){
           // Retrieves species from json data
           var species = (data.length > 0) ? data[0] : undefined;
           // Case species name best match has been found
           // Executes callback on best match value
           var result = {error: "Does not match any species name"}
           // Adds species found to result
           result.species = species;
           callback(result);
         })
         // Ajax failure callback
         .fail(function(data){
           // Pass a generic server-side warning (does not block execution) to callback
           callback({warning: "Unable to check species name"});
         });
       }, delay);

     });

     return delayed;
   }

   /**
    * @function validateAll validates every field of form
    * Changes validation procedure if session.type changes
    * Every validation is treated as asynchronous
    * This means that a callback to set the return value of the function must be returned
    * Last executed function must check value of the others
    * @param fields is an array in format {name: <name>, value: <value>}
    * @return true if every field is valid
    * @return false if falidation found at least 1 invalid field
    */
   function validateAll(fields, callback) {
     // Defines a counter of valid and invalid validation results, which sum must be equal to fields length when triggering callback
     var len = fields.length;
     var valid = 0;
     var invalid = 0;

     // Checks every field
     // Array.fn.forEach must be used to avoid closure issues
     fields.forEach(function(e, i) {
       // Defines current field name
       var field = e.name;
       // Defines current field value
       var value = e.value;

       // Defines field validation callback
       var validate_cb = function(result) {
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

       // Case field is species_name
       if(field == 'species_name') {
         validateSpeciesName(value, function(result){
           // Checks if best match is the same as value
           if(result.species && value == result.species.organism) {
             result = true;
           }

           // Passes changed result to callback
           validate_cb(result);
         });
       } else {
         validate(field, value, validate_cb);
       }
     });
   }

   /**
    * @function validateConfigSession is a wrapper of validateAll function
    */
   function validateConfigSession(callback) {

     // Defines an array of validable fields
     var validables = new Array();
     // Loops validables $fields defined on document ready
     $fields.filter('[name][name!=\'\']').each(function(){
       validables.push({name: $(this).attr('name'), value: $(this).val()});
     });

     // Validates every validable field
     validateAll(validables, function(field, result, last) {
       // Retrieves field using name
       var $field = $fields.filter('[name=\'' + field + '\']');
       // Validates retrieved field
       $field.validate(result);

       // Executes only for the last callback
       if(last !== undefined) {
         callback(last);
       }
     });
   }

   /**
    * @function clickSessionType changes the screen based on session's type value
    * If type equals 'update', only species info must be shown
    * If type equals 'insert', all form must be displayed
    */
   function clickSessionType() {
     // Saves a reference to radio button
     var $rb = $(this);

     // Continues execution only if this is the checked radio button
     if(!$rb.prop('checked')) return;

     // Transition time: sets how much time must fadeIn/fadeOut take
     // If it executed before page content is displayed, it doesn't need animation
     var tt = ($main.css('display') == 'none') ? 0 : 'slow';

     var $sections = $main
      .find('.section#group')
      .add('.section#config-files');

     // Case 'update': displays only species information
     if($rb.val() == 'update') {
       // Updates session.type
       genomecs.type = 'update';
       // Applies visible session type changes
       $sections.not('[style*=\'display: none\']').fadeOut(tt);
     }
     // Case 'insert': display all session's information
     else {
       // Updates session.type
       genomecs.type = 'insert';
       // Applies visible session type changes
       $sections.filter('[style*=\'display: none\']').fadeIn(tt);
     }
   }

   function keyupSpeciesName() {
     // Saves this element's reference
     var $self = $(this);

     // Clears previously set timeout (set into this field)
     clearTimeout($.data($self.get(0), 'validation'));

     // Sets new timeout
     var timeout = validateSpeciesName(
       $self.val(),
       function(result) {
         // Defines the species which has been retrieved from API response
         var species = undefined;

         // Checks if a successful response has been thrown (format is {success: <species>})
         // Puts returned species into $.data of '#species-name'
         if(result.species != undefined) {
           species = result.species;
         }

         // Puts or removes species into species name field
         if(species) {
           $self.data('species', species);
         } else {
           $self.removeData('species');
         }

         // If there isn't any error, checks if species name matches
         if(species) {
           if(species.organism == $self.val()) {
             result = true;
           }
         }

         // Validates result
         $self.validate(result);

         // Deletes previously set hints
         $self.siblings('.species-name-hint').remove();
         // Appends hint if similar species name has been found
         if(species && species.organism != $self.val()) {
           $self.after($('<small/>', {
             class: 'text-primary species-name-hint'
           }));
         }

         if(species) {
           // TODO: trigger species-taxid and species-5code validation based on species-name data
           $fields.filter('[name=\'species_taxid\'], [name=\'species_5code\']').trigger('keyup');
         }

       },
       500
     );
   }

   function keyupSpecies() {

     // Validates throught standard validator
     var result = validator.validate($(this).attr('name'), $(this).val());

     if(genomecs && genomecs.type == 'update') {
       // Retreievs best matching element
       var species = $fields.filter('[name=\'species_name\']').data('species');

       // Retrieves field name
       var name = $(this).attr('name');

       if(!result.error) {
         if(name == 'species_taxid') {
           if(!(species && species['NCBI_taxid'] == $(this).val())) {
             result = {error: "Species taxonomy id does not match given species name"};
           }
         }
         else if(name == 'species_5code') {
           if(!(species && species['5code'] == $(this).val())) {
             result = {error: "Species shortname does not match given species name"};
           }
         }
       }
     }

     $(this).validate(result);
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

   function keyupGroup() {
     $(this).validate();
   }

   /**
    * @function clickSaveButton defines actions executes on save button click
    * First checks if it is enabled (should be enabled only when there isn't any error)
    * If it is enabled: serializes form data, sends it to server, executes action on result
    */
   function clickSaveButton(){
     // Saves button reference
     var $button = $(this);
     var $section = $button.closest('.section');

     // Substitutes every previous message with "Validating..."
     $section.siblings('#save-message').remove();
     $section.before($('<div/>', {
       id: 'save-message',
       class: 'section alert alert-secondary',
       role: 'alert',
       text: 'Validating...'
     }));

     // Disables every input field
     $fields.each(function(){
       $(this).prop('disabled', true);
     });
     // Disables button
     $button.prop('disabled', true);

     // First of all: validates every field
     validateConfigSession(function(result){

       // Case error during validation re enables all fields and button, then stops saving execution
       if(!result) {
         // Disables every input field
         $fields.each(function(){
           $(this).prop('disabled', false);
         });
         // Disables button
         $button.prop('disabled', false);
         // Shows error message
         $section.siblings('#save-message')
          .removeClass('alert-secondary')
          .addClass('alert-danger')
          .text('There are some errors which prevent changes from being saved correctly');
         return;
       }

       // Case validation successful
       // Serializes data
       var data = serializeConfigSession();
       // Saves data
       saveConfigSession(data)
        .done(function(data){
          $section.siblings('#save-message')
           .removeClass('alert-secondary')
           .addClass('alert-primary')
           .text('Data saved correctly');
         })
         // Checks errors during saving
         .fail(function(data){
           $section.siblings('#save-message')
            .removeClass('alert-secondary')
            .addClass('alert-danger')
            .text('Some errors prevent changes from being saved correctly, check the form');
         })
         // After result retrieved
         .always(function(data){
           $fields.each(function(){
             $(this).prop('disabled', false);
           });
           $button.prop('disabled', false);
         });
     });
   }
</script>
