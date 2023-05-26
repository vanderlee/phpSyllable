<?php

namespace Vanderlee\SyllableTest\Build;

use Vanderlee\SyllableBuild\DownloadManager;
use Vanderlee\SyllableTest\AbstractTestCase;

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
        $this->createFileInTestDirectory('hyph-af.tex', 'original');
        $this->createFileInTestDirectory('remote-hyph-af.tex', 'original');
        $this->createFileInTestDirectory('hyph-as.tex', 'original');
        $this->createFileInTestDirectory('remote-hyph-as.tex', 'changed');
        $this->createFileInTestDirectory('configuration.json', json_encode([
            'files' => [
                [
                    'fromUrl' => $this->getPathInTestDirectoryAsUrl('remote-hyph-af.tex'),
                    'toPath'  => $this->getPathInTestDirectory('hyph-af.tex'),
                ],
                [
                    'fromUrl' => $this->getPathInTestDirectoryAsUrl('remote-hyph-as.tex'),
                    'toPath'  => $this->getPathInTestDirectory('hyph-as.tex'),
                ],
                [
                    'fromUrl' => $this->getPathInTestDirectoryAsUrl('remote-not-available.tex'),
                    'toPath'  => $this->getPathInTestDirectory('hyph-bg.tex'),
                ],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $expectedOutputRegex = '#Result: 3/3 files processed, 1 changed, 1 unchanged and 1 failed.#';

        $configurationFile = $this->getPathInTestDirectory('configuration.json');

        $this->downloadManager->setConfigurationFile($configurationFile);
        $result = $this->downloadManager->delegate();

        $this->assertTrue($result);
        $this->expectOutputRegex($expectedOutputRegex);
    }
}
