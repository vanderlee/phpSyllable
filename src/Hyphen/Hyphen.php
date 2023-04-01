<?php

namespace Vanderlee\Syllable\Hyphen;

use DOMNode;

interface Hyphen
{
    public function joinText($parts);

    public function joinHtmlDom($parts, DOMNode $node);

    public function stripHtml($html);
}
