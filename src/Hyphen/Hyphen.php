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

    public function joinHtmlDom($parts, DOMNode $node);

    public function stripHtml($html);
}
