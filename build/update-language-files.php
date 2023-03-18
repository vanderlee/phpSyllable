<?php

use Vanderlee\SyllableBuild\LanguageFileService;

require_once __DIR__.'/../vendor/autoload.php';

$result = (new LanguageFileService())->updateLanguageFiles();

if ($result === false) {
    exit(1);
}
exit(0);
