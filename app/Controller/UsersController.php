<?php

  App::uses('ApiController', 'Controller');

  // this controller handles users
  class UsersController extends ApiController {

    public $uses = array('User');

    public function beforeFilter() {
      // executes parent before filter
      parent::beforeFilter();

      // checks if user is authorized
      if(!$this->auth()) {
        // send 403 forbidden
        $this->response->statusCode(403);
        // send response
        $this->response->send();
        // stops execution flow
        die();
      }
    }

    // finds users by email
    public function index() {
      if(!$this->request->is('get')) {
        // send 404 API not found
        parent::index();
        return;
      }

      // retrieves get query parameters
      $email = $this->request->query('email');
      // finds users
      $users = $this->User->find(
        'all',
        array(
          'conditions' => array(
            'User.email LIKE' => '%'.$email.'%',
            'User.frozen' => 0
          )
        )
      );

      // set response http code as 200 OK
      $this->response->statusCode(200);
      // output found users
      echo json_encode(compact('users'));
    }
  }
?>
