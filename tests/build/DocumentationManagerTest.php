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
`Syllable` class reference
--------------------------
The following is an incomplete list, containing only the most common methods.
For a complete documentation of all classes, read the generated [PHPDoc](doc).

### public setMethods(array $methods = [])

..

Example
-------
        ');

        $expectedOutputRegex = '#The API documentation in the README.md has CHANGED.#';
        $expectedReadme = trim('
`Syllable` class reference
--------------------------
The following is an incomplete list, containing only the most common methods.
For a complete documentation of all classes, read the generated [PHPDoc](doc).

### public setMethods(array $methods = [])

The public setter method.
See https://github.com/vanderlee/phpSyllable/blob/master/tests/build/ReflectionFixture.php.

### public getMethods(): array

The public getter method.

### public static getParameters(): array

The public static method.

Example
-------
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
Syllable class reference
--------------------------
The following is an incomplete list, containing only the most common methods.
For a complete documentation of all classes, read the generated [PHPDoc](doc).

### public setMethods(array $methods = [])

..

Examples
--------
        ');

        $expectedOutput = trim('
Could not update README.md. The format has probably changed:
[
    "Missing headlines \"`Syllable` class reference\" and \"Example\" to locate API documentation."
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
