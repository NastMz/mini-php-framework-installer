#!/bin/bash

# MiniFramework PHP Global Installer Setup Script
# This script installs the MiniFramework PHP global installer

set -e

echo ""
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║         MiniFramework PHP Global Installer Setup            ║"
echo "║              Quick installation script                       ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

success() {
    echo -e "${GREEN}✅ $1${NC}"
}

warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

error() {
    echo -e "${RED}❌ $1${NC}"
}

# Check if Composer is installed
check_composer() {
    if ! command -v composer &> /dev/null; then
        error "Composer is not installed!"
        echo ""
        echo "Please install Composer first:"
        echo "  https://getcomposer.org/download/"
        echo ""
        exit 1
    fi
    success "Composer is installed"
}

# Check if PHP version is adequate
check_php() {
    if ! command -v php &> /dev/null; then
        error "PHP is not installed!"
        exit 1
    fi
    
    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    info "PHP version: $PHP_VERSION"
    
    if php -r "exit(version_compare(PHP_VERSION, '8.4.0', '<') ? 1 : 0);"; then
        error "PHP 8.4+ is required. Current version: $PHP_VERSION"
        exit 1
    fi
    success "PHP version is compatible"
}

# Install the global installer
install_global() {
    info "Installing MiniFramework PHP global installer..."
    
    if composer global require miniframework/installer --no-interaction; then
        success "Global installer installed successfully!"
    else
        error "Failed to install global installer"
        echo ""
        echo "You can try manual installation:"
        echo "  git clone https://github.com/miniframework/installer.git"
        echo "  cd installer"
        echo "  composer install"
        echo "  ln -s \$(pwd)/bin/miniframework /usr/local/bin/miniframework"
        echo ""
        exit 1
    fi
}

# Check if Composer global bin directory is in PATH
check_path() {
    COMPOSER_BIN_DIR=$(composer global config bin-dir --absolute 2>/dev/null || echo "$HOME/.composer/vendor/bin")
    
    if [[ ":$PATH:" != *":$COMPOSER_BIN_DIR:"* ]]; then
        warning "Composer global bin directory is not in PATH"
        echo ""
        echo "Add the following line to your shell profile (~/.bashrc, ~/.zshrc, etc.):"
        echo "  export PATH=\"\$PATH:$COMPOSER_BIN_DIR\""
        echo ""
        echo "Then restart your terminal or run:"
        echo "  source ~/.bashrc  # or ~/.zshrc"
        echo ""
        echo "Alternative: You can also run the installer directly:"
        echo "  $COMPOSER_BIN_DIR/miniframework new my-project"
        echo ""
        return 1
    else
        success "Composer global bin directory is in PATH"
        return 0
    fi
}

# Test the installation
test_installation() {
    info "Testing installation..."
    
    if command -v miniframework &> /dev/null; then
        success "Installation successful!"
        echo ""
        miniframework version
        echo ""
        echo "You can now create new projects with:"
        echo "  miniframework new my-project"
        echo ""
    else
        warning "Command 'miniframework' not found in PATH"
        echo ""
        echo "The installer was installed but is not available globally."
        echo "Please check your PATH configuration above."
        echo ""
    fi
}

# Show usage examples
show_examples() {
    echo "Usage examples:"
    echo ""
    echo "  # Create a basic project"
    echo "  miniframework new my-project"
    echo ""
    echo "  # Create with custom options"
    echo "  miniframework new my-api --namespace=MyApi --path=/var/www/api"
    echo ""
    echo "  # Create a blog"
    echo "  miniframework new my-blog --description=\"My personal blog\""
    echo ""
    echo "  # Show help"
    echo "  miniframework help"
    echo ""
}

# Main installation process
main() {
    info "Starting MiniFramework PHP installer setup..."
    echo ""
    
    # Check prerequisites
    check_php
    check_composer
    echo ""
    
    # Install globally
    install_global
    echo ""
    
    # Check PATH and test
    if check_path; then
        test_installation
    fi
    
    echo ""
    show_examples
    
    success "Setup complete! 🎉"
    echo ""
    echo "Documentation: https://github.com/miniframework/installer"
    echo "Framework docs: https://github.com/nastmz/mini-php-framework"
    echo ""
}

# Run main function
main "$@"
