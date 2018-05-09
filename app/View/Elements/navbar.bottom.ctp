<nav class="navbar fixed-bottom navbar-expand-lg navbar-light navbar-bottom">

    <?php if($page == 'uploads'): ?>
    <!-- left side -->
    <ul class="navbar-nav mr-auto">
      <li class="nav-item">
        <!-- goes from genome uploads page to config -->
        <a class="nav-link text-primary" href="../">
          <u>Go to genome configuration</u>
        </a>
      </li>
    </ul>
    <?php endif; ?>

    <?php if($page == 'config'): ?>
    <!-- right side -->
    <ul class="navbar-nav ml-auto">
      <li class="nav-item">
        <!-- goes from genome config page to uploads -->
        <a class="nav-link text-primary" href="./uploads/">
          <u>Go to genome files upload</u>
        </a>
      </li>
    </ul>
  <?php endif; ?>

</nav>
