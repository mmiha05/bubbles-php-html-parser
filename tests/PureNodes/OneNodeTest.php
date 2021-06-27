<?php

class OneNodeTest extends \Codeception\Test\Unit
{
    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testOneNode()
    {
        $html_string = '<p></p>';

        $parser = new BubblesParser\BubblesCore($html_string);

        $result = $parser->parse_pure();

        $parsed = $result[0];

        $this->assertEquals($parsed->tag, 'p');
    }

    public function testOneNodeWithAttributes()
    {
        $html_string = '<div id="someId" foo=\'bar\' abc empty></div>';

        $parser = new BubblesParser\BubblesCore($html_string);

        $result = $parser->parse_pure();

        $parsed = $result[0];

        $this->assertEquals($parsed->tag, 'div');

        $this->assertEquals(count($parsed->attributes), 4);

        $this->assertEquals($parsed->attributes['id'], 'someId');

        $this->assertEquals($parsed->attributes['foo'], 'bar');

        $this->assertEquals($parsed->attributes['abc'], 'true');

        $this->assertEquals($parsed->attributes['empty'], 'true');
    }

    public function testOneNodeWithEscapedSlashes()
    {
        $html_string = '<div id="te\"st"></div>';

        $parser = new BubblesParser\BubblesCore($html_string);

        $result = $parser->parse_pure();

        $parsed = $result[0];

        $this->assertEquals($parsed->tag, 'div');

        $this->assertEquals(count($parsed->attributes), 1);

        $this->assertEquals($parsed->attributes['id'], 'te\"st');
    }

    public function testSelfEnclosingTag()
    {
        $html_string = '<img src="picture.jpg">';

        $parser = new BubblesParser\BubblesCore($html_string);

        $result = $parser->parse_pure();

        $parsed = $result[0];

        $this->assertEquals($parsed->tag, 'img');

        $this->assertEquals(count($parsed->attributes), 1);

        $this->assertEquals($parsed->attributes['src'], 'picture.jpg');
    }

    public function testSelfEnclosingTagAlternate()
    {
        $html_string = '<img src="picture.jpg"/>';

        $parser = new BubblesParser\BubblesCore($html_string);

        $result = $parser->parse_pure();

        $parsed = $result[0];

        $this->assertEquals($parsed->tag, 'img');

        $this->assertEquals(count($parsed->attributes), 1);

        $this->assertEquals($parsed->attributes['src'], 'picture.jpg');
    }
    
    public function testSelfEnclosingTagAlternateWithEmptyAttribute()
    {
        $html_string = '<img src="picture.jpg" lazy/>';

        $parser = new BubblesParser\BubblesCore($html_string);

        $result = $parser->parse_pure();

        $parsed = $result[0];

        $this->assertEquals($parsed->tag, 'img');

        $this->assertEquals(count($parsed->attributes), 2);

        $this->assertEquals($parsed->attributes['src'], 'picture.jpg');

        $this->assertEquals($parsed->attributes['lazy'], 'true');
    }
}
