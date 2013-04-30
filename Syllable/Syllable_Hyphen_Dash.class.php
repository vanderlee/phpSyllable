<?php

	class Syllable_Hyphen_Dash implements Syllable_Hyphen_Text {
		public function __construct() {
			parent::__construct('-');
		}
	}