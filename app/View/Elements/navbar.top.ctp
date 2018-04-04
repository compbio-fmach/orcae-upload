<?php
  // adds scripts required for this navbar
  echo $this->Html->script('logout');
?>

<nav class="navbar navbar-expand-lg navbar-light navbar-top">

  <!-- title -->
  <a class="navbar-brand" href="./">ORCAE-upload</a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>

  <div class="collapse navbar-collapse" id="navbarNav">

    <!-- left side -->
    <ul class="navbar-nav">
      <li class="nav-item <?php if($current == 'sessions') echo 'active'; ?>">
        <a class="nav-link" href="<?php echo $this->webroot.'sessions'; ?>">Sessions <?php if($current == 'sessions') echo "<span class=\"sr-only\">(current)</span>"; ?></a>
      </li>
    </ul>

    <!-- right side -->
    <ul class="navbar-nav ml-auto">
      <li class="nav-item">
        <a class="nav-link" onclick="logout(<?php echo $this->webroot; ?>)" style="cursor:pointer;">Logout</a>
      </li>
    </ul>
  </div>

</nav>
