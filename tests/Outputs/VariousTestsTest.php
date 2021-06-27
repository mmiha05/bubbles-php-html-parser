<?php

class VariousTestsTest extends \Codeception\Test\Unit
{
    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests

    /**
     * <div>\n<div> should end up <div></div>\n regardless eachElementInNewLine
     * if keepEmptyElementsInSameLine is true
     */
    public function testKeepingEmptyElementsInOneLine()
    {
        $html = "<div>\n\n</div>";

        $parser = new BubblesParser\BubblesCore($html);

        $output = $parser->write_output(array(
            'keepEmptyElementsInSameLine' => true
        ));

        $this->assertEquals($output, "<div></div>\n");
    }

    public function testKeepingEmptyElementsInOneLineFalse()
    {
        $html = "<div>\n\n</div>";

        $parser = new BubblesParser\BubblesCore($html);

        $output = $parser->write_output(array(
            'keepEmptyElementsInSameLine' => false
        ));

        $this->assertEquals($output, "<div>\n</div>\n");
    }

    public function testIndentation()
    {
        $html = "<div><p><span> 123</span></p></div>";

        $parser = new BubblesParser\BubblesCore($html);

        $output = $parser->write_output(array(
            'indentation' => '**'
        ));

        $this->assertEquals($output, "<div>\n**<p>\n<span> 123</span>\n**</p>\n</div>\n");
    }

    /**
     * <input checked="true"> should be <input checked>
     * And elements should remain in same line
     */
    public function testShorteningTrueAtributesAndEachLineRule()
    {
        $html = '<div><input disabled="true"></div>';

        $parser = new BubblesParser\BubblesCore($html);

        $output = $parser->write_output(array(
            'indentation' => '',
            'eachElementInNewLine' => false,
            'shortenTrueAtributes' => true
        ));

        $this->assertEquals($output, '<div><input disabled/></div>');
    }

    /**
     * Should keep or not keep HTML comments
     *
     */
    public function testKeepCommentsAndClosingTagStyle()
    {
        $html = '<!-- I am a HTML Comment --><div><input disabled="true"></div>';

        $parser = new BubblesParser\BubblesCore($html);

        $output = $parser->write_output(array(
            'selfClosingTagStyle' => '>',
            'indentation' => '',
            'eachElementInNewLine' => false,
            'shortenTrueAtributes' => true,
            'keepComments' => true
        ));

        $this->assertEquals($output, '<!-- I am a HTML Comment --><div><input disabled></div>');

        $parser = new BubblesParser\BubblesCore($html);

        $output = $parser->write_output(array(
            'selfClosingTagStyle' => '>',
            'indentation' => '',
            'eachElementInNewLine' => false,
            'shortenTrueAtributes' => true,
            'keepComments' => false
        ));

        $this->assertEquals($output, '<div><input disabled></div>');
    }

    /**
     * If trimStyleTags is set to false, its contents should remain untouched.
     * Same goes for style, pre and textarea
     * @return void
     */
    public function testTrimmingTags()
    {
        $html = "<style> I am inside \n\n a tag  </style>";

        $parser = new BubblesParser\BubblesCore($html);

        $output = $parser->write_output(array(
            'indentation' => '',
            'eachElementInNewLine' => false,
            'trimStyleTags' => false
        ));

        $this->assertEquals($output, $html);

        $html = "<script> I am inside \n\n a tag  </script>";

        $parser = new BubblesParser\BubblesCore($html);

        $output = $parser->write_output(array(
            'indentation' => '',
            'eachElementInNewLine' => false,
            'trimScriptTags' => false
        ));

        $this->assertEquals($output, $html);

        $html = "<pre> I am inside \n\n a tag  </pre>";

        $parser = new BubblesParser\BubblesCore($html);

        $output = $parser->write_output(array(
            'indentation' => '',
            'eachElementInNewLine' => false,
            'trimPreTags' => false
        ));

        $this->assertEquals($output, $html);

        $html = "<textarea> I am inside \n\n a tag  </textarea>";

        $parser = new BubblesParser\BubblesCore($html);

        $output = $parser->write_output(array(
            'indentation' => '',
            'eachElementInNewLine' => false,
            'trimTextAreaTags' => false
        ));

        $this->assertEquals($output, $html);
    }

    public function testTrimingEndOutput()
    {
        $html = "<div><p></p></div>\n\n";

        $parser = new BubblesParser\BubblesCore($html);

        $output = $parser->write_output(array(
            'trimFinalOutput' => true
        ));

        $this->assertEquals($output, "<div>\n  <p></p>\n</div>");
    }
}
