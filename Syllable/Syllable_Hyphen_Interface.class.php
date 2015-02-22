<?php

	interface Syllable_Hyphen_Interface {
		public function joinText($parts);
		public function joinHtmlDom($parts, DOMNode $node);
		public function stripHtml($html);
	}