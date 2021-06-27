<?php

class MinifyTest extends \Codeception\Test\Unit
{
    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testMinifcation()
    {
        $html_string = "<!-- I am comment -->\n  <div shouldBeShort=\"True\">\n<p shouldNotBeShort=\"something\"></p></div>";
        $html_string .= "<img src=\"foo.jpg\"/>";

        $minifier = new BubblesParser\Minify();

        $result = $minifier->minify($html_string);

        $expected = "<div shouldBeShort><p shouldNotBeShort=\"something\"></p></div><img src=\"foo.jpg\">";

        $this->assertEquals($result, $expected);
    }
}
