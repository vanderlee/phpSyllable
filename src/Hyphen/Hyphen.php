<?php
declare(strict_types=1);

namespace Vanderlee\Syllable\Hyphen;

use DOMNode;

interface Hyphen
{

    /**
     * @param string[] $parts
     *
     * @return string
     */
    public function joinText(array $parts): string;

    /**
     * @param string[] $parts
     * @param DOMNode  $node
     */
    public function joinHtmlDom(array $parts, DOMNode $node): void;
}
