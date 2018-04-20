<?php echo $this->Html->script('navbar_bottom'); ?>
<nav class="navbar fixed-bottom navbar-expand-lg navbar-light navbar-bottom">

    <?php if($page == 'uploads'): ?>
    <!-- left side -->
    <ul class="navbar-nav mr-auto">
      <li class="nav-item">
        <a class="nav-link text-primary" onclick="toConfig()">&larr; <u>Genome configuration</u></a>
      </li>
    </ul>
    <?php endif; ?>

    <?php if($page == 'config'): ?>
    <!-- right side -->
    <ul class="navbar-nav ml-auto">
      <li class="nav-item">
        <a class="nav-link text-primary" onclick="toUploads()"><u>Genome files upload</u> &rarr;</a>
      </li>
    </ul>
  <?php endif; ?>

</nav>
