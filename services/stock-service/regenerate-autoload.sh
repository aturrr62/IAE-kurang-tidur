#!/bin/bash

# Regenerate Composer Autoload
# Run this script to fix VSCode warnings about missing classes

echo "🔄 Regenerating Composer autoload..."

cd "$(dirname "$0")"

# Dump autoload
composer dump-autoload

echo "✅ Autoload regenerated!"
echo ""
echo "📝 If VSCode still shows errors:"
echo "1. Restart VSCode/Intelephense"
echo "2. Run: Ctrl+Shift+P -> 'PHP: Restart PHP Language Server'"
echo "3. Clear cache: rm -rf storage/framework/cache/*"
