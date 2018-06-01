<?php
// Tests process execution and stderror handling in a Unix environment
App::uses('Process', 'Model');
class ProcessTest extends CakeTestCase {
  public $fixtures = array('app.process');

  public function setUp() {
    parent::setUp();
    // Defines Process model reference
    $this->Process = ClassRegistry::init('Process');
  }

  // Tests process start
  public function testStartProcess() {
    // Defines a command which retrieves system info
    $process = $this->Process->start('(lscpu)');
    // DEBUG
    debug($process);
    // Tests results
    $this->assertTrue(isset($process['process_id']));
    $this->assertTrue(isset($process['process_start']));
  }

  // Tests process stop
  public function testStopProcess() {
    // Defines a process to be executed on a unix bash.
    // It must loop for a reasonable amount of time.
    // It has to not require much resources.
    $shell = '(for i in {1..1000} do sleep 100 done &)';
    // Executes process
    $process = $this->Process->start($shell);
    debug($process);
    // Checks process execution result
    $this->assertEquals(true, $process);
    // Stops process
    $stopped = $this->Process->stop($process['process_id'], $process['process_start']);
    // Checks result
    $this->assertEquals(false, $stopped);
    debug($stopped);
    // Checks if proces has actually been stopped
    $this->assertEquals(false, $this->Process->get($process['process_id'], $process['process_start']));
  }
}
?>
