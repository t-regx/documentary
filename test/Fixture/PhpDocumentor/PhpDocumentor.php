<?php
namespace Test\Fixture\PhpDocumentor;

use Test\Fixture\File\File;
use Test\Fixture\PhpDocumentor\Internal\Process;

readonly class PhpDocumentor
{
    private string $phpDocumentor;
    private File $working;

    public function __construct(File $workingDirectory)
    {
        $this->phpDocumentor = __DIR__ . '/../../lib/phpDocumentor.phar';
        $this->working = $workingDirectory;
    }

    public function documentString(string $sourceCode): string
    {
        $file = $this->working->join('file.php');
        $file->write($sourceCode);
        return $this->document($file);
    }

    public function document(File $file): string
    {
        $output = $this->working->join('output');
        $this->phpDocumentorXml($file, $output, 'xml');
        return $output->join('structure.xml')->read();
    }

    public function renderHtml(File $input, string $outputDirectory): void
    {
        $this->phpDocumentorXml($input, new File($outputDirectory), 'default');
    }

    private function phpDocumentorXml(File $input, File $output, string $template): void
    {
        $this->run([
            $this->phpExecutablePath(),
            $this->phpDocumentor, 'run',
            ...$this->inputArgs($this->abs($input)),
            '-t', $output->path,
            '--template', $template,
        ]);
    }

    private function inputArgs(File $input): array
    {
        if (!\file_exists($input->path)) {
            throw new \Exception("Failed to document non-existent file: '$input->path'");
        }
        if (\is_file($input->path)) {
            return ['-d', $input->parentDirectory()->path, '-f', $input->baseName()];
        }
        return ['-d', $input->path];
    }

    private function abs(File $file): File
    {
        if ($file->isAbs()) {
            return $file;
        }
        return (new File(\getCwd()))->join($file->path);
    }

    private function run(array $shellArguments): void
    {
        $shellCommand = $this->shell($shellArguments);
        $process = new Process($this->working->path, $shellCommand);
        if ($process->returnCode !== 0) {
            throw new \RuntimeException("Failed to generate phpDocumentor structure.xml.\n\n$process->stdOutput\n\n$process->stdError");
        }
    }

    private function shell(array $args): string
    {
        return \implode(' ', \array_map('\escapeShellArg', $args));
    }

    private function phpExecutablePath(): string
    {
        if (\DIRECTORY_SEPARATOR === '/') {
            return '/usr/bin/php8.1';
        }
        return 'C:\Program Files\PHP\php-8.1.3\php.exe';
    }
}
