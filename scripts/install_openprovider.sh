#!/bin/bash

# Openprovider Install Script - Version v1.1

# Variables
GIT_REPO="https://github.com/openprovider/Openprovider-WHMCS-domains.git"
LATEST_RELEASE_API="https://api.github.com/repos/openprovider/Openprovider-WHMCS-domains/releases/latest"
BASE_RELEASE_URL="https://github.com/openprovider/Openprovider-WHMCS-domains/archive/refs/tags"
TEMP_DIR="/tmp/openprovider_module"

# -----------------------------
# Helpers: horizontal loader
# -----------------------------
draw_bar() {
    # draw_bar <pct> [width]
    local pct="$1"
    local width="${2:-24}"

    [ "$pct" -lt 0 ] && pct=0
    [ "$pct" -gt 100 ] && pct=100

    local filled=$((pct * width / 100))
    local empty=$((width - filled))

    local bar="" i=0
    while [ $i -lt $filled ]; do bar="${bar}#"; i=$((i+1)); done
    i=0
    while [ $i -lt $empty ]; do bar="${bar}-"; i=$((i+1)); done

    printf "[%s] %3s%%" "$bar" "$pct"
}

get_content_length() {
    # get_content_length <url>
    local url="$1"
    local len=""

    if command -v curl >/dev/null 2>&1; then
        len="$(curl -sIL "$url" | awk '
            BEGIN{IGNORECASE=1}
            /^content-length:/ {gsub("\r",""); print $2}
        ' | tail -n 1)"
    elif command -v wget >/dev/null 2>&1; then
        # wget --spider outputs headers to stderr
        len="$(wget --spider -S "$url" 2>&1 | awk '
            BEGIN{IGNORECASE=1}
            /Content-Length:/ {print $2}
        ' | tail -n 1)"
    fi

    # Keep digits only
    len="$(printf "%s" "$len" | tr -cd '0-9')"
    printf "%s" "$len"
}

indeterminate_bar() {
    # indeterminate_bar <pos> [width]
    local pos="$1"
    local width="${2:-24}"

    local bar="" i=0
    while [ $i -lt $width ]; do
        if [ $i -eq "$pos" ]; then
            bar="${bar}#"
        else
            bar="${bar}-"
        fi
        i=$((i+1))
    done

    printf "[%s]" "$bar"
}

download_with_loader() {
    # download_with_loader <url> <outpath>
    local url="$1"
    local out="$2"
    local method=""
    local pid=""
    local total=""
    local width=24

    # Pick tool + start download in background (silent)
    if command -v curl >/dev/null 2>&1; then
        method="curl"
        curl -fSL -sS "$url" -o "$out" &
        pid=$!
    elif command -v wget >/dev/null 2>&1; then
        method="wget"
        wget -q "$url" -O "$out" &
        pid=$!
    else
        echo "Error: Neither curl nor wget is available. Cannot proceed."
        return 127
    fi

    # Try to get total size for % bar (best UX)
    total="$(get_content_length "$url")"

    # Only animate if stdout is a TTY (keeps logs clean)
    if [ -t 1 ]; then
        if [ -n "$total" ] && [ "$total" -gt 0 ] 2>/dev/null; then
            # Determinate bar with percentage
            while kill -0 "$pid" 2>/dev/null; do
                local cur=0 pct=0
                if [ -f "$out" ]; then
                    cur="$(wc -c < "$out" 2>/dev/null || echo 0)"
                fi
                pct=$((cur * 100 / total))
                [ "$pct" -gt 99 ] && pct=99
                printf "\rUsing %s... %s" "$method" "$(draw_bar "$pct" "$width")"
                sleep 0.2
            done
        else
            # Indeterminate moving bar (still ONE LINE)
            local pos=0 dir=1 max=$((width-1))
            while kill -0 "$pid" 2>/dev/null; do
                printf "\rUsing %s... %s" "$method" "$(indeterminate_bar "$pos" "$width")"
                pos=$((pos + dir))
                if [ "$pos" -ge "$max" ]; then dir=-1; fi
                if [ "$pos" -le 0 ]; then dir=1; fi
                sleep 0.08
            done
        fi
    fi

    wait "$pid"
    local rc=$?

    if [ "$rc" -ne 0 ]; then
        # Finish line neatly
        if [ -t 1 ]; then
            printf "\rUsing %s... %s\n" "$method" "$(draw_bar 0 "$width")"
        fi
        echo "Error: Failed to download package using $method."
        return "$rc"
    fi

    # Success: finalize to 100% in same line
    if [ -t 1 ]; then
        printf "\rUsing %s... %s\n" "$method" "$(draw_bar 100 "$width")"
    fi
    return 0
}

# -----------------------------
# Main
# -----------------------------

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

#Check if git is installed
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
