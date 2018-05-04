<?php
App::uses('PagesController', 'Controller');
class PagesGenomeConfigsController extends PagesController {

  public function beforeFilter() {
    parent::beforeFilter();

    // Checks authorization
    if(!$this->Auth->loggedIn()) {
      $this->redirect('/');
    }
    // Sets page folder (views are taken from there)
    $this->viewPath = '/GenomeConfigs/';
  }

  public function index() {}

  public function config($id = null) {
    // Sets id for view
    $this->set('id', $id);
  }

  public function uploads($id = null) {
    $this->set('id', $id);
  }

  /*
   // defines models used inside this controller
   public $uses = array('User', 'GenomeCS');
   public function beforeFilter() {
     // Calls parent initialization
     parent::beforeFilter();

     // defines layout for every action
     $this->layout = 'orcae_upload';

     // checks if user is not authorized and redirects
     if(!$this->User->getUser()) {
       $this->redirect('/login');
     }
   }

   public function index() {}


   public function config($id = 'new') {}


   public function uploads($id = null) {
     // Retrieves current user object (as an associative array)
     $user = $this->User->getUser();
     // Checks if id is bound to a valid genomecs
     $result = $this->GenomeCS->find('count', array(
       'conditions' => array(
         array('GenomeCS.id' => $id),
         array('GenomeCS.user_id' => $user['id'])
       )
     ));

     // If no result has been found: redirects to default page
     if($result < 1) {
       $this->redirect('/');
     }
   }
   */
}
?>
