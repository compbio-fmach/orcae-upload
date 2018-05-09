<?php
class ApiSpeciesControllerTest extends ControllerTestCase {
  /**
   * @method setUp is executed before every other method
   * Logs the user in
   * @return void
   */
  public function setUp() {
    // Calls parent setUp method
    parent::setUp();
    // Calls login API
    $this->testAction('/API/login', array(
      'method' => 'POST',
      'data' => array(
        'username' => 'start',
        'password' => 'changeme!'
      )
    ));
  }

  /**
   * @method testIndex
   * Tests if it returns species stored on orcae_bogas.taxid
   * @return void
   */
  public function testWrongIndex() {
    // Retrieves species stored into orcae_bogas
    $results = json_decode($this->testAction('/API/species/', array(
      'method' => 'GET',
      'data' => array(
        'name' => ''
      ),
      'return' => 'contents'
    )));
    // Checks response http status
    $this->assertEquals(200, $this->controller->response->statusCode());
    // Checks responses
    $this->assertEquals(0, count($results));
    // Shows results
    debug($results);
  }

  /**
   * @method testIndex
   * Tests if it returns species stored on orcae_bogas.taxid
   * @return void
   */
  public function testCorrectIndex() {
    // Retrieves species stored into orcae_bogas
    $results = json_decode($this->testAction('/API/species/', array(
      'method' => 'GET',
      'data' => array(
        'name' => 'Trypanosoma vandePeerii',
        'taxid' => 666,
        '5code' => 'Trpee'
      ),
      'return' => 'contents'
    )));

    // Checks response http status
    $this->assertEquals(200, $this->controller->response->statusCode());
    // Checks number of results
    $this->assertEquals(1, count($results));
    // Shows results
    debug($results);
  }
}
?>
