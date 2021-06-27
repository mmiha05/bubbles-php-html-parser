<?php

class NonTrimableTest extends \Codeception\Test\Unit
{
    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testScriptTag()
    {
        $html_string = '<script> const a = true; </script>';

        $parser = new BubblesParser\BubblesCore($html_string);

        $result = $parser->parse_pure();

        $text_inside = $result[1];

        $this->assertEquals($text_inside->attributes['textContent'], ' const a = true; ');
    }

    public function testPreTag()
    {
        $pre_content = '
        My text
        inside
         pre     tag
        ';
        $html_string = "<pre>{$pre_content}</pre>";

        $parser = new BubblesParser\BubblesCore($html_string);

        $result = $parser->parse_pure();

        $text_inside = $result[1];

        $this->assertEquals($text_inside->attributes['textContent'], $pre_content);
    }
}
