<?php
  // this page shows the form which replaces AddNewGenome.pl
  // it creates a genome-related session

  // set current page title
  $this->assign('title', 'Configure genome');
  // set css
  $this->Html->css('common', array('inline' => false));
  // set js libraries
  $this->Html->script('fileHandler', array('inline' => false));

  // navbar
  echo $this->element('navbar.top', array('active' => 'sessions'));
?>

<!-- main container -->
<div class="container pt-3">

  <!-- title -->
  <h4 class="mb-3">Species information</h4>

  <form>
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
    <label for="speciesImage">Species' image</label>
    <div class="custom-file">
      <label for="speciesImage">Species' image</label>
      <input type="file" class="custom-file-input" id="speciesImage" onchange="fileBrowser(this)">
      <label class="custom-file-label" for="speciesImage">Choose file</label>
    </div>

  </form>

</div>
