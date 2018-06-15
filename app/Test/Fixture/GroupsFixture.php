<?php
// Cretaes a fake table for groups into orcae_upload_test database
// The table will be automatically deleted after testing
class GroupsFixture extends CakeTestFixture {
  // Imports original table and its rows
  public $table = 'groups';
  public $import = array('table' => 'groups', 'connection' => 'orcae_bogas', 'records' => false);
}
?>
