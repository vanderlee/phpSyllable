<?php

    /**
     * Default language strategy tries to load TeX files from a relative path
     * to the class sourcefile.
     */
    class Syllable_Source_File implements Syllable_Source_Interface {
		private $path		= null;
		private $language	= null;
        private $lines      = null;
        private $position   = 0;

        public function __construct($language, $path) {
			$this->setLanguage($language);
			$this->setPath($path);
            $this->rewind();
        }

		public function setPath($path) {
			$this->path = $path;
			$this->lines = null;
		}

		public function setLanguage($language) {
			$this->language = $language;
			$this->lines = null;
		}

		private function load() {
			if (!$this->lines) {
				$this->lines	= file("{$this->path}/hyph-{$this->language}.tex");
			}
		}

        function rewind() {
            $this->position = 0;
        }

        function current() {
			$this->load();
            return $this->lines[$this->position];
        }

        function key() {
            return $this->position;
        }

        function next() {
            ++$this->position;
        }

        function valid() {
			$this->load();
            return isset($this->lines[$this->position]);
        }
    }