<h3>LOF Audio File Sync - Help</h3>

<p>This plugin automatically syncs audio files from FPP's <code>/home/fpp/media/music</code> directory to your WordPress server using lsyncd and rsync over SSH.</p>

<h4>Features</h4>
<ul>
    <li>Real-time automatic file synchronization</li>
    <li>Manual sync on demand</li>
    <li>SSH connection testing</li>
    <li>Sync status monitoring</li>
    <li>Configurable sync delay</li>
</ul>

<h4>Setup Instructions</h4>
<ol>
    <li>Go to <strong>Content Setup → LOF Audio Sync</strong></li>
    <li>Configure your WordPress server IP address</li>
    <li>Set up SSH key authentication (see instructions on config page)</li>
    <li>Test the SSH connection</li>
    <li>Enable automatic sync</li>
    <li>Save settings</li>
</ol>

<h4>SSH Key Setup (Required)</h4>
<p>For passwordless sync to work, you must set up SSH keys:</p>
<pre>
# SSH into FPP
ssh fpp@10.9.7.102

# Generate SSH key (if doesn't exist)
ssh-keygen -t rsa -b 4096 -N "" -f ~/.ssh/id_rsa

# Copy key to WordPress server
ssh-copy-id www-data@YOUR_WORDPRESS_IP

# Test (should work without password)
ssh www-data@YOUR_WORDPRESS_IP "echo 'Success'"
</pre>

<h4>How It Works</h4>
<p>lsyncd monitors the FPP music directory and automatically syncs changes to your WordPress server within the configured delay (default 1 second). This ensures your WordPress audio player always has the latest files available for streaming.</p>

<h4>Troubleshooting</h4>
<p><strong>SSH connection fails:</strong> Make sure SSH key is set up correctly using ssh-copy-id</p>
<p><strong>Files not syncing:</strong> Check the sync log on the config page</p>
<p><strong>Service not running:</strong> Ensure lsyncd package is installed</p>

<h4>Support</h4>
<p>For issues or questions: <a href="https://github.com/ljhaydn/fpp-lof-audio-sync/issues" target="_blank">GitHub Issues</a></p>
