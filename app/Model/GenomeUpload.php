<?php
  class GenomeUpload extends AppModel {
    public $useDbConfig = 'orcae_upload';
    public $useTable = 'genome_uploads';

    public $prevData = null;

    /**
     * @method beforeDeleteAll gets executed before deleteAll
     * Executes a find query with same conditions as deleteAll
     * Stores previous values into @var this->prevData
     * @return void
     */
    protected function beforeDeleteAll($conditions) {
      $this->prevData = $this->find(
        'all',
        array(
          'conditions' => $conditions
        )
      );
    }

    public function deleteAll($conditions, $cascade = true, $callbacks = false) {
      // Executes 'before' callback
      $this->beforeDeleteAll($conditions);
      // Executes standard deletion function
      return parent::deleteAll($conditions, $cascade, $callbacks);
    }
  }
?>
