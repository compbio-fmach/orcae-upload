<?php
// Cretaes a fake table for species into orcae_upload_test database
// The table will be automatically deleted after testing
class TaxidFixture extends CakeTestFixture {
  // Imports original table and its rows
  public $table = 'taxid';
  public $import = array('table' => 'taxid', 'connection' => 'orcae_bogas', 'records' => true);
}
?>
