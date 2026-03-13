<?php

namespace App\Console\Commands\Make;

use RuntimeException;
use App\Abstracts\BaseCommand;

/** Генерирует файл-заготовку нового ресурса на основе стаба.
 * Если в app/Http/Resources/Api/ обнаружены версионные папки (V1, V2, ...),
 * требует флаг --version=<версия>. Иначе создаёт в app/Http/Resources/.
 */
class MakeResourceCommand extends BaseCommand
{
    public static string $name = 'make:resource';

    private string $basePath;
    private string $stubsPath;

    /** @param string|null $basePath   Базовый путь к Resources (для тестов).
     * @param string|null $stubsPath  Путь к директории стабов (для тестов).
     */
    public function __construct(?string $basePath = null, ?string $stubsPath = null)
    {
        $this->basePath  = $basePath  ?? __DIR__ . '/../../../../app/Http/Resources';
        $this->stubsPath = $stubsPath ?? __DIR__ . '/../../../../stubs';
    }

    /** Вернуть краткое описание команды. */
    public function description(): string
    {
        return 'Создать файл ресурса: <ModelName> [--version=<V1>]';
    }

    /** Выполнить команду.
     * @param array<int, string> $args Аргументы: <ModelName> [--version=<V1>]
     */
    public function handle(array $args): void
    {
        $name    = null;
        $version = null;

        foreach ($args as $arg) {
            if (str_starts_with($arg, '--version=')) {
                $version = substr($arg, 10);
            } else {
                $name = $arg;
            }
        }

        if ($name === null) {
            echo 'Usage: php run make:resource <ModelName> [--version=<V1>]' . PHP_EOL;
            throw new RuntimeException('Model name is required.');
        }

        $stubPath = $this->stubsPath . '/resources/resource.stub';

        if (!file_exists($stubPath)) {
            throw new RuntimeException("Stub file not found: {$stubPath}");
        }

        [$outputPath, $namespace] = $this->resolveTarget($version);

        $filename = "{$name}Resource.php";
        $filepath = $outputPath . '/' . $filename;

        if (file_exists($filepath)) {
            throw new RuntimeException("Resource already exists: {$filename}");
        }

        $variable = lcfirst($name);
        $content  = str_replace(
            ['DummyNamespace', 'DummyModel', 'DummyResource', 'DummyVariable'],
            [$namespace, $name, $name, $variable],
            file_get_contents($stubPath)
        );

        if (!is_dir($outputPath)) {
            mkdir($outputPath, 0755, true);
        }

        file_put_contents($filepath, $content);

        $relative = str_replace($this->basePath . '/', 'app/Http/Resources/', $outputPath . '/');
        echo "Created: {$relative}{$filename}" . PHP_EOL;
    }

    /** Определить директорию и namespace для нового ресурса.
     * @param string|null $version Значение флага --version.
     * @return array{string, string} [путь, namespace]
     */
    private function resolveTarget(?string $version): array
    {
        $apiPath     = $this->basePath . '/Api';
        $versionDirs = $this->detectVersionDirs($apiPath);

        if (!empty($versionDirs)) {
            if ($version === null) {
                if (count($versionDirs) === 1) {
                    $version = $versionDirs[0];
                } else {
                    $version = $this->askVersion($versionDirs);
                }
            }

            return [
                $apiPath . '/' . $version,
                'App\\Http\\Resources\\Api\\' . $version,
            ];
        }

        return [
            $this->basePath,
            'App\\Http\\Resources',
        ];
    }

    /** Спросить пользователя, в какую версию создать ресурс.
     * @param array<int, string> $versions Доступные версии.
     * @return string Выбранная версия.
     */
    private function askVersion(array $versions): string
    {
        $list = implode(', ', $versions);
        echo "Multiple API versions detected ({$list}). Which version? " . PHP_EOL;

        foreach ($versions as $i => $v) {
            echo "  [" . ($i + 1) . "] {$v}" . PHP_EOL;
        }

        echo '> ';
        $input = trim((string)fgets(STDIN));

        if (is_numeric($input)) {
            $index = (int)$input - 1;
            if (isset($versions[$index])) {
                return $versions[$index];
            }
        } elseif (in_array($input, $versions, true)) {
            return $input;
        }

        throw new RuntimeException("Invalid version: {$input}");
    }

    /** Найти версионные папки (совпадающие с паттерном V\d+) в указанной директории.
     * @param string $path Путь к директории Api.
     * @return array<int, string>
     */
    private function detectVersionDirs(string $path): array
    {
        if (!is_dir($path)) {
            return [];
        }

        $dirs = array_filter(
            scandir($path),
            fn(string $entry) => $entry !== '.' && $entry !== '..'
                && is_dir($path . '/' . $entry)
                && preg_match('/^V\d+$/i', $entry)
        );

        return array_values($dirs);
    }
}
