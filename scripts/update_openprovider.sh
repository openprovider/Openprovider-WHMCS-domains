#!/bin/bash

# Openprovider Upgrade Script - Version v1.1

# Variables
GIT_REPO="https://github.com/openprovider/Openprovider-WHMCS-domains.git"
LATEST_RELEASE_API="https://api.github.com/repos/openprovider/Openprovider-WHMCS-domains/releases/latest"
BASE_RELEASE_URL="https://github.com/openprovider/Openprovider-WHMCS-domains/archive/refs/tags"
TEMP_DIR="/tmp/openprovider_module"

# Check if the current directory is the WHMCS root directory
if [ ! -f "configuration.php" ] || [ ! -d "modules/registrars" ] || [ ! -d "modules/addons" ]; then
    echo "Error: This script must be run from the WHMCS root directory."
    exit 1
fi

# Prompt user for confirmation to proceed with the update
if [ -r /dev/tty ]; then
    read -u 3 -p "Important: Updating the Openprovider module may overwrite any custom modifications you've made. Do you want to proceed? (Y/n): " confirm 3</dev/tty
    if [[ "$confirm" =~ ^[Yy]$ ]]; then
        echo "Proceeding with the update..."
    else
        echo "Update canceled. Please backup your customizations before proceeding."
        exit 0
    fi
else
    echo "No interactive terminal detected. Update canceled."
    exit 1
fi

# Create a temporary directory
mkdir -p "$TEMP_DIR"
if [ $? -ne 0 ]; then
    echo "Error: Failed to create temporary directory. Check permissions."
    exit 1
fi

# Check if git is installed
# if command -v git &> /dev/null; then
#     echo "Fetching the latest version of Openprovider module using git..."
#     git clone "$GIT_REPO" "$TEMP_DIR"
#     if [ $? -ne 0 ]; then
#         echo "Error: Failed to clone repository. Falling back to downloading package."
#         FALLBACK=true
#     fi
# else
#     echo "Git is not installed. Falling back to downloading package."
#     FALLBACK=true
# fi

FALLBACK=true

# Fallback to downloading the latest release if git is unavailable or fails
if [ "$FALLBACK" = true ]; then
    echo "Fetching the latest release version..."
    if command -v curl &> /dev/null; then
        LATEST_TAG=$(curl -s "$LATEST_RELEASE_API" | grep '"tag_name"' | cut -d '"' -f 4)
    elif command -v wget &> /dev/null; then
        LATEST_TAG=$(wget -qO- "$LATEST_RELEASE_API" | grep '"tag_name"' | cut -d '"' -f 4)
    else
        echo "Error: Neither curl nor wget is available. Cannot fetch latest release."
        exit 1
    fi
    
    if [ -z "$LATEST_TAG" ]; then
        echo "Error: Failed to fetch the latest release tag. Check your internet connection or GitHub API rate limits."
        exit 1
    fi
    
    LATEST_URL="${BASE_RELEASE_URL}/${LATEST_TAG}.tar.gz"
    
    echo "Downloading latest release: $LATEST_TAG ..."
    if command -v curl >/dev/null 2>&1; then
        echo "Using curl..."
        if ! curl -fSL --progress-bar "$LATEST_URL" \
            -o "$TEMP_DIR/openprovider_module.tar.gz"; then
            echo "Error: Failed to download package using curl."
            exit 1
        fi

    elif command -v wget >/dev/null 2>&1; then
        echo "Using wget..."
        if ! wget --show-progress --progress=bar:force:noscroll \
            "$LATEST_URL" -O "$TEMP_DIR/openprovider_module.tar.gz"; then
            echo "Error: Failed to download package using wget."
            exit 1
        fi
    else
        echo "Error: Neither curl nor wget is available. Cannot proceed."
        exit 1
    fi

    echo "Download complete."
    
    if [ ! -s "$TEMP_DIR/openprovider_module.tar.gz" ]; then
        echo "Error: Downloaded file is empty or corrupted."
        exit 1
    fi
    
    echo "Extracting package..."
    tar -xzf "$TEMP_DIR/openprovider_module.tar.gz" -C "$TEMP_DIR" --strip-components=1
    if [ $? -ne 0 ]; then
        echo "Error: Failed to extract package."
        exit 1
    fi
fi

# Copy files to update the Openprovider module in WHMCS
echo "Updating registrar module files..."
cp -r "$TEMP_DIR/modules/registrars/openprovider" "modules/registrars/"
if [ $? -ne 0 ]; then
    echo "Error: Failed to copy registrar module files. Check permissions."
    exit 1
fi

echo "Updating hook files..."
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
echo "Updating optional addon module files..."
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

echo "Openprovider module updated successfully!"
echo "Please follow the instructions in readme file to activate and configure the Openprovider module"

exit 0
