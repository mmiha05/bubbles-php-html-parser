<?php

class WithChildrenTest extends \Codeception\Test\Unit
{
    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testWithOneChildren()
    {
        $html_string = '<div><p></p></div>';

        $parser = new BubblesParser\BubblesCore($html_string);

        $result = $parser->parse_pure();

        $this->assertEquals(count($result), 2);

        $first_node = $result[0];

        $this->assertEquals($first_node->tag, 'div');
        $this->assertEquals($first_node->depth, 0);

        $second_node = $result[1];

        $this->assertEquals($second_node->tag, 'p');
        $this->assertEquals($second_node->depth, 1);
    }

    public function testWithMoreChildren()
    {
        $html_string = '
            <div>
                <p>
                    Hello world
                </p>
                <div> Hello you </div>
            </div>
        ';

        $parser = new BubblesParser\BubblesCore($html_string);

        $result = $parser->parse_pure();

        $this->assertEquals(count($result), 5);

        $first_node = $result[0];

        $this->assertEquals($first_node->tag, 'div');
        $this->assertEquals($first_node->depth, 0);
        $this->assertEquals($first_node->type, 'node');

        $second_node = $result[1];

        $this->assertEquals($second_node->tag, 'p');
        $this->assertEquals($second_node->depth, 1);
        $this->assertEquals($second_node->type, 'node');

        $third_node = $result[2];

        $this->assertEquals($third_node->tag, '');
        $this->assertEquals($third_node->depth, 2);
        $this->assertEquals($third_node->type, 'textNode');
        
        $fourth_node = $result[3];

        $this->assertEquals($fourth_node->tag, 'div');
        $this->assertEquals($fourth_node->depth, 1);
        $this->assertEquals($fourth_node->type, 'node');
        
        $fifth_node = $result[4];

        $this->assertEquals($fifth_node->tag, '');
        $this->assertEquals($fifth_node->depth, 2);
        $this->assertEquals($fifth_node->type, 'textNode');
    }

    public function testWithMoreChildrenWithAttributes()
    {
        $html_string = '
            <div id="root">
                <p message="A quote \" for you!">
                    Hello world
                </p>
                <div style="color: blue;"> Hello you </div>
                <script src="foo.js" async defer></script>
                <img src="hello.jpeg" alt=""/>
                Final text
            </div>
        ';

        $parser = new BubblesParser\BubblesCore($html_string);

        $result = $parser->parse_pure();

        $this->assertEquals(count($result), 9);

        $first_node = $result[0];

        $this->assertEquals($first_node->tag, 'div');
        $this->assertEquals($first_node->depth, 0);
        $this->assertEquals($first_node->type, 'node');
        $this->assertEquals($first_node->attributes['id'], 'root');

        $second_node = $result[1];

        $this->assertEquals($second_node->tag, 'p');
        $this->assertEquals($second_node->depth, 1);
        $this->assertEquals($second_node->type, 'node');
        $this->assertEquals($second_node->attributes['message'], 'A quote \" for you!');

        $third_node = $result[2];

        $this->assertEquals($third_node->tag, '');
        $this->assertEquals($third_node->depth, 2);
        $this->assertEquals($third_node->type, 'textNode');
        
        $fourth_node = $result[3];

        $this->assertEquals($fourth_node->tag, 'div');
        $this->assertEquals($fourth_node->depth, 1);
        $this->assertEquals($fourth_node->type, 'node');
        $this->assertEquals($fourth_node->attributes['style'], 'color: blue;');
        
        $fifth_node = $result[4];

        $this->assertEquals($fifth_node->tag, '');
        $this->assertEquals($fifth_node->depth, 2);
        $this->assertEquals($fifth_node->type, 'textNode');
        
        $sixth_node = $result[5];

        $this->assertEquals($sixth_node->tag, 'script');
        $this->assertEquals($sixth_node->depth, 1);
        $this->assertEquals($sixth_node->type, 'node');
        $this->assertEquals($sixth_node->attributes['src'], 'foo.js');
        $this->assertEquals($sixth_node->attributes['async'], 'true');
        $this->assertEquals($sixth_node->attributes['defer'], 'true');

        $sixth_node_b = $result[6];

        $this->assertEquals($sixth_node_b->tag, '');
        $this->assertEquals($sixth_node_b->depth, 2);
        $this->assertEquals($sixth_node_b->type, 'textNode');
        $this->assertEquals($sixth_node_b->attributes['textContent'], '');
        
        $seventh_node = $result[7];

        $this->assertEquals($seventh_node->tag, 'img');
        $this->assertEquals($seventh_node->depth, 1);
        $this->assertEquals($seventh_node->type, 'node');
        $this->assertEquals($seventh_node->attributes['src'], 'hello.jpeg');
        $this->assertEquals($seventh_node->attributes['alt'], '');
        
        $eighth_node = $result[8];

        $this->assertEquals($eighth_node->tag, '');
        $this->assertEquals($eighth_node->depth, 1);
        $this->assertEquals($eighth_node->type, 'textNode');
        $this->assertEquals($eighth_node->attributes['textContent'], ' Final text ');
    }

    public function testSelfClosingTagProblem()
    {
        $html_string = '
        <div>
            <img src="foo.bar"/>
        </div>
    ';

        $parser = new BubblesParser\BubblesCore($html_string);

        $result = $parser->parse_pure();

        $this->assertEquals(count($result), 2);

        $root_node = $result[0];
        $this->assertEquals($root_node->tag, 'div');
        $this->assertEquals($root_node->type, 'node');
        $this->assertEquals($root_node->depth, 0);

        $img_tag = $result[1];
        $this->assertEquals($img_tag->tag, 'img');
        $this->assertEquals($img_tag->type, 'node');
        $this->assertEquals($img_tag->depth, 1);
    }
}
