<?php

namespace Vanderlee\SyllableTest\Build;

use Vanderlee\SyllableBuild\Git;
use Vanderlee\SyllableBuild\ReleaseManager;
use Vanderlee\SyllableBuild\SemanticVersioning;
use Vanderlee\SyllableTest\AbstractTestCase;

class ReleaseManagerTest extends AbstractTestCase
{
    /**
     * @var ReleaseManager
     */
    protected $releaseManager;

    /**
     * Note: Use the @before annotation instead of the reserved setUp()
     * to be compatible with a wide range of PHPUnit versions.
     *
     * @before
     */
    protected function setUpFixture()
    {
        $this->releaseManager = new ReleaseManager();

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
Syllable
========
Version 1.5.3

..

Changes
-------
1.5.3
-   Fixed PHP 7.4 compatibility (#37) by @Dargmuesli.
        ');

        // Stub only the minimum required. We rely here for the most part on an existing Git
        // API that is available in most test environments. We only specify that the latest
        // version is the real existing 1.5.3 to be able to control the test flow.
        $gitStub = $this->getMockBuilder(Git::class)
            ->setMethods(['getTag'])
            ->getMock();
        $gitStub->expects($this->any())->method('getTag')->willReturn('1.5.3');

        $expectedOutputRegex = '#Create release 1.5.4.#';
        $expectedReadme = trim('
Syllable
========
Version 1.5.4

..

Changes
-------
1.5.4
%a
-   Fix small typo in README and add \'use\' in example.
-   Use same code format as in src/Source/File.php
-   Fix opening brace
-   Remove whitespace
-   Fix closing brace
-   Use PHP syntax highlighting

1.5.3
-   Fixed PHP 7.4 compatibility (#37) by @Dargmuesli.
        ');

        $this->addFileToTestDirectory('README.md', $readme);

        $releaseType = SemanticVersioning::PATCH_RELEASE;
        $readmeFile = $this->getPathOfTestDirectoryFile('README.md');

        $releaseManager = new ReleaseManager();
        $releaseManager->setReleaseType($releaseType);
        $releaseManager->setReadmeFile($readmeFile);
        $releaseManager->setGit($gitStub);
        $result = $releaseManager->delegate();

        $this->assertTrue($result);
        $this->expectOutputRegex($expectedOutputRegex);
        $this->assertStringMatchesFormat($expectedReadme, file_get_contents($readmeFile));
    }
}
