<?php
// This model aims to handle background processes of Orcae-Upload
class Process extends AppModel {
  // This model does not usa a table
  public $useTable = false;

  /**
   * @method parse
   * Parses result of process information
   * @param shell is the string which comes from the shell and needs to be parsed
   * @return process in form of array(id, start) output is correct
   * @return false otherwise
   */
  public function parse($shell) {
    // Defines parsed object which will be returned
    $parsed = null;
    // Sanitizes string: Deletes unwanted (horizontal) whitespaces from process output
    $shell = preg_replace('/\h+/', ' ', $shell);
    // debug($shell);
    // Retrieves string in format <pid> <start time>
    preg_match('/(\d+)(\s)(.+)(\n)/', $shell, $parsed);
    // DEBUG
    // debug($parsed);
    // Only first result is taken into account
    $parsed = isset($parsed[0]) ? $parsed[0] : '';
    // Splits result at the first whitespace, which divides pid from start date
    $parsed = preg_split('/\s/', $parsed, 2);
    // Trasforms output into an associative array
    $parsed['process_id'] = isset($parsed[0]) ? $parsed[0] : null;
    $parsed['process_start'] = isset($parsed[1]) ? $parsed[1] : null;
    unset($parsed[0], $parsed[1]);
    // Checks if process id and start time are valid
    if(!$parsed['process_id'] || !$parsed['process_start']) {
      return false;
    }
    // Parses process start into iso date format
    $parsed['process_start'] = date('Y-m-d H:i:s', strtotime($parsed['process_start']));
    // Retruns object representing parsed string
    return $parsed;
  }

  /**
   * @method get
   * @return process in form of array(id, start) if it is currently executed
   * @return false otherwise
   */
  public function get($id, $start) {
    // Runs shell command which will retrieve id and start time
    $shell = shell_exec('ps --no-headers -o pid,lstart ' . $id);
    // Retruns process value
    $parsed = $this->parse($shell);
    // Returns false if no valid process has been found
    if(!$parsed) {
      return false;
    }
    // Checks if parsed and passed start time matches
    if($parsed['process_start'] != $start) {
      return false;
    }
    // Returns process object only if reaches this point
    return $parsed;
  }

  /**
   * @method start
   * Start a parallel command
   * Then, retrieves pid and start times
   * WARNING: @param shell must contain a command which launches parallel process
   * Otherwise, shell exec qill wait until process has finished
   * @return process if process has been started
   * @return false otherwise
   */
  public function start($shell) {
    // Adds command which retrieves pid and start time
    $shell.= ' ps --no-headers -o pid,lstart $!';
    // Retrieves output
    $shell = shell_exec($shell);
    // Returns parsed result
    return $this->parse($shell);
  }

  /**
   * @method stop
   * Stops an asynchronous (background) process
   * @return true if process has been stopped
   * @return false if error
   */
  public function stop($id, $start) {
    // Checks if process is still executed
    $process = $this->get($id, $start);
    // Process not found: return false
    if(!$process) return false;
    // Kills process otherwise
    // Checks if there is an error
    return !shell_exec('kill ' . $id) ? true : false;
  }
}
?>
