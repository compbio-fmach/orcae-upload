<?php
/**
 * This controller renders login using index function
 * It checks if user is already logged in: if so, redirects to default page
 */
class LoginController extends AppController {

  // defines models used in this controller
  public $uses = array('User');

  /**
   * Checks if user is already logged in
   * If so, redirectes to login page
   * @return void
   */
  public function beforeFilter(){
    // Calls parent initialization, which contains User initialization
    parent::beforeFilter();

    //defines page layout
    $this->layout = 'orcae_upload';

    // checks if current user is set
    if(!$this->User->getUser()) {
      // exits the function
      return;
    }

    // if user is already set redirects to default path
    $this->redirect('/');
  }

  /**
   * Empty function used to bound /View/Login/index.ctp view to this controller action
   * @return void
   */
  public function index() {}
}
?>
