#!/bin/bash

# Script to update pill button classes across all dashboard files

echo "Updating pill button classes in dashboard files..."

# Function to update files
update_file() {
    local file=$1
    echo "Processing: $file"
    
    # Update primary buttons
    sed -i '' 's/btn-pill[[:space:]]\+inline-flex[[:space:]]\+items-center[[:space:]]\+px-[0-9.]\+[[:space:]]\+py-[0-9.]\+[[:space:]]\+border[[:space:]]\+border-transparent[[:space:]]\+.*text-white[[:space:]]\+bg-primary[[:space:]]\+hover-bg-primary-dark/pill-primary inline-flex items-center/g' "$file"
    
    # Update secondary buttons (white/gray)
    sed -i '' 's/btn-pill[[:space:]]\+inline-flex[[:space:]]\+items-center[[:space:]]\+px-[0-9.]\+[[:space:]]\+py-[0-9.]\+[[:space:]]\+border[[:space:]]\+border-gray-[0-9]\+[[:space:]]\+.*text-gray-[0-9]\+[[:space:]]\+bg-white[[:space:]]\+hover:bg-gray-[0-9]\+/pill-secondary inline-flex items-center/g' "$file"
    
    # Update danger buttons
    sed -i '' 's/btn-pill[[:space:]]\+inline-flex[[:space:]]\+items-center[[:space:]]\+px-[0-9.]\+[[:space:]]\+py-[0-9.]\+[[:space:]]\+border[[:space:]]\+border-red-[0-9]\+[[:space:]]\+.*text-red-[0-9]\+[[:space:]]\+bg-white[[:space:]]\+hover:bg-red-[0-9]\+/pill-danger inline-flex items-center/g' "$file"
    
    # Update status badges - success
    sed -i '' 's/inline-flex[[:space:]]\+items-center[[:space:]]\+px-2\.5[[:space:]]\+py-0\.5[[:space:]]\+rounded-full[[:space:]]\+text-xs[[:space:]]\+font-medium[[:space:]]\+bg-green-[0-9]\+[[:space:]]\+text-green-[0-9]\+/pill-badge pill-badge-success inline-flex items-center/g' "$file"
    
    # Update status badges - warning
    sed -i '' 's/inline-flex[[:space:]]\+items-center[[:space:]]\+px-2\.5[[:space:]]\+py-0\.5[[:space:]]\+rounded-full[[:space:]]\+text-xs[[:space:]]\+font-medium[[:space:]]\+bg-yellow-[0-9]\+[[:space:]]\+text-yellow-[0-9]\+/pill-badge pill-badge-warning inline-flex items-center/g' "$file"
    
    # Update status badges - danger
    sed -i '' 's/inline-flex[[:space:]]\+items-center[[:space:]]\+px-2\.5[[:space:]]\+py-0\.5[[:space:]]\+rounded-full[[:space:]]\+text-xs[[:space:]]\+font-medium[[:space:]]\+bg-red-[0-9]\+[[:space:]]\+text-red-[0-9]\+/pill-badge pill-badge-danger inline-flex items-center/g' "$file"
    
    # Update status badges - info
    sed -i '' 's/inline-flex[[:space:]]\+items-center[[:space:]]\+px-2\.5[[:space:]]\+py-0\.5[[:space:]]\+rounded-full[[:space:]]\+text-xs[[:space:]]\+font-medium[[:space:]]\+bg-primary-lightest[[:space:]]\+text-primary-dark/pill-badge pill-badge-info inline-flex items-center/g' "$file"
    
    # Update status badges - gray
    sed -i '' 's/inline-flex[[:space:]]\+items-center[[:space:]]\+px-2\.5[[:space:]]\+py-0\.5[[:space:]]\+rounded-full[[:space:]]\+text-xs[[:space:]]\+font-medium[[:space:]]\+bg-gray-[0-9]\+[[:space:]]\+text-gray-[0-9]\+/pill-badge pill-badge-gray inline-flex items-center/g' "$file"
}

# Find all PHP files in dashboard directory
for file in dashboard/*.php; do
    if [ -f "$file" ]; then
        update_file "$file"
    fi
done

echo "Update complete!"