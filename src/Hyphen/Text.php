<?php

namespace Vanderlee\Syllable\Hyphen;

use DOMNode;

class Text implements Hyphen
{
    private $text;

    public function __construct($text)
    {
        $this->text = $text;
    }

    public function joinText($parts)
    {
        return join($this->text, $parts);
    }

    public function joinHtmlDom($parts, DOMNode $node)
    {
        $node->textContent = $this->joinText($parts);
    }

    public function stripHtml($html)
    {
        return str_replace($this->text, '', $html);
    }
}
