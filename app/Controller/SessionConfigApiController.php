<?php
  /*
    This controller handles sessions API.
    Session creation is done through POST method. If session id is issued along with other session data,
    it is conisdered to be the update of an already existent session.
    Otherwise, a new session will be created using submitted data even if those are not complete (Session can be modified until submitted as an update to orcae).
  */

  // API controller required
  App::uses('ApiController', 'Controller');

  class SessionConfigApiController extends ApiController {

    public $uses = array('SessionConfig');

    public $user;

    // before every action, checks if user is authenticated
    function beforeFilter() {
      // triggers parent filtering to correctly initialize API controller
      parent::beforeFilter();
      // authorization required
      parent::authRequired();
    }

    /*
      This route retrieves session active for current user.
      @param id refers to session id.
      If no session Id is passed, it retrieves every session currently cative for current user.
    */
    public function view() {
      // retrieves session id, if any
      $id = isset($this->request->params['id']) ? $this->request->params['id'] : null;
      // defines if it is required to
      $searchAll = empty($id);

      // case every session must be returned
      if($searchAll) {
        $results = $this->SessionConfig->getSessions($this->user);

        // for every result, sets dates in human-format
        foreach($results as &$result) {
          // changes date format
          $result['created'] = date('d/m/Y', strtotime($result['created']));
          $result['updated'] = date('d/m/Y', strtotime($result['updated']));
        }

        // returns 200 OK in any case
        $this->response->statusCode(200);
        $this->response->body(json_encode($results));
      }
      // case single session must be returned
      else {
        $result = $this->SessionConfig->getSession($id, $this->user);

        // returns 404 if session not found
        if(!$result) {
          $this->response->statusCode(404);
          $this->response->send();
        }
        // returns 200 OK otherwise
        else {
          // defines response structure
          $response = array(
            'session' => array(),
            'errors' => array(),
            'warnings' => array()
          );

          // add warnings to response
          $this->validateSession($response, $result);

          // sets data into session
          $response['session'] = $result;

          $this->response->statusCode(200);
          $this->response->body(json_encode($response));
        }
      }
    }

    /*
      This route checks id of given session.
      If it is valid, triggers session update.
      If it is not valid, triggers new session creation.
    */
    public function edit() {

      // initializes return session
      $session = null;

      // initializes return message
      $message = array(
        // array of field_name => errors
        // empty if everything ok
        'errors' => array(),
        // some non-blocking errors (warinings) could be returned along 200 OK http status
        'warnings' => array()
      );

      // retireves session id from post
      $new_session = $this->request->data;

      // checks if it is necessary to insert new session
      // if session id has not been sent, it is set to null. This way, Session->exists(...) can immediately detect it
      $id = (isset($this->request->params['id']) ? $this->request->params['id'] : null);

      $old_session = $this->SessionConfig->getSession($id, $this->user);

      // checks if user can edit session and sets actions to be done
      if($old_session) {
        // sets new session id as new session id
        $new_session['id'] = $old_session['id'];
        // checks authorization using previously initialized user stored in session
        // (it must be set here, it has been checked in beforeFilter)
        if($this->SessionConfig->auth($old_session, $this->user)) {
          // updates already existent session
          $this->update($message, $new_session);
        }
        // case session with this id exists but current user is not authorized to edit it
        else {
          // returns 401 unauthorized
          $this->response->statusCode(401);
          $this->response->send();
          // stops execution flow
          return;
        }
      }
      // case new genome
      else {
        // creates new session into database
        $this->insert($message, $new_session);
      }

      // checks for bolcking errors
      if(count($message['errors'])) {
        // returns errors along with http status 403 forbidden
        $this->response->statusCode(403);
        // send response and exit
        $this->response->body(json_encode($message));
        return;
      }

      // saves data as defined by insert or update
      if($this->SessionConfig->save($new_session)) {
        // set status as 200 OK
        $this->response->statusCode(200);
        // returns session id
        $message['session'] = $this->SessionConfig->id;
      }
      // error while saving data
      else {
        // set status as 500 internal error
        $this->response->statusCode(500);
      }

      // set message variable as response body
      $this->response->body(json_encode($message));
      // sends response
      //$this->response->send();
    }

    // configures data for insertion (new session)
    protected function insert(&$message, &$data) {

      // set id for insertion
      $data['id'] = null;
      // set created and last updated date + time
      $data['created'] = $data['updated'] = date('Y-m-d H:i:s');
      // assigns current user id
      $data['user_id'] = $this->user['id'];

      // validates data
      $this->validateSession($message, $data);

      // TODO: set custom warnings for insert
    }

    // configures data for
    private function update(&$message, &$data) {
      // set last updated date + time
      $data['updated'] = date('Y-m-d H:i:s');
      // prevents session 'id' field modification
      /*
      if(isset($data['id'])) {
        unset($data['id']);
      }
      */
      // prevents 'created' field modification
      if(isset($data['created'])) {
        unset($data['created']);
      }
      // prevents 'user_id' field modification
      if(isset($data['user_id'])) {
        unset($data['user_id']);
      }

      // validates data
      $this->validateSession($message, $data);

      // TODO: set custom warnings for update
    }

    /*
      Validates session before saving it into database.
      Parameters: message and data (each one is passed as a reference because it will be modified).
      Generates errors, warnings and updates input data (previously retrieved from POST request body).
      Errors are blocking: if at least one is encountered, server will respond with 403 Forbidden.
      Errors means you cannot do this action because it could generate a server fault.
      Warnings are not blocking: they do not modify http header status.
      Warnings means you can save this value as is, but you must resolve it before updating orcae with saved session.
    */
    protected function validateSession(&$message, &$data) {

      // validates species name length
      $speciesName = isset($data['species_name']) ? $data['species_name'] : '';
      // removes useless white spaces
      $speciesName = trim($speciesName);
      if(strlen($speciesName) > 255) {
        // states that species name is too long
        // this is a blocking error
        $message['errors']['species_name'] = "Species name length exceeds 255 chars";
      }
      // updates data with parsed species name
      $data['species_name'] = $speciesName;

      // initializes 5code
      $species5Code = isset($data['species_5code']) ? $data['species_5code'] : '';
      // removes white spaces
      $species5Code = preg_replace('/\s+/', '', $species5Code);
      // does not allow over 5 char fields
      if(strlen($species5Code) > 5) {
        // this is a blocking error
        $message['errors']['species_5code'] = "Species shortname exceeds 5 chars length";
      }
      // gives a warning if shortname is not 5 digits
      else if(strlen($species5Code) != 5) {
        $message['warnings']['species_5code'] = "Species shortname must be 5 chars";
      }
      // updates original data field
      $data['species_5code'] = $species5Code;

      // checks type
      // must be enum('add', 'update')
      $type = isset($data['type']) ? $data['type'] : '';
      if(!in_array($type, array('insert', 'update'))) {
        // this is a blocking error: session type must be of a known type
        $message['errors']['type'] = "Select a valid session type";
      }

      // TODO: delete fields if type = update
      // TODO: check .yaml files input (as strings)
    }

  }

?>
