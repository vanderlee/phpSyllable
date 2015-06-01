Syllable
========
Version 1.2

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
italian, romanian, russian, etc. 67 languages in total.

Language sources: http://tug.org/tex-hyphen/#languages

Supports PHP 5.2 and up, so you can use it on older servers.

Changes
-------
### 1.2
-	Deprecated treshold feature. Was based on misinterpretation of the
	algorithm. Methods, constants and constructor signature unchanged, although
	you can now omit the treshold if you want (or leave it in, it's detected as
	a "fake" treshold).