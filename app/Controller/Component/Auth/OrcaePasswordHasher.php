<?php
/**
 * OrcaePasswordHasher implements sha1 hashing method of Orcae
 */
App::uses('AbstractPasswordHasher', 'Controller/Component/Auth');
class OrcaePasswordHasher extends AbstractPasswordHasher {

  /**
   * @method hash implements hashing of a plain text password
   * @return hashed password
   */
  public function hash($password) {
      return sha1($password);
  }

  /**
   * @method check cheks if password matches hash
   * @return true if password matches the given hash
   * @return false otherwise
   */
  public function check($password, $hashedPassword) {      
      return sha1($password) === $hashedPassword;
  }
}
?>
