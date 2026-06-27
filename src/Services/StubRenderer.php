<?php

namespace Shehroz\CrudGenerator\Services;

use Illuminate\Support\Facades\File;
use RuntimeException;

class StubRenderer
{
    protected string $stubsPath;

    public function __construct(?string $stubsPath = null)
    {
        $published = config('crud-generator.stubs_path');
        $default = dirname(__DIR__) . '/Stubs';

        $this->stubsPath = $stubsPath
            ?? (is_dir($published) ? $published : $default);
    }

    public function render(string $stub, array $replacements = [], array $options = []): string
    {
        $stubFile = $this->resolveStubPath($stub);

        if (! File::exists($stubFile)) {
            throw new RuntimeException("Stub missing: {$stubFile}");
        }

        $content = File::get($stubFile);

        if (isset($options['fields_loop'], $options['fields'], $options['loop_template'])) {
            $loop = '';
            foreach ($options['fields'] as $field) {
                $fieldReplacements = array_merge($replacements, [
                    '{{field.name}}' => $field['name'],
                    '{{field.camel}}' => $field['camel'],
                    '{{field.title}}' => $field['title'],
                    '{{field.input_type}}' => $field['input_type'],
                    '{{field.rules}}' => str_replace('{{field.name}}', $field['name'], $field['rules']),
                    '{{field.type}}' => $field['type'],
                ]);

                $loop .= str_replace(
                    array_keys($fieldReplacements),
                    array_values($fieldReplacements),
                    $options['loop_template']
                ) . "\n";
            }

            $content = str_replace($options['fields_loop'], $loop, $content);
        }

        if (isset($options['conditionals'])) {
            foreach ($options['conditionals'] as $key => $enabled) {
                $content = $this->applyConditional($content, $key, $enabled);
            }
        }

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    public function write(string $path, string $content): void
    {
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $content);
    }

    protected function resolveStubPath(string $stub): string
    {
        if (str_ends_with($stub, '.stub')) {
            return str_starts_with($stub, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:/', $stub)
                ? $stub
                : $this->stubsPath . '/' . $stub;
        }

        $direct = "{$this->stubsPath}/{$stub}.stub";

        if (File::exists($direct)) {
            return $direct;
        }

        return "{$this->stubsPath}/{$stub}/{$stub}.stub";
    }

    protected function applyConditional(string $content, string $key, bool $enabled): string
    {
        $pattern = '/\/\/\s*@if\(' . preg_quote($key, '/') . '\)\s*(.*?)\/\/\s*@endif/s';

        if ($enabled) {
            return preg_replace_callback($pattern, function ($matches) {
                return ltrim($matches[1]);
            }, $content) ?? $content;
        }

        return preg_replace($pattern, '', $content) ?? $content;
    }
}
