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

Copyright &copy; 2011-2019 Martijn van der Lee.

..

Changes
-------
1.5.3
-   Fixed PHP 7.4 compatibility (#37) by @Dargmuesli.
        ');

        // Replace only the minimum required with a test double. We rely here mainly on an
        // existing Git API - which is available in most test environments. We only pretend
        // that the latest release is 1.5.3, and we expect 1.5.4 to be released now.
        $gitStub = $this->getMockBuilder(Git::class)
            ->setMethods(['getTag'])
            ->getMock();
        $gitStub->expects($this->any())->method('getTag')->willReturn('1.5.3');

        $expectedOutputRegex = '#Create release 1.5.4.#';
        $expectedReadme = trim('
Syllable
========
Version 1.5.4

Copyright &copy; 2011-'.date('Y').' Martijn van der Lee.

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

        $this->releaseManager->setReleaseType($releaseType);
        $this->releaseManager->setReadmeFile($readmeFile);
        $this->releaseManager->setGit($gitStub);
        $result = $this->releaseManager->delegate();

        $this->assertTrue($result);
        $this->expectOutputRegex($expectedOutputRegex);
        $this->assertStringMatchesFormat($expectedReadme, file_get_contents($readmeFile));
    }

    /**
     * @test
     */
    public function delegateFailsIfReadmeFormatChanges()
    {
        $readme = trim('
Syllable
========
Version v1.5.3

&copy; 2011-2019 Martijn van der Lee.

..

Changes
-------
v1.5.3
-   Fixed PHP 7.4 compatibility (#37) by @Dargmuesli.
        ');

        // Replace only the minimum required with a test double. We rely here mainly on an
        // existing Git API - which is available in most test environments. We only pretend
        // that the latest release is 1.5.3, and we expect 1.5.4 to be released now.
        $gitStub = $this->getMockBuilder(Git::class)
            ->setMethods(['getTag'])
            ->getMock();
        $gitStub->expects($this->any())->method('getTag')->willReturn('1.5.3');

        $expectedOutput = trim('
Create release 1.5.4.
Could not update README.md. The format has probably changed:
[
    "Missing note \'Version 1.5.3\' of the last release below the title.",
    "Missing copyright claim \'Copyright &copy; 2011-{year} Martijn van der Lee.\'",
    "Missing changelog entry \'1.5.3: ..\' from the last release."
]
Aborting.
        ')."\n";
        $expectedReadme = $readme;

        $this->addFileToTestDirectory('README.md', $readme);

        $releaseType = SemanticVersioning::PATCH_RELEASE;
        $readmeFile = $this->getPathOfTestDirectoryFile('README.md');

        $this->releaseManager->setReleaseType($releaseType);
        $this->releaseManager->setReadmeFile($readmeFile);
        $this->releaseManager->setGit($gitStub);
        $result = $this->releaseManager->delegate();

        $this->assertFalse($result);
        $this->expectOutputString($expectedOutput);
        $this->assertEquals($expectedReadme, file_get_contents($readmeFile));
    }
}
