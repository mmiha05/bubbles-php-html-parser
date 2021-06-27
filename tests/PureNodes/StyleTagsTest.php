<?php;

class StyleTagsTest extends \Codeception\Test\Unit
{

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testStyleTagsComments()
    {
        $html_string = "<style>\n/* this text <div></div> should not trigger a new element*/\n#id { color: red;}\n</style>";

        $parser = new BubblesParser\BubblesCore($html_string);
        
        $result = $parser->parse_pure();

        // Style tag + text node inside style tag
        $this->assertEquals(count($result), 2);

        $this->assertEquals($result[1]->attributes['textContent'], "\n/* this tag <div></div> should not trigger a new element*/\n#id { color: red;}\n");
    }
}