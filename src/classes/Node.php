<?php

namespace BubblesParser\Classes;

/**
 * Node Class
 * Used for more semantic expression of parsed content
 */
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
