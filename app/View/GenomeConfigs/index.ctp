<?php
/**
 * This view shows a table, which will be filled of user's sessions
 * @param subtitle 'Your genome config sessions' is passed to layout
*/
// Defines subtitle
$this->assign('subtitle', "Your Genome Configurations");
// Defines custom css
$this->Html->css('genome_configs', array('inline' => false));
// Defines page scripts
$this->Html->script('pages/navbar_top', array('inline' => false));
$this->Html->script('pages/genome_configs_index', array('inline' => false));
?>

<!-- Top navbar -->
<?php
  // Outputs top navbar. Param page specifies which state navbar must have.
  echo $this->element('navbar.top', array('page' => 'genomeconfigs'));
?>

<!-- Page main content (not displayed by default, must be showed using javascript) -->
<div class="container main">

  <!-- Table title container -->
  <div class="table-title">
    <!-- Title (left) -->
    <h4 class="table-title-left">Genome configuration sessions</h4>
    <!-- Link to new session creation (right) -->
    <a class="table-title-right text-primary" href="./new/">Create new genome</a>
  </div>

  <!-- Table of sessions: ses Ajax to load contents -->
  <table class="table table-bordered bg-white">
    <thead>
      <tr>
        <!-- Row number -->
        <th>#</th>
        <th>Species' name</th>
        <th>Created</th>
        <th>Last update</th>
        <!-- Link to access the session represented by the current row -->
        <th>View</th>
        <!-- Asks if user wants to delete this session -->
        <th>Delete</th>
      </tr>
    </thead>
    <!-- Table body will be filled with ajax -->
    <tbody id="genome-configs"></tbody>
  </table>
</div>
