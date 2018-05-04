<?php
App::uses('AppController', 'Controller');
class PagesController extends AppController {

  public $uses = array('User');
  public $components = array('Auth');

  // Sets common parameters
  public function beforeFilter() {
    $this->Auth->allow();
    $this->layout = 'main';
  }

  // Main router, simply redirects
  public function index() {
    // User is authorized: redirects to default page
    if(!$this->Auth->loggedIn()) {
      $this->redirect('/login/');
    }
    // Unser unauthorized: redirects to login
    else {
      $this->redirect('/genome_configs/');
    }
  }
}
?>
