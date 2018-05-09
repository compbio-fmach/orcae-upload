<?php
class ApiGenomeConfigsControllerTest extends ControllerTestCase {

  // Shared array of genome configuration ids created during test cases execution
  public static $created = array();

  public function setUp() {
    // Calls parent setUp method
    parent::setUp();
    // Simulates user logged in
    CakeSession::write('Auth.User', array(
      'id' => 1,
      'username' => 'start'
    ));
  }

  /**
   * @method testCreateInsert
   * Tests creation of a genome configuration
   * Should return 200 OK for succesfully created genome confugurations
   * Should return also validation reesults
   */
  public function testCreateInsert() {
    // Defines array to use as data
    $data = array(
      'type' => 'insert',
      'species_name' => 'Test species',
      'species_taxid' => 123456,
      'species_5code' => 'tstsc',
      'group_description' => "This is a test group",
      'group_welcome' => "Welcome to orcae-upload!",
      'config_species' => "Species configuration .yaml file",
      'config_bogas' => "Bogas table configuration .yaml file"
    );

    $result = json_decode($this->testAction('/API/genome_configs/', array(
      'data' => $data,
      'method' => 'POST',
      'return' => 'contents'
    )));

    // Outputs results
    debug($result);

    // Checks response status code
    $this->assertEquals(200, $this->controller->response->statusCode());
    // Checks genome config id into result
    $this->assertEquals(true, isset($result->id) && !empty($result->id));
    // checks genome config validation results
    $this->assertEquals(true, isset($result->validation));

    // Mantains a reference to created test Genome Configuration
    if(isset($result->id) && !empty($result->id)) {
      self::$created[0] = $result->id;
      debug(self::$created);
    }
  }

  public function testCreateUpdate() {
    // Defines array to use as data
    $data = array(
      'type' => 'update',
      'species_name' => 'Test species',
      'species_taxid' => 123456,
      'species_5code' => 'tstsc',
      'group_description' => "This is a test group",
      'group_welcome' => "Welcome to orcae-upload!",
      'config_species' => "Species configuration .yaml file",
      'config_bogas' => "Bogas table configuration .yaml file"
    );

    $result = json_decode($this->testAction('/API/genome_configs/', array(
      'data' => $data,
      'method' => 'POST',
      'return' => 'contents'
    )));

    // Outputs results
    debug($result);

    // Checks response status code
    $this->assertEquals(200, $this->controller->response->statusCode());
    // Checks genome config id into result
    $this->assertEquals(true, isset($result->id));
    // checks genome config validation results
    $this->assertEquals(true, isset($result->validation));

    // Finds created genome configuration into database
    $found = $this->controller->GenomeConfig->find('all', array(
      'conditions' => array(
        'GenomeConfig.id' => $result->id
      )
    ));

    // Checks number of genome configurations found
    $this->assertEquals(1, count($found));
    if(count($found) == 1) {
      // Parses result
      $found = $found[0]['GenomeConfig'];
      // Checks updated fields
      foreach (array('type', 'species_name', 'species_taxid', 'species_5code') as $attribute) {
        $this->assertEquals(true, $found[$attribute] == $data[$attribute]);
      }
      // Checks fields which should have been discarded
      foreach (array('group_description', 'group_welcome', 'config_bogas', 'config_species') as $attribute) {
        $this->assertEquals(true, empty($found[$attribute]));
      }
    }

    // Mantains a reference to created test Genome Configuration
    if(isset($result->id) && !empty($result->id)) {
      self::$created[1] = $result->id;
      debug(self::$created);
    }
  }

  /**
   * @method testUpdateUTI (alias of testUpdateInsertToUpdate, UTI specifies genome configuration's type transformation)
   * Tests the update of a Genome Configuration of type 'insert' into type 'update'
   * Should update only values interested by type 'update'
   * @return void
   */
  public function testUpdateITU() {
    debug(self::$created);
    // Sets the id of the first Genome Configuration created (which is of type 'insert')
    $id = self::$created[0];
    // Defines data which will be sent to /API/genome_configs/ with post
    $data = array(
      'type' => 'update',
      'species_name' => 'test species from type insert to type update',
      'species_taxid' => 654321,
      'species_5code' => 'code2',
      'group_description' => "Another group description",
      'group_welcome' => "Another group welcome text",
      'config_species' => "Another configuration of 'config_species'",
      'config_bogas' => "Another configuration of 'config_bogas'"
    );

    // Executes POST request
    $result = json_decode($this->testAction("/API/genome_configs/$id/", array(
      'data' => $data,
      'method' => 'POST',
      'return' => 'contents'
    )));

    // Shows request results
    debug($result);

    // Checks response status code
    $this->assertEquals(200, $this->controller->response->statusCode());
    // Checks returned genome configuration id is the same which has been sent
    $this->assertEquals(true, isset($result->id) && ($result->id == self::$created[0]));
    // checks genome config validation results
    $this->assertEquals(true, isset($result->validation));

    // Retrieves Genome Configuration from database
    $found = $this->controller->GenomeConfig->find('all', array(
      'conditions' => array(
        'GenomeConfig.id' => $result->id
      )
    ));

    // Shows query results
    debug($found);

    // Checks if only one result has been found
    $this->assertEquals(1, count($found));
    // Executes checks on found genome configuration
    if(count($found) == 1) {
      // Parses query result
      $found = $found[0]['GenomeConfig'];
      // Checks that values of found genome configuration are not empty and are different from the last ones set
      foreach(array('group_description', 'group_welcome', 'config_bogas', 'config_species') as $attribute) {
        $this->assertEquals(false, empty($found[$attribute]));
        $this->assertEquals(false, $found[$attribute] == $data[$attribute]);
      }

      // Checks that update values have been changed
      foreach(array('type', 'species_name', 'species_taxid', 'species_5code') as $attribute) {
        $this->assertEquals(true, $found[$attribute] == $data[$attribute]);
      }
    }
  }

  /*
  public function testUpdateUTI() {
    // Sets the id of the first Genome Configuration created (which is of type 'insert')
    $id = self::$created[1];
    // Defines data which will be sent to /API/genome_configs/ with post
    $data = array(
      'type' => 'insert',
      'species_name' => 'test species from type insert to type update',
      'species_taxid' => 654321,
      'species_5code' => 'code2',
      'group_description' => "Another group description",
      'group_welcome' => "Another group welcome text",
      'config_species' => "Another configuration of 'config_species'",
      'config_bogas' => "Another configuration of 'config_bogas'"
    );

    // Executes POST request
    $result = json_decode($this->testAction("/API/genome_configs/$id/", array(
      'data' => $data,
      'method' => 'POST',
      'return' => 'contents'
    )));

    // Shows request results
    debug($result);

    // Checks response status code
    $this->assertEquals(200, $this->controller->response->statusCode());
    // Checks returned genome configuration id is the same which has been sent
    $this->assertEquals(true, isset($result->id) && ($result->id == self::$created[0]));
    // checks genome config validation results
    $this->assertEquals(true, isset($result->validation));

    // Retrieves Genome Configuration from database
    $found = $this->controller->GenomeConfig->find('all', array(
      'conditions' => array(
        'GenomeConfig.id' => $result->id
      )
    ));

    // Shows query results
    debug($found);

    // Checks if only one result has been found
    $this->assertEquals(1, count($found));
    // Executes checks on found genome configuration
    if(count($found) == 1) {
      // Parses query result
      $found = $found[0]['GenomeConfig'];
      // Checks that values of found genome configuration are not empty and are different from the last ones set
      foreach(array('group_description', 'group_welcome', 'config_bogas', 'config_species') as $attribute) {
        debug(array($attribute => $found[$attribute]));
        $this->assertEquals(false, empty($found[$attribute]));
        $this->assertEquals(false, $found[$attribute] == $data[$attribute]);
      }

      // Checks that update values have been changed
      foreach(array('type', 'species_name', 'species_taxid', 'species_5code') as $attribute) {
        $this->assertEquals(true, $found[$attribute] == $data[$attribute]);
      }
    }
  }
  */
}
?>
