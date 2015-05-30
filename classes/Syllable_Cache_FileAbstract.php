<?php

	abstract class Syllable_Cache_FileAbstract implements Syllable_Cache_Interface {
		private $language	= null;
		private $path		= null;
		private $data		= null;

		abstract protected function encode($array);
		abstract protected function decode($array);
		abstract protected function getFilename($language);

		public function __construct($language, $path) {
			$this->language = $language;
			$this->setPath($path);
		}

		public function setPath($path) {
			$this->path = $path;
			$this->data = null;
		}

		private function filename() {
			return $this->path.'/'.$this->getFilename($this->language);
		}

		private function load() {
			$file = $this->filename();
			if (is_file($file)) {
				$this->data = $this->decode(file_get_contents($file), true);
			}
		}

		private function save() {
			$file = $this->filename();
			file_put_contents($file, $this->encode($this->data));
			@chmod($file, 0777);
		}

		public function __set($key, $value) {
			$this->data[$key] = $value;
			$this->save();
		}

		public function __get($key) {
			if (!$this->data) {
				$this->load();
			}
			return $this->data[$key];
		}

		public function __isset($key) {
			if (!$this->data) {
				$this->load();
			}
			return isset($this->data[$key]);
		}

		public function __unset($key) {
			unset($this->data[$key]);
		}
	}