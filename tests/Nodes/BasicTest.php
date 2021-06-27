<?php

class BasicTest extends \Codeception\Test\Unit
{
    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testBasicFirst()
    {
        $html_string = '<div><p><span></span></p></div>';

        $parser = new BubblesParser\BubblesCore($html_string);
        
        $result = $parser->parse();

        $this->assertEquals($result[0]->tag, 'div');
        $this->assertEquals($result[0]->children[0]->tag, 'p');
        $this->assertEquals($result[0]->children[0]->children[0]->tag, 'span');
    }

    public function testBasicSecond()
    {
        $html_string = '<div><img src="foo.jpeg" alt=""/>some text</div>';

        $parser = new BubblesParser\BubblesCore($html_string);
        
        $result = $parser->parse();

        $this->assertEquals($result[0]->tag, 'div');
        $this->assertEquals($result[0]->children[0]->tag, 'img');
        $this->assertEquals($result[0]->children[1]->type, 'textNode');
        $this->assertEquals($result[0]->children[1]->attributes['textContent'], 'some text');
    }

    public function testBasicThird()
    {
        $html_string = '<!DOCTYPE html><!--comment--><html><body></html>';

        $parser = new BubblesParser\BubblesCore($html_string);
        
        $result = $parser->parse();

        $this->assertEquals($result[0]->tag, null);
        $this->assertEquals($result[0]->type, 'docType');
        $this->assertEquals(empty($result[0]->children), true);

        $this->assertEquals($result[1]->tag, null);
        $this->assertEquals($result[1]->type, 'comment');
        $this->assertEquals($result[1]->attributes['textContent'], 'comment');
        $this->assertEquals(count($result[1]->children), 0);

        $this->assertEquals($result[2]->tag, 'html');
        $this->assertEquals($result[2]->type, 'node');
        $this->assertEquals(count($result[2]->children), 1);

        $this->assertEquals($result[2]->children[0]->tag, 'body');
        $this->assertEquals($result[2]->children[0]->type, 'node');
        $this->assertEquals(count($result[2]->children[0]->children), 0);
    }
}
