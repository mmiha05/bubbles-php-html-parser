<?php

class ScriptTagsTest extends \Codeception\Test\Unit
{
    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testSingleLineComment()
    {
        $html_string = "<script>// This whole line should be a comment</script>";

        $parser = new BubblesParser\BubblesCore($html_string);
        
        $result = $parser->parse_pure();

        // Script tag + text node inside script tag
        $this->assertEquals(count($result), 2);

        $this->assertEquals($result[1]->attributes['textContent'], "// This whole line should be a comment");
    }

    public function testMultieLineComment()
    {
        $html_string = "<script>\n/* this text <div></div> should not trigger a new element*/\nvar a = 'foo';\n</script>";

        $parser = new BubblesParser\BubblesCore($html_string);
        
        $result = $parser->parse_pure();

        // Script tag + text node inside script tag
        $this->assertEquals(count($result), 2);

        $this->assertEquals($result[1]->attributes['textContent'], "\n/* this text <div></div> should not trigger a new element*/\nvar a = 'foo';\n");
    }

    public function testTagsInsideScriptStringsSingleQuotes()
    {
        $html_string = "<script>const a = 'this <div></div> should be ignored'</script>";

        $parser = new BubblesParser\BubblesCore($html_string);
        
        $result = $parser->parse_pure();

        // Script tag + text node inside script tag
        $this->assertEquals(count($result), 2);

        $this->assertEquals($result[1]->attributes['textContent'], "const a = 'this <div></div> should be ignored'");
    }

    public function testTagsInsideScriptStringsDoubleQuotes()
    {
        $html_string = "<script>const a = \"this <div></div> should be ignored\"</script>";

        $parser = new BubblesParser\BubblesCore($html_string);
        
        $result = $parser->parse_pure();

        // Script tag + text node inside script tag
        $this->assertEquals(count($result), 2);

        $this->assertEquals($result[1]->attributes['textContent'], "const a = \"this <div></div> should be ignored\"");
    }

    public function testScriptTagRegularExpresions()
    {
        $html_string = "<script>/^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/.test('foo')</script>";

        $parser = new BubblesParser\BubblesCore($html_string);
        
        $result = $parser->parse_pure();

        // Script tag + text node inside script tag
        $this->assertEquals(count($result), 2);

        $this->assertEquals($result[1]->attributes['textContent'], "/^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/.test('foo')");
    }

    public function testTemplateLiterals()
    {
        $html_string = '<script>const a = `Foo bar`; const b = `${a} fizz`; const c = `Backtick \` inside backtick ${b} var`;</script><bar></bar>';

        $parser = new BubblesParser\BubblesCore($html_string);
        
        $result = $parser->parse_pure();

        // Script tag + text node inside script tag + bar
        $this->assertEquals(count($result), 3);

        $this->assertEquals($result[1]->attributes['textContent'], 'const a = `Foo bar`; const b = `${a} fizz`; const c = `Backtick \` inside backtick ${b} var`;');
        $this->assertEquals($result[2]->tag, 'bar');
    }
}
