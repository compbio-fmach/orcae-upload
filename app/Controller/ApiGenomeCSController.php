<?php
/**
 * ApiGenomeCSController implements /API/genomecs routes
 * Being an API controller, it does not render pages
 */
App::uses('ApiController', 'Controller'); // Required to extend ApiController
class ApiGenomeCSController extends ApiController {

  // Defines models used in this controller
  public $uses = array('User', 'GenomeCS');

  /**
   * @method parseGenomeCS parses genomecs fields from another associative array
   * @param _genomecs is the associative array of genomecs before parsing
   * @return array corresponding to parsed genomecs
   */
  protected function parseGenomecs($_genomecs) {

    // Defines session var to be returned
    $genomecs = array();

    // Defines session schema using DB table
    $schema = $this->GenomeCS->schema();

    // Parses session using schema fields
    foreach($schema as $i => $field) {
      // Checks if value corresponding to current session index is set in _session param
      // If it is set, puts it as value for the current session index, else puts null
      $genomecs[$i] = isset($_genomecs[$i]) ? $_genomecs[$i] : null;
    }

    // Returns parsed session (as an associative array)
    return $genomecs;
  }

  /**
   * @method beforeFilter checks if user is authorized
   * If user not authorized, responds with http code '401 unauthorized', then exits the process
   * @return void
   */
  public function beforeFilter() {

    // Calls parent beforeFilter to initialize user
    parent::beforeFilter();

    // Checks if user is authorized
    if(!$this->User->getUser()) {
      // If user is not authorized, responds with http code '401 unauthorized'
      $this->response = $this->error401();
      // Sends response (automatic response doesn't get called due to _stop function)
      $this->response->send();
      // Exits the execution flow (do not want to execute methods taht require authentication)
      $this->_stop();
    }
  }

  /**
   * @method Index implements /API/genomecs and /API/genomecs/{id} routes
   * Redirects to CRUD operations for genome session configuration
   * @param id defines id of single session
   * @return response
   */
  public function index() {

    // Retrieves id from params
    $id = isset($this->request->params['id']) ? $this->request->params['id'] : false;

    // Checks method used
    switch ($this->request->method()) {

      case 'GET':
        // Defines validation parameter
        $validation = $this->request->query('validation');
        $this->read($id, $validation);
        break;

      case 'DELETE':
        // Triggers read with id set even if it is unvalid (should respond with 404 not found in this case)
        $this->delete($id);
        break;

      case 'POST':
        // Retrieves all POSTed data
        $data = $this->request->data;
        // Parses data fo fit genomecs schema
        $genomecs = $this->parseGenomeCS($data);
        // Adds image if it is set
        if(isset($_FILES['species_image'])) {
          $genomecs['species_image'] = $_FILES['species_image'];
        }
        // If id is set: updates already existent genomecs
        if($id !== false) {
          $this->update($id, $genomecs);
        }
        // If id not set: insert new genome
        else {
          $this->insert($genomecs);
        }
        break;

      default:
        $this->error404();
        break;
    }

    // Encodes response as json if it is not an error (http code is 2xx)
    if(preg_match('/^2[\d]{2}$/', (string)$this->response->statusCode())) {
      $body = $this->response->body();
      $body = json_encode($body);
      $this->response->body($body);
    }

    return $this->response;
  }

  /**
   * @method read is called at route /API/genomecs and /API/genomecs/{id}

   * @param id if set, makes read return single genomecs bound to this id (if any)
   * If @param id is false, returns a list of genomecs rowsm without largets fields
   * @param validation states if returned session needs validation (before it is returned), works only if id is set
   * @return response
   */
  protected function read($id = false, $validation = false) {
    // Defines current user
    $user = $this->User->getUser();

    // Case id is set, returns only one genomecs, if any
    if($id !== false) {
      // Executes query
      $result = $this->GenomeCS->find(
        'first',
        array(
          'conditions' => array(
            'GenomeCS.id' => $id,
            'GenomeCS.user_id' => $user['id']
          )
        )
      );
      // Parses the result of the query
      if(!empty($result)) {
        $result = $result['GenomeCS'];
        // Appends species image url
        $this->GenomeCS->loadSpeciesImage($result, $this->webroot);
        // Adds validation if validation parameter is set
        if($validation) {
          $result['validation'] = $this->GenomeCS->validateGenomeCS($result);
        }
        // Puts found Genome Configuration Session into response
        $this->response->body($result);
      }
      // Sets 404 error
      else {
        $this->error404('Genome Configuration Session not found');
      }
      // Returns response
      return $this->response;
    }

    // Case genomecs id is not set: returns a list of genomecs (only shortest fields)
    // Retrieves genomecs from database
    $result = $this->GenomeCS->find(
      'all',
      array(
        // Does not retrieve biggest field which contain configuration files content
        // and are useless in a genomecs overview
        'fields' => array('id', 'user_id', 'created', 'updated', 'type', 'species_taxid', 'species_name', 'species_5code'),
        // Retrieves only sessions owned by current user
        'conditions' => array('GenomeCS.user_id' => $user['id'])
      )
    );

    // Parses returned rows (uses only object data)
    foreach ($result as &$genomecs) {
      $genomecs = $genomecs['GenomeCS'];
    }

    // Sets response http code as '200 OK'
    $this->response->statusCode(200);
    // Puts retrieved genomecs into response body
    $this->response->body($result);
    // Sends response
    return $this->response;
  }

  /**
   * @method delete removes session configuration and associated uploaded files
   * TODO remove genome files from system
   * Responds with 204 if session correctly deleted
   * Responds with 404 if session specified not found (or user is not the owner of that session)
   * @return response
   */
  protected function delete($id) {

  }

  /**
   * @method saveGenomeCS validates and saves genome configuration session  data
   * Validates data before saving them: if at least 1 error has been found, does not save into database
   * If warnings are found saving data into database is allowed
   * @param genomecs specifies data to be validated
   * @return response
   */
  protected function saveGenomeCS($genomecs) {

    // Response body
    $body = array();

    // Validates genomecs data
    $validation = $this->GenomeCS->validateGenomeCS($genomecs);

    // Checks blocking errors
    if($validation !== true && !empty($validation['errors'])) {
      // Sets response http status code as 400 bad request
      $this->errorXxx("Genome Configuration Session validation found errors!", 400);
    }
    // No blocking error found
    else {
      // Saves data without validation (data which arrives here has already been validated)
      $saved = $this->GenomeCS->save($genomecs, array('validate' => false));
      // Checks database error
      if(!$saved) {
        // Exits the action returning response
        return $this->error5xx();
      }
      // Saved successfully
      else {
        // Creates the genomecs object that will be returned in response
        $genomecs = array(
          'id' => $this->GenomeCS->id
        );
        // Checks if is set species image into genomecs
        if(isset($_FILES['species_image'])) {
          // Adds species image field to genomecs
          $genomecs['species_image'] = $_FILES['species_image'];
          // Uploads image, returns true if upload was successful, error message otherwise
          $uploaded = $this->GenomeCS->uploadSpeciesImage($genomecs);
          // Case upload returned an error: puts it into validation warnings
          if($uploaded !== true) {
            if(!isset($validation['warnings']['species_image'])) {
              $validation['warnings']['species_image'] = array();
            }
            $validation['warnings']['species_image'][] = "It was not possible to upload species image";
          }
        }
        // Loads species image into resource that will be returned
        $this->GenomeCS->loadSpeciesImage($genomecs, $this->webroot);
        // Sets successful http header
        $this->response->statusCode(200);
        // Returns id of saved genomecs into body
        $body['genomecs'] = array(
          'id' => $genomecs['id'],
          'species_image' => $genomecs['species_image']
        );
      }
    }

    // Appends validation results to body
    $body['validation'] = $validation;

    // Sets body
    $this->response->body($body);
    // Returns response
    return $this->response;

    /*
    // Sets validation results into response body
    $this->response->body(array('validation' => $validation));
    // Exits the method returning response
    return $this->response;

    // Data can be saved (without validation which has been already done)
    if(!$this->SessionConfig->save($this->session, array('validate' => false))) {
      // Error while saving: responds with "500 Server Internal Error"
      return $this->error500();
    };

    // Sets saved session id
    $this->session['id'] = $this->SessionConfig->id;

    // Uploads image (only if image validation was successful and image upload is set)
    // It must be done here, because session id must be set
    if(isset($this->session['species_image'])) {
      if(!isset($response['warnings']['species_image']) && !isset($response['errors']['species_image'])) {
        // TODO: checks on upload functions

        // Uploads image file
        $this->SessionConfig->uploadSpeciesImage($this->session);
      }
    }

    // Sets image url (if there is any)
    $this->SessionConfig->setSpeciesImage($this->session, $this->webroot.'app'.DS.'webroot'.DS);

    // Adds session id to response
    $response['session']['id'] = $this->session['id'];
    // Adds species image url to response
    $response['session']['species_image'] = $this->session['species_image'];

    // Sets response body
    $this->response->body(json_encode($response));
    // Sends response status
    $this->response->statusCode(200);
    // Returns response
    return $this->response;
    */
  }

  /**
   * @method insert
   * @return response
   */
  public function update($id, $genomecs) {
    // Sets old id into new GenomeCS values
    $genomecs['id'] = $id;
    // Sets updated to now
    $genomecs['updated'] = date('Y-m-d H:i:s');
    // Deletes 'created' field form the ones which are being saved
    unset($genomecs['created']);
    // Deletes 'user_id' field form the ones which are being saved
    unset($genomecs['user_id']);
    // Saves new genomecs into database
    return $this->saveGenomeCS($genomecs);
  }

  /**
   * @method insert
   * @return response
   */
  public function insert($genomecs) {
    // Retrieves current user
    $user = $this->User->getUser();
    // Sets empty id into new GenomeCS values
    $genomecs['id'] = null;
    // Creation dates
    $genomecs['created'] = $genomecs['updated'] = date('Y-m-d H:i:s');
    // Sets user id
    $genomecs['user_id'] = $user['id'];
    // Saves new genomecs into database
    return $this->saveGenomeCS($genomecs);
  }

  /**
   * @method readConfig retrieves single genome session configuration from database
   * It uses $this->session['id'] previously set
   * @return response
   */
  /*public function readConfig() {
    // Retrieves current user
    $user = $this->User->getUser();

    // session to be returned
    $session = null;

    // Retrieves sesssion from database
    // It must be owned by current user and bound to passed session id
    if(!empty($this->session['id'])) {
      $session = $this->SessionConfig->find(
        'first',
        array(
          'conditions' => array(
            'SessionConfig.id' => $this->session['id'],
            'SessionConfig.user_id' => $user['id']
          )
        )
      );
    }

    // Case session has not been found
    if(empty($session)) {
      // returns error response
      return $this->error404("Requested session has not been found!");
    }

    // If execution flow reaches this point: requested session has been found

    // Searches for species images directory (creates it if it doesn't exist)
    $this->SessionConfig->setSpeciesImage($session['SessionConfig'], $this->webroot.'app'.DS.'webroot'.DS);

    // Sets response http code as '200 OK'
    $this->response->statusCode(200);
    // Puts found session into response body
    $this->response->body(json_encode($session['SessionConfig']));
    // Returns response
    return $this->response;
  }*/

  /**
   * @method create part of session's CRUD methods
   * Creates a new session into database
   * Sets current user as session owner
   * @return response
   */
  /* public function createConfig() {
    // Retrieves current user
    $user = $this->User->getUser();

    // Retrieves session values form POST body
    $this->session = $this->parseSession($this->request->data, isset($_FILES['species_image']) ? $_FILES['species_image'] : null);

    // Sets id as null (doing so, model's save method will create a new row in database)
    $this->session['id'] = null;
    // Sets creation date and last-update date as now
    $this->session['created'] = $this->session['updated'] = date('Y-m-d H:i:s');
    // Sets session owner as current user
    $this->session['user_id'] = $user['id'];

    // Tries to save data and returns result as http response
    return $this->saveConfig($this->session);
  } */

  /**
   * @method updateConfig gets called throught POST at /API/sessions/:id/config
   * Updates configuration using parameters passed in POST body
   * Target session (the one bound to passed parameter) must be owned by current user
   * @return response
   */
  /*public function updateConfig() {
    // Defines current user
    $user = $this->User->getUser();

    // Sets session id to trigger update instead of the creation of a new session
    $this->SessionConfig->id = $this->session['id'];

    // Defines values passsed into POST body
    $this->session = $this->parseSession($this->request->data, isset($_FILES['species_image']) ? $_FILES['species_image'] : null);
    // Resets session id
    $this->session['id'] = $this->SessionConfig->id;
    // user_id cannot be changed
    unset($this->session['user_id']);
    // Updates last-upate date
    $this->session['updated'] = date('Y-m-d H:i:s');
    // Deletes creation field (updates only set fields)
    unset($this->session['created']);

    // Tries to save data and returns result as http response
    return $this->saveConfig($this->session);
  } */
}
?>
