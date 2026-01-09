#!/bin/bash

# Openprovider Install Script - Version v1.1

# Variables
GIT_REPO="https://github.com/openprovider/Openprovider-WHMCS-domains.git"
LATEST_RELEASE_API="https://api.github.com/repos/openprovider/Openprovider-WHMCS-domains/releases/latest"
BASE_RELEASE_URL="https://github.com/openprovider/Openprovider-WHMCS-domains/archive/refs/tags"
TEMP_DIR="/tmp/openprovider_module"
SCRIPT_REF="${SCRIPT_REF:-master}"
HELPER_URL="https://raw.githubusercontent.com/openprovider/Openprovider-WHMCS-domains/${SCRIPT_REF}/scripts/lib/progress_utils.sh"
HELPER_FILE="/tmp/openprovider_progress_utils_${SCRIPT_REF//\//_}.sh"

# Load shared helpers (supports curl|bash execution)
if [ ! -f "$HELPER_FILE" ]; then
    if command -v curl >/dev/null 2>&1; then
        curl -fsSL "$HELPER_URL" -o "$HELPER_FILE" || {
            echo "Error: Failed to download helper utilities using curl."
            exit 1
        }
    elif command -v wget >/dev/null 2>&1; then
        wget -q "$HELPER_URL" -O "$HELPER_FILE" || {
            echo "Error: Failed to download helper utilities using wget."
            exit 1
        }
    else
        echo "Error: Neither curl nor wget is available to load helper utilities."
        exit 1
    fi
fi

# shellcheck disable=SC1090
source "$HELPER_FILE"

command -v download_with_loader >/dev/null 2>&1 || {
    echo "Error: Failed to load helper utilities (download_with_loader missing)."
    exit 1
}

# Check if the current directory is the WHMCS root directory
if [ ! -f "configuration.php" ] || [ ! -d "modules/registrars" ] || [ ! -d "modules/addons" ]; then
    echo "Error: This script must be run from the WHMCS root directory."
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
#     echo "Cloning Openprovider repository..."
#     git clone "$GIT_REPO" "$TEMP_DIR"
#     if [ $? -ne 0 ]; then
#         echo "Error: Failed to clone repository. Falling back to downloading latest release."
#         FALLBACK=true
#     fi
# else
#     echo "Git is not installed. Falling back to downloading latest release."
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
        echo "Error: Failed to fetch the latest release tag."
        exit 1
    fi
    
    LATEST_URL="${BASE_RELEASE_URL}/${LATEST_TAG}.tar.gz"
    
    echo "Downloading latest release: $LATEST_TAG ..."

    if ! download_with_loader "$LATEST_URL" "$TEMP_DIR/latest.tar.gz"; then
        exit 1
    fi
    
    if [ ! -s "$TEMP_DIR/latest.tar.gz" ]; then
        echo "Error: Downloaded file is empty or corrupted."
        exit 1
    fi
    
    echo "Extracting package..."
    tar -xzf "$TEMP_DIR/latest.tar.gz" -C "$TEMP_DIR" --strip-components=1
    if [ $? -ne 0 ]; then
        echo "Error: Failed to extract package."
        exit 1
    fi
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
