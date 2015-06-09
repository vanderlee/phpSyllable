<?php

/**
 * No caching
 */
class Syllable_Cache_None implements Syllable_Cache_Interface {
	public function __get($key) {
		return null;
	}

	public function __isset($key) {
		return false;
	}

	public function __set($key, $value) {
		// ignore
	}

	public function __unset($key) {
		// ignore
	}
}