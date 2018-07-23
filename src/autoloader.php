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
spl_autoload_register(function($class) {
	if ($class === 'Syllable') {
		// Bind old Class Names to be backwards compatible
		return class_alias('\\Vanderlee\\Syllable\\Syllable', '\\Syllable');		
	} elseif (strpos($class, 'Vanderlee\\Syllable\\') === 0) {
		if (!class_exists($class)) {			
			$classFile = __DIR__ . DIRECTORY_SEPARATOR . $class . '.php';
			if (file_exists($classFile)) {
				require $classFile;

				return true;
			}
		}
	}

	return false;
});
