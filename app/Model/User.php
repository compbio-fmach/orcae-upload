<?php

  class User extends AppModel {
    // users are registered in orcae_bogas
    public $useDbConfig = 'orcae_bogas';
    // set correct table name
    public $useTable = 'users';

    // attempts to log in the user
    static function login($username, $password) {
      // finds a user thorugh username
      $user = $this->find('first', array(
        'conditions' => array('User.username' => $username)
      ));

      // checks if at least one user has been found
      if(!isset($user['User'])) {
        return false;
      }

      // set user for convenience
      $user = $user['User'];
      //checks password
      if(!sha1($password) === $user['password']) {
        return false;
      }

      // if it reaches this point, user login is successfull
      return $user;
    }
  }

?>
