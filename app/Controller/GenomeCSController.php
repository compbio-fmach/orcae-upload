<?php

/**
* Controller for sessions pages
* This handles the operations done before the page are rendered
*/
class GenomeCSController extends AppController {

   // defines models used inside this controller
   public $uses = array('User', 'GenomeCS');

  /**
   * Initializes common elements of pages
   * Checks if user is authenticated: if not, redirects to /login page
   * Executed before other controller actions
   * @return void
   */
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

  /**
   * @method index renders /genomecs/ page
   * @return void
   */
   public function index() {}

   /**
    * @method config reders page /genomecs/id
    * @param id 'new' is not a valid id, therefore renders an empty form
    * @return void
    */
   public function config($id = 'new') {}

   /**
    * @method renders /genomecs/render/ upload interface
    * @return void
    */
   public function uploads($id = null) {
     // Retrieves current user object (as an associative array)
     $user = $this->User->getUser();
     // Checks if id is bound to a valid genomecs
     $result = $this->GenomeCS->find('count', array(
       array(
         'conditions' => array(
           'GenomeCS.id' => $id,
           'GenomeCS.user_id' => $user['id']
         )
       )
     ));

     // If no result has been found: redirects to default page
     if($result < 1) {
       $this->redirect('/');
     }
   }

}

?>
