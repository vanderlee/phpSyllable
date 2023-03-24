<?php

namespace Vanderlee\SyllableBuild;

class LanguagesDownloadManager extends DownloadManager
{
    protected function createCommitIfFilesChanged()
    {
        if ($this->withCommit === false || $this->numChanged === 0) {
            return;
        }

        $message = sprintf('Automatic update of %s languages', $this->numChanged);
        if ($this->numChanged <= 2) {
            $message = sprintf(
                'Automatic update of %s',
                implode(', ', array_map('basename', $this->filesChanged))
            );
        }

        $this->exec('git add .');
        $this->exec(sprintf('git commit -m "%s"', $message));
    }
}
