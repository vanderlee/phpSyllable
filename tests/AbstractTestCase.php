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
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->getTestDirectory(), \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $path) {
                if ($path->isDir() && !$path->isLink()) {
                    rmdir($path->getPathname());
                } else {
                    unlink($path->getPathname());
                }
            }
            rmdir($this->getTestDirectory());
        }
    }

    protected function createDirectoryInTestDirectory($path)
    {
        mkdir($this->getPathInTestDirectory($path), 0777, true);
    }

    protected function createFileInTestDirectory($path, $content)
    {
        file_put_contents($this->getPathInTestDirectory($path), $content);
    }

    protected function getPathInTestDirectory($path)
    {
        return $this->getTestDirectory().'/'.$path;
    }

    protected function getPathInTestDirectoryAsUrl($path)
    {
        return 'file://'.$this->getPathInTestDirectory($path);
    }
}
