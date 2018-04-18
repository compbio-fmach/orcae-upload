<?php
/**
 * Genome Configuration Session model
 * This model is bound to 'orcae_upload'.'genomecs' table
 */
App::uses('Folder', 'Utility'); // Required for folder handling (used with species_image)
App::uses('File', 'Utility'); //Required for file handling
class GenomeCS extends AppModel {

    // Defines database where table bound to SessionConfig is
    public $useDbConfig = 'orcae_upload';
    // Defines table bound to SessionConfig
    public $useTable = 'genomecs';

    // Empty array of validation rules, will be filled with errors and warnings later
    public $validate = array();

    // Defines error validation rules for cakephp validation
    protected $genomecsErrors = array(
      'type' => array(
        'rule' => array('inList', array('insert', 'update')),
        'required' => true,
        'allowEmpty' => false,
        'message' => "Selected session type is not valid"
      ),
      'species_name' => array(
        'rule' => '/^[a-zA-Z0-9]{0,255}$/',
        'allowEmpty' => true,
        'message' => "Species name must not exceed 255 limit of simple chars"
      ),
      'species_taxid' => array(
        'rule' => '/^\d+$/',
        'allowEmpty' => true,
        'message' => "Invalid species taxonomy id"
      ),
      'species_shortname' => array(
        'rule' => '/^[a-zA-Z0-9]{0,5}$/',
        'allowEmpty' => true,
        'message' => 'Invalid species shortname'
      ),
      'group_description' => array(
        'rule' => '/^(|.{0,255})$/',
        'allowEmpty' => true,
        'message' => 'Group description exceeds maximum length of 255 chars'
      )
    );

    // Defines warning validation rules for cakephp validation
    protected $genomecsWarnings = array(
      'species_taxid' => array(
        'rule' => '/^\d{1,}$/',
        'allowEmpty' => false,
        'message' => 'Species taxonomy id should be set and contain numbers only'
      ),
      'species_shortname' => array(
        'rule' => '/^[a-zA-Z0-9]{5}$/',
        'allowEmpty' => false,
        'message' => 'Species shortname should be set as a string of 5 chars'
      ),
      'species_image' => array(
        'rule' => array('validateSpeciesImage', 'extension'),
        'allowEmpty' => true,
        'message' => 'Species image type not supported'
      )
    );

    /**
     * @method validateErrors validates input before saving it into database
     * @param session is the session with data to be evaluated
     * @return true in no error/warning found
     * @return array of errors and warnings if errors or warning have been found
     */
    public function validateGenomeCS($genomecs) {
      // Defines result variable to be returned
      $result = array(
        'errors' => array(),
        'warnings' => array()
      );

      // First of all, sets session data into model
      $this->set($genomecs);

      // Secondly, sets blocking-error validation rules
      $this->validate = $this->genomecsError;
      // Cleares previously set validation error messages
      $this->validationErrors = array();
      // Validates blocking errors using cakephp model's function validates()
      if(!$this->validates()) {
        // Puts found errors into result
        $result['errors'] = $this->validationErrors;
      }

      // Validates warnings
      $this->validate = $this->genomecsWarnings;
      $this->validationErrors = array();
      if(!$this->validates()) {
        $result['warnings'] = $this->validationErrors;
      }

      // Returns true if no errors or warnings, otherwise returns error and warning messages found
      $count = count($result['warnings']) + count($result['errors']);
      return ($count > 0) ? $result : true;
    }

    /**
     * @method validateSpeciesImage sets a rule to validate species image
     * It checks image extension
     * @param check contains data to be evaluated
     * @param field specifies which field must be evaluated
     * TODO: check image size
     * @return bool which states if the validation was successful or not
     */
    public function validateSpeciesImage($check, $field) {
      // Defines image reference
      $image = $check['species_image'];

      // Validates image
      switch($field) {

        // Validates species image extensions
        case 'extension':
          // allows only jpg, png and jpeg file to be uploaded
          return preg_match('/(jpg|jpeg|png)$/', pathinfo($image['name'], PATHINFO_EXTENSION));

        case 'size':
        default: return false;
      }
    }

    /**
     * @method loadSpeciesImage retrieves the species' image relative url from the given genome configuration session
     * @param genomecs is the genome config session from where id attribute is taken
     * @param prefix is the part of url that can be added before relative path
     * @return void because url is set in session['species_image'] field
     */
    public function loadSpeciesImage(&$genomecs, $prefix = '') {

      // Defines a partial path of species images folder
      $imagesPath = 'img'.DS.'species_images'.DS;
      // Defines species images directory path in server (2nd parameter specifies to create the folder if not found)
      $imagesFolder = new Folder(WWW_ROOT.$imagesPath, true);

      // Searches for species image files with name "species_image_<id>.<allowed type>"
      $id = $genomecs['id'];
      $images = $imagesFolder->find("^species_image_$id\.(jpeg|jpg|png)$");
      // Search returns array of results
      if(!empty($images)) {
        // Sets species image attribute
        $genomecs['species_image'] = $prefix.$imagesPath.$images[0];
      }
      // Sets species image attribute as empty
      else {
        $genomecs['species_image'] = null;
      }
    }

    /**
     * @method uploadSpeciesImage uploads species_image into images folder
     * File is temporarily uploaded into @param genomecs['species_image'] (which is a reference to $_FILE['species_image'])
     *
     * @return true if image has been uploaded
     * @return false otherwise
     */
    public function uploadSpeciesImage(&$genomecs) {

      // Defines a pratial images folder path
      $imagesPath = 'img'.DS.'species_images'.DS;

      // Define server path to images folder
      $imagesPath = WWW_ROOT.$imagesPath;

      // Checks for upload errors
      if($genomecs['species_image']['error'] != UPLOAD_ERR_OK) {
        return false;
      }

      // Retrieves image extension
      $extension = exif_imagetype($genomecs['species_image']['tmp_name']);
      // If not valid extension found, returns an error
      if(!$extension) {
        return false;
      }

      // Retrieves images folder (creates it if nothing found)
      $imagesFolder = new Folder($imagesPath, true);

      // Searches for species image files with name "species_image_<id>.<allowed type>"
      $images = $imagesFolder->find("^species_image_".$genomecs['id']."\.(.*)$");

      // Deletes every image associated with the same session id
      foreach ($images as $image) {
        // Creates image file from directory path
        $image = new File($imagesPath.$image);
        // Deletes file
        $image->delete();
      }

      // Creates image file url
      // No need to check image_type_to_extension (already checked exif_imagetype before)
      $image = 'species_image_'.$genomecs['id'].image_type_to_extension($extension, true);
      // Moves file to images directory
      return move_uploaded_file($genomecs['species_image']['tmp_name'], $imagesPath.$image);
    }
}
?>
