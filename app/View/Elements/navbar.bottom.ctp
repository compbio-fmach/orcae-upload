<?php $this->Html->script('pages/navbar_bottom', array('inline' => false)); ?>
<nav class="navbar fixed-bottom navbar-expand-lg navbar-light navbar-bottom">

    <?php if($page == 'uploads'): ?>
    <!-- left side -->
    <ul class="navbar-nav mr-auto">
      <li class="nav-item">
        <!-- goes from genome uploads page to config -->
        <a class="nav-link text-primary" id="to-config">&larr; <u>Genome configuration</u></a>
      </li>
    </ul>
    <?php endif; ?>

    <?php if($page == 'config'): ?>
    <!-- right side -->
    <ul class="navbar-nav ml-auto">
      <li class="nav-item">
        <!-- goes from genome config page to uploads -->
        <a class="nav-link text-primary" id="to-uploads"><u>Genome files upload</u> &rarr;</a>
      </li>
    </ul>
  <?php endif; ?>

</nav>
