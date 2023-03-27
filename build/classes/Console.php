<?php

namespace Vanderlee\SyllableBuild;

class Console
{
    /**
     * @param string $command      CLI command.
     * @param bool   $returnOutput Return full multiline output instead of first line result?
     *
     * @throws Exception
     *
     * @return array|string First line result or full multiline output.
     */
    public function exec($command, $returnOutput = false)
    {
        $commandQuiet = str_replace(' 2>&1', '', $command).' 2>&1';

        $result = exec($commandQuiet, $output, $resultCode);

        if ($result === false) {
            throw new Exception(
                'PHP fails to execute external programs.'
            );
        } elseif ($resultCode !== 0) {
            throw new Exception(sprintf(
                "Command \"%s\" fails with:\n%s",
                $command,
                implode("\n", $output)
            ));
        }

        if ($returnOutput) {
            return $output;
        }

        return $result;
    }
}
