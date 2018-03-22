<nav class="navbar navbar-expand-lg navbar-light">
  <a class="navbar-brand" href="#">ORCAE-upload</a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse" id="navbarNav">
    <ul class="navbar-nav">
      <li class="nav-item <?php if($active == 'sessions') echo 'active'; ?>">
        <a class="nav-link" href="./sessions">Sessions <?php if($active == 'sessions') echo "<span class=\"sr-only\">(current)</span>"; ?></a>
      </li>
    </ul>
  </div>
</nav>
