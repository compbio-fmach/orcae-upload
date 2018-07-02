<?php
/**
 * ApiController is the parent of every API controller
 * It configures a controller to return http response
 * Response body is returned through cakephp's json or xml view rendering
 */
App::uses('AppController', 'Controller');
class ApiController extends AppController {

  public $uses = array('User');

  public $components = array('RequestHandler', 'Auth');

  public function beforeFilter() {
    // Defines response as json
    $this->RequestHandler->renderAs($this, 'json');
    // From there on allows every route
    $this->Auth->allow();
  }

  public function index() {
    $this->error404(null, true);
  }

  /**
  * @method restify redirects ot CRUD operations, checkoing out method used for request
  * Put it into index @method to create a restful api controller
  * GET / calls read()
  * GET /:id calls read(id)
  * POST / calls create()
  * POST/:id and PUT/:id calls update()
  * DELETE /:id calls delete()
  */
  public function restify($id = null) {
     $method = $this->request->method();
     switch($method) {
       case 'GET':
         $this->read($id);
         break;
       case 'POST':
         if(!empty($id)) $this->update($id);
         else $this->create();
         break;
       case 'PUT':
         $this->update($id);
         break;
       case 'DELETE':
         $this->delete($id);
         break;
     }
   }

  /**
   * @method errorXxx returns a message with a status code and a plain text message
   * Used for responding with error messages to API calls
   * @param error specifies an error
   * @param http specifies an http code
   * @return void
   */
  protected function errorXxx($error, $http, $send = false) {
    // Sets response http code
    $this->response->statusCode($http);
    // Sets response error message
    $this->set('error', $error);
    // Renders error through JSON or XML views
    $this->set('_serialize', 'error');

    // Sends response and stops execution flow
    if($send) {
      $this->render();
    }
  }

  /**
   * API not found error
   * It is returned when no API has been found
   * It can only be called internally
   * @param error is an optional error message that overrides original one
   * @return void
   */
  protected function error404($error = null, $send = false) {
    // If empty error param, sets default error message
    if(empty($error)) {
      $error = "Requested API has not been found!";
    }

    // Responds with error message
    $this->errorXxx($error, 404, $send);
  }

  /**
   * API internal error
   * It is returned when an internal error happens
   * Resturns error string along with error http code
   * Optionally, a different http code can be specified
   * @param error is an error string to be returned as plain text
   * @param http is an optional http code to be returned with the response (default = 500)
   * @return void
   */
  protected function error5xx($error = null, $http = 500, $send = false) {
    // Checks if error string is empty
    if(empty($error)) {
      // If error string is empty, responds with a generic error message
      $error = "Unexpected server internal error occurred. Report it to system admin.";
    }

    // Responds with error message
    $this->errorXxx($error, $http, $send);
  }

  /**
   * API access not authorized
   * Returned when a user tries to access a protected resource without authorization
   * Responds http status 401 along with error message
   * @return void
   */
  protected function error401($send = false) {
    // Responds with error message
    $this->errorXxx("Unauthorized user", 401, $send);
  }
}
?>
