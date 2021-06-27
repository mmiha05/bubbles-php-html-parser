<?php

class demoTest extends \Codeception\Test\Unit
{
    public $someInt = 2;
    protected function _before()
    {
        $this->someInt = 4;
    }

    protected function _after()
    {
    }

    // tests
    public function testDemo()
    {
        $this->assertEquals(4, $this->someInt);
    }
}
