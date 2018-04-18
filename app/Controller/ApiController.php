<?php
/**
 * ApiController is the parent of every API controller
 * It configures a controller to return http response instead of rendering a view
 */
App::uses('AppController', 'Controller');
class ApiController extends AppController {

  /**
   * Set response format for an API
   * No view must be rendered
   * @return void
   */
  public function beforeFilter() {
    // Calls for parent inizialization
    parent::beforeFilter();
    // Disables view auto-rendering
    $this->autoRender = false;
  }

  /**
   * @method index responds with 404 API not found by default
   */
  public function index() {
    $this->error404();
  }

  /**
   * @method errorXxx returns a message with a status code and a plain text message
   * Used for responding with error messages to API calls
   * @param error specifies an error
   * @param http specifies an http code
   * @return void
   */
  protected function errorXxx($error, $http) {
    // Sets response http code
    $this->response->statusCode(404);
    // Sets response error message
    $this->response->body($error);
    // Returns response
    return $this->response;
  }

  /**
   * API not found error
   * It is returned when no API has been found
   * It can only be called internally
   * @param error is an optional error message that overrides original one
   * @return void
   */
  protected function error404($error = null) {
    // If empty error param, sets default error message
    if(empty($error)) {
      $error = "Requested API has not been found!";
    }

    // Responds with error message
    return $this->errorXxx($error, 404);
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
  protected function error5xx($error = null, $http = 500) {
    // Checks if error string is empty
    if(empty($error)) {
      // If error string is empty, responds with a generic error message
      $error = "Unexpected server internal error occurred. Report it to system admin.";
    }

    // Responds with error message
    return $this->errorXxx($error, $http);
  }

  /**
   * API access not authorized
   * Returned when a user tries to access a protected resource without authorization
   * Responds http status 401 along with error message
   * @return void
   */
  protected function error401() {
    // Responds with error message
    return $this->errorXxx("Unauthorized user cannot access this resource", 401);
  }
}
?>
