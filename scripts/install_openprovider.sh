#!/bin/bash

# Variables
GIT_REPO="https://github.com/openprovider/Openprovider-WHMCS-domains.git"
TEMP_DIR="/tmp/openprovider_module"

# Check if the current directory is the WHMCS root directory
if [ ! -f "configuration.php" ] || [ ! -d "modules/registrars" ] || [ ! -d "modules/addons" ]; then
    echo "Error: This script must be run from the WHMCS root directory."
    exit 1
fi

# Check if git is installed
if ! command -v git &> /dev/null; then
    echo "Error: git is not installed. Please install git and try again."
    exit 1
fi

# Create a temporary directory
mkdir -p "$TEMP_DIR"
if [ $? -ne 0 ]; then
    echo "Error: Failed to create temporary directory. Check permissions."
    exit 1
fi

# Clone the Openprovider module repository
echo "Cloning Openprovider repository..."
git clone "$GIT_REPO" "$TEMP_DIR"
if [ $? -ne 0 ]; then
    echo "Error: Failed to clone repository. Check your network connection or git permissions."
    exit 1
fi

# Copy files to WHMCS directories
echo "Copying registrar module files..."
cp -r "$TEMP_DIR/modules/registrars/openprovider" "modules/registrars/"
if [ $? -ne 0 ]; then
    echo "Error: Failed to copy registrar module files. Check permissions."
    exit 1
fi

echo "Copying hook files..."
cp -r "$TEMP_DIR/includes/hooks/"* "includes/hooks/"
if [ $? -ne 0 ]; then
    echo "Error: Failed to copy hook files. Check permissions."
    exit 1
fi

# Check and update additionalfields.php
ADDITIONAL_FIELDS="resources/domains/additionalfields.php"

if [ ! -f "$ADDITIONAL_FIELDS" ]; then
    echo "Copying additionalfields.php example file..."
    cp "$TEMP_DIR/resources/domains/additionalfields.php" "$ADDITIONAL_FIELDS"
    if [ $? -ne 0 ]; then
        echo "Error: Failed to copy additionalfields.php. Check permissions."
        exit 1
    fi
else
    echo "Updating existing additionalfields.php..."
    grep -q 'openprovider_additional_fields' "$ADDITIONAL_FIELDS" || echo -e "<?php\nif (function_exists('openprovider_additional_fields'))\n    \$additionaldomainfields = openprovider_additional_fields();" >> "$ADDITIONAL_FIELDS"
    if [ $? -ne 0 ]; then
        echo "Error: Failed to update additionalfields.php. Check permissions."
        exit 1
    fi
fi

# [Optional] Copy addon module files
echo "Copying optional addon module files..."
cp -r "$TEMP_DIR/modules/addons/openprovider" "modules/addons/"
if [ $? -ne 0 ]; then
    echo "Error: Failed to copy addon module files. Check permissions."
    exit 1
fi

# Clean up temporary directory
echo "Cleaning up temporary files..."
rm -rf "$TEMP_DIR"
if [ $? -ne 0 ]; then
    echo "Error: Failed to clean up temporary files."
    exit 1
fi

# Verify that all necessary files were installed successfully
if [ -f "modules/registrars/openprovider/openprovider.php" ] && \
   [ -f "modules/addons/openprovider/openprovider.php" ] && \
   [ -f "includes/hooks/openprovider.js" ] && \
   [ -f "resources/domains/additionalfields.php" ]; then
    echo "Openprovider module installed successfully!"
    echo "Please follow the instructions in readme file to activate and configure the Openprovider module"
else
    echo "Error: Installation failed. Some required files are missing."
    exit 1
fi

exit 0
