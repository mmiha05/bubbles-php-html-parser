# BUBBLES
HTML parser written in PHP. Made for fun and practicing writing PHP and parsers a little. **Not guaranteed to be 100% foolproof**.

## Core
Core returns parsed HTML string as an array of `Node` instances.
```php
$parser = new BubblesParser\BubblesCore('<p>Hello world</p>');
$parsed = $parser->parse();
```


`Node` interface is the following:
```php
class Node
{
    public ?string $tag;

    public int $depth;

    /**
     * @var array<string,string>
     */
    public array $attributes = array();

    public string $type;

    /**
     * @var array<Node>
     */
    public array $children = array();
}
```
The `type` can be:
```php
protected array $node_types = [
  'node' => 'node',
  'textNode' => 'textNode',
  'docType' => 'docType',
  'comment' => 'comment'
];
```
For example:
```php
$html = '
<!DOCTYPE html>
<html>
  <head>
    <script async src="foo.js"></script>
    <title>Document title</title>
  </head>
  <body>
    <p>Hello<strong> world</strong></p>
  </body>
</html>
';
$parser = new BubblesParser\BubblesCore($html);
return $parser->parse();
```
Return result would be array of `Node`s:
```php
array(2) {
  [0]=>
  object(BubblesParser\Classes\Node) {
    ["tag"]=>
    NULL
    ["depth"]=>
    int(0)
    ["attributes"]=>
    array(0) {
    }
    ["type"]=>
    string(7) "docType"
    ["children"]=>
    array(0) {
    }
  }
  [1]=>
  object(BubblesParser\Classes\Node) {
    ["tag"]=>
    string(4) "html"
    ["depth"]=>
    int(0)
    ["attributes"]=>
    array(0) {
    }
    ["type"]=>
    string(4) "node"
    ["children"]=>
    array(2) {
      [0]=>
      object(BubblesParser\Classes\Node) {
        ["tag"]=>
        string(4) "head"
        ["depth"]=>
        int(1)
        ["attributes"]=>
        array(0) {
        }
        ["type"]=>
        string(4) "node"
        ["children"]=>
        array(2) {
          [0]=>
          object(BubblesParser\Classes\Node) {
            ["tag"]=>
            string(6) "script"
            ["depth"]=>
            int(2)
            ["attributes"]=>
            array(2) {
              ["async"]=>
              string(4) "true"
              ["src"]=>
              string(6) "foo.js"
            }
            ["type"]=>
            string(4) "node"
            ["children"]=>
            array(1) {
              [0]=>
              object(BubblesParser\Classes\Node) {
                ["tag"]=>
                NULL
                ["depth"]=>
                int(3)
                ["attributes"]=>
                array(1) {
                  ["textContent"]=>
                  string(0) ""
                }
                ["type"]=>
                string(8) "textNode"
                ["children"]=>
                array(0) {
                }
              }
            }
          }
          [1]=>
          object(BubblesParser\Classes\Node) {
            ["tag"]=>
            string(5) "title"
            ["depth"]=>
            int(2)
            ["attributes"]=>
            array(0) {
            }
            ["type"]=>
            string(4) "node"
            ["children"]=>
            array(1) {
              [0]=>
              object(BubblesParser\Classes\Node) {
                ["tag"]=>
                NULL
                ["depth"]=>
                int(3)
                ["attributes"]=>
                array(1) {
                  ["textContent"]=>
                  string(14) "Document title"
                }
                ["type"]=>
                string(8) "textNode"
                ["children"]=>
                array(0) {
                }
              }
            }
          }
        }
      }
      [1]=>
      object(BubblesParser\Classes\Node) {
        ["tag"]=>
        string(4) "body"
        ["depth"]=>
        int(1)
        ["attributes"]=>
        array(0) {
        }
        ["type"]=>
        string(4) "node"
        ["children"]=>
        array(1) {
          [0]=>
          object(BubblesParser\Classes\Node) {
            ["tag"]=>
            string(1) "p"
            ["depth"]=>
            int(2)
            ["attributes"]=>
            array(0) {
            }
            ["type"]=>
            string(4) "node"
            ["children"]=>
            array(2) {
              [0]=>
              object(BubblesParser\Classes\Node) {
                ["tag"]=>
                NULL
                ["depth"]=>
                int(3)
                ["attributes"]=>
                array(1) {
                  ["textContent"]=>
                  string(5) "Hello"
                }
                ["type"]=>
                string(8) "textNode"
                ["children"]=>
                array(0) {
                }
              }
              [1]=>
              object(BubblesParser\Classes\Node) {
                ["tag"]=>
                string(6) "strong"
                ["depth"]=>
                int(3)
                ["attributes"]=>
                array(0) {
                }
                ["type"]=>
                string(4) "node"
                ["children"]=>
                array(1) {
                  [0]=>
                  object(BubblesParser\Classes\Node) {
                    ["tag"]=>
                    NULL
                    ["depth"]=>
                    int(4)
                    ["attributes"]=>
                    array(1) {
                      ["textContent"]=>
                      string(6) " world"
                    }
                    ["type"]=>
                    string(8) "textNode"
                    ["children"]=>
                    array(0) {
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  }
}
```
### Write output
From parsed `Node`s writes back string.
Most basic usage is:
```php
$html = '
<!DOCTYPE html>
<html>
  <head>
    <script async src="foo.js"></script>
    <title>Document title</title>
  </head>
  <body>
    <p>Hello<strong> world</strong></p>
  </body>
</html>
';
$parser = new BubblesParser\BubblesCore($html);
echo $parser->write_output();
```

`write_output` accepts array of parameters that determine output, default values are:
```php
array (
'eachElementInNewLine' => true,
'keepEmptyElementsInSameLine' => true,
'indentation' => '  ' // (2 spaces)
'keepComments' => true,
'trimStyleTags' => true,
'trimScriptTags' => true,
'trimTextAreaTags' => false,
'trimPreTags' => false,
'shortenTrueAtributes' => true
'selfClosingTagStyle' => '/>',
'trimFinalOutput' => false,
'respectInlineElements' => true,
'trimTextNodes' => false
);
```
Parameters explained:
1. `eachElementInNewLine` makes every element opening and ending go into new line.
```html
  <p>
    <span>
    </span>
  </p>
```
2. `keepEmptyElementsInSameLine` overrides previous rule, keeps opening and endings of same tags in same line if element has no children
```html
  <p>
    <span></span>
  </p>
```
3. `indentation` What should be used as identation when going deeper into HTML tree
4. `keepComments` Remove or keep HTML comments
5. `trimStyleTags` Remove excess whitespace from style tags
6. `trimScriptTags` Remove excess whitespace from script tags
7. `trimTextAreaTags` Remove excess whitespace from textarea tags
8. `trimPreTags` Remove excess whitespace from pre tags
9. `shortenTrueAtributes` Change `<script async="true" src="bar.js">` to `<script async src="bar.js">`
10. `selfClosingTagStyle` - `<img src="img.jpg">` vs. `<img src="img.jpg"/>`
11. `trimFinalOutput` Remove excess whitespace from final output
12. `respectInlineElements` Ignore other rules to avoid messing formating of some inline elements such as `<span>` because they're affected by whitespaces
13. `trimTextNodes` Remove excess whitespace from any text node

## Minify
Minify is pre-defined set of rules for `Core`'s `write_output` method. It will minify HTML output (but not contents of `<script>` and `<style>` tags)
```php
public function minify(string $source, bool $aggresive = true): string
```
**Notice:** `aggresive` means it will ignore inline elements' whitespaces and trim text nodes which can mess up some output  
  
Example usage:
```php
$html = '
<!DOCTYPE html>
<html>
  <!-- Some HTML Comment -->
  <head>
    <script async="true" src="foo.js"></script>
    <title>Document title</title>
  </head>
  <body>
    <img src="welcome.jpg" />
    <p>Hello<strong> world</strong></p>
  </body>
</html>
';
$minifier = new BubblesParser\Minify();
return $minifier->minify($html);
```
Would return string like this:
```html
<!DOCTYPE html><html><head><script async src="foo.js"></script><title>Document title</title></head><body><img src="welcome.jpg"><p>Hello<strong>world</strong></p></body></html>
```

## Instalation and scripts
Clone repo and run `composer install`.  
Scripts are:  
1. `composer stan` - runs PHP Stan analyzer  
2. `composer test` - runs tests (codecept)
3. `composer lint` - runs PHP CS Fixer
4. `composer debug` - runs code in `debugging.php` file

## TODOS
- [ ] Error handling when invalid input is provided

# License
This project is licensed under the MIT License. Copy of license can be found in repository root.