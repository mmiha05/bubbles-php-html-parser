<?php

class WithEverythingTest extends \Codeception\Test\Unit
{
    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testWithEverything()
    {
        $html_string = '
        <!DOCTYPE html>
        <html lang="en">
            <head>
                <script src="something.js" async></script>
                <title>Foo bar</title>
            </head>
            <body>
                <div id="root">
                    <img src="image.png" alt="Some image"/>
                    <p comment="Some comment says: \"Hello\"">
                        An paragraph text
                    </p>
                    <!-- Maybe I should put style above? -->
                    <style>
                        #root { background: red; }
                    </style>
                </div>
            </body>
        </html>   
        ';

        $parser = new BubblesParser\BubblesCore($html_string);

        $result = $parser->parse_pure();

        // 9 tags + 1 doctype + 1 comment + 4 text nodes
        $this->assertEquals(count($result), 15);

        $this->assertEquals($result[0]->tag, '');
        $this->assertEquals($result[0]->depth, 0);
        $this->assertEquals(isset($result[0]->attributes), false);
        $this->assertEquals($result[0]->type, 'docType');

        $this->assertEquals($result[1]->tag, 'html');
        $this->assertEquals($result[1]->depth, 0);
        $this->assertEquals(isset($result[1]->attributes), true);
        $this->assertEquals($result[1]->attributes['lang'], 'en');
        $this->assertEquals($result[1]->type, 'node');

        $this->assertEquals($result[2]->tag, 'head');
        $this->assertEquals($result[2]->depth, 1);
        $this->assertEquals(isset($result[2]->attributes), false);
        $this->assertEquals($result[2]->type, 'node');

        $this->assertEquals($result[3]->tag, 'script');
        $this->assertEquals($result[3]->depth, 2);
        $this->assertEquals(isset($result[3]->attributes), true);
        $this->assertEquals($result[3]->attributes['src'], 'something.js');
        $this->assertEquals($result[3]->attributes['async'], 'true');
        $this->assertEquals($result[3]->type, 'node');
        
        $this->assertEquals($result[4]->tag, '');
        $this->assertEquals($result[4]->depth, 3);
        $this->assertEquals(isset($result[4]->attributes), true);
        $this->assertEquals($result[4]->attributes['textContent'], '');
        $this->assertEquals($result[4]->type, 'textNode');

        $this->assertEquals($result[5]->tag, 'title');
        $this->assertEquals($result[5]->depth, 2);
        $this->assertEquals(isset($result[5]->attributes), false);
        $this->assertEquals($result[5]->type, 'node');
        
        $this->assertEquals($result[6]->tag, '');
        $this->assertEquals($result[6]->depth, 3);
        $this->assertEquals(isset($result[6]->attributes), true);
        $this->assertEquals($result[6]->attributes['textContent'], 'Foo bar');
        $this->assertEquals($result[6]->type, 'textNode');
        
        $this->assertEquals($result[7]->tag, 'body');
        $this->assertEquals($result[7]->depth, 1);
        $this->assertEquals(isset($result[7]->attributes), false);
        $this->assertEquals($result[7]->type, 'node');
        
        $this->assertEquals($result[8]->tag, 'div');
        $this->assertEquals($result[8]->depth, 2);
        $this->assertEquals(isset($result[8]->attributes), true);
        $this->assertEquals($result[8]->attributes['id'], 'root');
        $this->assertEquals($result[8]->type, 'node');
        
        $this->assertEquals($result[9]->tag, 'img');
        $this->assertEquals($result[9]->depth, 3);
        $this->assertEquals(isset($result[9]->attributes), true);
        $this->assertEquals($result[9]->attributes['src'], 'image.png');
        $this->assertEquals($result[9]->attributes['alt'], 'Some image');
        $this->assertEquals($result[9]->type, 'node');

        $this->assertEquals($result[10]->tag, 'p');
        $this->assertEquals($result[10]->depth, 3);
        $this->assertEquals(isset($result[10]->attributes), true);
        $this->assertEquals($result[10]->attributes['comment'], 'Some comment says: \"Hello\"');
        $this->assertEquals($result[10]->type, 'node');
        
        $this->assertEquals($result[11]->tag, '');
        $this->assertEquals($result[11]->depth, 4);
        $this->assertEquals(isset($result[11]->attributes), true);
        $this->assertEquals($result[11]->attributes['textContent'], ' An paragraph text ');
        $this->assertEquals($result[11]->type, 'textNode');

        $this->assertEquals($result[12]->tag, '');
        $this->assertEquals($result[12]->depth, 3);
        $this->assertEquals(isset($result[12]->attributes), true);
        $this->assertEquals($result[12]->attributes['textContent'], ' Maybe I should put style above? ');
        $this->assertEquals($result[12]->type, 'comment');
        
        $this->assertEquals($result[13]->tag, 'style');
        $this->assertEquals($result[13]->depth, 3);
        $this->assertEquals(isset($result[13]->attributes), false);
        $this->assertEquals($result[13]->type, 'node');
        

        $style_content_after_comment = '#root { background: red; }';
        $this->assertEquals($result[14]->tag, '');
        $this->assertEquals($result[14]->depth, 4);
        $this->assertEquals(isset($result[14]->attributes), true);
        $this->assertEquals(trim($result[14]->attributes['textContent']), $style_content_after_comment);
        $this->assertEquals($result[14]->type, 'textNode');
    }
}
