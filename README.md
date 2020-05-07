Syllable
========
Version 1.5.3

[![Build Status](https://travis-ci.org/vanderlee/phpSyllable.svg?branch=master)](https://travis-ci.org/vanderlee/phpSyllable)

Copyright &copy; 2011-2019 Martijn van der Lee.
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

Supports PHP 5.6 and up, so you can use it on older servers.

Quick start
-----------
Just include phpSyllable in your project, set up the autoloader to the classes
directory and instantiate yourself a Sylllable class.

	$syllable = new Syllable('en-us');
	echo $syllable->hyphenateText('Provide a plethora of paragraphs');

`Syllable` class reference
--------------------------
The following is an incomplete list, containing only the most common methods.
For a complete documentation of all classes, read the generated [PHPDoc](doc).

### public static __construct(  $language = 'en',  $hyphen = null )
Create a new Syllable class, with defaults

### public static setCacheDir(  $dir )
Set the directory where compiled language files may be stored.
Default to the `cache` subdirectory of the current directory.

### public static setLanguageDir(  $dir )
Set the directory where language source files can be found.
Default to the `languages` subdirectory of the current directory.

### public static function setEncoding(  $encoding = null  )
Specify the character encoding to use or disable character encoding handling
completely by specifying `null` as encoding. The default encoding is `UTF-8`,
which will work in most situations.

### public setLanguage(  $language )
Set the language whose rules will be used for hyphenation.

### public setHyphen( Mixed $hyphen )
Set the hyphen text or object to use as a hyphen marker.

### public getHyphen( ) : Syllable_Hyphen_Interface
Get the hyphen object used as a hyphen marker.

### public setMinWordLength( integer $length = 0 )
Set the minimum length required for a word to be hyphenated.
Any words with less characters than this length will not be hyphenated.

### public getMinWordLength( ) : int
Get the minimum length required for a word to be hyphenated.

### public array splitWord(  $word )
Split a single word on where the hyphenation would go.

### public array splitText(  $text )
Split a text on where the hyphenation would go.

### public string hyphenateWord(  $word )
Hyphenate a single word.

### public string hyphenateText(  $text )
Hyphenate all words in the plain text.

### public string hyphenateHtml(  $html )
Hyphenate all readable text in the HTML, excluding HTML tags and attributes.

### public array histogramText(  $text )
Count the number of syllables in the text and return a map with
syllable count as key and number of words for that syllable count as
the value.

### public integer countWordsText(  $text )
Count the number of words in the text.

### public integer countSyllablesText(  $text )
Count the number of syllables in the text.

### public integer countPolysyllablesText(  $text )
Count the number of polysyllables (words with 3 or more syllables) in the text.

### public function excludeAll()
Exclude all HTML elements from hyphenation, allowing explicit whitelisting.

###	public function excludeElement(  $elements  )
Exclude from hyphenation all HTML content within the given elements.

###	public function excludeAttribute(  $attributes, $value = null  )
Exclude from hyphenation all HTML content within elements with the given
attributes. If a value is specified, only those elements with attributes with
that specific value are excluded.

###	public function excludeXpath(  $queries  )
Exclude from hyphenation all HTML content within elements matching the
specified xpath queries.

###	public function includeElement(  $elements  )
Hyphenate all HTML content within the given elements,
ignoring any rules which might exclude them from hyphenation.

###	public function includeAttribute(  $attributes, $value = null  )
Hyphenate all HTML content within elements with the given attributes. If a value
is specified, only those elements with attributes with that specific value are
included, ignoring any rules which might exclude them from hyphenation.

###	public function includeXpath(  $queries  )
Hyphenate all HTML content within elements matching the specified xpath queries,
ignoring any rules which might exclude them from hyphenation.

Example
-------
See the included [demo.php](demo.php) file for a working example.

```php
// Setup the autoloader (if needed)
require_once dirname(__FILE__) . '/classes/autoloader.php';

// Create a new instance for the language
$syllable = new Syllable('en-us');

// Set the directory where the .tex files are stored
$syllable->getSource()->setPath(__DIR__ . '/languages');

// Set the directory where Syllable can store cache files
$syllable->getCache()->setPath(__DIR__ . '/cache');

// Set the hyphen style. In this case, the &shy; HTML entity
// for HTML (falls back to '-' for text)
$syllable->setHyphen(new Syllable_Hyphen_Soft);

// Set the treshold (sensitivity)
$syllable->setTreshold(Syllable::TRESHOLD_MOST);

// Output hyphenated text
echo $syllable->hyphenateText('Provide your own paragraphs...');
```

Changes
-------
1.5.3
-   Fixed PHP 7.4 compatibility (#37) by @Dargmuesli.

1.5.2
-   Fixed bug reverted in refactoring (continue 3) by @Dargmuesli.

1.5.1
-   Fixed bug reverted in refactoring (continue 2).

1.5
-   Refactored for modern PHP and support for current PHP version.

1.4.6
-	Added `setMinWordLength($length)` and `getMinWordLength()` to limit
	hyphenation to words with at least the specified number of characters.

1.4.5
-	Fixes for composer.

1.4.4
-	Composer autoloader added

1.4.3
-	Improved documentation

1.4.2
-	Updated spanish language files.
-	Initial PHPDoc.

1.4.1
-	More fixes for apostrophes in splitting.

1.4
-	Fix for French language handling
-	Refactor .text loading into source class.
-	Massive cache performance increase (excessive writes).

1.3.1
-	Fix slow initial cache writing; too many writes (only one was needed).
-	Removed min_hyphenation; mb_strlen takes more time than hashmap lookup.

1.3
-	Added `array histogramText($text)`, `integer countWordsText($text)` and
	`integer countPolysyllableText($text)` methods.
-	Refactored cache interface.
-	Improved unittests.

1.2
-	Deprecated treshold feature. Was based on misinterpretation of the
	algorithm. Methods, constants and constructor signature unchanged, although
	you can now omit the treshold if you want (or leave it in, it's detected as
	a "fake" treshold).
