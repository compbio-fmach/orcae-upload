<?php
/**
 * This view shows a form where users can create, update and read the selected genome configuration session
 * @param subtitle 'genome config session' is passed to layout
*/
// Defines subtitle
$this->assign('subtitle', "Genome files upload");
// Defines custom css
$this->Html->css('uploads', array('inline' => false));
// Chunk upload API
$this->Html->script('fileuploader/vendor/jquery.ui.widget.js', array('inline' => false));
$this->Html->script('fileuploader/jquery.iframe-transport.js', array('inline' => false));
$this->Html->script('fileuploader/jquery.fileupload', array('inline' => false));
?>

<!-- Navbar top -->
<?php echo $this->element('navbar.top', array('page' => 'uploads')); ?>

<!-- Main container -->
<div class="main container">
  <!-- set form in center column -->
  <div class="row">
    <div class="col-md-8 offset-md-2">

      <!-- Genome upload section -->
      <form class="section" id="genome">
        <h4 class="section-title">Upload genome files</h4>
        <div class="section-item">
          <!-- File browser -->
          <div class="custom-file">
            <input class="custom-file-input" id="genome-1" type="file" name="files[]">
            <label class="custom-file-label" for="genome-1">Choose file</label>
          </div>
          <!-- Container of upload info -->
          <div>
            <!-- Upload progress bar -->
            <div class="progress">
              <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
          </div>
          <!-- <small class="text-muted">Insert the name of the species you want to upload genome files to</small> -->
        </div>
      </form>

      <!-- Allows annotation file upload -->
      <form class="section" id="annotations">
        <h4 class="section-title">Upload annotations</h4>
      </form>

      <!-- Allows other files to be uploaded -->
      <form class="section" id="others">
        <h4 class="section-title">Upload other files</h4>
      </form>

    </div>
  </div>
</div>

<!-- Navbar bottom -->
<?php echo $this->element('navbar.bottom', array('page' => 'uploads')); ?>

<!-- Scripts -->
<script type="text/javascript">
  $(document).ready(function(){

    $('.custom-file .custom-file-input').each(function(){

      var $field = $(this);

      // Initializes file upload
      $(this).fileupload({
        dataType: 'json',
        url: '../../API/genomecs/11/uploads',
        maxChunkSize: 1000000 // 1MB
      });

      $field.on('change', function(){

        $(this).fileupload({
          files: $field.prop('files')
        });

      });

    });

    $('.main.container').fadeIn('slow');
  });
</script>
