<?php namespace Pixie;

use Mockery as m;

class TestCase extends \PHPUnit_Framework_TestCase {
    protected $mockConnection;
    protected $mockPdo;
    protected $mockPdoStatement;

    public function setUp()
    {
        // Launch the container
        if (!class_exists('\\Pixie\\Container')) {
            new \Viocon\Container('Pixie\\Container');
        }

        $this->mockPdoStatement = $this->getMock('\\PDOStatement');

        $this->mockPdo = $this->getMock('\\Pixie\\MockPdo', array('prepare', 'setAttribute', 'quote'));
        $this->mockPdo->expects($this->any())->method('prepare')->will($this->returnValue($this->mockPdoStatement));
        $this->mockPdo->expects($this->any())->method('quote')->will($this->returnCallback(function($value){
                    return "'$value'";
                }));

        $this->mockConnection = m::mock('\\Pixie\\Connection');
        $this->mockConnection->shouldReceive('getPdoInstance')->andReturn($this->mockPdo);
        $this->mockConnection->shouldReceive('getAdapter')->andReturn('mysql');
        $this->mockConnection->shouldReceive('getAdapterConfig')->andReturn(array('prefix' => 'cb_'));
    }

    public function tearDown()
    {
        m::close();
    }

    public function callbackMock()
    {
        $args = func_get_args();

        return count($args) == 1 ? $args[0] : $args;
    }
}

class MockPdo extends \PDO
{
    public function __construct()
    {

    }
}