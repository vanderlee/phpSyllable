<?php

namespace Vanderlee\Syllable\Hyphen;

class Text implements Hyphen
{

    private $text;

    public function __construct($text)
    {
        $this->text = $text;
    }

    /**
     * @param string[] $parts
     *
     * @return string
     */
    public function joinText(array $parts): string
    {
        return join($this->text, $parts);
    }

    public function joinHtmlDom($parts, \DOMNode $node)
    {
        $node->textContent = $this->joinText($parts);
    }

    public function stripHtml($html)
    {
        return str_replace($this->text, '', $html);
    }
}
