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
      if(isset($session['user_id']) && isset($user['id'])) {
        if(!empty($session['user_id']) && $session['user_id'] == $user['id']) {
          // in case user id matches session user id, returns true
          return true;
        }
      }

      // returns false in any other case
      return false;
    }

    /*
      Selects a single session to be returned.
      @param id is used to specify which session to return.
      @param user specifies which user must be the owner of the returned session
      @return session if found through id and user_id
      @return false if nothing found
    */
    public function getSession($id, $user) {

      // checks session id
      if(!isset($id) || empty($id)) return false;

      // search for a session with passed id
      $session = $this->find(
        'first',
        array(
          'conditions' => array(
            'SessionConfig.id' => $id,
            'SessionConfig.user_id' => $user['id']
          )
        )
      );

      // parses query results, if any
      if(isset($session['SessionConfig'])) {
        $session = $session['SessionConfig'];
        return $session;
      }

      // returns false if no corresponding session has been found
      return false;
    }

    /*
      Selects every session
      Returned sessions does not have config files (used for overview)
      @return all active sessions owned by user
      @return empty array if no session active for current user
    */
    public function getSessions($user) {

      // search query (handled by cakephp)
      $sessions = $this->find('all',
        array(
          'conditions' => array(
            'SessionConfig.user_id' => $user['id']
          )
        )
      );

      // parses query results
      foreach($sessions as &$session) {
        $session = $session['SessionConfig'];
      }

      // returns results
      return $sessions;
    }

    /*
      Returns currently active sessions count
      @param user is the owner of sessions
    */
    public function getSessionsCount($user) {
      $count = $this->find('count', array(
        'conditions' => array(
          'SessionConfig.user_id' => $user['id']
        )
      ));

      return $count;
    }

  }
?>
