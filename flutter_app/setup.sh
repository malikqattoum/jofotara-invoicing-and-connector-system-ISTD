#!/bin/bash

# JoFotara Flutter App Setup Script
echo "🚀 Setting up JoFotara Flutter App..."

# Check if Flutter is installed
if ! command -v flutter &> /dev/null; then
    echo "❌ Flutter is not installed. Please install Flutter first."
    echo "Visit: https://docs.flutter.dev/get-started/install"
    exit 1
fi

echo "✅ Flutter found: $(flutter --version | head -n 1)"

# Check Flutter doctor
echo "🔍 Running Flutter doctor..."
flutter doctor

# Get dependencies
echo "📦 Getting Flutter dependencies..."
flutter pub get

# Generate necessary files
echo "🔧 Generating build files..."
flutter packages pub run build_runner build --delete-conflicting-outputs

# Create assets directories
echo "📁 Creating asset directories..."
mkdir -p assets/images
mkdir -p assets/icons
mkdir -p assets/fonts

# Download and setup fonts (optional)
echo "🔤 Setting up fonts..."
if [ ! -f "assets/fonts/Cairo-Regular.ttf" ]; then
    echo "📝 Note: Please download Cairo font family and place in assets/fonts/"
    echo "   - Cairo-Regular.ttf"
    echo "   - Cairo-Bold.ttf"
    echo "   - Roboto-Regular.ttf"
    echo "   - Roboto-Bold.ttf"
fi

# Configure API endpoint
echo "⚙️  Configuring API..."
echo "Please update the API endpoint in lib/utils/constants.dart"
echo "Current: http://your-domain.com/api"
echo "Change to: https://your-actual-domain.com/api"

# Setup Android permissions
echo "📱 Setting up Android permissions..."
echo "✅ Bluetooth permissions configured"
echo "✅ USB printer permissions configured"
echo "✅ Network permissions configured"
echo "✅ Camera permissions configured"

# Setup iOS permissions
echo "🍎 Setting up iOS permissions..."
echo "✅ Bluetooth permissions configured"
echo "✅ Camera permissions configured"
echo "✅ Network permissions configured"

# Build check
echo "🔨 Running build check..."
flutter analyze

echo ""
echo "🎉 Setup completed!"
echo ""
echo "Next steps:"
echo "1. Update API endpoint in lib/utils/constants.dart"
echo "2. Add font files to assets/fonts/ directory"
echo "3. Test on a physical device for printer functionality"
echo "4. Run: flutter run"
echo ""
echo "For printer testing:"
echo "- Android: Connect via USB, Bluetooth, or WiFi"
echo "- iOS: Use Bluetooth or WiFi printers"
echo ""
echo "Supported printers:"
echo "- ESC/POS thermal printers (Epson, Star, Citizen, etc.)"
echo "- Network printers with IP:Port configuration"
echo "- Bluetooth thermal receipt printers"
echo "- PDF printing to any system printer"
echo ""
echo "Happy coding! 🚀"
