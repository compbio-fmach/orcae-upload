<?php
/**
 * ApiAuthController implements /API/login and /API/logout routes
 * ApiAuthController is a class extended from ApiController, therefore uses same models
 */
App::uses('ApiController', 'Controller');
class ApiAuthController extends ApiController {

  // components used in this controller
  public $components = array('Session');

  /**
   * Attempts to login the user
   * If user login is successful, saves it in session and responds with http code 204
   * If user login was wrong, responds with http code 401 not authorized
   * @return void
   */
  public function login() {

    // retrieves username and password from POST body
    $username = $this->request->data('username');
    $password = $this->request->data('password');

    // Checks if user is already authorized
    // If so, does not attempt to login and responds with http code 204
    if($this->User->getUser()) {
      // sets response http code
      $this->response->statusCode(204);
      return $this->response;
    }

    // Calls model's login method
    $this->User->login($username, $password);

    // Checks if an user has been set
    if($user = $this->User->getUser()) {
      // User set: saves it in session to keep login active
      $this->Session->write('OrcaeUpload.user', $user);
      // responds with http code 204
      $this->response->statusCode(204);
    } else {
      // login attemp went wrong: responds with http code 401 unauthorized
      $this->response->statusCode(401);
      $this->response->body("Wrong username or password");
    }

    // sends response
    return $this->response;
  }

  /**
   * Deletes user credentials from the system
   * @return void
   */
  public function logout() {
    // deletes user from session
    $this->Session->delete('OrcaeUpload.user');
    // deletes user from User
    $this->User->initUser(false);

    // responds with http code 204
    $this->response->statusCode(204);
    return $this->response;
  }
}
?>
