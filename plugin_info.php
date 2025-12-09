#!/bin/bash
#
# Plugin Info - Registers menu item in FPP
#

# Add menu item under Content Setup
cat <<EOF
{
  "name": "LOF Audio File Sync",
  "page": "plugin.php?plugin=fpp-lof-audio-sync&page=config.php",
  "menuParent": "content"
}
EOF
