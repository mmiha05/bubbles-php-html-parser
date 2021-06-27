<?php

use BubblesParser\Classes\Node;

class FullDocumentTest extends \Codeception\Test\Unit
{
    protected function _before()
    {
    }

    protected function _after()
    {
    }

    /**
     * Check if array and its properties match parsed Node(s)
     *
     * @param array<Node> $recieved
     * @param array $expected
     * @return void
     */
    protected function _checkExpectedEqualsRecieved(array $recieved, array $expected): bool
    {
        if (count($recieved) !== count($expected)) {
            throw new Exception("Not equal lengths of recieved and expected");
            return false;
        }

        for ($i = 0; $i < count($recieved); $i++) {
            if ($recieved[$i]->tag !== $expected[$i]['tag']) {
                throw new Exception("{$recieved[i]->tag} does not equal {$expected[$i]['tag']}");
            }
            if ($recieved[$i]->depth !== $expected[$i]['depth']) {
                throw new Exception("{$recieved[i]->depth} does not equal {$expected[$i]['depth']}, tag: {$recieved[$i]->tag}");
            }
            if ($recieved[$i]->type !== $expected[$i]['type']) {
                throw new Exception("{$recieved[i]->type} does not equal {$expected[$i]['type']}");
            }
            if (count($recieved[$i]->attributes) !== count($expected[$i]['attributes'])) {
                throw new Exception("Unequal ammount of attributes, tag: {$recieved[$i]->tag}");
            }
            foreach ($recieved[$i]->attributes as $attr => $val) {
                if ($expected[$i]['attributes'][$attr] !== $val) {
                    throw new Exception("Values of attribute {$attr} do not match, value: {$val}");
                }
            }

            if (count($recieved[$i]->children) !== count($expected[$i]['children'])) {
                throw new Exception("{$recieved[$i]->tag} children do not match {$expected[$i]['tag']}");
            }
            $this->_checkExpectedEqualsRecieved($recieved[$i]->children, $expected[$i]['children']);
        }
        return true;
    }

    // tests
    public function testSomeFeature()
    {
        $html_string = '
        <!-- I am a comment -->
        <!DOCTYPE html>
        <html>
            <head>
                <link rel="stylesheet" href="style.css"/>
                <script src="script.js"></script>
                <script src="async.js" async defer="true"></script>
                <script>console.log("I have been logged");</script>
                <style>#root { display: block; }</style>
            </head>
            <body>
                <div id="root">
                    <img src="logo.png" alt=""/>
                    <input value="" placeholder="Input something">
                    <button>Click me!</button>
                </div>
            </body>
        </html>
        ';

        $parser = new BubblesParser\BubblesCore($html_string);
        
        $result = $parser->parse();

        $expected = [
            array(
                'tag' => null,
                'depth' => 0,
                'type' => 'comment',
                'attributes' => array(
                    'textContent' => ' I am a comment '
                ),
                'children' => []
            ),
            array(
                'tag' => null,
                'depth' => 0,
                'type' => 'docType',
                'attributes' => array(),
                'children' => []
            ),
            array(
                'tag' => 'html',
                'depth' => 0,
                'type' => 'node',
                'attributes' => array(),
                'children' => [
                    array(
                        'tag' => 'head',
                        'depth' => 1,
                        'type' => 'node',
                        'attributes' => array(),
                        'children' => [
                            array(
                                'tag' => 'link',
                                'depth' => 2,
                                'type' => 'node',
                                'attributes' => array(
                                    'rel' => 'stylesheet',
                                    'href' => 'style.css'
                                ),
                                'children' => []
                            ),
                            array(
                                'tag' => 'script',
                                'depth' => 2,
                                'type' => 'node',
                                'attributes' => array(
                                    'src' => 'script.js'
                                ),
                                'children' => [
                                    array(
                                        'tag' => null,
                                        'depth' => 3,
                                        'type' => 'textNode',
                                        'attributes' => array(
                                            'textContent' => ''
                                        ),
                                        'children' => []
                                    )
                                ]
                            ),
                            array(
                                'tag' => 'script',
                                'depth' => 2,
                                'type' => 'node',
                                'attributes' => array(
                                    'src' => 'async.js',
                                    'async' => 'true',
                                    'defer' => 'true'
                                ),
                                'children' => [
                                    array(
                                        'tag' => null,
                                        'depth' => 3,
                                        'type' => 'textNode',
                                        'attributes' => array(
                                            'textContent' => ''
                                        ),
                                        'children' => []
                                    )
                                ]
                            ),
                            array(
                                'tag' => 'script',
                                'depth' => 2,
                                'type' => 'node',
                                'attributes' => array(),
                                'children' => [
                                    array(
                                        'tag' => null,
                                        'depth' => 3,
                                        'type' => 'textNode',
                                        'attributes' => array(
                                            'textContent' => 'console.log("I have been logged");'
                                        ),
                                        'children' => []
                                    )
                                ]
                            ),
                            array(
                                'tag' => 'style',
                                'depth' => 2,
                                'type' => 'node',
                                'attributes' => array(),
                                'children' => [
                                    array(
                                        'tag' => null,
                                        'depth' => 3,
                                        'type' => 'textNode',
                                        'attributes' => array(
                                            'textContent' => '#root { display: block; }'
                                        ),
                                        'children' => []
                                    )
                                ]
                            )
                        ]
                    ),
                    array(
                        'tag' => 'body',
                        'depth' => 1,
                        'type' => 'node',
                        'attributes' => array(),
                        'children' => [
                            array(
                                'tag' => 'div',
                                'depth' => 2,
                                'type' => 'node',
                                'attributes' => array(
                                    'id' => 'root'
                                ),
                                'children' => [
                                    array(
                                        'tag' => 'img',
                                        'depth' => 3,
                                        'type' => 'node',
                                        'attributes' => array(
                                            'src' => 'logo.png',
                                            'alt' => ''
                                        ),
                                        'children' => []
                                    ),
                                    array(
                                        'tag' => 'input',
                                        'depth' => 3,
                                        'type' => 'node',
                                        'attributes' => array(
                                            'value' => '',
                                            'placeholder' => 'Input something'
                                        ),
                                        'children' => []
                                    ),
                                    array(
                                        'tag' => 'button',
                                        'depth' => 3,
                                        'type' => 'node',
                                        'attributes' => array(),
                                        'children' => [
                                            array(
                                                'tag' => null,
                                                'depth' => 4,
                                                'type' => 'textNode',
                                                'attributes' => array(
                                                    'textContent' => 'Click me!'
                                                ),
                                                'children' => []
                                            )
                                        ]
                                    )
                                ]
                            )
                        ]
                    )
                ]
            )
        ];
        $this->assertEquals($this->_checkExpectedEqualsRecieved($result, $expected), true);
    }
}
