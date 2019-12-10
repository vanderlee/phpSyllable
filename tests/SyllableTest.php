<?php

use PHPUnit\Framework\TestCase;
use Vanderlee\Syllable\Syllable;

class SyllableTest extends TestCase
{

    /**
     * @var Syllable
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        Syllable::setCacheDir(realpath(__DIR__ . '/../cache'));
        Syllable::setLanguageDir(realpath(__DIR__ . '/../languages'));

        // Make sure the cache dir exists for our tests.
        if (!file_exists(__DIR__ . '/../cache')) {
            mkdir(__DIR__ . '/../cache');
        }

        $this->object = new Syllable;
    }

    /**
     * @todo   Implement testSetLanguage().
     */
    public function testSetLanguage()
    {
        $this->object->setHyphen('-');

        $this->object->setLanguage('en-us');
        $this->assertEquals('Su-per-cal-ifrag-ilis-tic-ex-pi-ali-do-cious',
            $this->object->hyphenateText('Supercalifragilisticexpialidocious'));

        $this->object->setLanguage('nl');
        $this->assertEquals('Su-per-ca-lifra-gi-lis-ti-c-ex-pi-a-li-do-cious',
            $this->object->hyphenateText('Supercalifragilisticexpialidocious'));

        $this->object->setLanguage('fr');
        $this->assertEquals('Su-per-ca-li-fra-gi-lis-ti-cex-pia-li-do-cious',
            $this->object->hyphenateText('Supercalifragilisticexpialidocious'));
    }

    /**
     * @todo   Implement testSetHyphen().
     */
    public function testSetHyphen()
    {
        $this->object->setLanguage('en-us');

        $this->object->setHyphen('-');
        $this->assertEquals('Su-per-cal-ifrag-ilis-tic-ex-pi-ali-do-cious',
            $this->object->hyphenateText('Supercalifragilisticexpialidocious'));

        $this->object->setHyphen('/');
        $this->assertEquals('Su/per/cal/ifrag/ilis/tic/ex/pi/ali/do/cious',
            $this->object->hyphenateText('Supercalifragilisticexpialidocious'));
    }

    /**
     * @todo   Implement testGetHyphen().
     */
    public function testGetHyphen()
    {
        $this->object->setHyphen('-');
        $this->assertEquals(new Vanderlee\Syllable\Hyphen\Text('-'), $this->object->getHyphen());
        $this->assertNotEquals(new Vanderlee\Syllable\Hyphen\Text('+'), $this->object->getHyphen());

        $this->object->setHyphen('/');
        $this->assertEquals(new Vanderlee\Syllable\Hyphen\Text('/'), $this->object->getHyphen());
        $this->assertNotEquals(new Vanderlee\Syllable\Hyphen\Text('-'), $this->object->getHyphen());
    }

    /**
     * @todo   Implement testSetCache().
     */
    public function testSetCache()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo   Implement testGetCache().
     */
    public function testGetCache()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo   Implement testSetSource().
     */
    public function testSetSource()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo   Implement testGetSource().
     */
    public function testGetSource()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo   Implement testSplitWord().
     */
    public function testSplitWord()
    {
        $this->object->setHyphen('-');
        $this->object->setLanguage('en-us');

        $this->assertEquals(array(';Re', 'dun', 'dan', 't, punc', 'tu', 'a', 'tion...'),
            $this->object->splitWord(';Redundant, punctuation...'));
        $this->assertEquals(array('In', 'ex', 'plic', 'a', 'ble'), $this->object->splitWord('Inexplicable'));
    }

    /**
     * @todo   Implement testSplitText().
     */
    public function testSplitText()
    {
        $this->object->setHyphen('-');
        $this->object->setLanguage('en-us');

        $this->assertEquals(array(';Re', 'dun', 'dant, punc', 'tu', 'a', 'tion...'),
            $this->object->splitText(';Redundant, punctuation...'));
        $this->assertEquals(array('In', 'ex', 'plic', 'a', 'ble'), $this->object->splitText('Inexplicable'));
    }

    /**
     * @todo   Implement testHyphenateWord().
     */
    public function testHyphenateWord()
    {
        $this->object->setHyphen('-');
        $this->object->setLanguage('en-us');

        $this->assertEquals(';Re-dun-dan-t, punc-tu-a-tion...',
            $this->object->hyphenateWord(';Redundant, punctuation...'));
        $this->assertEquals('In-ex-plic-a-ble', $this->object->hyphenateWord('Inexplicable'));
    }

    /**
     * @todo   Implement testHyphenateText().
     */
    public function testHyphenateText()
    {
        $this->object->setHyphen('-');
        $this->object->setLanguage('en-us');

        $this->assertEquals(';Re-dun-dant, punc-tu-a-tion...',
            $this->object->hyphenateText(';Redundant, punctuation...'));
        $this->assertEquals('In-ex-plic-a-ble', $this->object->hyphenateText('Inexplicable'));

        // note that HTML attributes are hyphenated too!
        $this->assertEquals('Ridicu-lous-ly <b class="un-split-table">com-pli-cat-ed</b> meta-text',
            $this->object->hyphenateText('Ridiculously <b class="unsplittable">complicated</b> metatext'));
    }

    /**
     * @todo   Implement testHyphenateText().
     */
    public function testMinWordLength()
    {
        $this->object->setHyphen('-');
        $this->object->setLanguage('en-us');

        $this->assertEquals('I am the same thing en-core in-stead im-poster ven-er-a-ble',
            $this->object->hyphenateText('I am the same thing encore instead imposter venerable'));

        $this->object->setMinWordLength(6);
        $this->assertEquals('I am the same thing en-core in-stead im-poster ven-er-a-ble',
            $this->object->hyphenateText('I am the same thing encore instead imposter venerable'));

        $this->object->setMinWordLength(7);
        $this->assertEquals('I am the same thing encore in-stead im-poster ven-er-a-ble',
            $this->object->hyphenateText('I am the same thing encore instead imposter venerable'));

        $this->object->setMinWordLength(8);
        $this->assertEquals('I am the same thing encore instead im-poster ven-er-a-ble',
            $this->object->hyphenateText('I am the same thing encore instead imposter venerable'));

        $this->object->setMinWordLength(9);
        $this->assertEquals('I am the same thing encore instead imposter ven-er-a-ble',
            $this->object->hyphenateText('I am the same thing encore instead imposter venerable'));

        $this->object->setMinWordLength(10);
        $this->assertEquals('I am the same thing encore instead imposter venerable',
            $this->object->hyphenateText('I am the same thing encore instead imposter venerable'));

        $this->object->setMinWordLength();
        $this->assertEquals('I am the same thing en-core in-stead im-poster ven-er-a-ble',
            $this->object->hyphenateText('I am the same thing encore instead imposter venerable'));
    }

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

    public function testCaseInsensitivity()
    {
        $this->object->setHyphen('-');
        $this->object->setLanguage('en-us');

        $this->assertEquals(array('IN', 'EX', 'PLIC', 'A', 'BLE'), $this->object->splitText('INEXPLICABLE'));
        $this->assertEquals(array('in', 'ex', 'plic', 'a', 'ble'), $this->object->splitText('inexplicable'));
    }

    public function testHistogramText()
    {
        $this->object->setLanguage('en-us');
        $this->assertSame(array(), $this->object->histogramText('.'));
        $this->assertSame(array(1 => 1, 2 => 2, 3 => 1, 5 => 1, 7 => 1),
            $this->object->histogramText('1 is wonder welcome furthermore sophisticated extravagantically.'));
    }

    public function testCountWordsText()
    {
        $this->object->setLanguage('en-us');
        $this->assertSame(0, $this->object->countWordsText('.'));
        $this->assertSame(6,
            $this->object->countWordsText('1 is wonder welcome furthermore sophisticated extravagantically.'));
    }

    public function testCountPolysyllablesText()
    {
        $this->object->setLanguage('en-us');
        $this->assertSame(0, $this->object->countPolysyllablesText('.'));
        $this->assertSame(3,
            $this->object->countPolysyllablesText('1 is wonder welcome furthermore sophisticated extravagantically.'));
    }

    public function testCountSyllablesText()
    {
        $this->object->setLanguage('en-us');
        $this->assertSame(0, $this->object->countSyllablesText('.'));
        $this->assertSame(1 + 2 + 2 + 3 + 5 + 7,
            $this->object->countSyllablesText('1 is wonder welcome furthermore sophisticated extravagantically.'));
    }

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

    public function testExcludeElements()
    {
        $this->object->setLanguage('en-us');
        $this->object->setHyphen('-');
        $this->object->excludeElement(array('b', 'i'));

        // Do not Hypenate content within <b>
        $this->assertEquals(
            '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">'
            . "\n" . '<html><body><p>Ridicu-lous-ly <b class="unsplittable">complicated</b> meta-text <i>extravaganza</i></p></body></html>'
            . "\n",
            $this->object->hyphenateHtml('Ridiculously <b class="unsplittable">complicated</b> metatext <i>extravaganza</i>')
        );
    }

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
