<?php
/**
 * Genome Configuration model
 * This model is bound to 'orcae_upload'.'genome_config' table
 */
App::uses('Folder', 'Utility'); // Required for folder handling (used with species_image)
App::uses('File', 'Utility'); //Required for file handling
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

    /**
     * @method initConfig
     * Initializes configuration for orcae's update
     * It works like addNewGenome.pl
     * @return true if initialization executed successfully
     * @return string error message otherwise
     */
    public function initConfig(&$config) {
      // TODO: Execute configuration as a transaction
      // Saves species data
      if(!$this->saveSpecies($config)) {
        return "Could not save the species into Orcae's database";
      };
      // Saves species image if is set
      $this->saveSpeciesImage($config);
      // Saves group information into Orcae's db
      if(!$this->saveGroup($config)) {
        return "Could not save Group data into Orcae's database";
      }
      // Saves species YAML configuration file
      if(!$this->saveSpeciesYaml($config)) {
        return "Could not save species .yaml configuration file";
      }
      // Saves Orcae's YAML configuration file
      if(!$this->saveOrcaeYaml($config)) {
        return "Could not update Orcae's .yaml configuration file";
      }
      // If execution reaches this point: Orcae has been succesfully updated
      return true;
    }

    // Saves species data into database
    protected function saveSpecies(&$config) {
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

    // Saves species image into orcae's folder, if it is set
    protected function saveSpeciesImage(&$config) {
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
    protected function saveGroup(&$config) {
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
    protected function saveSpeciesYaml($config) {
      $speciesConfigFile = new File(Configure::read('OrcaeUpload.orcaeConfig') . DS . $config['species_5code'] . '_conf.yaml', true);
      $speciesConfigYaml = $speciesConfigFile->prepare($config['config_species']);
      debug($speciesConfigFile);
      // Tries to write species configuration into Orcae's folder
      if(!$speciesConfigFile->write($speciesConfigYaml)) {
        return false;
      }
      return true;
    }

    // Saves orcae config .yaml paragraph
    protected function saveOrcaeYaml($config) {
      $shortname = $config['species_5code'];
      // Makes a backup copy of Orcae config file
      $orcaeConfigFile = new File(Configure::read('OrcaeUpload.orcaeConfig') . DS . 'orcae_conf.yaml', true);
      $result = $orcaeConfigFile->copy($orcaeConfigFile->Folder->pwd() . DS . 'orcae_conf.yaml.bak', true);
      if(!$result) return false;
      // Reads Orcae's config file and parses from yaml to php array
      $orcaeConfigYaml = Spyc::YAMLLoad($orcaeConfigFile->read());
      // Writes new yaml ORCAE config file (orcae_conf.yaml)
      // Parses config yaml stored into database into associative array
      $readConfigYaml = Spyc::YAMLLoad($config['config_bogas']);
      // Creates a new associative array which will override the saved one (sets default values)
      $dataSource = $this->getDataSource()->config;
      // Creates associative array with default files which represents yaml configuration file
      $newConfigYaml = array(
        $shortname => array(
          'current' => array(
            'password' => $dataSource['password'],
      			'database' => $dataSource['database'],
      			'port' => (int)$dataSource['port'],
      			'current_release' => 1,
      			'description' => '',
      			'hostname' => $dataSource['host'],
      			'username' => $dataSource['login'],
      			'major_version' => 1,
      			'minor_version' => 1,
      			'security' => 'develop',
      			'source' => '',
      			'start_locus' => ''
          )
        )
      );
      // Retrieves only body of configuration file
      $body = &$newConfigYaml[$shortname]['current'];
      // Checks read config file
      if(count($readConfigYaml) == 1) {
        // Retrieves current shortname
        $key = array_keys($readConfigYaml)[0];
        $current = @$readConfigYaml[$key]['current'];
        $body['current_release'] = @$current['current_release'];
        $body['description'] = @$current['description'];
        $body['major_version'] = @$current['major_version'];
        $body['minor_version'] = @$current['minor_version'];
        $body['security'] = @$current['security'];
        $body['source'] = @$current['source'];
        $body['start_locus'] = @$current['start_locus'];
      }
      // Updates orcae config yaml associative file
      $orcaeConfigYaml[$shortname] = $newConfigYaml[$shortname];
      // Writes updated yaml file
      $result = $orcaeConfigFile->write(Spyc::YAMLDump($orcaeConfigYaml, false, 0));
      if(!$result) return false;

      // Overwrites
      // debug(array_keys($newConfigYaml)[0]);
      // debug($newConfigYaml);
      // debug($orcaeConfigYaml);
      // debug($orcaeConfigYaml);
      return true;
    }
}
?>
