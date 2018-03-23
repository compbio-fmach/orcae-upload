<?php
  // this page shows the form which replaces AddNewGenome.pl
  // it creates a genome-related session

  // set current page title
  $this->assign('title', 'Configure genome');
  // set css
  $this->Html->css('orcae-upload', array('inline' => false));

  // set js libraries
  $this->Html->script('fileHandler', array('inline' => false));
  // select2 js + css library
  $this->Html->css('https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css', array('inline' => false));
  $this->Html->script('https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js', array('inline' => false));

  // navbar
  echo $this->element('navbar.top', array('active' => 'configure'));
?>

<!-- main container -->
<div class="container pt-3" style="padding-bottom: 112px;">
  <div class="row">
    <div class="col-md-8 offset-md-2">

      <form>
        <!-- section title -->
        <h4 class="mb-3">Species information</h4>

        <div class="mb-3">
          <label for="speciesName">Species' name</label>
          <input type="text" class="form-control" id="speciesName" placeholder="Species' name">
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="speciesTaxid">Species' NCBI taxid</label>
            <input type="text" class="form-control" id="speciesTaxid" placeholder="Species' NCBI taxid">
          </div>
          <div class="col-md-6 mb-3">
            <label for="species5code">Species' short name</label>
            <input type="text" class="form-control" id="species5code" placeholder="Species' short name">
            <small class="text-muted">It is a 5 digits code which represents this species</small>
          </div>
        </div>

        <!-- image selection -->
        <div class="mb-3">
          <label for="speciesImage">Species' image</label>
          <div class="custom-file">
            <input type="file" class="custom-file-input" id="speciesImage" onchange="fileBrowser(this)">
            <label class="custom-file-label" for="speciesImage">Choose file</label>
          </div>
        </div>

        <!-- section title -->
        <h4 class="mb-3">Handle group and access rights</h4>

        <div class="mb-3">
          <label for="groupDescription">Group description</label>
          <textarea class="form-control" id="groupDescription" placeholder=""></textarea>
          <small class="text-muted">Enter the description of the group</small>
        </div>

        <div class="mb-3">
          <label for="groupWelcome">Group welcome text</label>
          <textarea class="form-control" id="groupWelcome" rows="5" placeholder=""></textarea>
          <small class="text-muted">Specify the text that will be present on the ORCAE homepage for your species</small>
        </div>

        <div class="mb-3">
          <label for="groupMembers">Group memebers</label>
          <!-- element used for select2 generation -->
          <select id="groupMembers" class="select2Base" name="groupMembers[]" multiple="multiple">
            <option value="AL">Alabama</option>
            <option value="WY">Wyoming</option>
          </select>
          <!-- select2 generation -->
          <script type="text/javascript">
            // select2 needs to be executed before the page is loaded
            // this way bad visual effects due to component transformation are avoided
            $('#groupMembers.select2Base').select2({
              tags: true,
              width: '100%'
            });
          </script>
        </div>
      </form>

      <!-- save button -->
      <button class="btn btn-primary btn-lg btn-block" type="button">Save &check;</button>

    </div>
  </div>



</div>

<?php
  // bottom navbar
  echo $this->element('navbar.bottom', array('current' => 'configure'));
?>

<!-- scripts are at the bottom of the body -->
