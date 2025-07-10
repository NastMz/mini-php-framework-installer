<?php

declare(strict_types=1);

namespace MiniFramework\Installer\Console;

class ProjectInstaller
{
    private const FRAMEWORK_REPO = 'https://github.com/nastmz/mini-php-framework/archive/refs/heads/main.zip';
    private const FRAMEWORK_REPO_GIT = 'https://github.com/nastmz/mini-php-framework.git';
    private const TEMP_DIR_PREFIX = 'miniframework_temp_';

    private array $excludedPaths = [
        '.git/',
        'node_modules/',
        'vendor/',
        'storage/cache/templates/',
        'storage/logs/',
        'logs/',
        'public/uploads/',
        'storage/uploads/',
        'storage/database/app.sqlite',
        'composer.lock',
        '.env',
        'installer/',
        'create-miniframework-project.php',
        'create-miniframework-project.ps1',
        'create-miniframework-project.bat',
        'GENERATOR_README.md',
        'USAGE_EXAMPLES.md'
    ];

    public function __construct(
        private string $projectName,
        private array $options = []
    ) {}

    public function install(): int
    {
        $this->info("Creating new MiniFramework PHP project: {$this->projectName}");

        $targetPath = $this->getTargetPath();
        $namespace = $this->getNamespace();
        $description = $this->getDescription();

        $this->info("Target path: {$targetPath}");
        $this->info("Namespace: {$namespace}");

        // Validate target directory
        if (!$this->validateTargetDirectory($targetPath)) {
            return 1;
        }

        $tempDir = $this->createTempDirectory();

        try {
            // Download framework
            $this->downloadFramework($tempDir);

            // Copy and customize project
            $this->copyFrameworkStructure($tempDir, $targetPath);
            $this->customizeProject($targetPath, $namespace, $description);

            // Initialize git and install dependencies
            if (!$this->options['no-git']) {
                $this->initializeGitRepository($targetPath);
            }

            if (!$this->options['no-install']) {
                $this->installDependencies($targetPath);
            }

            $this->showSuccessMessage($targetPath);
            return 0;

        } finally {
            $this->cleanupTempDirectory($tempDir);
        }
    }

    private function getTargetPath(): string
    {
        if (!empty($this->options['path'])) {
            return rtrim($this->options['path'], '/\\');
        }

        return getcwd() . DIRECTORY_SEPARATOR . $this->projectName;
    }

    private function getNamespace(): string
    {
        if (!empty($this->options['namespace'])) {
            return $this->options['namespace'];
        }

        return $this->generateNamespace($this->projectName);
    }

    private function getDescription(): string
    {
        if (!empty($this->options['description'])) {
            return $this->options['description'];
        }

        return "A new project built with MiniFramework PHP";
    }

    private function validateTargetDirectory(string $targetPath): bool
    {
        if (is_dir($targetPath) && !empty(glob($targetPath . '/*'))) {
            if (!$this->options['force']) {
                $this->error("Directory '{$targetPath}' already exists and is not empty.");
                $this->info("Use --force to overwrite existing directory.");
                return false;
            }

            $this->warning("Overwriting existing directory: {$targetPath}");
            $this->removeDirectory($targetPath);
        }

        return true;
    }

    private function createTempDirectory(): string
    {
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . self::TEMP_DIR_PREFIX . uniqid();
        
        if (!mkdir($tempDir, 0755, true)) {
            throw new \RuntimeException("Failed to create temporary directory: {$tempDir}");
        }

        return $tempDir;
    }

    private function downloadFramework(string $tempDir): void
    {
        $this->info("Downloading latest MiniFramework PHP...");

        if ($this->isCommandAvailable('git')) {
            $this->downloadViaGit($tempDir);
        } else {
            $this->downloadViaZip($tempDir);
        }

        $this->success("Framework downloaded successfully");
    }

    private function downloadViaGit(string $tempDir): void
    {
        $command = sprintf(
            'git clone --depth 1 %s %s 2>&1',
            escapeshellarg(self::FRAMEWORK_REPO_GIT),
            escapeshellarg($tempDir)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->warning("Git clone failed, falling back to ZIP download...");
            $this->downloadViaZip($tempDir);
        }
    }

    private function downloadViaZip(string $tempDir): void
    {
        if (!extension_loaded('curl')) {
            throw new \RuntimeException("cURL extension is required to download the framework");
        }

        $zipFile = $tempDir . '.zip';
        
        // Download ZIP file
        $this->downloadFile(self::FRAMEWORK_REPO, $zipFile);
        
        // Extract ZIP file
        $this->extractZip($zipFile, $tempDir);
        
        // Remove ZIP file
        unlink($zipFile);
    }

    private function downloadFile(string $url, string $destination): void
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'MiniFramework-Installer/1.0');
        
        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $data === false) {
            throw new \RuntimeException("Failed to download framework from {$url}");
        }

        file_put_contents($destination, $data);
    }

    private function extractZip(string $zipFile, string $destination): void
    {
        if (!extension_loaded('zip')) {
            throw new \RuntimeException("ZIP extension is required to extract the framework");
        }

        $zip = new \ZipArchive();
        $result = $zip->open($zipFile);

        if ($result !== true) {
            throw new \RuntimeException("Failed to open ZIP file: {$zipFile}");
        }

        // Create extraction directory
        $extractDir = $destination . '_extract';
        mkdir($extractDir, 0755, true);

        if (!$zip->extractTo($extractDir)) {
            $zip->close();
            throw new \RuntimeException("Failed to extract ZIP file");
        }

        $zip->close();

        // Move contents from extracted subdirectory to temp directory
        $extractedContents = glob($extractDir . '/*');
        if (count($extractedContents) === 1 && is_dir($extractedContents[0])) {
            // GitHub ZIP creates a subdirectory, move its contents
            $this->moveDirectoryContents($extractedContents[0], $destination);
        } else {
            // Direct extraction, move all contents
            $this->moveDirectoryContents($extractDir, $destination);
        }

        // Cleanup extraction directory
        $this->removeDirectory($extractDir);
    }

    private function moveDirectoryContents(string $source, string $destination): void
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = $iterator->getSubPathName();
            $targetPath = $destination . DIRECTORY_SEPARATOR . $relativePath;

            if ($item->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
            } else {
                $directory = dirname($targetPath);
                if (!is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }
                copy($item->getPathname(), $targetPath);
            }
        }
    }

    private function copyFrameworkStructure(string $source, string $target): void
    {
        $this->info("Setting up project structure...");

        if (!is_dir($target)) {
            mkdir($target, 0755, true);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = $iterator->getSubPathName();
            $targetPath = $target . DIRECTORY_SEPARATOR . $relativePath;

            // Skip excluded paths
            if ($this->shouldExcludePath($relativePath)) {
                continue;
            }

            if ($item->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
            } else {
                $directory = dirname($targetPath);
                if (!is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }
                copy($item->getPathname(), $targetPath);
            }
        }

        // Create necessary directories
        $this->createProjectDirectories($target);
    }

    private function shouldExcludePath(string $path): bool
    {
        foreach ($this->excludedPaths as $excludedPath) {
            if (str_starts_with($path, $excludedPath) || 
                str_starts_with($path . '/', $excludedPath) ||
                fnmatch($excludedPath, $path)) {
                return true;
            }
        }
        return false;
    }

    private function createProjectDirectories(string $targetPath): void
    {
        $dirsToCreate = [
            'storage/cache/templates',
            'storage/logs',
            'storage/uploads',
            'storage/avatars',
            'public/uploads/avatars',
            'logs'
        ];

        foreach ($dirsToCreate as $dir) {
            $dirPath = $targetPath . DIRECTORY_SEPARATOR . $dir;
            if (!is_dir($dirPath)) {
                mkdir($dirPath, 0755, true);
            }

            // Create .gitkeep file
            $gitkeepPath = $dirPath . DIRECTORY_SEPARATOR . '.gitkeep';
            if (!file_exists($gitkeepPath)) {
                file_put_contents($gitkeepPath, '');
            }
        }
    }

    private function customizeProject(string $targetPath, string $namespace, string $description): void
    {
        $this->info("Customizing project...");

        $this->updateComposerJson($targetPath, $namespace, $description);
        $this->updateNamespaces($targetPath, $namespace);
        $this->createEnvTemplate($targetPath);
        $this->createReadme($targetPath, $description);
    }

    private function updateComposerJson(string $targetPath, string $namespace, string $description): void
    {
        $composerPath = $targetPath . DIRECTORY_SEPARATOR . 'composer.json';
        
        if (!file_exists($composerPath)) {
            return;
        }

        $composer = json_decode(file_get_contents($composerPath), true);
        
        $composer['name'] = $this->generatePackageName($this->projectName);
        $composer['description'] = $description;
        $composer['autoload']['psr-4'] = [
            $namespace . '\\' => 'src/'
        ];

        // Remove installer-specific scripts and dependencies
        unset($composer['scripts']['create:project']);

        file_put_contents(
            $composerPath, 
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    private function updateNamespaces(string $targetPath, string $namespace): void
    {
        $srcPath = $targetPath . DIRECTORY_SEPARATOR . 'src';
        
        if (!is_dir($srcPath)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($srcPath),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());
                $content = str_replace('namespace App\\', "namespace {$namespace}\\", $content);
                $content = str_replace('use App\\', "use {$namespace}\\", $content);
                file_put_contents($file->getPathname(), $content);
            }
        }
    }

    private function createEnvTemplate(string $targetPath): void
    {
        $envPath = $targetPath . DIRECTORY_SEPARATOR . '.env.example';
        $envContent = <<<ENV
# Application Configuration
APP_NAME="{$this->projectName}"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_KEY=

# Database Configuration
DB_CONNECTION=sqlite
DB_DATABASE=storage/database/app.sqlite
DB_HOST=localhost
DB_PORT=3306
DB_USERNAME=root
DB_PASSWORD=

# JWT Configuration
JWT_SECRET=
JWT_ALGORITHM=HS256
JWT_EXPIRATION=3600
JWT_REFRESH_EXPIRATION=604800

# Rate Limiting
RATE_LIMIT_ENABLED=true
RATE_LIMIT_MAX_ATTEMPTS=60
RATE_LIMIT_WINDOW=60
RATE_LIMIT_STORAGE=file

# File Upload Configuration
MAX_FILE_SIZE=10M
ALLOWED_EXTENSIONS=jpg,jpeg,png,gif,pdf,txt,doc,docx
UPLOAD_PATH=storage/uploads

# CORS Configuration
CORS_ENABLED=true
CORS_ALLOWED_ORIGINS=*
CORS_ALLOWED_METHODS=GET,POST,PUT,DELETE,OPTIONS
CORS_ALLOWED_HEADERS=Content-Type,Authorization,X-Requested-With
CORS_ALLOW_CREDENTIALS=false

# Security Configuration
CSRF_ENABLED=true
XSS_PROTECTION=true
CONTENT_TYPE_NOSNIFF=true
FRAME_OPTIONS=DENY

# Logging Configuration
LOG_LEVEL=info
LOG_CHANNEL=file
ENV;

        file_put_contents($envPath, $envContent);
    }

    private function createReadme(string $targetPath, string $description): void
    {
        $readmePath = $targetPath . DIRECTORY_SEPARATOR . 'README.md';
        $readme = <<<MD
# {$this->projectName}

{$description}

Built with [MiniFramework PHP](https://github.com/nastmz/mini-php-framework) - A modern PHP micro-framework with DDD and Clean Architecture.

## Quick Start

1. **Install dependencies:**
   ```bash
   composer install
   ```

2. **Set up environment:**
   ```bash
   cp .env.example .env
   php bin/console key:generate
   ```

3. **Initialize database:**
   ```bash
   php bin/console db:setup
   php bin/console migrate
   ```

4. **Start development server:**
   ```bash
   php bin/console serve
   ```

Visit http://localhost:8000 to see your application running!

## Framework Features

- ✅ **Domain-Driven Design (DDD)** architecture
- ✅ **Clean Architecture** principles
- ✅ **Dependency Injection** container with autowiring
- ✅ **Advanced Routing** with attributes and parameters
- ✅ **Middleware Pipeline** (PSR-15 compatible)
- ✅ **Rate Limiting** with multiple backends
- ✅ **CSRF Protection** for forms and AJAX
- ✅ **JWT Authentication** with refresh tokens
- ✅ **File Upload System** with validation
- ✅ **Template Engine** with layouts and components
- ✅ **Database Migrations** and seeders
- ✅ **CLI Commands** for development
- ✅ **Error Handling** with custom pages
- ✅ **Security Headers** and CORS support

## Development Commands

```bash
# Generate components
php bin/console make:controller UserController
php bin/console make:migration CreateUsersTable
php bin/console make:middleware AuthMiddleware

# Database operations
php bin/console migrate
php bin/console db:setup

# Development tools
php bin/console serve
php bin/console cache:clear
php bin/console test
php bin/console routes:list

# Security
php bin/console key:generate
php bin/console jwt:secret
```

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).
MD;

        file_put_contents($readmePath, $readme);
    }

    private function initializeGitRepository(string $targetPath): void
    {
        if (!$this->isCommandAvailable('git')) {
            $this->warning("Git not available, skipping repository initialization");
            return;
        }

        $this->info("Initializing Git repository...");

        // Create .gitignore
        $this->createGitignore($targetPath);

        // Initialize git
        $currentDir = getcwd();
        chdir($targetPath);

        try {
            exec('git init', $output, $returnCode);
            if ($returnCode === 0) {
                exec('git add .');
                exec("git commit -m \"🎉 Initial commit: {$this->projectName} project created with MiniFramework PHP\"");
                $this->success("Git repository initialized");
            }
        } finally {
            chdir($currentDir);
        }
    }

    private function createGitignore(string $targetPath): void
    {
        $gitignorePath = $targetPath . DIRECTORY_SEPARATOR . '.gitignore';
        $gitignoreContent = <<<GITIGNORE
# Dependencies
/vendor/
/node_modules/

# Environment files
.env
.env.local
.env.*.local

# Cache and logs
/storage/cache/*
!/storage/cache/.gitkeep
/storage/logs/*
!/storage/logs/.gitkeep
/logs/*
!/logs/.gitkeep

# Database
/storage/database/*.sqlite
/storage/database/*.db

# Uploads
/storage/uploads/*
!/storage/uploads/.gitkeep
/public/uploads/*
!/public/uploads/.gitkeep

# IDE files
.vscode/
.idea/
*.swp
*.swo
*~

# OS files
.DS_Store
.DS_Store?
._*
.Spotlight-V100
.Trashes
ehthumbs.db
Thumbs.db

# Composer
composer.phar
composer.lock

# PHPUnit
.phpunit.result.cache
/coverage/
/build/

# Temporary files
*.tmp
*.temp
GITIGNORE;

        file_put_contents($gitignorePath, $gitignoreContent);
    }

    private function installDependencies(string $targetPath): void
    {
        if (!$this->isCommandAvailable('composer')) {
            $this->warning("Composer not available, skipping dependency installation");
            return;
        }

        $this->info("Installing dependencies...");

        $currentDir = getcwd();
        chdir($targetPath);

        try {
            $installCommand = 'composer install';
            if (!$this->options['dev']) {
                $installCommand .= ' --no-dev';
            }
            $installCommand .= ' --optimize-autoloader';

            exec($installCommand, $output, $returnCode);

            if ($returnCode === 0) {
                $this->success("Dependencies installed successfully");
            } else {
                $this->warning("Failed to install dependencies. Please run 'composer install' manually.");
            }
        } finally {
            chdir($currentDir);
        }
    }

    private function cleanupTempDirectory(string $tempDir): void
    {
        if (is_dir($tempDir)) {
            $this->removeDirectory($tempDir);
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($dir);
    }

    private function generateNamespace(string $projectName): string
    {
        $namespace = str_replace(['-', '_', ' '], '', ucwords($projectName, '-_ '));
        $namespace = preg_replace('/[^a-zA-Z0-9]/', '', $namespace);
        
        if (empty($namespace) || !ctype_alpha($namespace[0])) {
            $namespace = 'App';
        }
        
        return $namespace;
    }

    private function generatePackageName(string $projectName): string
    {
        $packageName = strtolower($projectName);
        $packageName = str_replace([' ', '_'], '-', $packageName);
        $packageName = preg_replace('/[^a-z0-9\-]/', '', $packageName);
        
        return "mycompany/{$packageName}";
    }

    private function isCommandAvailable(string $command): bool
    {
        // Store current directory and ensure we're in a valid one
        $currentDir = getcwd();
        if (!$currentDir || !is_dir($currentDir)) {
            chdir(sys_get_temp_dir());
        }
        
        try {
            // Try to execute the command with version flag to check if it's available
            $commands = [
                'git' => 'git --version',
                'composer' => 'composer --version'
            ];
            
            $testCommand = $commands[$command] ?? "{$command} --version";
            $nullDevice = PHP_OS_FAMILY === 'Windows' ? '2>NUL' : '2>/dev/null';
            
            exec("{$testCommand} {$nullDevice}", $output, $returnCode);
            return $returnCode === 0 && !empty($output);
        } finally {
            // Restore original directory if it was valid
            if ($currentDir && is_dir($currentDir)) {
                chdir($currentDir);
            }
        }
    }

    private function showSuccessMessage(string $targetPath): void
    {
        echo "\n";
        $this->success("Project '{$this->projectName}' created successfully!");
        echo "\n";
        $this->info("📁 Location: {$targetPath}");
        echo "\n";
        echo "Next steps:\n";
        echo "  1️⃣  cd " . basename($targetPath) . "\n";
        if ($this->options['no-install']) {
            echo "  2️⃣  composer install\n";
        }
        echo "  3️⃣  cp .env.example .env\n";
        echo "  4️⃣  php bin/console key:generate\n";
        echo "  5️⃣  php bin/console db:setup\n";
        echo "  6️⃣  php bin/console serve\n";
        echo "\n";
        $this->success("🚀 Visit http://localhost:8000 to see your application!");
        echo "\n";
        $this->info("📚 Documentation: https://github.com/nastmz/mini-php-framework");
        echo "\n";
    }

    private function info(string $message): void
    {
        echo "ℹ️  {$message}\n";
    }

    private function success(string $message): void
    {
        echo "✅ {$message}\n";
    }

    private function warning(string $message): void
    {
        echo "⚠️  {$message}\n";
    }

    private function error(string $message): void
    {
        echo "❌ {$message}\n";
    }
}
