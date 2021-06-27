<?php

namespace BubblesParser\Classes;

/**
 * PureNode Class
 * Used for inner parsing of parser
 */
class PureNode
{
    public string $tag = '';

    public int $depth;

    /**
     * @var array<string,string> $attributes
     */
    public array $attributes;

    public string $type;
}
