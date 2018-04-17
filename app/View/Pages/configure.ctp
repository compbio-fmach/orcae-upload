<?php
  // this page shows the form which replaces AddNewGenome.pl
  // it creates a genome-related session

  // set current page title
  $this->assign('title', 'Configure genome');
  // set css
  $this->Html->css('configure', array('inline' => false));

  // set js libraries
  $this->Html->script('file-handler', array('inline' => false));
  // select2 js + css library
  //$this->Html->css('https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css', array('inline' => false));
  //$this->Html->script('https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js', array('inline' => false));

  // ace text editor (used for yaml files) imported from cdn
  $this->Html->script('https://cdnjs.cloudflare.com/ajax/libs/ace/1.3.3/ace.js', array('inline' => false));

  // navbar top
  echo $this->element('navbar.top', array('current' => 'configure'));
?>

<!-- main container -->
<div class="container main">

  <!-- There is the main form of this page. It is composed of many sections (forms or divs). Ervery section has its own form to be serialized trough js -->

  <!-- set form in center column -->
  <div class="row">
    <div class="col-md-8 offset-md-2">

      <!-- defines if this is a new genome insertion or an update -->
      <form class="section" id="action-choice">

        <!-- title -->
        <h4 class="section-title">Select an action</h4>

        <div class="section-paragraph">
          <!-- Insert new genome -->
          <label class="custom-radio"> Add new genome to ORCAE
            <input class="input" type="radio" name="action-choosen" value="new" checked>
            <span class="checkmark"></span>
          </label>
          <!-- Update an existent genome -->
          <label class="custom-radio"> Update an already existent genome in ORCAE
            <input class="input" type="radio" name="action-choosen" value="update">
            <span class="checkmark"></span>
          </label>
        </div>

      </form>

      <!-- Species information section -->
      <form class="section" id="species-info">

        <!-- title -->
        <h4 class="section-title">Species information</h4>

        <div class="section-paragraph">
          <label for="species-name">Species' name</label>
          <input type="text" class="form-control" id="species-name" placeholder="Species' name">
        </div>

        <!-- Multiple columns on one row: uses bootstrap grid -->
        <div class="row">

          <!-- NCBI taxid input -->
          <div class="col-md-6 section-paragraph">
            <label for="species-taxid">Species' NCBI taxid</label>
            <input type="text" class="form-control" id="species-taxid" placeholder="Species' NCBI taxid">
          </div>

          <!-- Shortname input -->
          <div class="col-md-6 section-paragraph">
            <label for="species5code">Species' short name</label>
            <input type="text" class="form-control" id="species-5code" placeholder="Species' short name">
            <small class="text-muted">It is a 5 digits code which represents this species</small>
          </div>

        </div>

        <div class="row">
          <!-- image for species selection -->
          <div class="col-md-6 section-paragraph">
            <!-- input label -->
            <label for="species-image">Species' image</label>
            <!-- image preview -->
            <div class="thumbnail rounded">
              <div class="bg-secondary"></div>
              <img src="..." alt="">
            </div>
            <!-- image selector -->
            <div class="custom-file">
              <input type="file" class="custom-file-input" id="species-image" accept=".jpg, .png, .gif">
              <label class="custom-file-label" for="species-image">Choose file</label>
            </div>
            <small class="text-muted">Image will be stretched or cropped to fit 155px x 155px.</small>
          </div>
        </div>

      </form>

      <!-- This section handles user admin rights, messages and group-related things -->
      <form class="section" id="group-info">

        <h4 class="section-title">Handle group and access rights</h4>

        <div class="section-paragraph">
          <label for="group-description">Group description</label>
          <textarea class="form-control" id="group-description" placeholder=""></textarea>
          <small class="text-muted">Enter the description of the group</small>
        </div>

        <div class="section-paragraph">
          <label for="group-welcome">Group welcome text</label>
          <textarea class="form-control" id="group-welcome" rows="5" placeholder=""></textarea>
          <small class="text-muted">Specify the text that will be present on the ORCAE homepage for your species</small>
        </div>

      </form>

      <!-- This section contains configuration files -->
      <div class="section" id="config-files">

        <h4 class="section-title">Create configuration files</h4>

        <!-- yaml editor for orcae_bogas.yaml section -->
        <div class="section-paragraph">
          <label for="group-welcome">orcae_bogas configuration section (.yaml)</label>
          <div class="ace-editor" id="config-file-bogas"></div>
        </div>

        <!-- yaml editor for orcae_<species' shortname>.yaml -->
        <div class="section-paragraph">
          <label for="group-welcome">orcae_&lt;species' shortname&gt; configuration file (.yaml)</label>
          <div class="ace-editor" id="config-file-5code"></div>
        </div>

      </div>

      <!-- Save chnages button. Id does not need a section -->
      <button class="btn btn-primary btn-lg btn-block section-paragraph" id="button-save" type="button">Save &check;</button>

    </div>
  </div>

</div>

<?php
  // bottom navbar
  echo $this->element('navbar.bottom', array('current' => 'configure'));
?>

<!-- Script for this page only -->
<script type="text/javascript">

  // functions on document initialization
  $(document).ready(function(){

    //initializes file browsing and image preview for species' image
    $('#species-image').change(function(e){
      // enable correct file name visualization into input field
      // fileBrowser(...) is defined in file-handler.js
      fileBrowser(this);
      // previews image
      $(this)
        // goes up to closest section paragraph
        .closest('.section-paragraph')
        // goes down to find image
        .find('img')
        // changes src attribute of image
        // it uses a 64-bit encoded version of the image (loaded into browser)
        .attr('src', window.URL.createObjectURL(this.files[0]));
    });


    // initializes ace editors (for yaml files)
    $('.ace-editor').each(function(i){
      // initialization (plain js element is needed for ace editor)
      var editor = ace.edit($(this).get(0));
      // mode (for yaml files editing)
      editor.session.setMode("ace/mode/yaml");
      // options
      editor.setOptions({
          minLines: 30,
          maxLines: Infinity
      });
    });

  });
</script>