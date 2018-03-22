<?php

  App::uses('ApiController', 'Controller');

  // AuthController is responsible for login and logout related tasks
  class AuthController extends ApiController {

    // components used in this controller
    public $components = array('Session', 'Cookie');

    // models used in this controller
    public $uses = array('User');

    // throught this action an user can attempt to login
    // if the login is successfull, it returns 204 OK
    // else it returns 401 not authorized
    // TODO remember-me feature not yet implemented
    public function login() {
      //if request is not post: API not found
      if(!$this->request->is('post')) {
        parent::index();
        return;
      }

      // checks if user is already logged in
      if($this->Session->read('OrcaeUpload.user')) {
        $this->response->statusCode(204);
        return;
      }

      // retrieves username and password from post
      $password = $this->request->data('password');
      $username = $this->request->data('username');
      $remember = $this->request->data('remember-me');

      // triggers user login
      $user = $this->User->login($username, $password);
      if($user) {
        // delete pasword from user
        unset($user['password']);
        // set session
        $this->Session->write('OrcaeUpload.user', $user);

        // cehcks if user wants to be remembered
        if($remember) {
          // TODO
        }

        // returns status 204 OK
        $this->response->statusCode(204);
        return;
      }

      $this->response->statusCode(401);
    }

    // allows users to log out from the system
    // return 204
    // TODO remove cookie for remember-me feature
    public function logout() {
      // deletes user of orcae-upload from session
      $this->Session->delete('OrcaeUpload.user');
    }
  }

?>
