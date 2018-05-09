<?php
/**
 * This class extends UploadComponen taken from https://github.com/hugodias/FileUpload and licensed under MIT
 * It does not takes completely the component, because it needs to be sightly modified in order to work with genome files
 */
App::uses('UploadComponent', 'Controller/Component');
class GenomeUploadComponent extends UploadComponent {

  // Auth component required to handle upload rights
  public $components = array('Auth');

  // Model GenomeUpload
  public $GenomeUpload = null;

  // Current genome config id
  public $genome_config_id = null;

  // Overrides constructor
  public function __construct(ComponentCollection $collection, $options = null) {
    // Loads GenomeUpload model
    $this->GenomeUpload = ClassRegistry::init('GenomeUpload');
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
   * @method get_unique_filename
   * @return filename
   */
  protected function get_unique_filename($file_path, $name, $size, $type, $error, $index, $content_range) {
    // Offset of bytes to upload. If equals to 0, starts new file upload
    $uploaded_bytes = $this->get_uploaded_bytes($content_range);
    // Unique name to be returned
    $unique_name = false;

    // Breaks file information
    $ext = pathinfo($name, PATHINFO_EXTENSION);
    $name = pathinfo($name, PATHINFO_FILENAME);

    // New file
    if($uploaded_bytes <= 0) {
      // Generates unique file name
      do {
        $unique_name = implode('.', array(uniqid(), $ext));
      } while (is_file($this->get_upload_path($unique_name)));
    }
    // Appends to existent file
    else {
      // Retrieves file name from parameters
      $name = $this->get_param_name($index);
      // Searches if file exists
      if(is_file($this->get_upload_path($name))) {
        // Checks offset
        if ($uploaded_bytes === $this->get_file_size($this->get_upload_path($name))) {
          $unique_name = $name;
        }
      }
    }

    // Returns unique file name or false if error
    return $unique_name;
  }

  /**
   * @method get_file_title
   * @return title from request parameters
   */
  protected function get_param_title($index = null) {
    $title = isset($_REQUEST['title']) ? $_REQUEST['title'] : '';
    if(is_array($title)) {
      $title = isset($title[$index]) ? $title[$index] : '';
    }
    return $title;
  }

  /**
   * @method get_file_name
   * @return name from request parameters
   */
  protected function get_param_name($index = null) {
    $name = isset($_REQUEST['name']) ? $_REQUEST['name'] : '';
    if(is_array($name)) {
      $name = isset($name[$index]) ? $name[$index] : '';
    }
    return $name;
  }

  /**
   * Overrides @method get_user_id
   * Uses user id saved into session, instead of session id
   * @return id of current user
   */
  protected function get_user_id() {
    return $this->Auth->user('id');
  }

  /**
   * @method get_genome_config_id
   * @return id of current genome config
   */
  protected function get_genome_config_id() {
    return $this->genome_config_id;
  }

  /**
   * @method set_genome_config_id sets id of current genome configuration
   * @return void
   */
  public function set_genome_config_id($id) {
    $this->genome_config_id = $id;
  }

  /**
   * @method get_upload_path returns upload path, based on current
   * @return path for upload directory
   */
  /*
  protected function get_upload_path($file_name = null, $version = null) {
    $upload_dir = $this->options['upload_dir'];
    $upload_path = parent::get_upload_path($file_name, $version);
    $genome_config_id = $this->get_genome_config_id();
    // Adds genome config id subdirectory
    $upload_path = preg_replace("/^$upload_dir", "$upload_dir/$genome_config_id/");
    return $upload_path;
  }
  */

  protected function validate($uploaded_file, $file, $error, $index) {

    // Checks genome configuration id
    if(empty($this->get_genome_config_id())) {
      $file->error = "Genome configuration id not set";
      return false;
    }

    // Checks if file can be uploaded
    if($file->name === false) {
      $file->error = "Unable to upload file";
      return false;
    }

    // Checks if if file name is valid (present and into boudaries)
    if(!preg_match('/^(.){1,50}$/', pathinfo($file->name, PATHINFO_FILENAME))) {
      $file->error = "File name is not valid";
      return false;
    }

    // Checks file title
    $title = $this->get_param_title($index);
    $is_genome = preg_match('/^genome(\d+)$/', $title);
    $is_annot = preg_match('/^annot(\d+)$/', $title);
    if(!$is_genome && !$is_annot) {
      $file->error = "File title is not valid";
      return false;
    }

    // Validates file extension
    return parent::validate($uploaded_file, $file, $error, $index);
  }

  // Added database handling alongside file handling
  protected function handle_file_upload($uploaded_file, $name, $size, $type, $error, $index = null, $content_range = null) {
    // Calculates offset of uploaded bytes
    $uploaded_bytes = $this->get_uploaded_bytes($content_range);
    // If new file is going to be created: deletes previous version
    if($uploaded_bytes <= 0) {
      // Deletes file row with same title
      $this->GenomeUpload->deleteAll(array(
        'GenomeUpload.title' => $this->get_param_title($index),
        'GenomeUpload.user_id' => $this->get_user_id(),
        'GenomeUpload.config_id' => $this->get_genome_config_id()
      ));

      // Deletes file saved on server using prevData attribute
      $prevData = $this->GenomeUpload->prevData;
      if(!empty($prevData)) {
        // prevData is an array, therefore loops are needed
        foreach($prevData as $p) {
          $name = $p['GenomeUpload']['name'];
          if(is_file($this->get_upload_path($name))) {
            unlink($this->get_upload_path($name));
          }
        }
      }
    }

    // Calls original method for creating a file
    $file = parent::handle_file_upload($uploaded_file, $name, $size, $type, $error, $index, $content_range);

    // Retrieves title from request
    $title = $this->get_param_title($index);
    // Executes query only if no error has been found during file upload
    if (empty($file->error)) {
      // Saves into database new uploads
      if($uploaded_bytes == 0) {
        // Uses GenomeUpload model
        $this->GenomeUpload->save(array(
          'name' => $file->name, // Current file name generated with uniqueid().ext
          'user_id' => $this->get_user_id(), // File owner
          'config_id' => $this->get_genome_config_id(),
          'source' => $name, // Current file from which data is taken
          'size' => $size, // Total size of file
          'title' => $title, // Title (name which will be displayed)
        ));
      }
    }

    // Returns the file object created (contains errors)
    return $file;
  }

  /**
   * @method get handles GET requests
   * Checks authorization before sending response
   * @return response
   */
  public function get($print_response = true) {
      $file_name = $this->get_file_name_param();
      // Case file name specified: returns only if authorized
      if ($file_name) {
        $files = $this->GenomeUpload->find(
          'first',
          // Retrieved only if authorized
          array(
            'conditions' => array(
              'GenomeUpload.user_id' => $this->Auth->user('id'),
              'GenomeUpload.config_id' => $this->get_config_id(),
              'GenomeUpload.name' => $file_name
            )
          )
        );

        if(count($files) == 1) {
          // Download file if requested
          if ($print_response && $this->get_query_param('download')) {
              return $this->download();
          }

          $file = $this->get_file_object($file_name);
        }
        else {
          $file = null;
        }

        $response = array(
            $this->get_singular_param_name() => $file
        );
      }
      // No file name specified
      else {
          // Does not return multiple files: returns the list of currently active files
          $files = $this->GenomeUpload->find(
            'all',
            array(
              // Retrieved only if authorized
              'conditions' => array(
                'GenomeUpload.user_id' => $this->get_user_id(),
                'GenomeUpload.config_id' => $this->get_genome_config_id()
              ),
              // Orders by title first, then by id (max id is the actually valid file)
              'order' => array(
                'GenomeUpload.title ASC',
                'GenomeUpload.id DESC'
              )
            )
          );

          // Parses files
          foreach($files as &$file) {
            $file = $file['GenomeUpload'];
            $file['uploaded'] = $this->get_file_size($this->get_upload_path($file['name']));
          }

          // Puts files into response
          $response = array(
              // e.g. options['param_name'] == $_GET['files']
              $this->options['param_name'] => $files
          );
      }
      return $this->generate_response($response, $print_response);
  }

  /**
   * @method delete handles DELETE requests
   * Does not use parent funztion: overwrites it
   * @return response
   */
   public function delete($print_response = true, $title = null) {
     if(empty($title)) {
       $file_names = array();
     } else {
       // Executes query to retrieve file names bound to passed title
       $file_names = $this->GenomeUpload->find(
         'all',
         array(
           'conditions' => array(
             'GenomeUpload.user_id' => $this->Auth->user('id'),
             'GenomeUpload.config_id' => $this->get_genome_config_id(),
             // Searches by array of ile names
             'GenomeUpload.title' => $title,
           ),
           'fields' => array('GenomeUpload.name')
         )
       );

       // Parses file names
       foreach($file_names as &$file_name) {
         $file_name = $file_name['GenomeUpload']['name'];
       }
     }

     // Deletes from database every file name
     $this->GenomeUpload->deleteAll(array(
       'GenomeUpload.name' => $file_names,
       'GenomeUpload.user_id' => $this->Auth->user('id'),
       'GenomeUpload.config_id' => $this->get_genome_config_id()
     ));

     // Phisically deletes files
     $response = array();
     foreach ($file_names as $file_name) {
       $file_path = $this->get_upload_path($file_name);
       $success = is_file($file_path) && $file_name[0] !== '.' && unlink($file_path);
       if ($success) {
           foreach ($this->options['image_versions'] as $version => $options) {
               if (!empty($version)) {
                   $file = $this->get_upload_path($file_name, $version);
                   if (is_file($file)) {
                       unlink($file);
                   }
               }
           }
       }
       $response[$file_name] = $success;
     }

     return $this->generate_response($response, $print_response);
   }

}
?>
