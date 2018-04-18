<?php
/**
 * This view shows a form where users can create, update and read the selected genome configuration session
 * @param subtitle 'genome config session' is passed to layout
*/
// Defines subtitle
$this->assign('subtitle', "Genome files upload");
// Defines custom css
$this->Html->css('sessions_config', array('inline' => false));
// Loads ace text editor library (used for yaml files configuration) from external cdn
$this->Html->script('https://cdnjs.cloudflare.com/ajax/libs/ace/1.3.3/ace.js', array('inline' => false));
?>

<!-- Navbar top -->

<!-- Main container -->
<div class="main container">
  <!-- set form in center column -->
  <div class="row">
    <div class="col-md-8 offset-md-2">

      

    </div>
  </div>
</div>

<!-- Navbar bottom -->
