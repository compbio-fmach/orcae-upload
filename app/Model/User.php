<?php

  class User extends AppModel {

    // users are registered in orcae_bogas
    public $useDbConfig = 'orcae_bogas';

    // set correct table name
    public $useTable = 'users';

    // set components used in this model
    public $components = array('Session');

    /**
    * $user is the actual user of the system
    * It is retrieved from session, if any
    */
    protected $user = false;

    /**
    * Initializes user attribute
    * user attribute represents user stored into session
    * This way, any controllers that uses this model can access easily to user data stored in session
    * @param user is the user to set
    * @return void
    */
    public function initUser($user) {
      // checks if user passed as parameter is valid
      if(empty($user) || !$user) {
        // user not valid: saves false value directly
        $this->user = false;
        return;
      }

      // saves user into user attribute
      $this->setUser($user);
    }

    /**
    * Returns currents user if any
    * @return User (associative array) if it is stroed in session
    * @return false otherwise
    */
    public function getUser() {
      return $this->user;
    }

    /**
    * Updates user attribute
    * Deletes fields that should not be saved (e.g. password)
    * @param user is an associative array representing a user
    * @return void
    */
    protected function setUser($user) {
      // deletes password field form user object
      unset($user['password']);
      // updates user (for current user control)
      $this->user = $user;
    }

    /**
    * Attempts to log in user against orcae_bogas database, using credentials passed as parameters
    * Sets user attribute if log in was successful
    * @param username speciefies the username for finding corresponding User
    * @param password specifies password which must match found User, if any
    * @return void
    */
    public function login($username, $password) {
      // searchs for a user using username field
      $result = $this->find('first', array(
        'conditions' => array('User.username' => $username)
      ));

      // checks if searched user has been found and has the correct format
      if(!isset($result['User']) || !isset($result['User']['password'])) {
        return false;
      }

      // checks given password against the one of the user retireved from the database
      // TODO sha1 is not sercure and should be changed
      if(sha1($password) !== $result['User']['password']) {
        return false;
      }

      // if login's execution flow reaches this point, it means that user login was successful
      // sets retrieved user in session
      $this->setUser($result['User']);
    }
  }

?>
