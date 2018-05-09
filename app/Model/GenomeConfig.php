<?php
/**
 * Genome Configuration model
 * This model is bound to 'orcae_upload'.'genome_config' table
 */
App::uses('Folder', 'Utility'); // Required for folder handling (used with species_image)
App::uses('File', 'Utility'); //Required for file handling
class GenomeConfig extends AppModel {

    // Defines database where table bound to SessionConfig is
    public $useDbConfig = 'orcae_upload';
    // Defines table bound to SessionConfig
    public $useTable = 'genome_configs';

    // Empty array of validation rules, will be filled with errors and warnings later
    public $validate = array();

    // Defines error validation rules for cakephp validation
    protected $errors = array(
      'type' => array(
        'rule' => array('inList', array('insert', 'update')),
        'required' => true,
        'allowEmpty' => false,
        'message' => "Selected session type is not valid"
      ),
      'species_name' => array(
        'species_name_format' => array(
          'rule' => '/^[a-zA-Z0-9\s]{0,255}$/',
          'allowEmpty' => true,
          'message' => "Species name must not exceed 255 limit of simple chars"
        ),
        'species_name_congruency' => array(
          'rule' => 'validateSpecies',
          'message' => "Species attributes does not match any species data currently on Orcae"
        )
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
    protected $warnings = array(
      'species_taxid' => array(
        'rule' => '/^\d{1,}$/',
        'allowEmpty' => false,
        'message' => 'Species taxonomy id should be set and contain numbers only'
      ),
      'species_shortname' => array(
        'rule' => '/^[a-zA-Z0-9]{5}$/',
        'allowEmpty' => false,
        'message' => 'Species shortname should be set as a string of 5 chars'
      )
    );

    /**
     * @method validate
     * Validates GenomeConfig passed as associative array
     * Validates only set fields (which could be empty however)
     * @param data is the Genome Configuration which will be validated
     * @param type specifies to validate errors, warnings or both
     * @return array of errors and warnings
     */
    public function validate($data, $type = null) {
      if($type != 'errors' && $type != 'warnings') {
        // Data about errors and warnings to be returned
        $return = array(
          'errors' => $this->validate($data, 'errors'),
          'warnings' => $this->validate($data, 'warnings')
        );
        // Deletes previous validation errors results
        $this->validationErrors = array();
        // Returns validation results
        return $return;
      }

      // Defines validation rules
      $rules = ($type == 'errors') ? $this->errors : $this->warnings;
      // Removes rules which does not match data set fields
      $rules = array_intersect_key($rules, $data);
      // Sets data to be validated
      $this->set($data);
      // Sets validation rules
      $this->validate = $rules;
      // Deletes previous validation results
      $this->validationErrors = array();
      // Validates
      $this->validates();
      // Returns validation errors
      return $this->validationErrors;
    }

    /**
     * @method validateSpecies
     * This method validates if there is a species which matches name, taxid and 5code of validated data
     * If
     */
    public function validateSpecies($check) {
      // Imports species model
      $Species = ClassRegistry::init('Species');
      // Retreives other fields
      $data = $this->data['GenomeConfig'];
      // Validates congruency only for update type
      if($data['type'] != 'update') {
        return true;
      }
      // Checks if species with same name, taxid and 5code exists on Orcae
      $existsSpecies = $Species->find('count', array(
        'conditions' => array(
          'Species.organism' => $data['species_name'],
          'Species.NCBI_taxid' => $data['species_taxid'],
          'Species.5code' => $data['species_5code']
        )
      ));
      return $existsSpecies > 0;
    }

    /**
     * @method getSpeciesImage
     * Return species images of Genome Configuration given as param, if any
     * @param data is an associative array representing Genome Configuration
     * @return species_image url
     */
    public function getSpeciesImage($data) {
      // List all matching images
      $images = preg_grep('/^species_image_'.$data['id'].'\./', scandir(WWW_ROOT.'img/species_images/'));
      // Returns only the first image found
      return !empty($images) ? Router::url('/', true).'img/species_images/'.array_shift($images) : '';
    }
}
?>
