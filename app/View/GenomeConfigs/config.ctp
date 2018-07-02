<?php
  $this->assign('subtitle', 'Edit Genome Configuration');
  $this->Html->css('pages/genome_configs/common', array('inline' => false));
  $this->Html->css('pages/genome_configs/config', array('inline' => false));
  $this->Html->script('https://cdnjs.cloudflare.com/ajax/libs/ace/1.3.3/ace.js', array('inline' => false));
  $this->Html->script('scripts/validator', array('inline' => false));
  $this->Html->script('scripts/genome_updates', array('inline' => false));
  // Loads bootstrap 3 typeahead (handles suggestions under text input fields)
  $this->Html->script('typeahead/bootstrap3-typeahead.js', array('inline' => false));
  // Loads bootstrap 4 style for bootstrap 3 typeahead
  $this->Html->css('typeahead.css', array('inline' => false));
  // Loads page's scripts
  $this->Html->script('pages/genome_configs/config', array('inline' => false));
?>

<?php
  // Outputs top navbar. Param page specifies which state navbar must have.
  echo $this->element('navbar.top', array('page' => 'config'));
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
          <input id="species-name" type="text" class="form-control typeahead" name="species_name" autocomplete="off" maxlength="50" placeholder="Species name">
          <small class="text-muted">Insert the name of the species you want to insert or update</small>
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
            <!-- <div class="custom-file">
              <input type="file" class="custom-file-input" id="species-image-browser" name="species_image" accept=".jpg">
              <label class="custom-file-label" for="species-image">Choose file</label>
            </div> -->
            <input type='file' id='species-image' name='species_image' value='' accept='.png, .jpg, .jpeg'>
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
          <div class="ace-editor" id="config-file-bogas"></div>
        </div>

        <!-- yaml editor for orcae_<species' shortname>.yaml -->
        <div class="section-item">
          <label for="group-welcome">orcae_&lt;species' shortname&gt; configuration file (.yaml)</label>
          <div class="ace-editor" id="config-file-species"></div>
        </div>

      </div>

      <!-- This contains error messages -->
      <div class="section" id="messages"></div>

      <!-- Save changes button. Id does not need a section -->
      <div class="section" id="actions">
        <div class="row">
          <div class="col-12">
            <!-- <button class="btn btn-primary btn-block " id="save-genome-config" type="button" disabled>Save unavaiable: updating Orcae</button> -->
            <button class="btn btn-primary btn-block " id="save-genome-config" type="button">Save</button>
          </div>
        </div>
        <div class="row">
          <div class="offset-6 col-6">
            <a class="btn btn-outline-primary btn-block" href="./uploads/" id="go-to-uploads" style="display:none">Go to uploads</a>
          </div>
        </div>
      </div>

    </div>
  </div>

</div>

<?php
  // Outputs bottom navbar. Param page specifies which state navbar must have.
  // echo $this->element('navbar.bottom', array('page' => 'config'));
?>

<!-- Page configuration scripts -->
<script type="text/javascript">
  // Initializes GenomeConfig
  if(typeof GenomeConfig == 'undefined') {
    var GenomeConfig = {};
  }
  // Puts data into GenomeConfig object
  GenomeConfig.data = {
    id: <?php echo json_encode($id); ?>
  }
</script>
