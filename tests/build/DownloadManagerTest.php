<?php

namespace Vanderlee\SyllableBuildTest;

use Vanderlee\SyllableBuild\DownloadManager;

class DownloadManagerTest extends AbstractTestCase
{
    /**
     * @var DownloadManager
     */
    protected $downloadManager;

    /**
     * Note: Use the @before annotation instead of the reserved setUp()
     * to be compatible with a wide range of PHPUnit versions.
     *
     * @before
     */
    protected function setUpFixture()
    {
        $this->downloadManager = new DownloadManager();

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
        $this->addFileToTestDirectory('hyph-af.tex', 'original');
        $this->addFileToTestDirectory('remote-hyph-af.tex', 'original');
        $this->addFileToTestDirectory('hyph-as.tex', 'original');
        $this->addFileToTestDirectory('remote-hyph-as.tex', 'changed');
        $this->addFileToTestDirectory('configuration.json', json_encode([
            'files' => [
                [
                    'fromUrl' => $this->getPathOfTestDirectoryFileAsUrl('remote-hyph-af.tex'),
                    'toPath'  => $this->getPathOfTestDirectoryFile('hyph-af.tex'),
                ],
                [
                    'fromUrl' => $this->getPathOfTestDirectoryFileAsUrl('remote-hyph-as.tex'),
                    'toPath'  => $this->getPathOfTestDirectoryFile('hyph-as.tex'),
                ],
                [
                    'fromUrl' => $this->getPathOfTestDirectoryFileAsUrl('remote-not-available.tex'),
                    'toPath'  => $this->getPathOfTestDirectoryFile('hyph-bg.tex'),
                ],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $expectedOutputRegex = '#Result: 3/3 files processed, 1 changed, 1 unchanged and 1 failed.#';

        $configurationFile = $this->getPathOfTestDirectoryFile('configuration.json');

        $this->downloadManager->setConfigurationFile($configurationFile);
        $result = $this->downloadManager->delegate();

        $this->assertTrue($result);
        $this->expectOutputRegex($expectedOutputRegex);
    }
}
