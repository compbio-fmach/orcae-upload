<?php
App::uses('PagesController', 'Controller');
class PagesLoginController extends PagesController {

  public function beforeFilter() {
    parent::beforeFilter();

    if($this->Auth->loggedIn()) {
      $this->redirect('/');
    }

    // Bounds correct view folder
    $this->viewPath = '/Login/';
  }

  public function index() {}
}
?>
