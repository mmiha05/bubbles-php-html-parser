<?php

class WithTextNodeChildrenTest extends \Codeception\Test\Unit
{
    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testBasicWithOneChildren()
    {
        $html_string = '<div>Hello World</div>';

        $parser = new BubblesParser\BubblesCore($html_string);

        $result = $parser->parse_pure();

        $this->assertEquals(count($result), 2);

        $text_node = $result[1];

        $this->assertEquals($text_node->type, 'textNode');

        $this->assertEquals($text_node->attributes['textContent'], 'Hello World');

        $this->assertEquals($text_node->depth, 1);
    }

    public function testAdvancedWithOneChildren()
    {
        $html_string = '<div id="foo" class="bar" contentEditable> Hello World</div>';

        $parser = new BubblesParser\BubblesCore($html_string);

        $result = $parser->parse_pure();

        $this->assertEquals(count($result), 2);

        $text_node = $result[1];

        $this->assertEquals($text_node->type, 'textNode');

        $this->assertEquals($text_node->attributes['textContent'], ' Hello World');

        $this->assertEquals($text_node->depth, 1);
    }
}
