<?php
class ApiAuthControllerTest extends ControllerTestCase {
  // Uses orcae_bogas database for testing user login and logout
  public $useDbConfig = 'orcae_bogas';

  /**
   * @method testLogin
   * Tries to login using valid username and password
   * @return void
   */
  public function testWrongLogin() {
    // Defines data which will be passed to controller
    $data = array(
      'username' => 'start',
      'password' => ''
    );

    // Tries to login
    $result = $this->testAction('/API/login/', array(
      'return' => 'contents',
      'data' => $data,
      'method' => 'post'
    ));

    // Parses json result (response body)
    $result = json_decode($result, true);

    // Tests response message
    $this->assertEquals('Wrong username or password', $result);
    // Tests response http status code
    $this->assertEquals(401, $this->controller->response->statusCode());
  }

  public function testCorrectLogin() {
    $data = array('username' => 'start', 'password' => 'changeme!');

    // Tries to login
    $result = $this->testAction('/API/login/', array(
      'return' => 'contents',
      'data' => $data,
      'method' => 'post'
    ));

    // Parses json result (response body)
    $result = json_decode($result, true);
    // Tests response http status code
    $this->assertEquals(200, $this->controller->response->statusCode());
  }

  /**
   * @method testLogout
   * Logs out the user
   * @return void
   */
  public function testLogout() {
    $result = $this->testAction('/API/logout/');
    // Tests status code
    $this->assertEquals(200, $this->controller->response->statusCode());
    // Tests if user session has been deleted
    $this->assertEquals(false, $this->controller->Auth->loggedIn());
  }
}
?>
