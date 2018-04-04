<?php

  App::uses('AppController', 'Controller');

  // implements API
  class ApiController extends AppController {
    // executed before every action is executed
    // set response as API response (no view is displayed)
    public function beforeFilter() {
      // calls for parent inizialization
      parent::beforeFilter();
      // do not render a view from this controller
      $this->autoRender = false;
    }

    // default action: 404 API not found
    public function index() {
      $this->response->statusCode(404);
    }

    // checks authentication
    // if user is not authenticated: stops execution flow and returns 401 unauthorized
    public function authRequired() {
      if(!$this->auth()) {
        // response is 403 unauthorized in this case
        $this->response->statusCode(401);
        $this->response->send();
        // exits execution flow
        die();
      }
    }

  }
?>
