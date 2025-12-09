#!/bin/bash
#
# LOF Audio File Sync - Installation Script
# Installs lsyncd and sets up configuration
#

PLUGIN_DIR="/opt/fpp/plugins/lof-audio-sync"
CONFIG_FILE="$PLUGIN_DIR/config.json"
LSYNCD_CONFIG="/etc/lsyncd/lsyncd-lof.conf.lua"

echo "Installing LOF Audio File Sync plugin..."

# Install lsyncd if not already installed
if ! command -v lsyncd &> /dev/null; then
    echo "Installing lsyncd..."
    apt-get update
    apt-get install -y lsyncd
fi

# Create config file if doesn't exist
if [ ! -f "$CONFIG_FILE" ]; then
    echo "Creating default configuration..."
    cat > "$CONFIG_FILE" <<EOF
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

echo "LOF Audio File Sync plugin installed successfully!"
echo ""
echo "Next steps:"
echo "1. Configure plugin settings in FPP web interface"
echo "2. Setup SSH key for passwordless sync"
echo "3. Enable automatic sync"
echo ""
