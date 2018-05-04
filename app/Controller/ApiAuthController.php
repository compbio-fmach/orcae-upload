<?php
/**
 * ApiAuthController implements /API/login and /API/logout routes
 * ApiAuthController is a class extended from ApiController, therefore uses same models
 * Uses Auth component for authentication into orcae system
 */
App::uses('ApiController', 'Controller');
class ApiAuthController extends ApiController {

  public $components = array(
    'RequestHandler',
    'Auth' => array(
      'authenticate' => array(
        'Form' => array(
          'passwordHasher' => 'Orcae'
        )
      )
    )
  );

  public function beforeFilter() {
    parent::beforeFilter();

    // Creates an User object with username and password
    $this->request->data['User'] = array(
      'username' => $this->request->data('username'),
      'password' => $this->request->data('password')
    );
  }

  /**
   * Attempts to login the user using Auth component
   * If user login is successful, saves it in session and responds with http code 204
   * If user login was wrong, responds with http code 401 not authorized
   * @return void
   */
  public function login() {

    // Only POST requests are valid
    if(!$this->request->isPost()) {
      $this->error404(null, true);
    }

    // Already logged in
    if($this->Auth->loggedIn()) {
      $this->response->statusCode(200);
      $this->set('message', "Already logged in");
    }
    // Successful login
    else if($this->Auth->login()) {
      $this->response->statusCode(200);
      $this->set('message', "Authentication successful");
    }
    // Wrong login
    else {
      $this->response->statusCode(401);
      $this->set('message', "Wrong username or password");
    }

    // Retruns json
    $this->set('_serialize', 'message');
  }

  /**
   * Logout of the user
   * @return void
   */
  public function logout() {
    $this->Auth->logout();
    $this->set('message', 'Bye bye');
    $this->set('_serialize', 'message');
  }
}
?>
