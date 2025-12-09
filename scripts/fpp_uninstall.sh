#!/bin/bash

# LOF Audio File Sync uninstall script

echo "Uninstalling LOF Audio File Sync plugin..."

# Stop lsyncd if running
systemctl stop lsyncd 2>/dev/null || true

echo "LOF Audio File Sync plugin uninstalled"
