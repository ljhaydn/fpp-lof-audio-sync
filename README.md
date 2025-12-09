# LOF Audio File Sync - FPP Plugin

## Description

Automatically syncs audio files from FPP to WordPress server for real-time audio streaming with Lights on Falcon show.

## Features

- **Automatic sync:** Files sync within 1 second of changes
- **Efficient:** Only syncs changed files (uses rsync)
- **Web UI:** Configure everything from FPP web interface
- **Manual control:** Test connections and trigger manual syncs
- **Monitoring:** View sync logs directly in FPP

## Installation

### Method 1: From FPP Plugin Manager (Recommended)

1. Open FPP web interface
2. Go to: Content Setup → Plugin Manager
3. Click "Install Plugin"
4. Select "Install from Git URL"
5. Enter: `https://github.com/ljhaydn/fpp-lof-audio-sync`
6. Click "Install"

### Method 2: Manual Installation

```bash
# SSH into FPP
ssh fpp@10.9.7.102

# Clone repository
cd /opt/fpp/plugins
git clone https://github.com/ljhaydn/fpp-lof-audio-sync lof-audio-sync

# Run installation
cd lof-audio-sync
bash install.sh
```

## Configuration

### Step 1: Setup SSH Key (One-Time)

**On FPP via SSH:**

```bash
# Generate SSH key if doesn't exist
ssh-keygen -t rsa -b 4096 -N "" -f ~/.ssh/id_rsa

# Copy key to WordPress server
ssh-copy-id www-data@<WORDPRESS_IP>

# Test connection (should work without password)
ssh www-data@<WORDPRESS_IP> "echo 'Success'"
```

### Step 2: Configure Plugin

1. In FPP web interface, go to: Content Setup → Plugins → LOF Audio File Sync
2. Click plugin name to open configuration
3. Enter settings:
   - **WordPress Server IP:** Your WordPress server IP
   - **Destination Path:** `/var/www/lof-audio`
   - **SSH User:** `www-data`
   - **Sync Delay:** `1` second
4. Click "Test SSH Connection" button
   - Should show: "SSH connection successful!"
5. Enable "Enable automatic sync" checkbox
6. Click "Save Settings"

### Step 3: Initial Sync

1. Click "Sync Now (Manual)" button
2. Wait for sync to complete
3. Verify files copied to WordPress server:
   ```bash
   ssh www-data@<WORDPRESS_IP> "ls -lh /var/www/lof-audio/"
   ```

## Usage

Once configured, audio files automatically sync when:
- New files added to `/home/fpp/media/music/`
- Existing files modified
- Files deleted (also deleted on WordPress server)

**Sync happens within 1 second of file changes.**

## Monitoring

### View Sync Status

In FPP web interface:
- Go to: Content Setup → Plugins → LOF Audio File Sync
- Check "Sync Status" section
- View "Sync Log" at bottom

### Manual Actions

- **Test SSH Connection:** Verify SSH key is working
- **Sync Now:** Trigger immediate sync (useful for testing)

## Troubleshooting

### "SSH connection failed"

**Problem:** SSH key not set up correctly

**Solution:**
```bash
# On FPP:
ssh-copy-id www-data@<WORDPRESS_IP>

# Test:
ssh www-data@<WORDPRESS_IP> "echo 'Works'"
```

### "Sync not running"

**Problem:** lsyncd service not started

**Solution:**
```bash
# On FPP:
systemctl status lsyncd
systemctl start lsyncd
systemctl enable lsyncd
```

### "Files not syncing"

**Problem:** Configuration error or permission issue

**Solutions:**

1. Check sync log in FPP web interface
2. Verify destination directory exists:
   ```bash
   ssh www-data@<WORDPRESS_IP> "ls -ld /var/www/lof-audio/"
   ```
3. Test manual rsync:
   ```bash
   rsync -avz /home/fpp/media/music/ www-data@<WORDPRESS_IP>:/var/www/lof-audio/
   ```

## How It Works

### Technology: lsyncd

The plugin uses `lsyncd` (Live Syncing Daemon):
- Watches `/home/fpp/media/music/` for file changes
- Triggers rsync when changes detected
- Batches multiple changes (1-second delay)
- Uses SSH for secure transfer

### Sync Flow

```
1. You add song to FPP
   ↓
2. lsyncd detects change (within 1 second)
   ↓
3. Waits 1 second (in case more files added)
   ↓
4. Runs rsync to WordPress server
   ↓
5. Files available for streaming immediately
```

### Network Impact

- **Bandwidth:** Only changed files transferred
- **CPU:** Minimal (<1% during sync)
- **FPP Performance:** No impact on show playback

## Configuration Files

- **Plugin directory:** `/opt/fpp/plugins/lof-audio-sync/`
- **Configuration:** `/opt/fpp/plugins/lof-audio-sync/config.json`
- **lsyncd config:** `/etc/lsyncd/lsyncd-lof.conf.lua`
- **Logs:** `/var/log/lsyncd/lsyncd.log`

## Uninstallation

```bash
# Stop syncing
systemctl stop lsyncd
systemctl disable lsyncd

# Remove plugin
rm -rf /opt/fpp/plugins/lof-audio-sync

# Remove lsyncd (if not used by anything else)
apt-get remove lsyncd
```

## Support

**Issues:** https://github.com/ljhaydn/fpp-lof-audio-sync/issues  
**Website:** https://lightsonfalcon.com

## License

MIT License - Free to use and modify

## Version History

**1.0.0** (2024-12-08)
- Initial release
- Automatic file sync via lsyncd
- Web UI for configuration
- SSH key setup instructions
- Manual sync capability
- Log viewing
