<?php
  /*
    This model implements sessions.
  */

  class SessionConfig extends AppModel {
    // Session refers to sessions table in orcae_upload database
    public $useDbConfig = 'orcae_upload';
    public $useTable = 'sessions';

    // checks if user passed as parameter is authorized to edit the passed session
    public function auth($session, $user) {
      // checks if the former parameter can be a session and the latter an user
      if(isset($session->user_id) && isset($user->id)) {
        if(!empty($session->user_id) && $session->user_id == $user->id) {
          // in case user id matches session user id, returns true
          return true;
        }
      }

      // returns false in any other case
      return false;
    }

    // checks if session passed as argument exists
    public function exists($session) {

      // checks if session id can be checked
      if(!isset($session->id) || empty($session->id)) {
        return false;
      }

      // search for a session with same id
      $found = $this->find(
        'first',
        array(
          'conditions' => array(
            'SessionConfig.id' => $session->id
          )
        )
      );

      // checks if searched session has been found
      if(isset($found->Session)) {
        $found = $found->Session;
        if($found->id == $session->id) {
          // returns found session
          return $found;
        }
      }

      // returns false if no corresponding session has been found
      return false;
    }

  }
?>
