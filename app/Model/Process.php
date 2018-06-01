<?php
// This model aims to handle background processes of Orcae-Upload
class Process extends AppModel {
  // This model does not usa a table
  public $useTable = false;

  /**
   * @method parse
   * Parses result of process information
   * @return process in form of array(id, start) output is correct
   * @return false otherwise
   */
  protected function parse($shell) {
    debug($shell);
    preg_match('/^(\d+)\s(\w+)\n$/', $shell, $output);
    debug($output);
    $output = preg_split('/\s/', $shell, 1);
    // Creates process from output
    $process['process_id'] = isset($output[0]) ? $output[0] : null;
    $process['process_start'] = isset($output[1]) ? $output[1] : null;
    // Retruns process only if values are correct
    return (!$process['process_id'] || !$process['process_start']) ? null : $process;
  }

  /**
   * @method get
   * @return process in form of array(id, start) if it is currently executed
   * @return false otherwise
   */
  public function get($id, $start) {
    // Runs shell command which will retrieve id and start time
    $output = shell_exec('ps --no-headers -o pid,lstart ' . $id);
    // Retruns process value
    return $this->parse($output);
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
    $shell.= '; ps --no-headers -o pid,lstart `echo "$!"`';
    // Retrieves output
    $output = shell_exec($shell);
    // Returns parsed result
    return $this->parse($output);
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
    return !shell_exec('kill ' . $id . ' 2>&1 1> /dev/null') ? true : false;
  }
}
?>
