<?php

namespace Vanderlee\SyllableBuild;

abstract class Manager
{
    /**
     * @var int
     */
    protected $logLevel;

    public function __construct()
    {
        $this->logLevel = LOG_INFO;
    }

    /**
     * @param int $logLevel
     */
    public function setLogLevel($logLevel)
    {
        $this->logLevel = $logLevel;
    }

    protected function info($text)
    {
        $this->log($text, LOG_INFO);
    }

    protected function warn($text)
    {
        $this->log($text, LOG_WARNING);
    }

    protected function error($text)
    {
        $this->log($text, LOG_ERR);
    }

    protected function log($text, $logLevel)
    {
        if ($logLevel <= $this->logLevel) {
            echo "$text\n";
        }
    }
}
