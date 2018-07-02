<?php
// Defines subtitle
$this->assign('subtitle', "Genome Files Upload");
$this->Html->css('pages/genome_configs/common', array('inline' => false));
$this->Html->css('pages/genome_configs/uploads', array('inline' => false));
// Chunk upload API
$this->Html->script('fileuploader/vendor/jquery.ui.widget.js', array('inline' => false));
$this->Html->script('fileuploader/jquery.iframe-transport.js', array('inline' => false));
$this->Html->script('fileuploader/jquery.fileupload', array('inline' => false));

$this->Html->script('scripts/genome_updates', array('inline' => false));
$this->Html->script('pages/genome_configs/uploads', array('inline' => false));
?>

<!-- Navbar top -->
<?php echo $this->element('navbar.top', array('page' => 'uploads')); ?>

<!-- Main container -->
<div class="main container">
  <div class="row">
    <div class="col-md-8 offset-md-2">
      <div class="section" id="uploader">
        <!-- Title -->
        <h4 class="section-title">Upload files</h4>
        <!-- Example of genome file uploader -->
        <div class="file-uploader">
          <!-- First row contains file selector and commands -->
          <div class="row">
            <!-- File selector -->
            <div class="col-10">
              <div class="custom-file">
                <input type="file" class="custom-file-input" name="files[]" id="uploader-files" multiple>
                <label class="custom-file-label" for="uploader-files">Choose files...</label>
              </div>
            </div>
            <!-- Start upload button -->
            <div class="col-2">
              <button type="button" class="btn btn-primary btn-block" id="uploader-upload">Upload</button>
            </div>
          </div>
        </div>
      </div>

      <!-- .fasta files -->
      <div class="section" id="genome">
        <!-- Title -->
        <h4 class="section-title">Genome files</h4>
        <!-- Alert -->
        <div class="alert alert-warning" role="alert">
          No genome file has been uploaded yet!
        </div>
      </div>

      <!-- .gff3 files -->
      <div class="section" id="annot">
        <!-- title -->
        <h4 class="section-title">Annotation files</h4>
        <!-- alert -->
        <div class="alert alert-warning" role="alert">
          No annotation file has been uploaded yet!
        </div>
      </div>

      <!-- Other files -->
      <div class="section" id="others">
        <h4 class="section-title">Other files</h4>
        <!-- alert -->
        <div class="alert alert-warning" role="alert">
          Any secondary file has been uploaded yet!
        </div>
      </div>

      <!-- Actions -->
      <div class="section" id="actions">
        <!-- Contains alerts -->
        <div class="row">
          <div class="col-12" id="alerts"></div>
        </div>
        <!-- Update button -->
        <div class="row">
          <div class="col-12">
            <button class="btn btn-primary btn-block " id="update-button" type="button">Update</button>
          </div>
        </div>
        <!-- Link to genome configuration -->
        <div class="row">
          <div class="col-6">
            <a class="btn btn-outline-primary btn-block" href="./../" >Go to genome configuration</a>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Navbar bottom -->
<?php //echo $this->element('navbar.bottom', array('page' => 'uploads')); ?>

<script type="text/javascript">
  // Initializes values for current page
  var GenomeConfig = {
    id: <?php echo json_encode($id); ?>
  }
</script>
