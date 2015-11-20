<?php
	/**
	 * phpSyllable
	 * Splits up text into syllables and/or hyphenates text according to TeXbook language rules.
	 *
	 * Based on the work by Frank M. Liang (http://www.tug.org/docs/liang/)
	 * and the many volunteers in the TeX community.
	 *
	 * Patterns from the CTAN archive
	 * - http://www.ctan.org/tex-archive/language/hyph-utf8/tex/generic/hyph-utf8/patterns/tex/
	 * - http://www.ctan.org/tex-archive/language/hyphenation/
	 *
	 * @author Martijn W. van der Lee <martijn-at-vanderlee-dot-com>
	 * @author Wim Muskee <wimmuskee-at-gmail-dot-com>
	 * @copyright Copyright (c) 2011, Martijn W. van der Lee
	 * @license http://www.opensource.org/licenses/mit-license.php
	 */

	/**
	 * Classloader for the library
	 * @param string $class
	 */
	function Syllable_autoloader($class) {
		if (!class_exists($class) && is_file(dirname(__FILE__). '/' . $class . '.php')) {
			require dirname(__FILE__). '/' . $class . '.php';
		}
	}
	
	spl_autoload_register('Syllable_autoloader');
