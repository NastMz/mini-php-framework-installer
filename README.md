# MiniFramework PHP Global Installer

[![Packagist](https://img.shields.io/packagist/v/miniframework/installer.svg)](https://packagist.org/packages/miniframework/installer)
[![Downloads](https://img.shields.io/packagist/dt/miniframework/installer.svg)](https://packagist.org/packages/miniframework/installer)
[![License](https://img.shields.io/packagist/l/miniframework/installer.svg)](https://github.com/NastMz/mini-php-framework-installer/blob/main/LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/miniframework/installer.svg)](https://packagist.org/packages/miniframework/installer)

Global command-line installer for creating new MiniFramework PHP projects from anywhere.

> **⚡ Laravel-style project creation**: Create new MiniFramework projects with a single command, just like `laravel new`!

## Installation

### Global Installation via Composer

```bash
composer global require miniframework/installer
```

Make sure your global Composer bin directory is in your `PATH`:

```bash
# Add to your ~/.bashrc, ~/.zshrc, or equivalent
export PATH="$PATH:$HOME/.composer/vendor/bin"

# For Windows, add to your PATH environment variable:
# %APPDATA%\Composer\vendor\bin
```

### Manual Installation

```bash
# Clone this repository
git clone https://github.com/miniframework/installer.git
cd installer

# Install dependencies
composer install

# Make the script executable (Unix/Linux/macOS)
chmod +x bin/miniframework

# Add to PATH or create symlink
ln -s $(pwd)/bin/miniframework /usr/local/bin/miniframework
```

## Usage

### Create a New Project

```bash
# Basic project creation
miniframework new my-project

# Create with custom path
miniframework new my-api --path=/var/www/my-api

# Create with custom namespace and description
miniframework new my-blog \
  --namespace=Blog \
  --description="My personal blog built with MiniFramework PHP"

# Force overwrite existing directory
miniframework new my-project --force

# Skip Git initialization
miniframework new my-project --no-git

# Skip dependency installation
miniframework new my-project --no-install

# Include development dependencies
miniframework new my-project --dev
```

### Available Commands

```bash
# Show help
miniframework help

# Show version
miniframework version

# Create new project (main command)
miniframework new <project-name> [options]
```

### Options

| Option                  | Description                                                             |
| ----------------------- | ----------------------------------------------------------------------- |
| `--path=PATH`           | Custom path for the project (default: current directory + project name) |
| `--namespace=NAMESPACE` | Custom namespace (default: generated from project name)                 |
| `--description=DESC`    | Project description                                                     |
| `--force`               | Overwrite existing directory                                            |
| `--no-git`              | Skip Git repository initialization                                      |
| `--no-install`          | Skip dependency installation                                            |
| `--dev`                 | Install development dependencies                                        |

## Examples

### Create a REST API

```bash
miniframework new my-api \
  --namespace=MyApi \
  --description="REST API for my application"
```

### Create a Web Application

```bash
miniframework new my-webapp \
  --path=/var/www/webapp \
  --namespace=WebApp \
  --description="Full-stack web application"
```

### Create a Microservice

```bash
miniframework new auth-service \
  --namespace=AuthService \
  --description="Authentication microservice"
```

## What Gets Created

The installer creates a complete MiniFramework PHP project with:

### ✅ Project Structure

- Complete DDD/Clean Architecture structure
- All necessary directories with `.gitkeep` files
- Proper PSR-4 autoloading configuration

### ✅ Configuration Files

- `composer.json` with updated namespace and project info
- `.env.example` with all configuration options
- `.gitignore` with appropriate exclusions

### ✅ Customization

- Updated namespaces throughout the codebase
- Personalized README.md with project-specific instructions
- Custom package name and description

### ✅ Development Tools

- Git repository initialized (unless `--no-git`)
- Dependencies installed (unless `--no-install`)
- Ready-to-use CLI commands
- Development server ready to start

## Framework Features

Each created project includes:

- **Domain-Driven Design** architecture
- **Clean Architecture** principles
- **Dependency Injection** container with autowiring
- **Advanced Routing** with attributes and parameters
- **Middleware Pipeline** (PSR-15 compatible)
- **Rate Limiting** with multiple backends
- **CSRF Protection** for forms and AJAX
- **JWT Authentication** with refresh tokens
- **File Upload System** with validation
- **Template Engine** with layouts and components
- **Database Migrations** and seeders
- **CLI Commands** for development

## Post-Creation Steps

After creating a project:

```bash
# Navigate to project directory
cd my-project

# Copy environment file (if not done automatically)
cp .env.example .env

# Generate application key
php bin/console key:generate

# Initialize database
php bin/console db:setup
php bin/console migrate

# Start development server
php bin/console serve
```

## Requirements

- PHP 8.4+
- Composer
- cURL extension (for downloading framework)
- ZIP extension (for extracting framework)
- Git (optional, for repository initialization)

## Troubleshooting

### Command not found

Make sure the global Composer bin directory is in your PATH:

```bash
# Check if it's in PATH
echo $PATH | grep composer

# Add to PATH (add to your shell profile)
export PATH="$PATH:$HOME/.composer/vendor/bin"
```

### Permission denied

Make the script executable:

```bash
chmod +x ~/.composer/vendor/bin/miniframework
```

### Download failed

The installer will try Git first, then fall back to ZIP download. Make sure you have either:

- Git installed and available in PATH
- cURL and ZIP extensions enabled

### Force overwrite

Use `--force` to overwrite existing directories:

```bash
miniframework new my-project --force
```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This installer is open-sourced software licensed under the [MIT license](LICENSE).

## Links

- [MiniFramework PHP Repository](https://github.com/nastmz/mini-php-framework)
- [Documentation](https://github.com/nastmz/mini-php-framework#readme)
- [Report Issues](https://github.com/miniframework/installer/issues)
