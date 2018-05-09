<?php $this->Html->script('pages/navbar_top', array('inline' => false)); ?>
<nav class="navbar fixed-top navbar-expand-lg navbar-light navbar-top">

  <!-- title -->
  <a class="navbar-brand" href="<?php echo $this->Html->url('/', true); ?>">
     <img src="<?php echo $this->Html->url('/img/orcae-logo.png'); ?>" width="30" height="30" alt="Orcae-Upload">
  </a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>

  <div class="collapse navbar-collapse" id="navbarNav">

    <!-- left side -->
    <ul class="navbar-nav">
      <li class="nav-item <?php if($page == 'genomeconfigs') echo 'active'; ?>">
        <a class="nav-link" href="<?php echo $this->Html->url('/genome_configs/', true); ?>">Sessions <?php if($page == 'genomeconfigs') echo "<span class=\"sr-only\">(current)</span>"; ?></a>
      </li>
    </ul>

    <!-- right side -->
    <ul class="navbar-nav ml-auto">
      <li class="nav-item">
        <a class="nav-link" id="logout" style="cursor:pointer;">Logout</a>
      </li>
    </ul>
  </div>

</nav>
