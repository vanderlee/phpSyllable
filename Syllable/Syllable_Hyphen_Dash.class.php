<?php

	class Syllable_Hyphen_Dash implements Syllable_Hyphen_Interface {
		public function joinText($parts) {
			return join('-', $parts);
		}

		public function joinHtmlDom($parts, DOMNode $node) {
			$node->data = $this->joinText($parts);
		}
	}