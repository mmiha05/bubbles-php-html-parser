<?php

class OneNodeClosingTest extends \Codeception\Test\Unit
{
    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testLevel()
    {
        $html_string = '<div></div>';

        $parser = new BubblesParser\BubblesCore($html_string);

        $parser->parse_pure();

        $this->assertEquals($parser->get_current_level(), -1);
    }

    public function testLevelWithAttributes()
    {
        $html_string = '<div id class="someClass"></div>';

        $parser = new BubblesParser\BubblesCore($html_string);

        $parser->parse_pure();

        $this->assertEquals($parser->get_current_level(), -1);
    }

    public function testLevelSelfClosing()
    {
        $html_string = '<img>';

        $parser = new BubblesParser\BubblesCore($html_string);

        $parser->parse_pure();

        $this->assertEquals($parser->get_current_level(), -1);
    }
    
    public function testLevelSelfClosingAlternate()
    {
        $html_string = '<img src="image.jpeg" />';

        $parser = new BubblesParser\BubblesCore($html_string);

        $parser->parse_pure();

        $this->assertEquals($parser->get_current_level(), -1);
    }
}
