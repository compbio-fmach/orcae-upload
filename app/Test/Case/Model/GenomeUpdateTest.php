<?php
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');
App::import('Vendor', 'Spyc', array('file' => 'spyc' . DS . 'Spyc.php'));
// Tests genome updates
class GenomeUpdateTest extends CakeTestCase {

  // Loads fixtures (fake tables) into this test case
  public $fixtures = array('app.Taxid', 'app.Groups', 'app.UsersGroups');

  // Defines fake user
  public static $user = array('id' => 2);

  // Defines a fake genome config istance
  public static $config = array(
    'id' => null,
    'user_id' => null,
    'species_name' => 'test species',
    'species_taxid' => 101010,
    'species_5code' => 'Trial',
    'group_description' => 'Test description',
    'group_welcome' => 'Test welcome text',
    'config_bogas' => '',
    'config_species' => ''
  );

  // Defines fake genome uploads
  public static $uploads = array(
    // Genome file
    array(
      'id' => null,
      'config_id' => null,
      'stored_as' => null,
      'type' => 'genome',
      'sort' => 0,
      // This is used to grab the file from test files
      'source' => 'chr00_1-1.fasta',
      'size' => null
    ),
    // Annot file
    array(
      'id' => null,
      'config_id' => null,
      'stored_as' => null,
      'type' => 'annot',
      'sort' => 0,
      // This is used to grab the file from test files
      'source' => 'annot_00.gff3',
      'size' => null
    )
  );

  // Defines update which will be tested
  public static $update = array('id' => null);

  public function setUp() {
    parent::setUp();
    // Defines model references
    $this->GenomeConfig = ClassRegistry::init('GenomeConfig');
    $this->GenomeUpload = ClassRegistry::init('GenomeUpload');
    $this->GenomeUpdate = ClassRegistry::init('GenomeUpdate');
    $this->Process = ClassRegistry::init('Process');
    $this->Species = ClassRegistry::init('Species');
    // Initializes test variables
    $this->initTest();
  }

  // Initializes test
  protected function initTest() {
    // Sets user id in config
    self::$config['user_id'] = self::$user['id'];
    // Saves genome config
    $this->GenomeConfig->save(self::$config);
    // Retrieves id
    self::$config['id'] = $this->GenomeConfig->id;
    // Saves genome uploads
    foreach(self::$uploads as &$upload) {
      // Defines upload directory
      $folder = new Folder($this->GenomeUpload->getUploadPath(self::$user['id']));
      // Takes file from test folder and puts it into upload folder
      $file = new File(WWW_ROOT . 'files' . DS . 'test' . DS . 'genome_uploads' . DS . $upload['source']);
      // Defines new name for copied file
      $upload['stored_as'] = uniqid() . '.' . $file->ext();
      // Copy file into uploads folder
      $copied = $file->copy($folder->pwd() . DS . $upload['stored_as']);
      // Updates other upload istance's attributes
      $upload['config_id'] = self::$config['id'];
      $upload['size'] = $file->size();
      // Deletes previously set id
      unset($this->GenomeUpload->id);
      // Takes file and
      $this->GenomeUpload->save($upload);
      // Retrieves saved id
      $upload['id'] = $this->GenomeUpload->id;
    }
    // Populates update istance
    self::$update['config_id'] = self::$config['id'];
    self::$update['config'] = self::$config;
    self::$update['uploads'] = self::$uploads;
  }

  // Test update's initialization
  // This does not need parallel execution
  public function testInitUpdate() {
    // Defines reference to Genome Update istance
    $update = self::$update;
    // Initializes update
    $result = $this->GenomeUpdate->initUpdate($update);
    // Should return true: all the updates that hve the status 'updating' does not have a process executed
    $this->assertSame(true, $result);
    // Executes a background process which will last for 10 seconds
    $process = $this->Process->start('(sleep 10s) > /dev/null 2>&1 &');
    // Checks that process has started
    $this->assertEquals(false, !$process);
    // Updates Genome Update with process' data
    $update['process_id'] = $process['process_id'];
    $update['process_start'] = $process['process_start'];
    // Deletes modified field (it is auto updated)
    unset($update['modified']);
    // Saves updates to Genome Update
    $this->GenomeUpdate->save($update);
    unset($this->GenomeUpdate->id);
    // Tries to save new update before process has finished: should return error
    $update2 = $update;
    $update2['id'] = null;
    // Unsets some fields that need to be reinitialized
    unset($update2['created'], $update2['modified']);
    $result = $this->GenomeUpdate->initUpdate($update2);
    // Checks if update procedure returned an error message instead of true
    $this->assertSame(false, $result === true);
    // Updates original Genome Update istance
    self::$update = $update;
    // Stops process
    $this->Process->stop($update['process_id'], $update['process_start']);
    // Tries to insert another Genome Update: should return true because no process is going on
    $result = $this->GenomeUpdate->initUpdate($update2);
    $this->assertSame(true, $result === true);
    // Stops ongoing process
    $this->Process->stop($update2['process_id'], $update2['process_start']);
  }

  // Tests configuration files initialization
  public function testInitConfig() {
    $update = self::$update;
    $config = $update['config'];
    // Initializes orcae_<5code>.yaml
    $result = $this->GenomeConfig->writeSpeciesYaml($config);
    debug($result);
    $this->assertSame(true, $result);
    // Initializes orcae_conf.yaml
    $result = $this->GenomeConfig->writeConfigYaml($config);
    $this->assertSame(true, $result);
    // Checks if orcae_<5code>.yaml has been created
    $file = new File(Configure::read('OrcaeUpload.orcaeConfig') . DS . $config['species_5code'] . '_conf.yaml', false);
    $this->assertSame(true, $file->exists());
    // Checkcs if orcae_conf.yaml has been updated
    $file = new File(Configure::read('OrcaeUpload.orcaeConfig') . DS . 'orcae_conf.yaml', false);
    $this->assertSame(true, $file->exists());
    // Parses raw .yaml file into php assciative array
    $yaml = Spyc::YAMLLoad($file->read());
    // Checks the entry relative to the newly created genome
    $entry = isset($yaml[$config['species_5code']]) ? $yaml[$config['species_5code']] : null;
    $this->assertEquals(false, !$entry);
    $this->assertEquals(false, !$entry['current']);
  }

  // Tests folder creation and folder parsing
  public function testCreateUpdateFolder() {
    // Retrieves update istance
    $update = &self::$update;
    // Retrieves config istance
    $config = $update['config'];
    // Sets an id if it hasn't been already set
    if(!$update['id']) {
      do {
        // Defines random idea
        $update['id'] = rand(1, getrandmax());
        // Defines directory bound to that id
        $folder = new Folder($this->GenomeUpdate->getUpdatePath($config['user_id'], $update['id']));
        // Executes until an unused id has been found
      } while($folder->path);
    }
    // Lauches folder creation
    $this->GenomeUpdate->createUpdateFolder($update);
    // Retrieves genome update folder
    $updateFolder = new Folder($this->GenomeUpdate->getUpdatePath($config['user_id'], $update['id']));
    // States if update folder exists
    $this->assertEquals(false, !$updateFolder->path);
    // DEBUG
    debug($updateFolder->find());
    // Checks update files
    $this->assertSame(1, count($updateFolder->find('genome.fasta')));
    $this->assertSame(1, count($updateFolder->find('annot.gff3')));
  }

  // Tests folder parsing
  public function testParseUpdateFolder() {
    // Checks if update istance has an id set
    if(!self::$update['id']) {
      // Creates update folder
      $this->testCreateUpdateFolder();
    }
    // Retrieves update path
    $result = $this->GenomeUpdate->parseUpdateFolder(self::$update);
    // debug($result);
    $this->assertSame(false, !$result);
  }

  // Test upload of csv into database
  public function testLoadUpdateFolder() {
    // Checks if update istance has an id set
    if(!self::$update['id']) {
      // Creates update folder
      $this->testParseUpdateFolder();
    }

    // Test upload of csv files info database
    $result = $this->GenomeUpdate->loadUpdateFolder(self::$update);
    // Checks result
    $this->assertSame(true, $result);
  }

  // Tests saving new genome
  public function testSaveConfig() {
    $update = &self::$update;
    $config = &$update['config'];
    // Saves genome configuration
    $result = $this->GenomeUpdate->saveConfig($update);
    $this->assertSame(true, $result);
    // Searches for inserted species
    $result = $this->Species->find('count', array(
      'conditions' => array(
        'Species.NCBI_taxid' => $config['species_taxid'],
        'Species.organism' => $config['species_name'],
        'Species.5code' => $config['species_5code']
      )
    ));
    // Checks query result
    $this->assertSame(1, (int)$result);
    // Searches for group inserted
    $result = $this->Species->query('SELECT * FROM groups WHERE taxid = ' . $config['species_taxid'] . ';');
    $this->assertSame(count($result), 1);
    // Retrieves inserted group
    $group = $result[0]['groups'];
    debug($group);
    // Searches for user_group relation
    $result = $this->Species->query('SELECT * FROM users_groups WHERE group_id = ' . $group['id'] . ' AND user_id = ' . $config['user_id'] . ';');
    $this->assertSame(count($result), 1);
    debug($result);
  }

  // Executed when last test case ended
  public function endTest($test) {
    $update = self::$update;
    $config = $update['config'];
    $uploads = $update['uploads'];
    // Deletes uploads
    foreach($uploads as $upload) {
      $file = new File($this->GenomeUpload->getUploadPath($config['user_id'], $upload['stored_as']), false);
      $file->delete();
    }
    // Deletes update folder
    $folder = new Folder($this->GenomeUpdate->getUpdatePath($config['user_id'], $update['id']), false);
    $folder->delete();
  }
}
?>
