#!/bin/bash

echo "Installing PDF conversion tools for macOS..."
echo "=========================================="

# Check if Homebrew is installed
if ! command -v brew &> /dev/null; then
    echo "Homebrew not found. Installing Homebrew first..."
    /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
fi

echo "Installing Ghostscript (for PDF conversion)..."
brew install ghostscript

echo "Installing ImageMagick with Ghostscript support..."
brew install imagemagick

echo ""
echo "Installation complete!"
echo ""
echo "Testing Ghostscript..."
gs -version

echo ""
echo "Testing ImageMagick..."
convert -version

echo ""
echo "=========================================="
echo "PDF tools installed successfully!"
echo "Your system can now convert PDF labels to images."
echo ""
echo "Note: You may need to restart XAMPP/Apache for changes to take effect."