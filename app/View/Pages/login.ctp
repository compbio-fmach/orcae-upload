<?php
  // set current page title
  $this->assign('title', 'Login');
  // set current page css
  $this->Html->css('login', array('inline' => false));
  // set current page js
  $this->Html->script('login', array('inline' => false));
?>

<form id="form-signin" class="form-signin">
  <div class="text-center mb-4">
    <img class="mb-4" src="https://getbootstrap.com/assets/brand/bootstrap-solid.svg" alt="" width="72" height="72">
    <h1 class="h3 mb-3 font-weight-normal">ORCAE-upload</h1>
    <p>Sign in using ORCAE's credentials.</p>
  </div>

  <div class="form-group">
    <label for="inputEmail">Username</label>
    <input type="text" id="inputUsername" class="form-control" name="username" placeholder="Username" required autofocus>
  </div>

  <div class="form-group">
    <label for="inputPassword">Password</label>
    <input type="password" id="inputPassword" class="form-control" name="password" placeholder="Password" required>
  </div>

  <!-- <div class="checkbox mb-3">
    <label>
      <input type="checkbox" name="remember-me" value="remember-me"> Remember me
    </label>
  </div> -->

  <!-- error message conteiner -->
  <div id="message-signin" class="mb-3 text-center" style="display:none;"></div>

  <button id="btn-signin" class="btn btn-lg btn-primary btn-block" type="button" onclick="login(<?php echo $this->webroot; ?>)">Sign in</button>
  <!-- <p class="mt-5 mb-3 text-muted text-center">&copy; 2017-2018</p> -->
  <p class="mt-5 mb-3 text-muted text-center">Forgot username or password? <a href='#'>Check out ORCAE</a></p>
</form>
