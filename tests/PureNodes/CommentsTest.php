<?php

class CommentsTest extends \Codeception\Test\Unit
{
    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testComments()
    {
        $html_string = '
        <!DOCTYPE html>
        <!-- I am a comment -->
        <html>
            <body>
            <!-- I am also a comment -->
            </body>
        </html>
        ';

        $parser = new BubblesParser\BubblesCore($html_string);

        $result = $parser->parse_pure();

        $this->assertEquals($result[1]->type, 'comment');
        $this->assertEquals($result[1]->depth, 0);
        $this->assertEquals($result[1]->attributes['textContent'], ' I am a comment ');

        $this->assertEquals($result[4]->type, 'comment');
        $this->assertEquals($result[4]->depth, 2);
        $this->assertEquals($result[4]->attributes['textContent'], ' I am also a comment ');
    }
}
