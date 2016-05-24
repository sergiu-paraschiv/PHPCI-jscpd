<?php

namespace SergiuParaschiv\PHPCI\Plugin;

use PHPCI\Builder;
use PHPCI\Helper\Lang;
use PHPCI\Model\Build;
use PHPCI\Model\BuildError;

class JSCPD implements \PHPCI\Plugin
{
    public function __construct(Builder $phpci, Build $build, array $options = array())
    {
        $this->phpci = $phpci;
        $this->build = $build;
        $this->directory = '';
        $this->command = '';
        $this->allowed_duplication_percent = 50;

        if (isset($options['directory'])) {
            $this->directory = $options['directory'];
        }

        if (isset($options['allowed_duplication_percent'])) {
            $this->allowed_duplication_percent = $options['allowed_duplication_percent'];
        }

        if (isset($options['command'])) {
            $this->command = $options['command'];
        }
    }

    public function execute()
    {
        if (empty($this->command)) {
            $this->phpci->logFailure('Configuration command not found.');
            return false;
        }

        if (empty($this->directory)) {
            $this->phpci->logFailure('Configuration directory not found.');
            return false;
        }

        $this->phpci->logExecOutput(false);

        $out = $this->phpci->buildPath . 'jscpd.xml';

        $cmd = 'cd ' . $this->directory . '; LIMIT=' . $this->allowed_duplication_percent . ' OUTPUT=' . $out . '  ' . $this->command . '; cat ' . $out;
        $success = $this->phpci->executeCommand($cmd);
        $output = $this->phpci->getLastOutput();

        $output = explode("\n", $output);
        $dataOffset = 0;
        for($i = 0; $i < count($output); $i++) {
            if($this->startsWith($output[$i], '<?xml')) {
                $dataOffset = $i;
                break;
            }
        }

        $output = implode("\n", array_slice($output, $dataOffset));

        $this->processReport(trim($output));

        $this->phpci->logExecOutput(true);

        return $success;
    }

    protected function processReport($xmlString)
    {
        $xml = simplexml_load_string($xmlString);

        if ($xml === false) {
            $this->phpci->log($xmlString);
            throw new \Exception(Lang::get('could_not_process_report'));
        }

        $warnings = 0;
        foreach ($xml->duplication as $duplication) {
            foreach ($duplication->file as $file) {
                $fileName = (string) $file['path'];
                $fileName = str_replace($this->phpci->buildPath, '', $fileName);

                $message = <<<CPD
Copy and paste detected:
{$duplication->codefragment}
CPD;

                $this->build->reportError(
                    $this->phpci,
                    'php_cpd',
                    $message,
                    BuildError::SEVERITY_NORMAL,
                    $fileName,
                    (int) $file['line'],
                    (int) $file['line'] + (int) $duplication['lines']
                );
            }

            $warnings++;
        }

        return $warnings;
    }

    private function startsWith($haystack, $needle)
    {
         $length = strlen($needle);
         return (substr($haystack, 0, $length) === $needle);
    }
}
