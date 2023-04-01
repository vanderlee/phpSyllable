<?php

namespace Vanderlee\SyllableTest\Src;

use Vanderlee\Syllable\Hyphen\Text;
use Vanderlee\Syllable\Syllable;
use Vanderlee\SyllableTest\AbstractTestCase;

/**
 * @coversDefaultClass \Vanderlee\Syllable\Syllable
 */
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
     * @covers ::setLanguage
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
     * @covers ::setHyphen
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
     * @covers ::getHyphen
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
     * @covers ::setCache
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
     * @covers ::getCache
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
     * @covers ::setSource
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
     * @covers ::getSource
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
     * @dataProvider dataSplitWord
     * @covers ::splitWord
     * @return void
     */
    public function testSplitWord($expected, $word)
    {
        $this->markTestIncomplete('splitWord is known to fail in specific cases');

        $this->object->setHyphen('-');
        $this->object->setLanguage('en-us');

        $this->assertEquals($expected, $this->object->splitWord($word));
    }

    /**
     * @return array[]
     */
    public function dataSplitWord()
    {
        return [
            'simple' => [
                ['In', 'ex', 'plic', 'a', 'ble'],
                'Inexplicable',
            ],

            'punctuation' => [
                [';Re', 'dun', 'dant,'],
                ';Redundant,',
            ],
        ];
    }

    /**
     * @dataProvider dataSplitWords
     * @covers ::splitWords
     */
    public function testSplitWords($expected, $text)
    {
        $this->object->setHyphen('-');
        $this->object->setLanguage('en-us');

        $this->assertEquals($expected, $this->object->splitWords($text));
    }

    /**
     * @return array
     */
    public function dataSplitWords()
    {
        return [
            'simple word' => [
                [
                    0 => [
                        0 => 'In',
                        1 => 'ex',
                        2 => 'plic',
                        3 => 'a',
                        4 => 'ble'
                    ]
                ],
                'Inexplicable',
            ],

            'words with punctuation' => [
                [
                    0 => [
                        0 => ';Re',
                        1 => 'dun',
                        2 => 'dant,',
                    ],
                    1 => [
                        0 => 'punc',
                        1 => 'tu',
                        2 => 'a',
                        3 => 'tion...'
                    ]
                ],
                ';Redundant, punctuation...'
            ],
        ];
    }

    /**
     * @covers ::splitText
     * @return void
     */
    public function testSplitText()
    {
        $this->object->setHyphen('-');
        $this->object->setLanguage('en-us');

        $this->assertEquals(
            [';Re', 'dun', 'dant, punc', 'tu', 'a', 'tion...'],
            $this->object->splitText(';Redundant, punctuation...')
        );
        $this->assertEquals(['In', 'ex', 'plic', 'a', 'ble'], $this->object->splitText('Inexplicable'));
    }

    /**
     * @covers ::hyphenateWord
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
     * @covers ::hyphenateText
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
     * @covers ::setMinWordLength
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
     * @covers ::hyphenateHtml
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
     * @covers ::splitText
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
     * @covers ::histogramText
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
     * @covers ::countWordsText
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
     * @covers ::countPolysyllablesText
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
     * @covers ::countSyllablesText
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
     * @covers ::excludeElement
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
     * @covers ::excludeElement
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
     * @covers ::excludeAll
     * @covers ::includeElement
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
     * @covers ::excludeElement
     * @covers ::includeElement
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
     * @covers ::excludeAttribute
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
     * @covers ::excludeAttribute
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
