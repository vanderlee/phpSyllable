<?php

	class Syllable_Hyphen_Text implements Syllable_Hyphen_Interface {
		private $text;

		public function __construct($text) {
			$this->text = $text;
		}

		public function joinText($parts) {
			return join($this->text, $parts);
		}

		public function joinHtmlDom($parts, DOMNode $node) {
			$node->data = $this->joinText($parts);
		}

		public function stripHtml($html) {
			return str_replace($this->text, '', $html);
		}
	}