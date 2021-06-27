<?php

class DocTypeTest extends \Codeception\Test\Unit
{
    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testDocTypeParse()
    {
        $html_string = '
        <!DOCTYPE html>
        <html>
            <body>
            </body>
        </html>
        ';

        $parser = new BubblesParser\BubblesCore($html_string);

        $result = $parser->parse_pure();

        $this->assertEquals(count($result), 3);

        $this->assertEquals($result[0]->type, 'docType');
        $this->assertEquals($result[0]->depth, 0);

        $this->assertEquals($result[1]->type, 'node');
        $this->assertEquals($result[1]->tag, 'html');
        $this->assertEquals($result[1]->depth, 0);

        $this->assertEquals($result[2]->type, 'node');
        $this->assertEquals($result[2]->tag, 'body');
        $this->assertEquals($result[2]->depth, 1);

        $this->assertEquals($parser->get_current_level(), -1);
    }
}
