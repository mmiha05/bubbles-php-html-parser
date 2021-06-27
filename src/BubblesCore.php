<?php declare(strict_types=1);

namespace BubblesParser;

require_once __DIR__ . '/../vendor/autoload.php';

use BubblesParser\Classes\PureNode;
use BubblesParser\Classes\Node;

/**
 * Core Instance of parser
 */
class BubblesCore
{

  /**
   * @var array<string> $self_enclosing_tags
   */
    private array $self_enclosing_tags = [
    'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'link', 'meta', 'param', 'source', 'track', 'wbr', 'input'
  ];

    /**
     * Tags whose contents should not be trimmed
     *
     * @var array<string>
     */
    protected $non_trimable_tags = ['script', 'style', 'textarea', 'pre'];

    /**
     * Inline tags, whitespaces affect this elements
     *
     * @var array<string>
     */
    protected $inline_tags = ['a', 'abbr', 'acronym', 'b', 'bdo', 'big', 'br', 'button', 'cite', 'code', 'dfn', 'em', 'i', 'input', 'kbd', 'label', 'map', 'object', 'output', 'q', 'samp', 'script', 'select', 'small', 'span', 'strong', 'sub', 'sup', 'textarea', 'time', 'tt', 'var'];

    /**
     * @var array<string,string>
     */
    protected array $node_types = [
      'node' => 'node',
      'textNode' => 'textNode',
      'docType' => 'docType',
      'comment' => 'comment'
    ];

    /**
     * Needed to know when to ignore contents inside style and script tags
     */
    private bool $is_inside_script_or_style_tag = false;
    private bool $is_inside_script_tag = false;
    private bool $is_inside_style_tag = false;

    /**
     * @var array<PureNode> $parsed_elements
     */
    private array $parsed_elements = [];

    /**
     * @var array<Node>
     */
    protected array $elements = [];

    /**
     * How deep in DOM Tree parser currently is
     * @var int $current_level
     */
    private int $current_level = -1;

    /**
     * How far has parser parsed
     * @var int $current_index
     */
    private int $current_index = 0;

    private string $comment_start = '<!--';

    private string $comment_end = '-->';

    private string $quote_mark = '"';

    private string $quote_mark_alternate = '\'';

    private string $tag_start_opening = '<';

    private string $tag_end_opening = '</';

    private string $tag_start_closing = '>';

    private string $tag_start_closing_alternate = '/>';

    /**
     * Just to store some temporary data while parsing
     */
    private string $buffer = '';

    private bool $is_inside_non_trimable_tag = false;

    private bool $is_inside_inline_tag = false;

    private function string_starts_with(string $input, string $search) : bool
    {
        return substr($input, 0, strlen($search)) === $search;
    }

    private function cutoff_string_at(string $input, int $index) : string
    {
        return substr($input, $index, strlen($input));
    }

    /**
     * Used for pushing text nodes as nodes
     *
     * @return void
     */
    private function push_buffer_as_node(): void
    {
        /**
         * Unless text node belongs to a tag such as <pre> whose contents should be left as they are
         * do not push node if it is empty
         */
        if (empty(trim($this->buffer)) && !$this->is_inside_non_trimable_tag) {
            return;
        }
        $node = new PureNode();
        $node->type = $this->node_types['textNode'];

        // We leave content as it was if text is inside tags such as <pre> or any inline tags
        $node->attributes['textContent'] = $this->is_inside_non_trimable_tag ? $this->buffer :
            $this->remove_un_needed_white_space($this->buffer, $this->is_inside_non_trimable_tag);
        // Text node will always be child of something
        $node->depth = $this->current_level + 1;
        $this->parsed_elements[] = $node;
        $this->buffer = '';
    }

    /**
     * Removes all unnecessary white space from string
     * If bool $aggresive is true it will remove all white spaces,
     * otherwise it will leave one whitespace at beginning and at start if they exist
     * (due to whitespace affecting the inline elements)
     * @param string $input
     * @param boolean $aggresive
     * @return string
     */
    protected function remove_un_needed_white_space(string $input, bool $aggresive): string
    {
        return $aggresive ? trim($input) : preg_replace('!\s+!', ' ', $input);
    }

    /**
     * A function that parses inside quotes
     *
     * @param string $source
     * ! $source must not start with quote, it should be stripped before passing on
     * @param boolean $using_double
     * @return string
     */
    private function parse_inside_quotes(string $source, bool $using_double = true) : string
    {
        // Make a clone of original input string that will be changed
        // since original will have to remain untouched for indexing
        $cloned_source = $source;

        // Local index
        $i = 0;

        $result = '';

        // Determine which type of quote mark is used (' or ")
        $quote = $using_double ? $this->quote_mark : $this->quote_mark_alternate;
        $escaped_quote = '\\' . $quote;

        while (!empty($cloned_source)) {
            if ($this->string_starts_with($cloned_source, $escaped_quote)) {
                // Need to increment by two here since escaping quote mark
                // require two characters (\" or \')
                $i += 2;
                $this->current_index += 2;
                $result .= $escaped_quote;
                $cloned_source = $this->cutoff_string_at($source, $i);
            } elseif ($this->string_starts_with($cloned_source, $quote)) {
                // If it just a quote mark means it is end for this parser
                break;
            } else {
                $result .= substr($cloned_source, 0, 1);
                $i++;
                $this->current_index++;
                $cloned_source = $this->cutoff_string_at($source, $i);
            }
        }
        return $result;
    }

    /**
     * Parsers a opening of tag or just a tag if it is self-closing like <img />
     *
     * @param string $source
     * ! $source must have < removed before passing on
     * @return void
     */
    private function parse_tag(string $source): void
    {
        // Reset back the state of self enclosing tag flag
        // That flag is needed for determining depth of structure
        $is_inside_self_closing_tag = false;
        $node = new PureNode();
        $node->type = $this->node_types['node'];
        $tag_name_parsed = false;

        // A variable to store various strings in procces
        $buffer = '';

        // Local index
        $i = 0;

        $cloned_source = $source;
        while (!empty($cloned_source)) {
            // Grab a next char
            $next_str = substr($cloned_source, 0, 1);

            // First get the tag name here, parser wont search for attributes until it gets tag name
            if (preg_match('/^[a-z1-6]/i', $next_str) && $tag_name_parsed === false) {
                $buffer .= $next_str;
                $this->current_index++;
                $i++;
                $cloned_source = $this->cutoff_string_at($source, $i);
                continue;
            } elseif ($tag_name_parsed === false) {
                $buffer = strtolower($buffer);
                $node->tag = $buffer;

                if (in_array($buffer, $this->self_enclosing_tags)) {
                    $is_inside_self_closing_tag = true;
                }
                if (in_array($buffer, $this->non_trimable_tags)) {
                    $this->is_inside_non_trimable_tag = true;
                }
                if (in_array($buffer, $this->inline_tags)) {
                    $this->is_inside_inline_tag = true;
                }
                if ($buffer === 'script') {
                    $this->is_inside_script_or_style_tag = true;
                    $this->is_inside_script_tag = true;
                }
                if ($buffer === 'style') {
                    $this->is_inside_script_or_style_tag = true;
                    $this->is_inside_style_tag = true;
                }
                $buffer = '';
                $tag_name_parsed = true;
            }

            // Check if it is end of tag (>) or (/>) for self closing
            if ($next_str === $this->tag_start_closing || $this->string_starts_with($cloned_source, $this->tag_start_closing_alternate)) {
                if ($is_inside_self_closing_tag === true) {

                    // Increase current index here too because /> is 2 characters long
                    // It is increased again at the end of this if block to cover for case
                    // of not self closing tag too
                    if ($this->string_starts_with($cloned_source, $this->tag_start_closing_alternate)) {
                        $this->current_index++;
                    }
                } else {

                    // Not a self-closing tag, meaning strucutre tree is going deeper
                    $this->current_level++;
                }

                /**
                 * If buffer was left empty for some previous attribute add it here
                 * because it wont get caught anywhere else.
                 * For example: In <input disabled/> the disabled attribute would get skipped
                 * over without this
                 */
                if (!empty($buffer)) {
                    $node->attributes[$buffer] = 'true';
                }

                $node->depth = $is_inside_self_closing_tag ? $this->current_level + 1 : $this->current_level;
                $this->current_index++;
                break;
            }


            /**
             * Parse attributes here, the tag name is parsed by this point
             * so we can assume that characters between spaces are attributes
             */
            if ($next_str !== ' ' && $next_str !== '=') {
                $buffer .= $next_str;
            }
            /**
             * Only attribute present assumes it is true
             * For example <srcipt asnyc></script>
             */
            elseif ($next_str === ' ' && !empty($buffer)) {
                $node->attributes[$buffer] = 'true';
                $buffer = '';
            }
            /**
             * If next string is =
             * go into quote parser and grab value
             */
            elseif ($next_str === '=' && !empty($buffer)) {
                // Since current char is =, we need to go one step more to get quote mark
                $i++;
                $this->current_index++;
                $cloned_source = $this->cutoff_string_at($source, $i);
                $is_using_double_quotes = $this->string_starts_with($cloned_source, $this->quote_mark);

                // Remove first quote mark because parse_inside_quotes method expects that to be removed
                $i++;
                $this->current_index++;
                $cloned_source = $this->cutoff_string_at($source, $i);

                // Parse quote and set the attribute value
                $parsed_quote = $this->parse_inside_quotes($cloned_source, $is_using_double_quotes);
                $node->attributes[$buffer] = $parsed_quote;

                // New local index ($i) must increment to adjust for parsing the quote
                $i = $i + strlen($parsed_quote) + 1; // 1 accounts for quote
                
                // * Increase current index for quote, notice that it was already increased inside quote parser
                $this->current_index++;
                $cloned_source = $this->cutoff_string_at($source, $i);

                $buffer = '';
                continue;
            }

            $this->current_index++;
            $i++;
            $cloned_source = $this->cutoff_string_at($source, $i);
        }
        $this->parsed_elements[] = $node;
    }

    /**
     * Handles when parser encounter </div> for example
     * ! $source must have </ cut off
     * @param string $source
     * @return void
     */
    private function parse_end_of_tag(string $source) : void
    {
        for ($i = 0; $i < strlen($source); $i++) {
            // Tag start closing: >
            $this->current_index++;
            if ($source[$i] === $this->tag_start_closing) {
                $this->current_level--;
                $this->is_inside_non_trimable_tag = false;
                $this->is_inside_inline_tag = false;
                $this->is_inside_script_or_style_tag = false;
                $this->is_inside_script_tag = false;
                $this->is_inside_style_tag = false;
                break;
            }
        }
    }

    /**
     * Parses <!DOCTYPE html>
     * ! $source will have everything included already
     *
     * @param string $source
     * @return void
     */
    private function parse_doctype(string $source) : void
    {
        $node = new PureNode();
        $node->type = $this->node_types['docType'];
        $node->depth = $this->current_level + 1;
        $this->parsed_elements[] = $node;
        $this->current_index += strlen($source);
    }

    /**
     * Parses comment tag
     * ! $source must have <!-- cut off
     * @param string $source
     * @return void
     */
    private function parse_comment(string $source): void
    {
        $cloned_source = $source;

        // Where contents of comment will be stored
        $buffer = '';

        $i = 0;
        while (!empty($cloned_source)) {
            /**
             * If comment end flag (-->) is encountered create new node and push buffer
             */
            if ($this->string_starts_with($cloned_source, $this->comment_end)) {
                $node = new PureNode();
                $node->type = $this->node_types['comment'];
                $node->attributes['textContent'] = $buffer;
                $node->depth = $this->current_level + 1;
                $this->parsed_elements[] = $node;
                $this->current_index += strlen($this->comment_end);
                break;
            }
            // If comment end flag is not encountered push contents into $buffer
            $buffer .= substr($cloned_source, 0, 1);
            $i++;
            $this->current_index++;
            $cloned_source = $this->cutoff_string_at($source, $i);
        }
    }

    /**
     * Parses string until it reaches end delimiter(s)
     *
     * @param string $source
     * @param array<string> $end_delimiters - Strings that will be included in final output of this function and stop parsing
     * @param array<string> $non_inclusive_delimiters:
     *      Strings that will not be included in final output but will stop parsing (withing this function)
     * @param array<string> $skip_delimiters: Strings that will not trigger stoping parsing but might contain end delimiter(s)
     * @return string
     */
    private function parse_until(
        string $source,
        array $end_delimiters,
        array $non_inclusive_delimiters = array(),
        array $skip_delimiters = array()
    ): string {
        $cloned_source = $source;

        // Where contents of comment will be stored
        $buffer = '';

        $i = 0;
        while (!empty($cloned_source)) {
            $triggered_skip_delimiter = false;
            foreach ($skip_delimiters as $delimiter) {
                if ($this->string_starts_with($cloned_source, $delimiter)) {
                    $buffer .= substr($cloned_source, 0, strlen($delimiter));
                    $i += strlen($delimiter);
                    $this->current_index += strlen($delimiter);
                    $this->buffer .= substr($cloned_source, 0, strlen($delimiter));
                    $cloned_source = $this->cutoff_string_at($source, $i);
                    $triggered_skip_delimiter = true;
                    break;
                }
            }
            if ($triggered_skip_delimiter) {
                continue;
            }

            $triggered_non_inclusive_delimiter = false;
            foreach ($non_inclusive_delimiters as $end_delimiter) {
                if ($this->string_starts_with($cloned_source, $end_delimiter)) {
                    $triggered_non_inclusive_delimiter = true;
                    break;
                }
            }
            if ($triggered_non_inclusive_delimiter) {
                break;
            }

            /**
             * If end delimit is encountered
             */
            $triggered_delimiter = false;
            foreach ($end_delimiters as $end_delimiter) {
                if ($this->string_starts_with($cloned_source, $end_delimiter)) {
                    $buffer .= substr($cloned_source, 0, strlen($end_delimiter));
                    $this->buffer .= substr($cloned_source, 0, strlen($end_delimiter));
                    $this->current_index += strlen($end_delimiter);
                    $triggered_delimiter = true;
                    break;
                }
            }
            if ($triggered_delimiter) {
                break;
            }

            // If end delimiter flag is not encountered push contents into $buffer
            $buffer .= substr($cloned_source, 0, 1);
            $i++;
            $this->current_index++;
            $this->buffer .= substr($cloned_source, 0, 1);
            $cloned_source = $this->cutoff_string_at($source, $i);
        }
        return $buffer;
    }

    /**
     * Parses <script> tag to avoid triggering false tag openings and closings within strings and comments
     *
     * @param string $source
     * @return void
     */
    private function parse_script_tag(string $source): void
    {
        $comment_start = '/*';
        $comment_end = '*/';
        $single_line_comment = '//';
        $new_line = "\n";
        $cloned_source = $source;
        $script_tag_end = '</script>';
        $regex_patern = '/';
        $regex_skip_paterns = ['\\/', '=/', '/=', '/+', '/*', '/?', '/{', '/$', '^/', '?=/', '?!/'];
        $template_literal = '`';

        // Where contents of tag will be stored
        $buffer = '';

        $i = 0;
        while (!empty($cloned_source)) {
            if (
                $this->string_starts_with($cloned_source, $this->quote_mark) ||
                $this->string_starts_with($cloned_source, $this->quote_mark_alternate)
            ) {
                $is_using_double_quotes = $this->string_starts_with($cloned_source, $this->quote_mark);
                $the_quote = $is_using_double_quotes ? $this->quote_mark : $this->quote_mark_alternate;

                // Remove first quote mark because parse_inside_quotes method expects that to be removed
                $i++;
                $this->current_index++;
                $cloned_source = $this->cutoff_string_at($source, $i);

                // Add quote start to global buffer
                $this->buffer .= $the_quote;

                // Parse quote and set the attribute value
                $parsed_quote = $this->parse_inside_quotes($cloned_source, $is_using_double_quotes);

                // Add parsed inside of quote + quote (parse_inside_quote wont return quote)
                $this->buffer .= $parsed_quote . $the_quote;

                // New local index ($i) must increment to adjust for parsing the quote
                $i = $i + strlen($parsed_quote) + 1; // 1 accounts for quote
                
                // * Increase current index for quote, notice that it was already increased inside quote parser
                $this->current_index++;
                $cloned_source = $this->cutoff_string_at($source, $i);
                continue;
            }
            if ($this->string_starts_with($cloned_source, $comment_start)) {
                // Incrase index by length of comment start
                $i += strlen($comment_start);
                $buffer .= $comment_start;

                // Also increase global index and buffer
                $this->current_index += strlen($comment_start);
                $this->buffer .= $comment_start;

                // Cut off input source
                $cloned_source = $this->cutoff_string_at($source, $i);


                /**
                 * Increase local index and cloned source by parsed length
                 * * Notice: parse_until() increases global buffer and index unlike parse_inside_quotes()
                 */
                $parsed = $this->parse_until($cloned_source, [$comment_end]);
                $buffer .= $parsed;
                $i += strlen($parsed);
                $cloned_source = $this->cutoff_string_at($source, $i);

                continue;
            }
            // This code is (almost) same as with comment_start, but only for single line comment
            if ($this->string_starts_with($cloned_source, $single_line_comment)) {
                $i += strlen($single_line_comment);
                $buffer .= $single_line_comment;
                $this->current_index += strlen($single_line_comment);
                $this->buffer .= $single_line_comment;
                $cloned_source = $this->cutoff_string_at($source, $i);
                // Encountering </script> in same line means end of script tag
                $parsed = $this->parse_until($cloned_source, [$new_line], [$script_tag_end]);
                $buffer .= $parsed;
                $i += strlen($parsed);
                $cloned_source = $this->cutoff_string_at($source, $i);

                continue;
            }
            // Regexpressions can contain quotes etc
            if ($this->string_starts_with($cloned_source, $regex_patern)) {
                $i += strlen($regex_patern);
                $buffer .= $regex_patern;
                $this->current_index += strlen($regex_patern);
                $this->buffer .= $regex_patern;
                $cloned_source = $this->cutoff_string_at($source, $i);

                $parsed = $this->parse_until($cloned_source, [$regex_patern], [], $regex_skip_paterns);
                $buffer .= $parsed;
                $i += strlen($parsed);
                $cloned_source = $this->cutoff_string_at($source, $i);
                continue;
            }

            // Template literals e.g: `${var} something`
            if ($this->string_starts_with($cloned_source, $template_literal)) {
                $i += strlen($template_literal);
                $buffer .= $template_literal;
                $this->current_index += strlen($template_literal);
                $this->buffer .= $template_literal;
                $cloned_source = $this->cutoff_string_at($source, $i);

                $parsed = $this->parse_until($cloned_source, [$template_literal], [], ["\\{$template_literal}"]);
                $buffer .= $parsed;
                $i += strlen($parsed);
                $cloned_source = $this->cutoff_string_at($source, $i);
                continue;
            }

            // If it is not inside comment and not inside quotes and it encounter </ consider it end of script tag
            if ($this->string_starts_with($cloned_source, $this->tag_end_opening)) {
                break;
            }

            // If nothing was triggered, just append content normaly
            $buffer .= substr($cloned_source, 0, 1);
            $i++;
            $this->current_index++;
            $this->buffer .= substr($cloned_source, 0, 1);
            $cloned_source = $this->cutoff_string_at($source, $i);
        }
    }

    /**
     * Parses <style> tag to avoid triggering false tag openings and closings within comments
     *
     * @param string $source
     * @return void
     */
    private function parse_style_tag(string $source): void
    {
        $comment_start = '/*';
        $comment_end = '*/';

        // Where contents of tag will be stored
        $buffer = '';

        $i = 0;
        while (!empty($cloned_source)) {
            if ($this->string_starts_with($cloned_source, $comment_start)) {
                // Incrase index by length of comment start
                $i += strlen($comment_start);
                $buffer .= $comment_start;

                // Also increase global index and buffer
                $this->current_index += strlen($comment_start);
                $this->buffer .= $comment_start;

                // Cut off input source
                $cloned_source = $this->cutoff_string_at($source, $i);


                /**
                 * Increase local index and cloned source by parsed length
                 * * Notice: parse_until() increases global buffer and index unlike parse_inside_quotes()
                 */
                $parsed = $this->parse_until($cloned_source, [$comment_end]);
                $buffer .= $parsed;
                $i += strlen($parsed);
                $cloned_source = $this->cutoff_string_at($source, $i);

                continue;
            }

            // If it is not inside comment and it encounter </ consider it end of style tag
            if ($this->string_starts_with($cloned_source, $this->tag_end_opening)) {
                break;
            }

            // If nothing was triggered, just append content normaly
            $buffer .= substr($cloned_source, 0, 1);
            $i++;
            $this->current_index++;
            $this->buffer .= substr($cloned_source, 0, 1);
            $cloned_source = $this->cutoff_string_at($source, $i);
        }
    }

    private function reset_buffer(): void
    {
        $this->buffer = '';
    }

    public function __construct(string $source)
    {
        $this->source = $source;
    }

    public function get_current_level(): int
    {
        return $this->current_level;
    }

    /**
     * This method parses nodes in "pure" mode before transforming them with Node array
     * that has children
     *
     * @return array<PureNode>
     */
    public function parse_pure(): array
    {
        // Create local copy of instance source that will be modified for parsing needs
        $source = $this->source;
        while (!empty($source)) {

            // If we're encountering HTML comment
            if ($this->string_starts_with($source, $this->comment_start) && !$this->is_inside_script_or_style_tag) {
                // Push text node that was parsed before this
                $this->push_buffer_as_node();

                /**
                 * Current index needs to be increased for the length of comment start flag (<!--)
                 * and also change $source
                 */
                $this->current_index += strlen($this->comment_start);
                $source = $this->cutoff_string_at($this->source, $this->current_index);

                $this->parse_comment($source);

                // Reset buffer after this
                $this->reset_buffer();

            // Detect !DOCTYPE
            } elseif (preg_match('/^<!DOCTYPE [^>]+>/i', $source, $matches) > 0 && !$this->is_inside_script_or_style_tag) {
                $this->push_buffer_as_node();
                $this->parse_doctype($matches[0]);
                $this->reset_buffer();

            // Detect end of tag (</), for example: </div>
            } elseif ($this->string_starts_with($source, $this->tag_end_opening)) {
                $this->push_buffer_as_node();

                /**
                 * Current index needs to be increased for the length of tag end flag (</)
                 * and also change $source
                 */
                $this->current_index += strlen($this->tag_end_opening);
                $source = $this->cutoff_string_at($this->source, $this->current_index);
                $this->parse_end_of_tag($source);
            
            // Detect tag start (<)
            } elseif ($this->string_starts_with($source, $this->tag_start_opening) && !$this->is_inside_script_or_style_tag) {
                $this->push_buffer_as_node();

                /**
                 * Current index needs to be increased for the length of tag start flag (<)
                 * and also change $source
                 */
                $this->current_index++;
                $source = $this->cutoff_string_at($this->source, $this->current_index);
                $this->parse_tag($source);
                $this->reset_buffer();

            // If none of scenarios above, assume node is text content, and push it into buffer or parse if is script or style
            } else {
                if (!$this->is_inside_script_or_style_tag) {
                    $this->buffer .= substr($source, 0, 1);
                    $this->current_index++;
                // Need to ignore for example: </div> inside quotes of <script> so we parse here
                // also applies to comments both inside script and style tags
                } else {
                    if ($this->is_inside_script_tag) {
                        $this->parse_script_tag($source);
                        $this->is_inside_script_tag = false;
                    } else {
                        $this->parse_style_tag($source);
                        $this->is_inside_style_tag = false;
                    }
                    $this->is_inside_script_or_style_tag = false;
                }
            }

            // Finally, cut off $source string
            $source = $this->cutoff_string_at($this->source, $this->current_index);
        }
        return $this->parsed_elements;
    }

    protected string $source;

    /**
     * Splits array based on some condition
     *
     * @param array<mixed> $arr
     * @param callable $fn
     * @return array<mixed>
     */
    protected function array_conditional_split(array $arr, callable $fn): array
    {
        $total = [];
        $current = $fn($arr[0]) === true ? [$arr[0]] : [];
        for ($i = 1; $i < count($arr); $i++) {
            if ($fn($arr[$i]) === true) {
                $total[] = $current;
                $current = [$arr[$i]];
            } else {
                $current[] = $arr[$i];
            }
        }
        $total[] = $current;
        return $total;
    }

    /**
     * Self-explainatory
     *
     * @param PureNode $pure
     * @return Node
     */
    private function map_pure_to_node(PureNode $pure): Node
    {
        $node = new Node();
        $node->tag = empty($pure->tag) ? null : $pure->tag;
        $node->depth = $pure->depth;
        $node->attributes = isset($pure->attributes) ? $pure->attributes : array();
        $node->type = $pure->type;
        $node->children = [];
        return $node;
    }

    /**
     * Attaches children to Node by sending an array of PureNode
     *
     * @param Node $parent_node
     * @param array<PureNode> $elements
     * @return void
     */
    private function get_children(Node $parent_node, array $elements): void
    {
        // We are currently getting first descendants of parent Node
        $current_depth = $parent_node->depth + 1;
        $filter_elements = array_filter($elements, fn (PureNode $node) => $node->depth >= $current_depth);

        // Split array<PureNode> again
        $split_by_level = $this->array_conditional_split($elements, fn (PureNode $node) => $node->depth === $current_depth);

        // Remove empty arrays (no children)
        $split_by_level = array_filter($split_by_level, fn (array $nodes) => !empty($nodes));
        
        // Seems like array_filter keeps indexes same, so call this to reset them
        $split_by_level = array_values($split_by_level);
    
        // Map array<PureNode> into array<Node>
        $elements = array_map(fn (array $nodes) => $this->map_pure_to_node($nodes[0]), $split_by_level);

        $parent_node->children = $elements;

        // Do the same thing again
        for ($i = 0; $i < count($elements); $i++) {
            $this->get_children($elements[$i], $split_by_level[$i]);
        }
    }

    /**
     * Parses content in more semantic way
     *
     * @return array<Node>
     */
    public function parse(): array
    {
        // Check if content has maybe been parsed already
        if (!empty($this->elements)) {
            return $this->elements;
        }
        $pure_elements = empty($this->parsed_elements) ? $this->parse_pure() : $this->parsed_elements;

        // Get first the root items (array<PureNode>)
        $split_by_level = $this->array_conditional_split($pure_elements, fn (PureNode $node) => $node->depth === 0);

        // Map first items as parents into Node instance
        $elements = array_map(fn (array $nodes) => $this->map_pure_to_node($nodes[0]), $split_by_level);

        // Now do the similiar thing with children of parents
        for ($i = 0; $i < count($split_by_level); $i++) {
            $this->get_children($elements[$i], $split_by_level[$i]);
        }
        $this->elements = $elements;
        return $elements;
    }

    /**
     * Trims textNode children of Node, also removes them if they're empty
     * Used for style, textarea, pre and script tags
     * @param Node $node
     * @return void
     */
    private function trimTextChildren(Node $node): void
    {
        $new_children = [];
        foreach ($node->children as $child) {
            // In case comments
            if ($child->type !== $this->node_types['textNode']) {
                $new_children[] = $child;
                continue;
            }
            $child->attributes['textContent'] = trim($child->attributes['textContent']);
            if (strlen($child->attributes['textContent']) > 0) {
                $new_children[] = $child;
            }
        }
        // Remove empty children
        $node->children = $new_children;
    }

    /**
     * Writes output according to options
     *
     * @param array<string|bool> $options
     * Following shows all possible options with their types and their default values
     * array (
     *  'eachElementInNewLine' => true,
     *  'keepEmptyElementsInSameLine' => true,
     *  'indentation' => '  ' (2 spaces)
     *  'keepComments' => true,
     *  'trimStyleTags' => true,
     *  'trimScriptTags' => true,
     *  'trimTextAreaTags' => false,
     *  'trimPreTags' => false,
     *  'shortenTrueAtributes' => true
     *  'selfClosingTagStyle' => '/>',
     *  'trimFinalOutput' => false,
     *  'respectInlineElements' => true,
     *  'trimTextNodes' => false
     * )
     * @param bool $forceDoNotApplyNewLine - some tags require no meddling with new lines (style, script, textarea, pre)
     * @param array<Node>|null $nodes
     * @return string
     */
    public function write_output(
        array $options = array(),
        bool $forceDoNotApplyNewLine = false,
        bool $forceDoNotApplyIndent = false,
        ?array $nodes = null
    ): string {
        $options['indentation'] = $forceDoNotApplyIndent ? '' :
        (isset($options['indentation']) ? $options['indentation'] : '  ');
        $options['shortenTrueAtributes'] ??= true;
        $options['selfClosingTagStyle'] ??= '/>';
        $options['keepEmptyElementsInSameLine'] ??= true;
        $options['eachElementInNewLine'] = $forceDoNotApplyNewLine ? false :
            (isset($options['eachElementInNewLine']) ? $options['eachElementInNewLine'] : true);
        $options['trimStyleTags'] ??= true;
        $options['trimScriptTags'] ??= true;
        $options['trimTextAreaTags'] ??= false;
        $options['trimPreTags'] ??= false;
        $options['keepComments'] ??= true;
        $options['respectInlineElements'] ??= true;
        $options['trimTextNodes'] ??= false;
        
        // Since $options['trimFinalOutput'] refers only to final output
        // it should be applied only here
        $trimFinalOutput = isset($options['trimFinalOutput']) ? $options['trimFinalOutput'] : false;
        // But options array is send down in recursion so we need to reset this back
        $options['trimFinalOutput'] = false;

        $nodes ??= $this->parse();
        
        // Final output
        $output = '';

        // Define new line string that will be used later
        $new_line = $options['eachElementInNewLine'] ? "\n" : "";

        foreach ($nodes as $node) {
            // Define indentation string to be used later
            $indentation = str_repeat($options['indentation'], $node->depth);
            
            // Check if inline_tag rule should apply
            $is_inline_tag = ($node->type === $this->node_types['textNode'] || in_array($node->tag, $this->inline_tags)) && $options['respectInlineElements'];

            if ($node->type === $this->node_types['node']) {
                $attributes = '';
                // This flags are needed if we want to keep style, script, textarea and pre tags untouched
                $ignore_new_line_after_start = false;
                $ignore_new_line_before_end = true;
                $force_do_not_apply_indent = false;
                if (($node->tag === 'style' && $options['trimStyleTags'] === true)
                || ($node->tag === 'script' && $options['trimScriptTags'] === true)
                || ($node->tag === 'textarea' && $options['trimTextAreaTags'] === true)
                || ($node->tag === 'pre' && $options['trimPreTags'] === true)
                ) {
                    $this->trimTextChildren($node);
                    $ignore_new_line_before_end = false;
                    $force_do_not_apply_indent = true;
                } elseif (($node->tag === 'style' && $options['trimStyleTags'] === false)
                || ($node->tag === 'script' && $options['trimScriptTags'] === false)
                || ($node->tag === 'textarea' && $options['trimTextAreaTags'] === false)
                || ($node->tag === 'pre' && $options['trimPreTags'] === false)
                ) {
                    // Do not add \n inside tag
                    $ignore_new_line_after_start = true;
                    $ignore_new_line_before_end = true;
                    $force_do_not_apply_indent = true;
                }

                // Parse attributes if they exist
                foreach ($node->attributes as $attr => $value) {
                    // Should checked="true" be shortened to just checked
                    $is_true_and_should_be_short = strtolower($value) === 'true' && $options['shortenTrueAtributes'];
                    if ($is_true_and_should_be_short) {
                        $attributes .= "{$attr} ";
                    } else {
                        $attributes .= "{$attr}=\"{$value}\" ";
                    }
                }
                $attributes = trim($attributes);

                // If is self closing tag, put appropriate ending (defined by options) otherwise standard >
                $is_self_closing_tag = in_array($node->tag, $this->self_enclosing_tags);
                $closing_part = $is_self_closing_tag ? $options['selfClosingTagStyle'] : $this->tag_start_closing;

                // Need to join first the middle part so trim whitespaces before adding opening and closing flags (< and (> or />))
                $middle_part = trim("{$node->tag} {$attributes}");

                
                // This is tag parsed pretty much (<div id="something"> or <img src="image.png"/>) for example
                $tag_main = "{$this->tag_start_opening}{$middle_part}{$closing_part}";

                // If it is self closing tag new line can be checked and element has no children so script can be continued here
                if ($is_self_closing_tag) {
                    $output .= "{$indentation}{$tag_main}{$new_line}";
                    continue;
                }

                // Apply indentation and new line
                $end_of_tag = "{$this->tag_end_opening}{$node->tag}{$this->tag_start_closing}{$new_line}";

                // If element is empty
                if (count($node->children) === 0) {
                    // We want <div></div> output
                    if ($options['keepEmptyElementsInSameLine']) {
                        $output .= "{$indentation}{$tag_main}{$end_of_tag}";
                    }
                    /**
                     * We want:
                     *  <div>
                     *  </div>
                     * output
                     */
                    else {
                        $output .= "{$indentation}{$tag_main}{$new_line}{$indentation}{$end_of_tag}";
                    }
                    continue;
                }

                // Element has children, open the tag, example: <div> or is inline tag so we do not want new line
                if ($ignore_new_line_after_start || $is_inline_tag) {
                    /**
                     * If we have for example:
                     * <script>
                     *          console.log(123);
                     * </script>
                     * and 'trimScriptTags set to false, we do not want to touch contents between <script> and </script>
                     * Also, do not touch the inline tags
                     */
                    if (!$is_inline_tag) {
                        $output .= "{$indentation}{$tag_main}";
                    } else {
                        $output .= $tag_main;
                    }
                } else {
                    $output .= "{$indentation}{$tag_main}{$new_line}";
                }

                // Write children
                $forceDisableNewLine = in_array($node->tag, $this->non_trimable_tags) || $is_inline_tag;
                $output .= $this->write_output($options, $forceDisableNewLine, $force_do_not_apply_indent, $node->children);

                // Now write end of tag
                if ($ignore_new_line_before_end || $is_inline_tag) {
                    if ($is_inline_tag) {
                        $output .= $end_of_tag;
                    } else {
                        $output .= "{$indentation}{$end_of_tag}";
                    }
                } else {
                    $output .= "{$new_line}{$indentation}{$end_of_tag}";
                }
            } elseif ($node->type === $this->node_types['textNode']) {
                $textContent = $options['trimTextNodes'] ? trim($node->attributes['textContent']) : $node->attributes['textContent'];
                // Ignore new line to avoid whitespaces for inline elements
                if ($is_inline_tag) {
                    $output .= $textContent;
                } else {
                    $output .= "{$textContent}{$new_line}";
                }
            } elseif ($node->type === $this->node_types['comment'] && $options['keepComments']) {
                $output .= "{$indentation}{$this->comment_start}{$node->attributes['textContent']}{$this->comment_end}{$new_line}";
            } elseif ($node->type === $this->node_types['docType']) {
                $output .= "{$indentation}<!DOCTYPE html>{$new_line}";
            }
        }
        if ($trimFinalOutput === true) {
            $output = trim($output);
        }
        return $output;
    }
}
