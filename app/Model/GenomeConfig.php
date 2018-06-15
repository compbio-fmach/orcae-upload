<?php
/**
 * Genome Configuration model
 * This model is bound to 'orcae_upload'.'genome_config' table
 */
App::uses('Folder', 'Utility'); // Required for folder handling (used with species_image)
App::uses('File', 'Utility'); //Required for file handling
App::uses('ConnectionManager', 'Model'); // required to access database configurations
// Imports spyc for yaml handling
App::import('Vendor', 'Spyc', array('file' => 'spyc' . DS . 'Spyc.php'));
class GenomeConfig extends AppModel {

    // Defines database where table bound to SessionConfig is
    public $useDbConfig = 'orcae_upload';
    // Defines table bound to SessionConfig
    public $useTable = 'genome_configs';

    // Constructor: imports Species, UsersGroups and Groups models
    public function __construct($id = false , $table = null , $ds = null ) {
      parent::__construct($id, $table, $ds);
      // Loads Species model
      $this->Species = ClassRegistry::init('Species');
      // Loads Group model
      $this->Group = ClassRegistry::init('Group');
      // Loads UserGroup model
      $this->UserGroup = ClassRegistry::init('UserGroup');
    }

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
      if($data['type'] != 'update') return true;
      // Checks if species with same name, taxid and 5code exists on Orcae
      $exists = $Species->find('count', array(
        'conditions' => array(
          'Species.organism' => $data['species_name'],
          'Species.NCBI_taxid' => $data['species_taxid'],
          'Species.5code' => $data['species_5code']
        )
      ));
      return $exists > 0;
    }

    /**
     * @method getSpeciesImage
     * Return species images of Genome Configuration given as param, if any
     * @param data is an associative array representing Genome Configuration
     * @return species_image url
     */
    public function getSpeciesImage($data) {
      // Defines images folder
      $folder = new Folder(WWW_ROOT . 'img' . DS . 'species_images' . DS, false);
      // Checks if folder exists
      if(!$folder->path) return false;
      // List all matching image files
      $images = preg_grep('/^species_image_'.$data['id'].'\./', $folder->find());
      // Returns only the first image found
      // WARNING: uses array_shift beacuse indexes are not modified form preg_grep
      return count($images) > 0 ? (Router::url('/', true) . 'img' . DS . 'species_images' . DS . array_shift($images)) : false;
    }

    // Retrieves genome updates bound to this config istance
    public function getGenomeUpdates($config) {
      // List of updates to be returned
      $updates = array();
      // Executes query using this config attributes
      $result = $this->find('all', array(
        'conditions' => array(
          'GenomeUpdate.id' => $config['id']
        ),
        'joins' => array(
          'table' => 'genome_updates',
          'alias' => 'GenomeUpdate',
          'type' => 'inner',
          'conditions' => array(
            'GenomeConfig.id' => 'GenomeUpdate.config_id'
          )
        ),
        'fields' => 'GenomeUpdate.*',
        'order' => 'GenomeUpdate.id DESC'
      ));
      // Parses results
      if(!empty($result)) {
        if(isset($result['GenomeUpdate']) && !empty($result['GenomeUpdate'])) {
          $updates = $result['GenomeUpdate'];
        }
      }
      // Returns populated array
      return $result;
    }

    // Wrapper for getGenomeUpdates, retrieves only first genome update (the one with highest id)
    public function popGenomeUpdates($config) {
      $updates = $this->getGenomeUpdates($config);
      return array_shift($updates);
    }

    // Saves species data into database
    public function saveSpecies(&$config) {
      // Creates 2code
      $ch1 = chr(rand(97,122));
      $ch2 = chr(rand(97,122));
      $config['species_2code'] = strtoupper(substr($config['species_name'] . $ch1 . $ch2, 0, 2));
      // Inserts into database
      $result = $this->Species->save(array(
        'NCBI_taxid' => $config['species_taxid'],
        'internal_taxid' => null,
        '2code' => $config['species_2code'],
        '5code' => $config['species_5code'],
        'organism' => $config['species_name']
      ));
      // Wrong insert execution's results
      if(!$result) {
        return false;
      }
      // Data successfully inserted
      else {
        // Updates species id
        $config['species_id'] = $this->Species->id;
        return true;
      }
    }

    // Saves species image into orcae's folder, if it is set (not blocking)
    public function saveSpeciesImage(&$config) {
      $speciesImage = $this->getSpeciesImage($config);
      // Executes onfly if image exists
      if(!empty($speciesImage)) {
        // References to species image file
        $speciesImage = new File($speciesImage);
        // Full path to Orcae's images folder
        $orcaeSpeciesImage = Configure::read('OrcaeUpload.orcaeWeb') . DS . 'app' . DS . 'webroot' . DS . 'img' . DS . $speciesImage->name();
        // Copies species image to Orcae's folder
        $speciesImage->copy($orcaeSpeciesImage);
      }
    }

    // Saves group information
    public function saveSpeciesGroup(&$config) {
      // Saves group into Orcae's db
      $saved = $this->Group->save(array(
        'id' => null,
        'name' => '',
        'taxid' => $config['species_taxid'],
        'description' => $config['group_description'],
        'invitationmail' => null,
        'upgrademail' => null,
        'alertmail' => null,
        'welcome' => $config['group_welcome']
      ));
      // Returns false if saving went wrong
      if(!$saved) return false;
      // Updates group id
      $config['group_id'] = $this->Group->id;
      // Saves many-to-many relationshipt between Group and User, called UserGroup
      $saved = $this->UserGroup->save(array(
        'id' => null,
        'group_id' => $config['group_id'],
        'user_id' => $config['user_id'],
        'status' => null
      ));
      // Returns saving results
      return $saved;
    }

    // Saves species config yaml file
    public function writeSpeciesYaml($config) {
      // Creates new orcae_<5code>.yaml file
      debug(Configure::read('OrcaeUpload.orcaeConfig') . DS . $config['species_5code'] . '_conf.yaml');
      $speciesFile = new File(Configure::read('OrcaeUpload.orcaeConfig') . DS . $config['species_5code'] . '_conf.yaml', true);
      // Prepares file to be saved into orcae_conf directory
      $speciesYaml= $speciesFile->prepare($config['config_species']);
      // Tries to write species configuration into Orcae's folder
      return $speciesFile->write($speciesYaml);
    }

    // Saves orcae config .yaml paragraph
    public function writeConfigYaml($config) {
      // Defines 5code of species which will be updated
      $shortname = $config['species_5code'];
      // Reads and parses user created .yaml configuration
      $yaml = Spyc::YAMLLoad($config['config_bogas']);
      // Retrieves first element of .yaml configuration, which should be <5code>, but we don't know which
      $yaml = array($shortname => array_shift($yaml));
      // Leaves only 'current' section of .yaml configuration
      $current = isset($yaml[$shortname]['current']) ? $yaml[$shortname]['current'] : array();
      $yaml[$shortname] = array('current' => $current);
      // Defines standard configuration to be intersected with user defined values
      $yaml[$shortname]['current'] = array_intersect(
        // Default values
        array(
          'current_release' => 1,
          'description' => '',
          'major_version' => 1,
          'minor_version' => 1,
          'security' => 'develop',
          'source' => '',
          'start_locus' => ''
        ),
        // User specified values
        $yaml[$shortname]['current']
      );
      // Adds fields to .yaml configuration which cannot be modified by the user
      $db = ConnectionManager::getDataSource('orcae_bogas')->config; // Database's data
      $yaml[$shortname]['current'] = array_merge(
        $yaml[$shortname]['current'],
        array(
          'password'  => $db['password'],
          'database'  => $db['database'],
          'port'      => (int)$db['port'],
          'hostname'  => $db['host'],
          'username'  => $db['login']
        )
      );

      // Creates a reference to old configuration file
      $oldFile = new File(Configure::read('OrcaeUpload.orcaeConfig') . DS . 'orcae_conf.yaml', true);
      debug($oldFile);
      // Parses file into .yaml format
      $oldYaml = Spyc::YAMLLoad($oldFile->read());
      // Adds newly created configuration section
      $oldYaml = array_merge($oldYaml, $yaml);
      // Saves updated yaml file
      return $oldFile->write(Spyc::YAMLDump($oldYaml, false, 0));
    }
}
?>
