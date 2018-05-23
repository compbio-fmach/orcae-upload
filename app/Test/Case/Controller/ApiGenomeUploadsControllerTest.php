<?php
// Used to test controller
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');
class ApiGenomeUploadsControllerTest extends ControllerTestCase {

  // Stores reference to chunks created for this test
  // In this way those can be deleted after test
  public static $chunks = array();
  // Stores reference to uploaded files
  public static $uploaded = array();
  // Stores reference to test files folder
  public static $uploadsPath;
  // Stores new genome config
  public static $config = array(
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

  // Initializes test case
  public function setUp() {
    // Calls parent setUp method
    parent::setUp();
    // Init genome config model
    $this->GenomeConfig = ClassRegistry::init('GenomeConfig');
    // Init genome upload model
    $this->GenomeUpload = ClassRegistry::init('GenomeUpload');
    // Simulates user successful login
    CakeSession::write('Auth.User', array(
      'id' => 2,
      'username' => 'start'
    ));
    // Defines where test files are stored
    self::$uploadsPath = WWW_ROOT . 'files' . DS . 'test' . DS . 'genome_uploads';
  }

  // Executed before test case starts
  public function startTest($test) {
    $this->initGenomeConfig();
  }

  // Executed after test case ended
  public function endTest($test) {
    $this->deleteChunksUploaded();
  }

  // Saves fake Genome Config without validation
  protected function initGenomeConfig() {
    // Saves Genome Config into database
    $this->GenomeConfig->save(self::$config, false);
    // Puts saved id into currently stored data
    self::$config['id'] = $this->GenomeConfig->id;
  }

  // Initializes chunk upload (splits a file into chunks)
  protected function initChunkUpload($fileIn) {
    // Defines maxChunkSize
    $maxChunkSize = (int)Configure::read('OrcaeUpload.chunkSize');
    // Defines max number of chunks (otherwise test could loop for too much time)
    $maxChunkNumber = 3;

    // Reads file using fileIn
    $fileIn = new File(self::$uploadsPath . DS . $fileIn);
    // Defines a random file name for folder which will store chunked file
    // $folderOut = 'chunked_' . uniqid();
    // Creates output folder
    // $folderOut = new Folder(self::$uploadsPath . DS . $folderOut, true);

    // Input stream cursor
    $in = fopen($fileIn->pwd(), 'r');
    debug((int)$fileIn->size());

    // Output file (temporary file)
    $fileOut = new File(tempnam('', ''));
    $fileOut = $fileOut->name();
    debug($fileOut);
    $out = fopen($fileOut, 'a');
    array_push(self::$chunks, $fileOut);

    // Loops every char
    while(!feof($in) && count(self::$chunks) < $maxChunkNumber) {
      // Retrieves out size
      $size = fstat($out)['size'];

      // Creates another chunk
      if($size >= $maxChunkSize) {
        fclose($out);
        $fileOut = new File(tempnam('', ''));
        $fileOut = $fileOut->name();
        $out = fopen($fileOut, 'a');
        array_push(self::$chunks, $fileOut);
      }

      fwrite($out, fgetc($in));
    }

    // Closes input stream
    fclose($in);
    fclose($out);
  }

  // Deletes chunks created
  protected function deleteChunksUploaded() {
    foreach(self::$chunks as $chunk) {
      $chunk = new File($chunk);
      $chunk->delete();
    }
  }

  // Tests the creation of a new upload
  public function testCreateAnnot() {
    debug(__NAMESPACE__);
    $source = 'chr00_1-1.fasta';
    $source = new File(self::$uploadsPath . DS . $source);
    $this->initChunkUpload($source->name);

    // Defines file which will be sent
    $data = array(
      'type' => 'genome',
      'sort' => 0
    );

    // Defines a file which will be used for test as first chunk
    $chunk0 = new File(self::$chunks[0]);
    $_FILES['files'] = array(
      'tmp_name' => $chunk0->pwd(),
      'name' => $source->name,
      'type' => 'text',
      'size' => $chunk0->size(),
      'error' => 0
    );

    // Sets headers for test
    $_SERVER['HTTP_CONTENT_RANGE'] = 'bytes 0-' . $chunk0->size() . '/' . $source->size();
    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['CONTENT_LENGTH'] = $chunk0->size();

    // Tests upload of first chunk
    $result = $this->testAction('/API/genome_configs/' . self::$config['id'] . '/uploads/', array(
      'data' => $data,
      'method' => 'POST',
      'return' => 'contents'
    ));

    debug(json_decode($result));

    debug($this->controller->response->statusCode());
    debug($this->controller->response->body());
  }
}
?>
