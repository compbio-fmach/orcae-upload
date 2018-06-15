<?php
// Tests process execution and stderror handling in a Unix environment
App::uses('Process', 'Model');
class ProcessTest extends CakeTestCase {

  public function setUp() {
    parent::setUp();
    // Defines Process model reference
    $this->Process = ClassRegistry::init('Process');
  }

  // Test process output parsing
  public function testParseProcess() {
    // Defines string to be parsed
    $shell = '17092 Fri Jun  1 16:41:30 2018';
    debug($shell);
    // Attempts to parse shell input
    $parsed = $this->Process->parse($shell);
    debug($parsed);
    // Checks if parsing returned valid object (not false)
    $this->assertEquals(false, !$parsed);
    // Checks if process id remained the same
    $this->assertEquals('17092', $parsed['process_id']);
    // Checks if process start time remained the same
    $this->assertEquals(date('Y-m-d H:i:s', strtotime('2018-06-01 16:41:30')), $parsed['process_start']);
  }

  // Tests process start
  public function testStartProcess() {
    // Defines a command which terminates immediately
    // Only pid is interesting, therefore stdout and stderr is redirected to null
    $process = $this->Process->start('php -v 2>&1 >/dev/null &');
    debug($process);
    // Tests results
    $this->assertTrue(isset($process['process_id']));
    $this->assertTrue(isset($process['process_start']));
  }

  // Tests Process model get and stop methods
  public function testStopProcess() {
    // Defines a process to be executed on a unix bash.
    // It must loop for a reasonable amount of time.
    // It has to not require much resources.
    $shell = '(sleep 10s) > /dev/null 2>&1 &';
    // Executes process
    $process = $this->Process->start($shell);
    debug($process);
    // Checks process execution result
    $this->assertEquals(false, !$process);
    // Retrieves process info
    $process = $this->Process->get($process['process_id'], $process['process_start']);
    debug($process);
    // Checks that process info has been retrieved successfully
    $this->assertEquals(false, !$process);
    // Stops process
    $stopped = $this->Process->stop($process['process_id'], $process['process_start']);
    // Checks result
    $this->assertEquals(true, $stopped);
    debug($stopped);
    // Checks if proces has actually been stopped
    $this->assertEquals(false, $this->Process->get($process['process_id'], $process['process_start']));
  }
}
?>
