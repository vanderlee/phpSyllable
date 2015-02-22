<?php

	class Syllable_Hyphen_Entity implements Syllable_Hyphen_Interface {
		private $entity;

		public function __construct($entity) {
			$this->entity = $entity;
		}

		public function joinText($parts) {
			return join('&'.$this->entity.';', $parts);
		}

		public function joinHtmlDom($parts, DOMNode $node) {
			if (($p = count($parts)) > 1) {
				$node->data = $parts[--$p];
				while (--$p >= 0) {
					$node = $node->parentNode->insertBefore($node->ownerDocument->createEntityReference($this->entity), $node);
					$node = $node->parentNode->insertBefore($node->ownerDocument->createTextNode($parts[$p]), $node);
				}
			}
		}
		
		public function stripHtml($html) {
			return str_replace('&'.$this->entity.';', '', $html);
		}
	}