<?php

  App::uses('AppController', 'Controller');

  // implements API
  class ApiController extends AppController {

    // models used in this controller
    public $uses = array('User');

    // currently authenticated user
    protected $user;

    // executed before every action is executed
    // set response as API response (no view is displayed)
    public function beforeFilter() {
      // do not render a view from this controller
      $this->autoRender = false;
      // retrieves user (if it is not set, it will be null)
      $this->user = $this->Session->read('OrcaeUpload.user');
    }

    // default action: 404 API not found
    public function index() {
      $this->response->statusCode(404);
    }

  }
?>
