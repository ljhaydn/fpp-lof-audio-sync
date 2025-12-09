# FPP LOF Audio File Sync Plugin

Automatically syncs audio files from Falcon Player (FPP) to WordPress server for real-time audio streaming.

## Installation

### Method 1: Via FPP Plugin Manager (Recommended)

1. Open FPP web interface
2. Go to: **Content Setup → Plugins**
3. In the search box, paste:
   ```
   https://raw.githubusercontent.com/ljhaydn/fpp-lof-audio-sync/main/pluginInfo.json
   ```
4. Press Enter
5. Click **Install** when the plugin appears

### Method 2: Manual Installation

```bash
# SSH into FPP
ssh fpp@10.9.7.102

# Clone plugin
cd /opt/fpp/plugins
git clone https://github.com/ljhaydn/fpp-lof-audio-sync.git

# Run setup
cd fpp-lof-audio-sync
sudo ./plugin_setup.php
```

## Configuration

After installation:

1. Go to: **Content Setup → LOF Audio File Sync**
2. Configure settings:
   - **WordPress Server IP:** Your WordPress server IP address
   - **Destination Path:** `/var/www/lof-audio`
   - **SSH User:** `www-data`
   - **Sync Delay:** `1` second
3. Set up SSH key (see below)
4. Click "Test SSH Connection"
5. Enable "Enable automatic sync"
6. Click "Save Settings"

## SSH Key Setup (One-Time)

Required for passwordless sync:

```bash
# SSH into FPP
ssh fpp@10.9.7.102

# Generate SSH key (if doesn't exist)
ssh-keygen -t rsa -b 4096 -N "" -f ~/.ssh/id_rsa

# Copy key to WordPress server
ssh-copy-id www-data@YOUR_WORDPRESS_IP

# Test connection (should work without password)
ssh www-data@YOUR_WORDPRESS_IP "echo 'Success'"
```

## How It Works

1. **lsyncd** monitors `/home/fpp/media/music/` for changes
2. When files change, waits configured delay (1 second)
3. Uses **rsync** over SSH to sync to WordPress server
4. Runs in background automatically
5. Logs to `/var/log/lsyncd/lsyncd.log`

## Features

- **Automatic sync:** Files sync within 1 second of changes
- **Efficient:** Only syncs changed files (uses rsync)
- **Delete handling:** Deleted files on FPP also deleted on WordPress
- **Web UI:** Configure and monitor from FPP interface
- **Manual sync:** Trigger manual sync anytime
- **Connection testing:** Test SSH before enabling auto-sync

## Troubleshooting

### "SSH connection failed"

**Problem:** SSH key not set up

**Solution:**
```bash
ssh-copy-id www-data@YOUR_WORDPRESS_IP
```

### "Sync service not running"

**Problem:** lsyncd not installed or not started

**Solution:**
```bash
sudo apt-get install lsyncd
sudo systemctl start lsyncd
sudo systemctl enable lsyncd
```

### "Files not syncing"

**Check logs:**
```bash
tail -f /var/log/lsyncd/lsyncd.log
```

**Verify destination directory:**
```bash
ssh www-data@YOUR_WORDPRESS_IP "ls -la /var/www/lof-audio/"
```

**Test manual rsync:**
```bash
rsync -avz /home/fpp/media/music/ www-data@YOUR_WORDPRESS_IP:/var/www/lof-audio/
```

## Requirements

- FPP v8.0 or later
- SSH access to WordPress server
- lsyncd package (auto-installed)
- rsync package (included in FPP)

## Support

- **Issues:** https://github.com/ljhaydn/fpp-lof-audio-sync/issues
- **Website:** https://lightsonfalcon.com

## License

MIT License - Free to use and modify
