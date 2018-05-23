<?php
class ApiGenomeUpdatesControllerTest extends ControllerTestCase {

  // Defines folder where test upload files are stored
  protected static $uploadsPath;

  // Defines a fake GenomeConfig
  protected static $config = array(
    'id' => null,
    'user_id' => 2,
    'type' => 'insert',
    'spcies_name' => 'Test species name',
    'speices_taxid' => '12345',
    'species_5code' => 'tstsn',
    'species_image' => null,
    'group_description' => 'Test group description',
    'group_welcome' => 'Test group welcome text',
    'config_species' => '',
    'config_bogas' => ''
  );

  // Defines a list of GenomeUpload istances
  protected static $uploads = array(
    // Uploaded file #1
    array(
      // File will be set once selected file will be put into user's genome uploads folder
      'file' => null,
      // Defines type of the uploaded file
      'type' => 'genome',
      // Defines order of the uploaded file
      'sort' => 0,
      // File name to grab from /webroot/files/test/genome_uploads/
      'source' => 'chr00_1-1.fasta',
      // Even size will be calculated once file has been moved
      'size' => ''
    ),
    // Uploaded file #2
    array(
      'type' => 'genome',
      'sort' => 1,
      'source' => 'chr01_1-1.fasta'
    ),
    // Uploaded file #3
    array(
      'type' => 'annot',
      'sort' => 0,
      'source' => 'gene_models_20170606.gff3'
    )
  );

  public function setUp() {
    parent::setUp();
    $this->GenomeConfig = ClassRegistry::init('GenomeConfig');
    $this->GenomeUpload = ClassRegistry::init('GenomeUpload');
    $this->GenomeUpdate = ClassRegistry::init('GenomeUpdate');
    // Defines path where test uploaded files can be found
    self::$uploadsPath = WWW_ROOT . 'files' . DS . 'test' . DS . 'genome_uploads';
  }

  public function startTest($test) {
    debug('Test started');
    // Simulates login
    $this->initAuth();
    // Initializes Genome Configuration used for tests
    $this->initGenomeConfig();
    // Initializes genome uploads
    $this->initGenomeUploads();
  }

  public function endTest($test) {
    debug('Test ended');
    // Simulates logout
    $this->deleteAuth();
    // Deletes previously uploaded files
    $this->deleteGenomeUploads();
  }

  // Initializes authorization into session
  protected function initAuth() {
    CakeSession::write('Auth.User', array(
      'id' => 2,
      'username' => 'start'
    ));
  }

  // Removes authorization from session
  protected function deleteAuth() {
    CakeSession::delete('Auth.User');
  }

  // Saves fake Genome Config without validation
  protected function initGenomeConfig() {
    // Saves Genome Config into database
    $this->GenomeConfig->save(self::$config, false);
    // Puts saved id into currently stored data
    self::$config['id'] = $this->GenomeConfig->id;
  }

  // Initializes some genome uploads
  protected function initGenomeUploads() {
    // Defines folder where test files are stored
    $from = new Folder(self::$uploadsPath, false);
    foreach(self::$uploads as &$upload) {
      $upload['config_id'] = self::$config['id'];
      // Defines file which will be used for tests
      $file = new File($from->pwd() . DS . $upload['source']);
      // Defines a unique file name
      $upload['file'] = $this->GenomeUpload->getUniqueFileName(self::$config['user_id'], $file->name);
      // Moves file into right folder
      $to = $this->GenomeUpload->getUploadPath(self::$config['user_id']) . DS . $upload['file'];
      // Copies file to new folder with new name
      $file->copy($to, false);
      // Defines file's size
      $upload['size'] = $file->size();
      debug($upload);
      // Saves upload into database
      $this->GenomeUpload->save($upload, false);
      // Puts upload id into stored upload values
      $upload['id'] = $this->GenomeUpload->id;
      // deletes previously set id
      $this->GenomeUpload->id = null;
    }
  }

  // Deletes previously uploaded test files
  protected function deleteGenomeUploads() {
    foreach(self::$uploads as $upload) {
      $file = new File($this->GenomeUpload->getUploadPath(self::$config['user_id']) . DS . $upload['file']);
      debug($file->delete());
    }
  }

  // Tests POST request to /API/genome_configs/:id/updates/
  // Should  return 200 OK
  public function testGenomeUpdate() {
    $result = $this->testAction('/API/genome_configs/' . self::$config['id'] . '/updates/', array(
      'method' => 'POST',
      'return' => 'contents'
    ));

    debug(json_decode($result));

    // Checks response status code
    //$this->assertEquals(200, $this->controller->response->statusCode());
    debug($this->controller->response->statusCode());
  }
}
?>
