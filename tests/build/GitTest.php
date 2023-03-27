<?php

namespace Vanderlee\SyllableBuildTest;

use Vanderlee\SyllableBuild\Console;
use Vanderlee\SyllableBuild\Git;

class GitTest extends AbstractTestCase
{
    /**
     * @var Git
     */
    protected $git;

    /**
     * Note: Use the @before annotation instead of the reserved setUp()
     * to be compatible with a wide range of PHPUnit versions.
     *
     * @before
     */
    protected function setUpFixture()
    {
        $this->git = new Git();
    }

    public function hasCleanWorkingTreeDataProvider()
    {
        return [
            [[], true],
            [[' M languages/hyph-af.tex'], false],
            [['?? languages/hyph-zh.tex'], false],
        ];
    }

    /**
     * @test
     *
     * @dataProvider hasCleanWorkingTreeDataProvider
     */
    public function hasCleanWorkingTree($gitStatus, $expected)
    {
        $consoleStub = $this->getMockBuilder(Console::class)->getMock();
        $consoleStub->expects($this->once())->method('exec')->willReturn($gitStatus);
        $this->git->setConsole($consoleStub);

        $this->assertEquals($expected, $this->git->hasCleanWorkingTree());
    }

    public function getAllChangedFilesDataProvider()
    {
        return [
            [[], []],
            [[' M languages/hyph-af.tex'], ['languages/hyph-af.tex']],
            [['?? languages/hyph-zh.tex'], ['languages/hyph-zh.tex']],
        ];
    }

    /**
     * @test
     *
     * @dataProvider getAllChangedFilesDataProvider
     */
    public function getAllChangedFiles($gitStatus, $expected)
    {
        $consoleStub = $this->getMockBuilder(Console::class)->getMock();
        $consoleStub->expects($this->once())->method('exec')->willReturn($gitStatus);
        $this->git->setConsole($consoleStub);

        $this->assertEquals($expected, $this->git->getAllChangedFiles());
    }
}
