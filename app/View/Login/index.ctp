<?php
/**
 * This view shows a login form
 * Login action is handled throught ajax calls to /API/login route
 * @param subtitle 'genome config session' is passed to layout
*/
// Sets page title
$this->assign('subtitle', "Login");
// Sets stylesheet for this page only
$this->Html->css('login', array('inline' => false));
$this->Html->script('pages/login', array('inline' => false));
?>

<!-- This page content is just a login form -->
<form id="credentials" class="form-signin">

  <!-- Form header -->
  <div class="text-center mb-4">
    <img class="mb-4" src="<?php echo $this->Html->url('/img/orcae-logo.png'); ?>" alt="Orcae-Upload" width="130" height="130">
    <!-- <h1 class="h3 mb-3 font-weight-normal">ORCAE-upload</h1> -->
    <p>Sign in using ORCAE's credentials.</p>
  </div>

  <!-- Username field -->
  <div class="form-group">
    <label for="inputEmail">Username</label>
    <input type="text" id="inputUsername" class="form-control form-control-lg" name="username" placeholder="Username" required autofocus>
  </div>

  <!-- Password field -->
  <div class="form-group">
    <label for="inputPassword">Password</label>
    <input type="password" id="inputPassword" class="form-control form-control-lg" name="password" placeholder="Password" required>
  </div>

  <!-- Error message container (not displayed by default) -->
  <div id="error" class="alert alert-danger" style="display:none;"></div>

  <!-- Login button -->
  <button id="login-button" class="btn btn-lg btn-primary btn-block" type="button">Sign in</button>

  <!-- Redirects to Orcae for user issues -->
  <p class="text-muted text-center">Forgot username or password? <a href='#'>Check out ORCAE</a></p>

</form>
