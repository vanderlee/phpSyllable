<?php

namespace Vanderlee\SyllableTest;

use PHPUnit\Framework\TestCase;

abstract class AbstractTestCase extends TestCase
{
    protected $testDirectory = '';

    protected function createTestDirectory()
    {
        if (!is_dir($this->getTestDirectory())) {
            mkdir($this->getTestDirectory());
        }
    }

    protected function getTestDirectory()
    {
        if ($this->testDirectory === '') {
            $inheritingClassFQCN = get_class($this);
            $inheritingClassName = substr($inheritingClassFQCN, strrpos($inheritingClassFQCN, '\\') + 1);

            try {
                $inheritingClassDirectory = dirname((new \ReflectionClass($inheritingClassFQCN))->getFileName());
            } catch (\ReflectionException $exception) {
                $inheritingClassDirectory = __DIR__;
            }

            $this->testDirectory = $inheritingClassDirectory.'/'.$inheritingClassName;
        }

        return $this->testDirectory;
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
