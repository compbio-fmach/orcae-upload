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
?>

<!-- This page content is just a login form -->
<form id="credentials" class="form-signin">

  <!-- Form header -->
  <div class="text-center mb-4">
    <img class="mb-4" src="https://getbootstrap.com/assets/brand/bootstrap-solid.svg" alt="" width="72" height="72">
    <h1 class="h3 mb-3 font-weight-normal">ORCAE-upload</h1>
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
  <button class="btn btn-lg btn-primary btn-block" type="button" onclick="login()">Sign in</button>

  <!-- Redirects to Orcae for user issues -->
  <p class="text-muted text-center">Forgot username or password? <a href='#'>Check out ORCAE</a></p>

</form>

<!-- Page's scripts -->
<script type="text/javascript">
  // Defines error message container
  var $error = undefined;
  // Defines login form
  var $credentials = undefined

  // Function executed when page has been loaded
  $(document).ready(function(){
    // Retrieves error container DOM element
    $error = $('#error');
    // Retrives login form
    $credentials = $('#credentials');
  });

  /**
   * @function login attempts to login user, passing username and password to /API/login
   * If login successful, redirects to default page (once user is logged in, it will show /sessions page)
   * If login attempt went wrong, shows error message
   * @return void
   */
  function login() {
    $.ajax({
      url: './API/login',
      method: 'POST',
      // Passes serialized array to API inside POST body
      data: $credentials.serializeArray(),
      dataType: 'json',
      complete: function(xhr) {
        // Retrieves response status
        var http = xhr.status;

        // Checks if response http code is '204' for successful login
        if(xhr.status == 204) {
          // Reloads page
          location.reload();
        }
        // Checks if response is '401 Not Authorized' or any other error message
        else {
          // Shows error message
          $error.html('<strong>Error!</strong> ' + xhr.responseText + '!').fadeIn();
        }
      }
    });
  }
</script>
