<?php

namespace Vanderlee\SyllableTest\Src;

use Vanderlee\Syllable\Hyphen\Text;
use Vanderlee\Syllable\Syllable;
use Vanderlee\SyllableTest\AbstractTestCase;

class SyllableTest extends AbstractTestCase
{
    /**
     * @var Syllable
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * Note: Use the @before annotation instead of the reserved setUp()
     * to be compatible with a wide range of PHPUnit versions.
     *
     * @before
     */
    protected function setUpFixture()
    {
        $this->createTestDirectory();

        Syllable::setDirectoryCache($this->getTestDirectory());
        Syllable::setDirectoryLanguage(realpath(__DIR__ . '/../../languages'));

        $this->object = new Syllable();
    }

    /**
     * Note: Use the @after annotation instead of the reserved tearDown()
     * to be compatible with a wide range of PHPUnit versions.
     *
     * @after
     */
    protected function tearDownFixture()
    {
        $this->removeTestDirectory();
    }

    /**
     * @return void
     */
    public function testSetLanguage()
    {
        $this->object->setHyphen('-');

        $this->object->setLanguage('en-us');
        $this->assertEquals(
            'Su-per-cal-ifrag-ilis-tic-ex-pi-ali-do-cious',
            $this->object->hyphenateText('Supercalifragilisticexpialidocious')
        );

        $this->object->setLanguage('nl');
        $this->assertEquals(
            'Su-per-ca-lifra-gi-lis-ti-c-ex-pi-a-li-do-cious',
            $this->object->hyphenateText('Supercalifragilisticexpialidocious')
        );

        $this->object->setLanguage('fr');
        $this->assertEquals(
            'Su-per-ca-li-fra-gi-lis-ti-cex-pia-li-do-cious',
            $this->object->hyphenateText('Supercalifragilisticexpialidocious')
        );
    }

    /**
     * @return void
     */
    public function testSetHyphen()
    {
        $this->object->setLanguage('en-us');

        $this->object->setHyphen('-');
        $this->assertEquals(
            'Su-per-cal-ifrag-ilis-tic-ex-pi-ali-do-cious',
            $this->object->hyphenateText('Supercalifragilisticexpialidocious')
        );

        $this->object->setHyphen('/');
        $this->assertEquals(
            'Su/per/cal/ifrag/ilis/tic/ex/pi/ali/do/cious',
            $this->object->hyphenateText('Supercalifragilisticexpialidocious')
        );
    }

    /**
     * @return void
     */
    public function testGetHyphen()
    {
        $this->object->setHyphen('-');
        $this->assertEquals(new Text('-'), $this->object->getHyphen());
        $this->assertNotEquals(new Text('+'), $this->object->getHyphen());

        $this->object->setHyphen('/');
        $this->assertEquals(new Text('/'), $this->object->getHyphen());
        $this->assertNotEquals(new Text('-'), $this->object->getHyphen());
    }

    /**
     * @todo Implement testSetCache().
     */
    public function testSetCache()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testGetCache().
     */
    public function testGetCache()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testSetSource().
     */
    public function testSetSource()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testGetSource().
     */
    public function testGetSource()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @return array[]
     */
    public function dataSplitWord()
    {
        return [
            [
                'Inexplicable',
                ['In', 'ex', 'plic', 'a', 'ble'],
            ],
        ];
    }

    /**
     * @dataProvider dataSplitWord
     * @return void
     */
    public function testSplitWord($word, $expected)
    {
        $this->object->setHyphen('-');
        $this->object->setLanguage('en-us');

        $this->assertEquals($expected, $this->object->splitWord($word));
    }

    /**
     * @return array[]
     */
    public function dataSplitWordDoesNotSupportPunctuation()
    {
        return [
            [
                ';Redundant,',
                [';Re', 'dun', 'dan', 't,'],
            ],
        ];
    }

    /**
     * @dataProvider dataSplitWordDoesNotSupportPunctuation
     * @return void
     */
    public function testSplitWordDoesNotSupportPunctuation($word, $expected)
    {
        $this->object->setHyphen('-');
        $this->object->setLanguage('en-us');

        $this->assertEquals($expected, $this->object->splitWord($word));
    }

    /**
     * This is a collection of words that are not fully hyphenated.
     *
     * Please add words here if they were insufficiently hyphenated when using the package.
     * This may be caused by an insufficient pattern in the TeX language file or by an error
     * of Syllable::parseWord() and needs to be investigated.
     *
     * @return array[]
     */
    public function dataSplitWordDoesNotAlwaysProvideFullHyphenation()
    {
        return [
            [
                'prosody',
                ['prosody'],
            ],
            [
                'pictograms',
                ['pic', 'tograms'],
            ]
        ];
    }

    /**
     * @dataProvider dataSplitWordDoesNotAlwaysProvideFullHyphenation
     * @return void
     */
    public function testSplitWordDoesNotAlwaysProvideFullHyphenation($word, $expected)
    {
        $this->object->setHyphen('-');
        $this->object->setLanguage('en-us');

        $this->assertEquals($expected, $this->object->splitWord($word));
    }

    /**
     * @return array
     */
    public function dataSplitWords()
    {
        return [
            'simple word' => [
                'Inexplicable',
                [
                    ['In', 'ex', 'plic', 'a', 'ble']
                ],
            ],
            'text with punctuation' => [
                ';Redundant, punctuation...',
                [
                    [';'],
                    ['Re', 'dun', 'dant'],
                    [', '],
                    ['punc', 'tu', 'a', 'tion'],
                    ['...'],
                ],
            ],
            'large text' => [
                'A syllable is a unit of organization for a sequence of speech sounds typically made up of a syllable ' .
                'nucleus (most often a vowel) with optional initial and final margins (typically, consonants). ' .
                'Syllables are often considered the phonological "building blocks" of words.[1] They can influence the ' .
                'rhythm of a language, its prosody, its poetic metre and its stress patterns. Speech can usually be ' .
                'divided up into a whole number of syllables: for example, the word ignite is made of two syllables: ' .
                'ig and nite. Syllabic writing began several hundred years before the first letters. The earliest ' .
                'recorded syllables are on tablets written around 2800 BC in the Sumerian city of Ur. This shift from ' .
                'pictograms to syllables has been called "the most important advance in the history of writing".[2] ' .
                'Syllable is an Anglo-Norman variation of Old French sillabe, from Latin syllaba, from Koine Greek ' .
                'συλλαβή syllabḗ (Greek pronunciation: [sylːabɛ:]). συλλαβή means "the taken together", referring to ' .
                'letters that are taken together to make a single sound.[3]',
                [
                    ['A'], [' '], ['syl', 'la', 'ble'], [' '], ['is'], [' '], ['a'], [' '], ['unit'], [' '], ['of'],
                    [' '], ['or', 'ga', 'ni', 'za', 'tion'], [' '], ['for'], [' '], ['a'], [' '], ['se', 'quence'],
                    [' '], ['of'], [' '], ['speech'], [' '], ['sounds'], [' '], ['typ', 'i', 'cal', 'ly'], [' '],
                    ['made'], [' '], ['up'], [' '], ['of'], [' '], ['a'], [' '], ['syl', 'la', 'ble'], [' '],
                    ['nu', 'cle', 'us'], [' ('], ['most'], [' '], ['of', 'ten'], [' '], ['a'], [' '], ['vow', 'el'],
                    [') '], ['with'], [' '], ['op', 'tion', 'al'], [' '], ['ini', 'tial'], [' '], ['and'], [' '],
                    ['fi', 'nal'], [' '], ['mar', 'gins'], [' ('], ['typ', 'i', 'cal', 'ly'], [', '],
                    ['con', 'so', 'nants'], ['). '], ['Syl', 'la', 'bles'], [' '], ['are'], [' '], ['of', 'ten'],
                    [' '], ['con', 'sid', 'ered'], [' '], ['the'], [' '], ['phono', 'log', 'i', 'cal'], [' "'],
                    ['build', 'ing'], [' '], ['blocks'], ['" '], ['of'], [' '], ['words'], ['.[1] '], ['They'], [' '],
                    ['can'], [' '], ['in', 'flu', 'ence'], [' '], ['the'], [' '], ['rhythm'], [' '], ['of'], [' '],
                    ['a'], [' '], ['lan', 'guage'], [', '], ['its'], [' '], ['prosody'], [', '], ['its'], [' '],
                    ['po', 'et', 'ic'], [' '], ['me', 'tre'], [' '], ['and'], [' '], ['its'], [' '], ['stress'], [' '],
                    ['pat', 'terns'], ['. '], ['Speech'], [' '], ['can'], [' '], ['usu', 'al', 'ly'], [' '], ['be'],
                    [' '], ['di', 'vid', 'ed'], [' '], ['up'], [' '], ['in', 'to'], [' '], ['a'], [' '], ['whole'],
                    [' '], ['num', 'ber'], [' '], ['of'], [' '], ['syl', 'la', 'bles'], [': '], ['for'], [' '],
                    ['ex', 'am', 'ple'], [', '], ['the'], [' '], ['word'], [' '], ['ig', 'nite'], [' '], ['is'], [' '],
                    ['made'], [' '], ['of'], [' '], ['two'], [' '], ['syl', 'la', 'bles'], [': '], ['ig'], [' '],
                    ['and'], [' '], ['nite'], ['. '], ['Syl', 'lab', 'ic'], [' '], ['writ', 'ing'], [' '],
                    ['be', 'gan'], [' '], ['sev', 'er', 'al'], [' '], ['hun', 'dred'], [' '], ['years'], [' '],
                    ['be', 'fore'], [' '], ['the'], [' '], ['first'], [' '], ['let', 'ters'], ['. '], ['The'], [' '],
                    ['ear', 'li', 'est'], [' '], ['record', 'ed'], [' '], ['syl', 'la', 'bles'], [' '], ['are'], [' '],
                    ['on'], [' '], ['tablets'], [' '], ['writ', 'ten'], [' '], ['around'], [' 2800 '], ['BC'], [' '],
                    ['in'], [' '], ['the'], [' '], ['Sumer', 'ian'], [' '], ['city'], [' '], ['of'], [' '], ['Ur'],
                    ['. '], ['This'], [' '], ['shift'], [' '], ['from'], [' '], ['pic', 'tograms'], [' '], ['to'],
                    [' '], ['syl', 'la', 'bles'], [' '], ['has'], [' '], ['been'], [' '], ['called'], [' "'], ['the'],
                    [' '], ['most'], [' '], ['im', 'por', 'tant'], [' '], ['ad', 'vance'], [' '], ['in'], [' '],
                    ['the'], [' '], ['his', 'to', 'ry'], [' '], ['of'], [' '], ['writ', 'ing'], ['".[2] '],
                    ['Syl', 'la', 'ble'], [' '], ['is'], [' '], ['an'], [' '], ['An', 'glo'], ['-'], ['Nor', 'man'],
                    [' '], ['vari', 'a', 'tion'], [' '], ['of'], [' '], ['Old'], [' '], ['French'], [' '],
                    ['sil', 'l', 'abe'], [', '], ['from'], [' '], ['Latin'], [' '], ['syl', 'la', 'ba'], [', '],
                    ['from'], [' '], ['Koine'], [' '], ['Greek'], [' '], ['συλλαβή'], [' '], ['syl', 'labḗ'], [' ('],
                    ['Greek'], [' '], ['pro', 'nun', 'ci', 'a', 'tion'], [': ['], ['sylːabɛ'], [':]). '],
                    ['συλλαβή'], [' '], ['means'], [' "'], ['the'], [' '], ['tak', 'en'], [' '], ['to', 'geth', 'er'],
                    ['", '], ['re', 'fer', 'ring'], [' '], ['to'], [' '], ['let', 'ters'], [' '], ['that'], [' '],
                    ['are'], [' '], ['tak', 'en'], [' '], ['to', 'geth', 'er'], [' '], ['to'], [' '], ['make'], [' '],
                    ['a'], [' '], ['sin', 'gle'], [' '], ['sound'], ['.[3]' ]
                ]
            ],
        ];
    }

    /**
     * @dataProvider dataSplitWords
     * @return void
     */
    public function testSplitWords($text, $expected)
    {
        $this->object->setHyphen('-');
        $this->object->setLanguage('en-us');

        $this->assertEquals($expected, $this->object->splitWords($text));
    }

    /**
     * @return array
     */
    public function dataSplitText()
    {
        return [
            'simple word' => [
                'Inexplicable',
                ['In', 'ex', 'plic', 'a', 'ble'],
            ],
            'text with punctuation' => [
                ';Redundant, punctuation...',
                [';Re', 'dun', 'dant, punc', 'tu', 'a', 'tion...'],
            ],
            'large text' => [
                'A syllable is a unit of organization for a sequence of speech sounds typically made up of a syllable ' .
                'nucleus (most often a vowel) with optional initial and final margins (typically, consonants). ' .
                'Syllables are often considered the phonological "building blocks" of words.[1] They can influence the ' .
                'rhythm of a language, its prosody, its poetic metre and its stress patterns. Speech can usually be ' .
                'divided up into a whole number of syllables: for example, the word ignite is made of two syllables: ' .
                'ig and nite. Syllabic writing began several hundred years before the first letters. The earliest ' .
                'recorded syllables are on tablets written around 2800 BC in the Sumerian city of Ur. This shift from ' .
                'pictograms to syllables has been called "the most important advance in the history of writing".[2] ' .
                'Syllable is an Anglo-Norman variation of Old French sillabe, from Latin syllaba, from Koine Greek ' .
                'συλλαβή syllabḗ (Greek pronunciation: [sylːabɛ:]). συλλαβή means "the taken together", referring to ' .
                'letters that are taken together to make a single sound.[3]',
                [
                    'A syl', 'la', 'ble is a unit of or','ga', 'ni', 'za', 'tion for a se',
                    'quence of speech sounds typ', 'i', 'cal', 'ly made up of a syl', 'la', 'ble nu', 'cle',
                    'us (most of', 'ten a vow', 'el) with op', 'tion', 'al ini', 'tial and fi', 'nal mar',
                    'gins (typ', 'i', 'cal', 'ly, con', 'so', 'nants). Syl', 'la', 'bles are of', 'ten con', 'sid',
                    'ered the phono', 'log', 'i', 'cal "build', 'ing blocks" of words.[1] They can in', 'flu',
                    'ence the rhythm of a lan', 'guage, its prosody, its po', 'et', 'ic me', 'tre and its stress pat',
                    'terns. Speech can usu', 'al', 'ly be di', 'vid', 'ed up in', 'to a whole num', 'ber of syl', 'la',
                    'bles: for ex', 'am', 'ple, the word ig', 'nite is made of two syl', 'la', 'bles: ig and nite. Syl',
                    'lab', 'ic writ', 'ing be', 'gan sev', 'er', 'al hun', 'dred years be', 'fore the first let',
                    'ters. The ear', 'li', 'est record', 'ed syl', 'la', 'bles are on tablets writ',
                    'ten around 2800 BC in the Sumer', 'ian city of Ur. This shift from pic', 'tograms to syl', 'la',
                    'bles has been called "the most im', 'por', 'tant ad', 'vance in the his', 'to', 'ry of writ',
                    'ing".[2] Syl', 'la', 'ble is an An', 'glo-Nor', 'man vari', 'a', 'tion of Old French sil', 'l',
                    'abe, from Latin syl', 'la', 'ba, from Koine Greek συλλαβή syl', 'labḗ (Greek pro', 'nun', 'ci',
                    'a', 'tion: [sylːabɛ:]). συλλαβή means "the tak', 'en to', 'geth', 'er", re', 'fer', 'ring to let',
                    'ters that are tak', 'en to', 'geth', 'er to make a sin', 'gle sound.[3]'
                ]
            ],
        ];
    }

    /**
     * @dataProvider dataSplitText
     * @return void
     */
    public function testSplitText($text, $expected)
    {
        $this->object->setHyphen('-');
        $this->object->setLanguage('en-us');

        $this->assertEquals($expected, $this->object->splitText($text));
    }

    /**
     * @return void
     */
    public function testHyphenateWord()
    {
        $this->object->setHyphen('-');
        $this->object->setLanguage('en-us');

        $this->assertEquals(
            ';Re-dun-dan-t, punc-tu-a-tion...',
            $this->object->hyphenateWord(';Redundant, punctuation...')
        );
        $this->assertEquals('In-ex-plic-a-ble', $this->object->hyphenateWord('Inexplicable'));
    }

    /**
     * @return void
     */
    public function testHyphenateText()
    {
        $this->object->setHyphen('-');
        $this->object->setLanguage('en-us');

        $this->assertEquals(
            ';Re-dun-dant, punc-tu-a-tion...',
            $this->object->hyphenateText(';Redundant, punctuation...')
        );
        $this->assertEquals('In-ex-plic-a-ble', $this->object->hyphenateText('Inexplicable'));

        // note that HTML attributes are hyphenated too!
        $this->assertEquals(
            'Ridicu-lous-ly <b class="un-split-table">com-pli-cat-ed</b> meta-text',
            $this->object->hyphenateText('Ridiculously <b class="unsplittable">complicated</b> metatext')
        );
    }

    /**
     * @return void
     */
    public function testMinWordLength()
    {
        $this->object->setHyphen('-');
        $this->object->setLanguage('en-us');

        $this->assertEquals(
            'I am the same thing en-core in-stead im-poster ven-er-a-ble',
            $this->object->hyphenateText('I am the same thing encore instead imposter venerable')
        );

        $this->object->setMinWordLength(6);
        $this->assertEquals(
            'I am the same thing en-core in-stead im-poster ven-er-a-ble',
            $this->object->hyphenateText('I am the same thing encore instead imposter venerable')
        );

        $this->object->setMinWordLength(7);
        $this->assertEquals(
            'I am the same thing encore in-stead im-poster ven-er-a-ble',
            $this->object->hyphenateText('I am the same thing encore instead imposter venerable')
        );

        $this->object->setMinWordLength(8);
        $this->assertEquals(
            'I am the same thing encore instead im-poster ven-er-a-ble',
            $this->object->hyphenateText('I am the same thing encore instead imposter venerable')
        );

        $this->object->setMinWordLength(9);
        $this->assertEquals(
            'I am the same thing encore instead imposter ven-er-a-ble',
            $this->object->hyphenateText('I am the same thing encore instead imposter venerable')
        );

        $this->object->setMinWordLength(10);
        $this->assertEquals(
            'I am the same thing encore instead imposter venerable',
            $this->object->hyphenateText('I am the same thing encore instead imposter venerable')
        );

        $this->object->setMinWordLength();
        $this->assertEquals(
            'I am the same thing en-core in-stead im-poster ven-er-a-ble',
            $this->object->hyphenateText('I am the same thing encore instead imposter venerable')
        );
    }

    /**
     * @return void
     */
    public function testHyphenateHtml()
    {
        $this->object->setHyphen('-');
        $this->object->setLanguage('en-us');

        $this->assertEquals('<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">'
            . "\n" . '<html><body><p>Ridicu-lous-ly <b class="unsplittable">com-pli-cat-ed</b> meta-text</p></body></html>'
            . "\n", $this->object->hyphenateHtml('Ridiculously <b class="unsplittable">complicated</b> metatext'));

        $this->object->setLibxmlOptions(LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $this->assertEquals('<p>Ridicu-lous-ly <b class="unsplittable">com-pli-cat-ed</b> meta-text</p>'
            . "\n", $this->object->hyphenateHtml('Ridiculously <b class="unsplittable">complicated</b> metatext'));
    }

    /**
     * @return void
     */
    public function testCaseInsensitivity()
    {
        $this->object->setHyphen('-');
        $this->object->setLanguage('en-us');

        $this->assertEquals(['IN', 'EX', 'PLIC', 'A', 'BLE'], $this->object->splitText('INEXPLICABLE'));
        $this->assertEquals(['in', 'ex', 'plic', 'a', 'ble'], $this->object->splitText('inexplicable'));
    }

    /**
     * @return void
     */
    public function testHistogramText()
    {
        $this->object->setLanguage('en-us');
        $this->assertSame([], $this->object->histogramText('.'));
        $this->assertSame(
            [1 => 1, 2 => 2, 3 => 1, 5 => 1, 7 => 1],
            $this->object->histogramText('1 is wonder welcome furthermore sophisticated extravagantically.')
        );
    }

    /**
     * @return void
     */
    public function testCountWordsText()
    {
        $this->object->setLanguage('en-us');
        $this->assertSame(0, $this->object->countWordsText('.'));
        $this->assertSame(
            6,
            $this->object->countWordsText('1 is wonder welcome furthermore sophisticated extravagantically.')
        );
    }

    /**
     * @return void
     */
    public function testCountPolysyllablesText()
    {
        $this->object->setLanguage('en-us');
        $this->assertSame(0, $this->object->countPolysyllablesText('.'));
        $this->assertSame(
            3,
            $this->object->countPolysyllablesText('1 is wonder welcome furthermore sophisticated extravagantically.')
        );
    }

    /**
     * @return void
     */
    public function testCountSyllablesText()
    {
        $this->object->setLanguage('en-us');
        $this->assertSame(0, $this->object->countSyllablesText('.'));
        $this->assertSame(
            1 + 2 + 2 + 3 + 5 + 7,
            $this->object->countSyllablesText('1 is wonder welcome furthermore sophisticated extravagantically.')
        );
    }

    /**
     * @return void
     */
    public function testExcludeElement()
    {
        $this->object->setLanguage('en-us');
        $this->object->setHyphen('-');
        $this->object->excludeElement('b');

        // Do not Hypenate content within <b>
        $this->assertEquals(
            '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">'
            . "\n" . '<html><body><p>Ridicu-lous-ly <b class="unsplittable">complicated</b> meta-text <i>ex-trav-a-gan-za</i></p></body></html>'
            . "\n",
            $this->object->hyphenateHtml('Ridiculously <b class="unsplittable">complicated</b> metatext <i>extravaganza</i>')
        );
    }

    /**
     * @return void
     */
    public function testExcludeElements()
    {
        $this->object->setLanguage('en-us');
        $this->object->setHyphen('-');
        $this->object->excludeElement(['b', 'i']);

        // Do not Hypenate content within <b>
        $this->assertEquals(
            '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">'
            . "\n" . '<html><body><p>Ridicu-lous-ly <b class="unsplittable">complicated</b> meta-text <i>extravaganza</i></p></body></html>'
            . "\n",
            $this->object->hyphenateHtml('Ridiculously <b class="unsplittable">complicated</b> metatext <i>extravaganza</i>')
        );
    }

    /**
     * @return void
     */
    public function testExcludeAllAndInclude()
    {
        $this->object->setLanguage('en-us');
        $this->object->setHyphen('-');
        $this->object->excludeAll();
        $this->object->includeElement('b');

        // Do not Hypenate content within <b>
        $this->assertEquals(
            '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">'
            . "\n" . '<html><body><p>Ridiculously <b class="unsplittable">com-pli-cat-ed</b> metatext <i>extravaganza</i></p></body></html>'
            . "\n",
            $this->object->hyphenateHtml('Ridiculously <b class="unsplittable">complicated</b> metatext <i>extravaganza</i>')
        );
    }

    /**
     * @return void
     */
    public function testExcludeAndInclude()
    {
        $this->object->setLanguage('en-us');
        $this->object->setHyphen('-');
        $this->object->excludeElement('b');
        $this->object->includeElement('i');

        // Do not Hypenate content within <b>
        $this->assertEquals(
            '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">'
            . "\n" . '<html><body><p>Ridicu-lous-ly <b class="unsplittable">complicated <i>ex-trav-a-gan-za</i></b> meta-text</p></body></html>'
            . "\n",
            $this->object->hyphenateHtml('Ridiculously <b class="unsplittable">complicated <i>extravaganza</i></b> metatext')
        );
    }

    /**
     * @return void
     */
    public function testExcludeAttribute()
    {
        $this->object->setLanguage('en-us');
        $this->object->setHyphen('-');
        $this->object->excludeAttribute('class');

        // Do not Hypenate content within <b>
        $this->assertEquals(
            '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">'
            . "\n" . '<html><body><p>Ridicu-lous-ly <b class="unsplittable">complicated</b> meta-text <i>ex-trav-a-gan-za</i></p></body></html>'
            . "\n",
            $this->object->hyphenateHtml('Ridiculously <b class="unsplittable">complicated</b> metatext <i>extravaganza</i>')
        );
    }

    /**
     * @return void
     */
    public function testExcludeAttributeValue()
    {
        $this->object->setLanguage('en-us');
        $this->object->setHyphen('-');
        $this->object->excludeAttribute('class', 'unsplittable');

        // Do not Hypenate content within <b>
        $this->assertEquals(
            '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">'
            . "\n" . '<html><body><p>Ridicu-lous-ly <b class="unsplittable">complicated</b> meta-text <i class="go right ahead">ex-trav-a-gan-za</i></p></body></html>'
            . "\n",
            $this->object->hyphenateHtml('Ridiculously <b class="unsplittable">complicated</b> metatext <i class="go right ahead">extravaganza</i>')
        );
    }
}
