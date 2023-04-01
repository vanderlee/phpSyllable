<?php

namespace Vanderlee\Syllable\Hyphen;

use DOMNode;

class Entity implements Hyphen
{
    private $entity;

    public function __construct($entity)
    {
        $this->entity = $entity;
    }

    public function joinText($parts)
    {
        return join('&'.$this->entity.';', $parts);
    }

    public function joinHtmlDom($parts, DOMNode $node)
    {
        if (($p = count($parts)) > 1) {
            $node->textContent = $parts[--$p];
            while (--$p >= 0) {
                $node = $node->parentNode->insertBefore(
                    $node->ownerDocument->createEntityReference($this->entity),
                    $node
                );

                $node = $node->parentNode->insertBefore(
                    $node->ownerDocument->createTextNode($parts[$p]),
                    $node
                );
            }
        }
    }

    public function stripHtml($html)
    {
        return str_replace('&'.$this->entity.';', '', $html);
    }
}
