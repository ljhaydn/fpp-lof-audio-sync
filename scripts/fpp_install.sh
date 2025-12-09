#!/bin/bash

# LOF Audio File Sync install script

# Include common scripts functions and variables
. ${FPPDIR}/scripts/common

echo "Installing LOF Audio File Sync plugin..."

# Install lsyncd if not present
if ! command -v lsyncd &> /dev/null; then
    echo "Installing lsyncd..."
    apt-get update -qq
    apt-get install -y lsyncd >/dev/null 2>&1
fi

# Create settings file with defaults
PLUGIN_DIR="${PLUGINDIR}/${PLUGIN_NAME}"
SETTINGS_FILE="$PLUGIN_DIR/settings.json"

if [ ! -f "$SETTINGS_FILE" ]; then
    cat > "$SETTINGS_FILE" <<EOF
{
  "enabled": false,
  "destination_host": "",
  "destination_path": "/var/www/lof-audio",
  "ssh_user": "www-data",
  "sync_delay": 1,
  "source_path": "/home/fpp/media/music"
}
EOF
fi

# Create lsyncd log directory
mkdir -p /var/log/lsyncd
chown fpp:fpp /var/log/lsyncd

echo "LOF Audio File Sync plugin installed successfully"
