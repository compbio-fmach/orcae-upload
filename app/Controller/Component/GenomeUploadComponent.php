<?php
/**
 * This class extends UploadComponen taken from https://github.com/hugodias/FileUpload and licensed under MIT
 * It does not takes completely the component, because it needs to be sightly modified in order to work with genome files
 */
App::uses('UploadComponent', 'Controller/Component');
class GenomeUploadComponent extends UploadComponent {

  // Auth component required to handle upload rights
  public $components = array('Auth');

  // Current genome config id
  public $genome_config_id = null;

  // Overrides constructor
  public function __construct(ComponentCollection $collection, $options = null) {
    // Initializes genome upload model
    $this->GenomeUpload = ClassRegistry::init('GenomeUpload');
    // Initializes genome config modle
    $this->GenomeConfig = ClassRegistry::init('GenomeConfig');
    // Calls parent constructor
    parent::__construct($collection, $options);
  }

  /**
   * @method get_uploaded_bytes calculates bytes offset using content range
   * @return bytes
   */
  protected function get_uploaded_bytes($content_range) {
    return $this->fix_integer_overflow((int)$content_range[1]);
  }

  /**
   * @method get_content_range
   * @return content range header, if any
   */
  protected function get_content_range() {
    $content_range_header = $this->get_server_var('HTTP_CONTENT_RANGE');
    // debug($content_range_header);
    $content_range = $content_range_header ? preg_split('/[^0-9]+/', $content_range_header) : null;
    // debug($content_range);
    return $content_range;
  }

  /**
   * @method get_upload_path
   * Overrides parent's one with Genome Upload model's one
   */
  protected function get_upload_path($file_name = null, $version = null) {
    // Retrieves current user id
    $userId = $this->get_user('id');
    return $this->GenomeUpload->getUploadPath($userId, '') . DS . $file_name;
  }

  /**
   * @method get_unique_filename
   * @return filename
   */
  protected function get_unique_filename($file_path, $name, $size, $type, $error, $index, $content_range) {
    // Offset of bytes to upload. If equals to 0, starts new file upload
    $uploaded_bytes = $this->get_uploaded_bytes($content_range);
    // Unique name to be returned
    $unique_name = '';

    // New file
    if($uploaded_bytes <= 0) {
      // Generates new unique file name
      $unique_name = $this->GenomeUpload->getUniqueFileName($this->get_user_id(), $name);
    }
    // Appends to existent file
    else {
      // Retrieves file name from parameters
      // Name refers ro GenomeUpload.file
      $name = $this->get_post_param('stored_as');
      // Searches if file exists
      if(is_file($this->get_upload_path($name))) {
        // Checks offset
        if ($uploaded_bytes == $this->get_file_size($this->get_upload_path($name))) {
          $unique_name = $name;
        }
      }
    }

    // Returns unique file name or empty if error
    return $unique_name;
  }

  /**
   * @method get_user
   * @return user istance or @return attribute if @param attr is set
   * User is stored into session
   */
  protected function get_user($attr = null) {
    return $this->Auth->user($attr);
  }

  // Wrapper for get_user
  protected function get_user_id() {
    return $this->get_user('id');
  }

  /**
   * @method get_genome_config
   * @return genome_config istance or @return attribute if @param attr is set
   */
  protected function get_genome_config($attr = null) {
    // Case genome_config not found
    if(!$this->genome_config) {
      return null;
    }
    // Case no attribute has been specified
    if(!$attr) {
      return $this->genome_config;
    }
    // Case specific attributed has been requested
    else {
      return isset($this->genome_config[$attr]) ? $this->genome_config[$attr] : null;
    }
  }

  /**
   * @method set_genome_config allows to set genome configuration istance
   * @return void
   */
  public function set_genome_config($genome_config) {
    $this->genome_config = $genome_config;
  }

  /**
   * @method get_genome_upload
   * Retireves genome upload from POST param
   * @return genome_upload object
   */
  protected function retrieve_genome_upload() {
    $genome_upload = new \stdClass();
    $genome_upload->id = null;
    $genome_upload->config_id = null;
    $genome_upload->stored_as = $this->get_post_param('stored_as');
    $genome_upload->type = $this->get_post_param('type');
    $genome_upload->sort = $this->get_post_param('sort');
    $genome_upload->source = $this->get_post_param('source');
    return $genome_upload;
  }

  protected function validate($uploaded_file, $file, $error, $index) {
    // Checks if any error has already been found
    if($error == UPLOAD_ERR_OK) {
      // Defines if it is a simple append or if it is a new file
      $content_range = $this->get_content_range();
      $uploaded_bytes = $this->get_uploaded_bytes($content_range);
      $append = ($uploaded_bytes > 0);

      // Validations required only at the moment of the new file creation
      if(!$append) {
        // Defines genome upload sent using post
        $genome_upload = $this->retrieve_genome_upload();

        // Validates type sent through post
        if(!preg_match('/^(annot|genome)$/', $genome_upload->type)) {
          $file->error = 'File type is not valid';
          return false;
        }

        // Validates type and extension together
        $ext = pathinfo($file->name, PATHINFO_EXTENSION);
        //debug($ext);
        $type = $genome_upload->type;
        //debug($type);
        // Validates annotation files
        $is_annot = ($type == 'annot' && $ext == 'gff3');
        $is_genome = ($type == 'genome' && $ext == 'fasta');
        if(!$is_annot && !$is_genome) {
          $file->error = 'Sent file\'s extension does not match file type';
          return false;
        }

        // Validates sort number
        //debug($genome_upload->sort);
        if(!preg_match('/^(\d)+$/', $genome_upload->sort)) {
          $file->error = 'Sort number is not valid';
          return false;
        }

        // Retrieves configuration from db, using id
        $this->retrieve_genome_config();
        // Validates genome configuration
        if(!$this->get_genome_config()) {
          $file->error = 'User not allowed to access this resource';
          return false;
        }
      }

      // Checks if if file name is valid (present and into boudaries)
      if(!preg_match('/^(.){1,50}$/', pathinfo($file->name, PATHINFO_FILENAME))) {
        $file->error = "File name is not valid";
        return false;
      }
    }

    // Validates file extension
    return parent::validate($uploaded_file, $file, $error, $index);
  }

  // Retrieves genome configuration from id and sets it as attribute
  protected function retrieve_genome_config() {
    $configId = $this->get_genome_config('id');
    $userId = $this->get_user('id');
    $config = $this->GenomeConfig->find('first', array(
      'conditions' => array(
        'GenomeConfig.id' => $configId,
        'GenomeConfig.user_id' => $userId
      )
    ));
    // Setores found genome configuration
    $this->set_genome_config(!empty($config) ? $config['GenomeConfig'] : null);
  }

  // Added database handling alongside file handling
  protected function handle_file_upload($uploaded_file, $name, $size, $type, $error, $index = null, $content_range = null) {
    // Calculates offset of uploaded bytes
    $uploaded_bytes = $this->get_uploaded_bytes($content_range);
    // Defines if the file has to be appended to an existent one or if it is a new one
    $append = $uploaded_bytes > 0;

    // Calls original method for creating a file
    $file = parent::handle_file_upload($uploaded_file, $name, $size, $type, $error, $index, $content_range);

    // Executes query only if no error has been found during file upload
    if (empty($file->error)) {
      if(!$append) {
        // Deletes rows file associated with files with same type and sort
        $this->GenomeUpload->deleteAll(array(
          'GenomeUpload.type' => $this->get_post_param('type'),
          'GenomeUpload.sort' => $this->get_post_param('sort'),
          'GenomeUpload.config_id' => $this->get_genome_config('id')
        ));
        // Takes deleted data
        $deleted = $this->GenomeUpload->prevData;
        // Deletes files assciated with deleted rows
        if(!empty($deleted)) {
          foreach($deleted as $d) {
            $d = $d['GenomeUpload'];
            if(is_file($this->get_upload_path($d['file']))) {
              unlink($this->get_upload_path($d['file']));
            }
          }
        }
        // Uses GenomeUpload model
        $this->GenomeUpload->save(array(
          'config_id' => $this->get_genome_config('id'),
          'stored_as' => $file->name, // Current file name generated with uniquid
          'source' => $name, // Name of the file from which content is actually taken
          'size' => $size, // Total size of file
          'type' => $this->get_post_param('type'), // Title (name which will be displayed)
          'sort' => $this->get_post_param('sort')
        ), false);
      }
    }

    // Returns the file object created (contains errors)
    return $file;
  }

  // Returns file object
  protected function get_file_object($file_name) {
    $file = parent::get_file_object($file_name);
    $file = (object)array(
      'name' => $file->name,
      'size' => $file->size
    );
    return $file;
  }

  /**
   * @method get handles GET requests
   * Checks authorization before sending response
   * @return response
   */
  public function get($print_response = true) {
    // Defines 'stroed as' param (uniquely identifies file upload)
    $stored_as = $this->get_query_param('stored_as');

    // Retrieves Genome config
    $this->retrieve_genome_config();

    // Defines query conditions
    $conditions = array();
    // Returns only uploads owned by the user
    $conditions['GenomeUpload.config_id'] = $this->get_genome_config('id');
    // If stored_as is set, returns specific file
    if($stored_as) {
      $conditions['GenomeUpload.stored_as'] = $stored_as;
    }

    // Executes query
    $results = $this->GenomeUpload->find('all', array(
      'conditions' => $conditions
    ));

    // Parses results
    foreach($results as &$result) {
      $result = $result['GenomeUpload'];
      $result['file'] = $this->get_file_object($result['stored_as']);
    }

    // Creates response
    $response = $results;
    // Case single result is required
    if($stored_as) {
      // retrieves only first element, if any
      $response = array_shift($response);
      // Creates response to be printed
      if($print_response) {
        $response = array(
            $this->get_singular_param_name() => $response
        );
      }
    }
    // Case multiple results
    else {
      if($print_response) {
        // Puts files into response
        $response = array(
            // e.g. options['param_name'] == $_GET['files']
            $this->options['param_name'] => $response
        );
      }
    }

    // returns and prints reponse, if required
    return $this->generate_response($response, $print_response);
  }

  /**
   * @method delete handles DELETE requests
   * Does not use parent funztion: overwrites it
   * @return response
   */
  public function delete($print_response = true) {
     // Defines a response
     $response = null;
     // Retrieves parameter from query
     $stored_as = $this->get_query_param('stored_as');

     // Deletes file wich matches 'stored_as' parameter
     if($stored_as) {
       // Retrieves genome config
       $this->retrieve_genome_config();
       // Deletes file from database
       $result = $this->GenomeUpload->deleteAll(array(
         'GenomeUpload.stored_as' => $stored_as,
         'GenomeUpload.config_id' => $this->get_genome_config('id')
       ));
       // Retrieves deleted row
       $deleted = array_shift($this->GenomeUpload->prevData);
       // Deletes files from filesystem
       if($deleted) {
         $upload = $deleted['GenomeUpload'];
         // Defines path to the file
         $file = $this->get_upload_path($upload['stored_as']);
         // Attempts to remove file
         $success = is_file($file) && $file[0] !== '.' && unlink($file);
         // Adds deleted flag
         $upload['deleted'] = $success;
         // Deletes file path from returned file
         unset($file);
         // Adds upload object to response
         $response = $upload;
       }
     }

     // If ther result will be pronted out: creates proper structure
     if($print_response) {
       $response = array(
           $this->get_singular_param_name() => $response
       );
     }

     // Generates response
     return $this->generate_response($response, $print_response);
   }

}
?>
