<?php

namespace Vanderlee\SyllableTest\Build;

use Vanderlee\SyllableBuild\Exception;
use Vanderlee\SyllableBuild\Reflection;
use Vanderlee\SyllableTest\AbstractTestCase;

class ReflectionTest extends AbstractTestCase
{
    /**
     * @var Reflection
     */
    protected $reflection;

    /**
     * Note: Use the @before annotation instead of the reserved setUp()
     * to be compatible with a wide range of PHPUnit versions.
     *
     * @before
     */
    protected function setUpFixture()
    {
        $this->reflection = new Reflection();
    }

    /**
     * @test
     */
    public function getPublicMethodsWithSignatureAndComment()
    {
        $class = ReflectionFixture::class;

        $expected = [
            [
                'signature' => 'public setMethods(array $methods = [])',
                'comment'   => "The public setter method.\nSee https://github.com/vanderlee/phpSyllable/blob/master/tests/build/ReflectionFixture.php.",
            ],
            [
                'signature' => 'public getMethods(): array',
                'comment'   => 'The public getter method.',
            ],
            [
                'signature' => 'public static getParameters(): array',
                'comment'   => 'The public static method.',
            ],
        ];

        $this->assertEquals($expected, $this->reflection->getPublicMethodsWithSignatureAndComment($class));
    }

    /**
     * @test
     */
    public function getPublicMethodsThrowsExceptionForNonExistingClass()
    {
        $class = 'Vanderlee\SyllableTest\Build\NonExisting';

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Reflecting class Vanderlee\SyllableTest\Build\NonExisting has failed with:');

        $this->reflection->getPublicMethodsWithSignatureAndComment($class);
    }

    public function getArrayAsSignatureDataProvider()
    {
        return [
            [
                [],
                '[]',
            ],
            [
                ['123456789'],
                "['123456789']",
            ],
            [
                ['--extra', 'flags'],
                "['--extra', 'flags']",
            ],
            [
                ['key' => '--extra', 'value' => 'flags'],
                "['key' => '--extra', 'value' => 'flags']"],
            [
                ['param' => ['key' => '--extra', 'value' => 'flags']],
                "['param' => ['key' => '--extra', 'value' => 'flags']]",
            ],
            [
                [
                    'param1' => ['key' => '--extra1', 'value' => 'flags1'],
                    'param2' => ['key' => '--extra2', 'value' => 'flags2'],
                ],
                '['.
                    "'param1' => ['key' => '--extra1', 'value' => 'flags1'], ".
                    "'param2' => ['key' => '--extra2', 'value' => 'flags2']".
                ']',
            ],
        ];
    }

    /**
     * @test
     *
     * @dataProvider getArrayAsSignatureDataProvider
     */
    public function getArrayAsSignature($array, $expected)
    {
        $actual = $this->reflection->getArrayAsSignature($array);

        $this->assertEquals($expected, $actual);
    }
}
