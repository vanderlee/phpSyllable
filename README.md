Syllable
========
Version 1.5.5

[![Tests](https://github.com/vanderlee/phpSyllable/actions/workflows/tests.yml/badge.svg)](https://github.com/vanderlee/phpSyllable/actions/workflows/tests.yml)

Copyright &copy; 2011-2023 Martijn van der Lee.
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
directory and instantiate yourself a Syllable class.

```php
$syllable = new Syllable('en-us');
echo $syllable->hyphenateText('Provide a plethora of paragraphs');
```

`Syllable` class reference
--------------------------
The following is an incomplete list, containing only the most common methods.
For a complete documentation of all classes, read the generated [PHPDoc](doc).

### public __construct($language = 'en', string|Hyphen $hyphen = null)

Create a new Syllable class, with defaults.

### public static setCacheDir(string $dir)

Set the directory where compiled language files may be stored.
Default to the `cache` subdirectory of the current directory.

### public static setEncoding(string|null $encoding = null)

Set the character encoding to use.
Specify `null` encoding to not apply any encoding at all.

### public static setLanguageDir(string $dir)

Set the directory where language source files can be found.
Default to the `languages` subdirectory of the current directory.

### public setLanguage(string $language)

Set the language whose rules will be used for hyphenation.

### public setHyphen(mixed $hyphen)

Set the hyphen text or object to use as a hyphen marker.

### public getHyphen(): Hyphen

Get the current hyphen object.

### public setCache(Cache $cache = null)

### public getCache(): Cache

### public setSource($source)

### public getSource(): Source

### public setMinWordLength(int $length = 0)

Words need to contain at least this many character to be hyphenated.

### public getMinWordLength(): int

### public setLibxmlOptions(int $libxmlOptions)

Options to use for HTML parsing by libxml.
See https://www.php.net/manual/de/libxml.constants.php.

### public excludeAll()

Exclude all elements.

### public excludeElement(string|string[] $elements)

Add one or more elements to exclude from HTML.

### public excludeAttribute(string|string[] $attributes, $value = null)

Add one or more elements with attributes to exclude from HTML.

### public excludeXpath(string|string[] $queries)

Add one or more xpath queries to exclude from HTML.

### public includeElement(string|string[] $elements)

Add one or more elements to include from HTML.

### public includeAttribute(string|string[] $attributes, $value = null)

Add one or more elements with attributes to include from HTML.

### public includeXpath(string|string[] $queries)

Add one or more xpath queries to include from HTML.

### public splitWord(string $word): array

Split a single word on where the hyphenation would go.
Punctuation is not supported, only simple words. For parsing whole sentences
please use Syllable::splitWords() or Syllable::splitText().

### public splitWords(string $text): array

Split a text into an array of punctuation marks and words,
splitting each word on where the hyphenation would go.

### public splitText(string $text): array

Split a text on where the hyphenation would go.

### public hyphenateWord(string $word): string

Hyphenate a single word.

### public hyphenateText(string $text): string

Hyphenate all words in the plain text.

### public hyphenateHtml(string $html): string

Hyphenate all readable text in the HTML, excluding HTML tags and
attributes.

### public histogramText(string $text): array

Count the number of syllables in the text and return a map with
syllable count as key and number of words for that syllable count as
the value.

### public countWordsText(string $text): int

Count the number of words in the text.

### public countSyllablesText(string $text): int

Count the number of syllables in the text.

### public countPolysyllablesText(string $text): int

Count the number of polysyllables in the text.

Example
-------
See the [demo.php](demo.php) file for a working example.

```php
// Setup the autoloader (if needed)
require_once dirname(__FILE__) . '/classes/autoloader.php';
use Vanderlee\Syllable\Syllable;

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

Development
-----------

### Update language files

Run
```
composer dump-autoload --dev
./build/update-language-files
```
to fetch the latest language files remotely and optionally use environment variables to customize the update process:

#### CONFIGURATION_FILE
Specify the absolute path of the configuration file where the language files to be downloaded are defined. The 
configuration file has the following format: 
```
{
	"files": [
		{
			"_comment": "<comment>",
			"fromUrl": "<absolute-remote-file-url>",
			"toPath": "<relative-local-file-path>",
			"disabled": <true|false>
		}
	]
}
```
where the attributes are self-explanatory and `_comment` and `disabled` are optional. See for example 
[build/update-language-files.json](build/update-language-files.json). 
Default: The `build/update-language-files.json` file of this package.

#### MAX_REDIRECTS

Specify the maximum number of URL redirects allowed when retrieving a language file.
Default: `1`.

#### WITH_COMMIT

Create (1) or skip (0) a Git commit from the updated language files.
Default: `0`.

#### LOG_LEVEL

Set the verbosity of the script to verbose (6), warnings and errors (4), errors only (3) or silent (0).
Default: `6`.

For example use
```
composer dump-autoload --dev
LOG_LEVEL=0 ./build/update-language-files
```
to silently run the script without outputting any logging.

### Update API documentation

Run
```
composer dump-autoload --dev
./build/generate-docs
```
to update the API documentation in this README.md. This should be done when the Syllable class has been modified.
Optionally, you can use environment variables to modify the documentation update process:

#### WITH_COMMIT

Create (1) or skip (0) a Git commit from the adapted files.
Default: `0`.

#### LOG_LEVEL

Set the verbosity of the script to verbose (6), warnings and errors (4), errors only (3) or silent (0).
Default: `6`.

### Create release

Run
```
composer dump-autoload --dev
./build/create-release
```
to create a local release of the project by adding a changelog to this README.md.
Optionally, you can use environment variables to modify the release process:

#### RELEASE_TYPE

Set the release type to major (0), minor (1) or patch (2) release.
Default: `2`.

#### WITH_COMMIT

Create (1) or skip (0) a Git commit from the adapted files and apply the release tag.
Default: `0`.

#### LOG_LEVEL

Set the verbosity of the script to verbose (6), warnings and errors (4), errors only (3) or silent (0).
Default: `6`.

### Tests

Run
```
composer install
./vendor/bin/phpunit
```
to execute the tests.


Changes
-------
1.5.5
-   Automatic update of 74 languages

1.5.4
-   Automatically run tests for every push and pull request
-   Automatic monthly update and release of language files
-   Fix small typo in README and add 'use' in example.
-   Use same code format as in src/Source/File.php
-   Fix opening brace
-   Remove whitespace
-   Fix closing brace
-   Use PHP syntax highlighting

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
