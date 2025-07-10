<?php

declare(strict_types=1);

namespace MiniFramework\Installer\Console;

class Application
{
    private const VERSION = '1.0.0';
    private const FRAMEWORK_REPO = 'https://github.com/nastmz/mini-php-framework/archive/refs/heads/main.zip';
    private const FRAMEWORK_REPO_GIT = 'https://github.com/nastmz/mini-php-framework.git';

    public function run(array $argv): int
    {
        $this->showBanner();

        if (count($argv) < 2) {
            $this->showUsage();
            return 1;
        }

        $command = $argv[1] ?? '';

        return match ($command) {
            'new' => $this->handleNewCommand($argv),
            'help', '--help', '-h' => $this->showUsage(),
            'version', '--version', '-v' => $this->showVersion(),
            default => $this->handleUnknownCommand($command)
        };
    }

    private function handleNewCommand(array $argv): int
    {
        if (count($argv) < 3) {
            $this->error("Project name is required.");
            $this->info("Usage: miniframework new <project-name> [options]");
            return 1;
        }

        $projectName = $argv[2];
        $options = $this->parseOptions(array_slice($argv, 3));

        try {
            $installer = new ProjectInstaller($projectName, $options);
            return $installer->install();
        } catch (\Exception $e) {
            $this->error("Failed to create project: " . $e->getMessage());
            return 1;
        }
    }

    private function parseOptions(array $args): array
    {
        $options = [
            'path' => null,
            'namespace' => null,
            'description' => null,
            'force' => false,
            'no-git' => false,
            'no-install' => false,
            'dev' => false
        ];

        foreach ($args as $arg) {
            if (str_starts_with($arg, '--path=')) {
                $options['path'] = substr($arg, 7);
            } elseif (str_starts_with($arg, '--namespace=')) {
                $options['namespace'] = substr($arg, 12);
            } elseif (str_starts_with($arg, '--description=')) {
                $options['description'] = substr($arg, 14);
            } elseif ($arg === '--force') {
                $options['force'] = true;
            } elseif ($arg === '--no-git') {
                $options['no-git'] = true;
            } elseif ($arg === '--no-install') {
                $options['no-install'] = true;
            } elseif ($arg === '--dev') {
                $options['dev'] = true;
            }
        }

        return $options;
    }

    private function handleUnknownCommand(string $command): int
    {
        $this->error("Unknown command: {$command}");
        $this->showUsage();
        return 1;
    }

    private function showBanner(): void
    {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║              MiniFramework PHP Global Installer             ║\n";
        echo "║                   Create projects anywhere                   ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n";
        echo "\n";
    }

    private function showUsage(): int
    {
        echo "Usage:\n";
        echo "  miniframework new <project-name> [options]\n\n";
        echo "Arguments:\n";
        echo "  project-name              The name of the project to create\n\n";
        echo "Options:\n";
        echo "  --path=PATH              Custom path for the project\n";
        echo "  --namespace=NAMESPACE    Custom namespace (default: generated from name)\n";
        echo "  --description=DESC       Project description\n";
        echo "  --force                  Overwrite existing directory\n";
        echo "  --no-git                 Skip Git repository initialization\n";
        echo "  --no-install             Skip dependency installation\n";
        echo "  --dev                    Install development dependencies\n\n";
        echo "Examples:\n";
        echo "  miniframework new my-api\n";
        echo "  miniframework new blog --namespace=Blog --path=/var/www/blog\n";
        echo "  miniframework new ecommerce --description=\"E-commerce platform\"\n\n";
        echo "Other commands:\n";
        echo "  miniframework help       Show this help message\n";
        echo "  miniframework version    Show version information\n\n";
        return 0;
    }

    private function showVersion(): int
    {
        echo "MiniFramework PHP Installer v" . self::VERSION . "\n";
        echo "Global project generator for MiniFramework PHP\n\n";
        return 0;
    }

    private function info(string $message): void
    {
        echo "ℹ️  {$message}\n";
    }

    private function error(string $message): void
    {
        echo "❌ {$message}\n";
    }
}
