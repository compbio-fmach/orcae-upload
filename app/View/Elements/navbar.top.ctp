<?php
/**
 * Renders top navbar element
 * Scripts required for this navbar are loaded in a separate js file
 * This way, script can be loaded into document head
 */
// Adds logout script to document head
$this->Html->script('navbar_top', array('inline' => false));
// Defines local variable for webroot
$webroot = $this->webroot;
?>

<nav class="navbar fixed-top navbar-expand-lg navbar-light navbar-top">

  <!-- title -->
  <a class="navbar-brand" href="./">ORCAE-upload</a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>

  <div class="collapse navbar-collapse" id="navbarNav">

    <!-- left side -->
    <ul class="navbar-nav">
      <li class="nav-item <?php if($page == 'sessions') echo 'active'; ?>">
        <a class="nav-link" href="<?php echo $webroot.'sessions'; ?>">Sessions <?php if($page == 'sessions') echo "<span class=\"sr-only\">(current)</span>"; ?></a>
      </li>
    </ul>

    <!-- right side -->
    <ul class="navbar-nav ml-auto">
      <li class="nav-item">
        <a class="nav-link" onclick="logout(<?php echo $webroot; ?>)" style="cursor:pointer;">Logout</a>
      </li>
    </ul>
  </div>

</nav>
