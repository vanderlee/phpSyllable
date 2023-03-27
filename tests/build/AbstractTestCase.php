<?php

namespace Vanderlee\SyllableBuildTest;

use PHPUnit\Framework\TestCase;

abstract class AbstractTestCase extends TestCase
{
    protected function createTestDirectory()
    {
        if (!is_dir($this->getTestDirectory())) {
            mkdir($this->getTestDirectory());
        }
    }

    protected function getTestDirectory()
    {
        $inheritingClassFQCN = get_class($this);
        $inheritingClassName = substr($inheritingClassFQCN, strrpos($inheritingClassFQCN, '\\') + 1);

        return __DIR__.'/'.$inheritingClassName;
    }

    protected function removeTestDirectory()
    {
        if (is_dir($this->getTestDirectory())) {
            $files = glob($this->getTestDirectory().'/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->getTestDirectory());
        }
    }

    protected function addFileToTestDirectory($filename, $content)
    {
        file_put_contents($this->getPathOfTestDirectoryFile($filename), $content);
    }

    protected function getPathOfTestDirectoryFile($filename)
    {
        return $this->getTestDirectory().'/'.$filename;
    }

    protected function getPathOfTestDirectoryFileAsUrl($filename)
    {
        return 'file://'.$this->getPathOfTestDirectoryFile($filename);
    }
}
