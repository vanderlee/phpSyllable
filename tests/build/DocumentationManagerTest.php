<?php

namespace Vanderlee\SyllableTest\Build;

use Vanderlee\SyllableBuild\DocumentationManager;
use Vanderlee\SyllableTest\AbstractTestCase;

class DocumentationManagerTest extends AbstractTestCase
{
    /**
     * @var DocumentationManager
     */
    protected $documentationManager;

    /**
     * Note: Use the @before annotation instead of the reserved setUp()
     * to be compatible with a wide range of PHPUnit versions.
     *
     * @before
     */
    protected function setUpFixture()
    {
        $this->documentationManager = new DocumentationManager();

        $this->createTestDirectory();
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
     * @test
     */
    public function delegateSucceeds()
    {
        $readme = trim('
## `Syllable` API reference

The following describes the API of the main Syllable class. In most cases, 
you will not use any other functions. Browse the code under src/ for all 
available functions.

#### public setMethods(array $methods = [])

..


## Development
        ');

        $expectedOutputRegex = '#The API documentation in the README.md has CHANGED.#';
        $expectedReadme = trim('
## `Syllable` API reference

The following describes the API of the main Syllable class. In most cases, 
you will not use any other functions. Browse the code under src/ for all 
available functions.

#### public setMethodsDeprecated(array $methods = [])

The deprecated public setter method.
**Deprecated:** Use setMethods() instead.

#### public setMethods(array $methods = [])

The public setter method.
**See:** https://github.com/vanderlee/phpSyllable/blob/master/tests/build/ReflectionFixture.php.

#### public getMethods(): array

The public getter method.

#### public static getParameters(): array

The public static method.


## Development
        ');

        $this->createFileInTestDirectory('README.md', $readme);

        $readmeFile = $this->getPathInTestDirectory('README.md');
        $apiClass = ReflectionFixture::class;

        $this->documentationManager->setReadmeFile($readmeFile);
        $this->documentationManager->setApiClass($apiClass);
        $result = $this->documentationManager->delegate();

        $this->assertTrue($result);
        $this->expectOutputRegex($expectedOutputRegex);
        $this->assertEquals($expectedReadme, file_get_contents($readmeFile));
    }

    /**
     * @test
     */
    public function delegateFailsIfReadmeFormatChanges()
    {
        $readme = trim('
## Syllable API reference

The following describes the API of the main Syllable class. In most cases, 
you will not use any other functions. Browse the code under src/ for all 
available functions.

#### public setMethods(array $methods = [])

..


## Development
        ');

        $expectedOutput = trim('
Could not update README.md. The format has probably changed:
[
    "Missing headlines \"`Syllable` API reference\" and \"Development\" to locate API documentation."
]
Aborting.
        ')."\n";
        $expectedReadme = $readme;

        $this->createFileInTestDirectory('README.md', $readme);

        $readmeFile = $this->getPathInTestDirectory('README.md');

        $this->documentationManager->setReadmeFile($readmeFile);
        $this->documentationManager->setApiClass(ReflectionFixture::class);
        $result = $this->documentationManager->delegate();

        $this->assertFalse($result);
        $this->expectOutputString($expectedOutput);
        $this->assertEquals($expectedReadme, file_get_contents($readmeFile));
    }
}
