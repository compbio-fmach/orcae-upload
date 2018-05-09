<?php
// Defines subtitle
$this->assign('subtitle', "Genome Files Upload");
$this->Html->css('genome_configs_uploads', array('inline' => false));
// Chunk upload API
$this->Html->script('fileuploader/vendor/jquery.ui.widget.js', array('inline' => false));
$this->Html->script('fileuploader/jquery.iframe-transport.js', array('inline' => false));
$this->Html->script('fileuploader/jquery.fileupload', array('inline' => false));

$this->Html->script('pages/genome_configs_uploads', array('inline' => false));
?>

<!-- Navbar top -->
<?php echo $this->element('navbar.top', array('page' => 'uploads')); ?>

<!-- Main container -->
<div class="main container">
  <div class="row">
    <div class="col-md-8 offset-md-2">

      <!-- File uploader: starts a new genome upload -->
      <form class="section" id="file-uploader">
        <!-- title -->
        <h4 class="section-title">Upload files</h4>

        <div class="row">
          <div class="col-3">
            <!-- Which file is going to be uploaded -->
            <div class="form-group">
              <select class="form-control" id="type">
                <option value=''>Select type</option>
                <option value='genome'>Genome</option>
                <option value='annot'>Annotation</option>
                <!-- <option value='others'>Other</option> -->
              </select>
            </div>
          </div>
          <div class="col-6">
            <!-- Source file -->
            <div class="custom-file">
              <input type="file" class="custom-file-input" name="files[]" id="file">
              <label class="custom-file-label" for="file">Choose file...</label>
            </div>
          </div>
          <div class="col-3">
            <!-- Button which triggers the upload -->
            <button type="button" class="btn btn-primary" id='upload'>Upload</button>
          </div>
        </div>
      </form>

      <!-- Genome upload section -->
      <div class="section" id="genome">
        <!-- title -->
        <h4 class="section-title">Genome files</h4>
        <!-- alert -->
        <div class="alert alert-warning" role="alert">
          No genome file has been uploaded yet!
        </div>
      </div>

      <!-- Allows annotation file upload -->
      <div class="section" id="annot">
        <!-- title -->
        <h4 class="section-title">Annotation files</h4>
        <!-- alert -->
        <div class="alert alert-warning" role="alert">
          No annotation file has been uploaded yet!
        </div>

        <!--
        <div class="row">
          <div class="col-3">
            #1 file name
          </div>
          <div class="col-9">
            <div class="progress">
              <div class="progress-bar" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
          </div>
        </div>
        -->
      </div>

      <!-- Allows other files to be uploaded -->
      <div class="section" id="others">
        <h4 class="section-title">Other files</h4>
        <!-- alert -->
        <div class="alert alert-warning" role="alert">
          Any secondary file has been uploaded yet!
        </div>
      </div>

      <!-- Contains update button -->
      <div class="section">
        <button type="button" class="btn btn-primary btn-block" disabled>Update</button>
      </div>

    </div>
  </div>
</div>

<!-- Navbar bottom -->
<?php echo $this->element('navbar.bottom', array('page' => 'uploads')); ?>

<script type="text/javascript">
  // Initializes values for current page
  var GenomeConfig = {
    id: <?php echo json_encode($id); ?>
  }
</script>
