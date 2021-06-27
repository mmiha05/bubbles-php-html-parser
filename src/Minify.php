<?php declare(strict_types=1);

namespace BubblesParser;

require_once __DIR__ . '/../vendor/autoload.php';

use BubblesParser\Classes\PureNode;
use BubblesParser\Classes\Node;
use BubblesParser\BubblesCore;

class Minify extends BubblesCore
{
    /**
     * Override default construct
     */
    public function __construct()
    {
    }

    /**
     *
     * @param string $source
     */
    public function minify(string $source, bool $aggresive = true): string
    {
        $this->source = $source;
        $options = array(
      'eachElementInNewLine' => false,
      'keepEmptyElementsInSameLine' => false,
      'indentation' => '',
      'keepComments' => false,
      'trimStyleTags' => false,
      'trimScriptTags' => false,
      'trimTextAreaTags' => false,
      'trimPreTags' => false,
      'shortenTrueAtributes' => true,
      'selfClosingTagStyle' => '>',
      'trimFinalOutput' => true,
      'respectInlineElements' => $aggresive ? false : true,
      'trimTextNodes' => $aggresive ? true : false
    );
        return $this->write_output($options);
    }
}
