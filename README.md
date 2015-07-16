Syllable
========
Version 1.4.1

[![Build Status](https://travis-ci.org/vanderlee/phpSyllable.svg)](https://travis-ci.org/vanderlee/phpSyllable)

Copyright &copy; 2011-2015 Martijn van der Lee.
MIT Open Source license applies.

Introduction
------------
PHP Syllable splitting and hyphenation.
or rather...
PHP Syl-la-ble split-ting and hy-phen-ation.

Based on the work by Frank M. Liang (http://www.tug.org/docs/liang/)
and the many volunteers in the TeX community.

Many languages supported. i.e. english (us/uk), spanish, german, french, dutch,
italian, romanian, russian, etc. 76 languages in total.

Language sources: http://tug.org/tex-hyphen/#languages

Supports PHP 5.2 and up, so you can use it on older servers.

Changes
-------
### 1.4.1
-	More fixes for apostrophes in splitting.
### 1.4
-	Fix for French language handling
-	Refactor .text loading into source class.
-	Massive cache performance increase (excessive writes).
### 1.3.1
-	Fix slow initial cache writing; too many writes (only one was needed).
-	Removed min_hyphenation; mb_strlen takes more time than hashmap lookup.
### 1.3
-	Added `array histogramText($text)`, `integer countWordsText($text)` and
	`integer countPolysyllableText($text)` methods.
-	Refactored cache interface.
-	Improved unittests.
### 1.2
-	Deprecated treshold feature. Was based on misinterpretation of the
	algorithm. Methods, constants and constructor signature unchanged, although
	you can now omit the treshold if you want (or leave it in, it's detected as
	a "fake" treshold).