<?php

  App::uses('AppController', 'Controller');

  // implements API
  class ApiController extends AppController {

    // models used in this controller
    public $uses = array('User');

    // executed before every action is executed
    // set response as API response (no view is displayed)
    public function beforeFilter() {
      // do not render a view from this controller
      $this->autoRender = false;
    }

    // default action: 404 API not found
    public function index() {
      $this->response->statusCode(404);
    }

  }
?>
