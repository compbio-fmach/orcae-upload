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
      <form class="section" id="session-type">

        <!-- title -->
        <h4 class="section-title">Select an action</h4>

        <div class="section-input">
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
      <form class="section" id="species-info">

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
              <img id='species-image-preview' src="#" alt="">
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
      <form class="section" id="group-info">

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

      <!-- Save chnages button. Id does not need a section -->
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

<!-- Validator script -->
<?php
  echo $this->Html->script('sessions_config_validator');
?>

<!-- Page configuration scripts -->
<script type="text/javascript">

  // Web root path (required for calling APIs)
  var webroot = <?php echo json_encode($this->webroot); ?>;

  // Session object representing current session
  var session = {
    // Uses id passed by controller
    id: <?php echo json_encode($this->params['id']); ?>
  }

  // Mantains reference to page main container
  var $main = undefined;

  // Mantains a reference to validable fields
  var $validables = undefined;

  // Mantains a reference to .yaml files editors
  var $editor_orcae = undefined;
  var $editor_5code = undefined;

  // Creates new istance of session config validator
  var validator = new SessionConfigValidator();

  /**
   * Document initialization
   * Retreives genome config session from APIS
   * Sets the current view using retrieved configuration
   * The last thing that does is showing the form (after it is configured and loaded)
   */
  $(document).ready(function() {

    // Initializes main container
    $main = $('.main.container');

    // Defines the list fields which can be validated
    $validables = $main.find('input[name][name!=\'type\'][name!=\'species_image\'], textarea[name][name!=\'\']');

    // Initializes editors using Ace editor
    $editor_orcae = ace.edit($('#config-file-orcae').get(0));
    $editor_5code = ace.edit($('#config-file-5code').get(0));
    // Sets common editors properties
    [$editor_orcae, $editor_5code].forEach(function($editor) {
      // Defines editor id
      var id = $($editor.container).attr('id');

      // Sets editors in .yaml mode
      $editor.session.setMode('ace/mode/yaml');
      // Sets editor graphic properties
      $editor.setOptions({
          minLines: 30,
          maxLines: Infinity
      });

      // Defines default file to be retrieved
      switch(id) {
        // Case orcae bogas yaml file
        case 'config-file-orcae':
          initEditorDefault($editor, 'config_orcae.default.yaml');
          break;
        // Case genome configuration yaml file
        case 'config-file-5code':
          initEditorDefault($editor, 'config_species.default.yaml');
          break;
        // Case no default file found
        default: break;
      }
    });

    // Initializes image field change action
    $('#species-image-browser').on('change', changeSpeciesImage);
    // Initializes session type switching
    $('#session-type [name=\'type\']').on('click', changeSessionType);

    // Initializes session default validation for inputs with have a name set
    $validables.filter('[name!=\'species_name\']').on('keyup', function(){
      var field = $(this).attr('name');
      var value = $(this).val();
      validate(field, value, function(result) {
        $(this).validate(result);
      });
    });

    // Initializes validation on species_name field
    $validables.filter('[name=\'species_name\']').on('keyup', function(){
      // Saves this element's reference
      var $self = $(this);
      // Clears previously set timeout (set into this field)
      clearTimeout($.data($self.get(0), 'validation'));
      // Sets new timeout
      var timeout = validateSpeciesName(
        // First parameter is this input's value
        $self.val(),
        // Second parameter is the function that will be executed after validation
        function(result) {
          $self.validate(result);
        },
        // Third parameter is delay of ajax request (optimization of server requests)
        500
      );

      // Overrides older timeout into input
      $self.data('validation', timeout);
    });

    // Defines a function to be executed on save button click
    $('button#save-session').on('click', clickSaveButton);

    // Retrieves session id
    retrieveSessionConfig(session.id)
      // On success: sets session as returned value
      .done(function(data){
        console.log('DONE ', data);
        session = data;
      })
      // On error: deletes session values
      .fail(function(data){
        // Case error happened, does not set session
        session.id = undefined;
        console.log('ERROR ', data);
      })
      // Initializes view with returned values
      .always(function(){
        // Initializes session configuration form retrieved session value
        initSessionConfig(session);
        // Shows initialized form
        $main.fadeIn('slow');
      });
  });

  /**
   * @function retrieveSessionConfig
   * Retrieves session config using session.id
   * It is not aynchronous, because it must terminate before every other inizialization function are executed
   * @param done is the function executed after
   */
  function retrieveSessionConfig(id) {
    // Sends a GET request to /API/genomecs/:id
    return $.ajax({
      // Creates correct url
      url: webroot + 'API/genomecs/' + session.id,
      method: 'GET',
      dataType: 'json'
    });
  }

  /**
   * @function initSessionConfig
   * Initializes session into view
   * @param session specifies the session to initialize
   * @return void
   */
  function initSessionConfig(_session) {
    // Copy session by value, this way it can be modified without modifying original object
    var session = Object.assign({}, _session);

    // Initializes into dom values that need a special elaboration
    // then deletes those values in order to not initialize their DOM elements lately, for the second time

    // Initializes config_orcae.yaml editor's values
    if (session.config_orcae != undefined) {
      $editor_orcae.setValue(session.config_orcae, -1); // Sets editor's content (-1 sets for cursor position)
      delete session.config_orcae; // Deletes value from object (prevents value from being initialized lately)
    }

    // Initializes config_species.yaml editor's value
    if (session.config_species != undefined) {
      $editor_5code.setValue(session.config_species, -1); // Sets editor's content (-1 sets for cursor position)
      delete session.config_species; // Deletes value from object (prevents value from being initialized lately)
    }

    // Initializes species image (passed as url to image)
    if(session.species_image != undefined) {
      // Initialize image field
      $('#species-image-preview').attr('src', session.species_image);
      delete session.species_image;
    }

    // Sets default session type type
    if(session.type == undefined) session.type = 'insert';
    // Initializes session type
    $main.find('[name=\'type\']').prop('checked', false);
    if(session.type == 'insert') {
      $main.find('[name=\'type\'][value=\'insert\']').prop('checked', true);
    } else if(session.type == 'update') {
      $main.find('[name=\'type\'][value=\'update\']').prop('checked', true);
    }
    // Calls form initialization based on choosen session type value
    $.each($main.find('[name=\'type\']:checked'), changeSessionType);
    // Prevents session type later inizialization
    delete session.type;

    // Almost every session attribute in DOM has the same name of session object's attribute
    for(var attr in session) {
      // sets value in DOM element bound to attribute's name
      $main.find('[name=\'' + attr + '\']').val(session[attr]);
    }
  }

  /**
   * @function serializeSessionConfig serializes values of session config page's form into FormData
   * @return data as FormData
   */
  function serializeSessionConfig() {
    // Data serialized to be returned
    var data = new FormData();

    // Appends session type to data
    data.append('type', $('#session-type [name=\'type\']:checked').val());

    // Appends species info to data
    $('#species-info').serializeArray().forEach(function(e, i) {
      data.append(e.name, e.value);
    });

    // Appends group info to data
    $('#group-info').serializeArray().forEach(function(e, i){
      data.append(e.name, e.value);
    });

    // Appends yaml files configuration to data
    data.append('config_species', $editor_5code.getValue());
    data.append('config_orcae', $editor_orcae.getValue());

    // Appends image file to configuration data, if an image is set
    var species_image = $('#species-image-browser').get(0).files[0];
    if(species_image) {
      data.append('species_image', species_image);
    }

    return data;
  }

  /**
   * @function saveSessionConfig saves form data against server API
   * @return void
   */
  function saveSessionConfig(data) {
    // Sends data to /API/sessions/:sid/config using POST method
    return $.ajax({
      url: webroot + 'API/genomecs/' + (session.id ? session.id : ''),
      method: 'POST',
      processData: false,
      contentType: false,
      data: data,
      dataType: 'json'
    });
  }

  /**
   * @function defaultEditorValue sets default config file into editor
   * @param $editor is the ace editor which will be filled with default value retrieved from server
   * @param file is the file name to request to the server
   * @return void
   */
  function initEditorDefault($editor, file) {
    // makes request of default config file
    $.ajax({
      url: webroot + 'API/defaults/' + file,
      method: 'GET'
    })
    .fail(function(data){
      console.log('Error while retrieving default file', data);
    })
    .done(function(data){
      // Case server returned http code '200 OK' and editor is empty: puts value into form editor
      // In case editor is empty but there is a value already set in resource that will later be returned from the server,
      // the currently set value will howver be overwritten before form fadeIn
      if(!$editor.getValue()) {
        $editor.setValue(data, -1);
      }
    });
  }

  /**
   * @function changeSessionType changes the screen based on session's type value
   * This function is passed as parameter of session type DOM element (radio buttons) on click
   * If type equals 'update', only species info must be shown
   * If type equals 'insert', all form must be displayed
   */
  function changeSessionType() {
    // Saves a reference to radio button
    var $rb = $(this);

    // Continues execution only if this is the checked radio button
    if(!$rb.prop('checked')) return;

    // Transition time: sets how much time must fadeIn/fadeOut take
    // If it executed before page content is displayed, it doesn't need animation
    var tt = ($main.css('display') == 'none') ? 0 : 'slow';

    // Case 'update': displays only species information
    if($rb.val() == 'update') {
      // Updates session.type
      session.type = 'update';
      // Applies visible session type changes
      $('.section#group-info:not([style*=\'display: none\']), .section#config-files:not([style*=\'display: none\'])').fadeOut(tt);
    }
    // Case 'insert': display all session's information
    else {
      // Updates session.type
      session.type = 'insert';
      // Applies visible session type changes
      $('.section#group-info[style*=\'display: none\'], .section#config-files[style*=\'display: none\']').fadeIn(tt);
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
   * @function clickSaveButton defines actions executes on save button click
   * First checks if it is enabled (should be enabled only when there isn't any error)
   * If it is enabled: serializes form data, sends it to server, executes action on result
   */
  function clickSaveButton() {

    console.log('ok');
    // Defines an array of validable fields
    var validables = new Array();
    // Loops $validabels field defined on document ready
    $validables.each(function(){
      validables.push({ name: $(this).attr('name'), value: $(this).val() });
    });

    // Validates every validable field
    validateAll(validables, function(field, result, last) {
      // Retrieves field using name
      var $field = $main.find('[name=\'' + field + '\']');
      // Validates retrieved field
      $field.validate(result);

      // Executes only for the last callback
      if(last !== undefined) {
        // Case validation was successful
        if(last) {
          // Retrieves data from form
          var data = serializeSessionConfig();
          var message = '';
          // Sends to database
          saveSessionConfig(data)
          // Fields saved correctly
          .done(function() {
            message = 'Configuration saved';
          })
          // Fields not saved
          .fail(function() {
            message = data;
          })
          // Executed always
          .always(function() {
            // Re-enables save button
            // Throws result message
            console.log(message);
          });
        }
        // Case validation found at least one error
        else {
          // Re-enables save button
          // Throws error message
        }
      }
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
       if(session.type != 'update') {
         // Executes callback on local validation result
         callback(result);
         // Exits execution flow
         return delayed;
       }

       // Assigne timeout id to return value
       delayed = setTimeout(function() {
         // Validates species name congruency against database
         retrieveSpecies()
         // Ajax success callback
         .done(function(data){
           // Retrieves species from json data
           var species = (data.length > 0) ? data[0] : undefined;
           // Case species name equals specified value
           if(species && species.name == value){
             callback(result);
           }
           // Case species does not match specified value
           else {
             callback({error: "Does not match any species name"});
           }
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
         validateSpeciesName(value, validate_cb);
       } else {
         validate(field, value, validate_cb);
       }
     });
   }
</script>
