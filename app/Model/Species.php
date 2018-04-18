<?php
/**
 * Species model represent a species saved into ORCAE
 * It is bound to orcae database
 * orcae_bogas.taxid table structure:
 *  - NCBI_taxid int(10) unsigned -> bound to session.species_taxid
 *  - internal_taxid
 *  - 2code char(2)
 *  - 5code char(5) -> bound to session.species_shortname
 *  - organism varchar(50) -> bound to session.species_name
 */
class Species extends AppModel {
  // Defines model's database (orcae_bogas)
  public $useDbConfig = 'orcae_bogas';
  // Defines model's table (orcae_bogas.taxid)
  public $useTable = 'taxid';

  /**
   * @method parseSpecies parses orcae_bogas.taxid to orcae_upload.species_*
   * @param _species defines orcae_bogas table row
   * @param prefix allows to add a prefix to returned species fields
   * @return parsed species array
   */
  public function parseSpecies($_species, $prefix = 'species_') {
    $species = array(
      $prefix.'taxid' => $_species['NCBI_taxid'],
      $prefix.'name' => $_species['organism'],
      $prefix.'5code' => $_species['5code']
    );

    return $species;
  }
}
?>
