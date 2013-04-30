<?php

	/**
	 * Single-file cache using PHP-native serialization to encode data
	 */
	class Syllable_Cache_Serialized extends Syllable_Cache_FileAbstract {
		protected function encode($array) {
			return serialize($array);
		}

		protected function decode($array) {
			return unserialize($array);
		}

		protected function getFilename($language) {
			return "syllable.{$language}.serialized";
		}
	}