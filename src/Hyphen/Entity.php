<?php
declare(strict_types=1);

namespace Vanderlee\Syllable\Hyphen;

use DOMNode;

class Entity implements Hyphen
{

    /**
     * @var string
     */
    private $entity;

    /**
     * @param string $entity
     */
    public function __construct(string $entity)
    {
        $this->entity = $entity;
    }

    /**
     * @inheritdoc
     */
    public function joinText(array $parts): string
    {
        return join('&' . $this->entity . ';', $parts);
    }

    /**
     * @inheritdoc
     */
    public function joinHtmlDom(array $parts, DOMNode $node): void
    {
        if (($p = count($parts)) > 1) {
            $node->textContent = $parts[--$p];
            while (--$p >= 0) {
                $node = $node->parentNode->insertBefore($node->ownerDocument->createEntityReference($this->entity), $node);
                $node = $node->parentNode->insertBefore($node->ownerDocument->createTextNode($parts[$p]), $node);
            }
        }
    }

}
