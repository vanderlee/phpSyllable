<?php
declare(strict_types=1);

namespace Vanderlee\Syllable\Hyphen;

use DOMNode;

class Text implements Hyphen
{

    /**
     * @var string
     */
    private $text;

    /**
     * @param string $text
     */
    public function __construct(string $text)
    {
        $this->text = $text;
    }

    /**
     * @inheritdoc
     */
    public function joinText(array $parts): string
    {
        return join($this->text, $parts);
    }

    /**
     * @inheritdoc
     */
    public function joinHtmlDom(array $parts, DOMNode $node): void
    {
        $node->textContent = $this->joinText($parts);
    }
}
