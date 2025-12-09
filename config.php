<!DOCTYPE html>
<html>
<head>
    <title>LOF Audio File Sync - Configuration</title>
    <style>
        .config-section { margin: 20px 0; padding: 15px; background: #f5f5f5; border-radius: 5px; }
        .config-section h3 { margin-top: 0; }
        .form-group { margin: 15px 0; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input[type="text"], .form-group input[type="number"] { width: 400px; padding: 5px; }
        .form-group .help-text { font-size: 12px; color: #666; margin-top: 3px; }
        .status-indicator { display: inline-block; width: 12px; height: 12px; border-radius: 50%; margin-right: 5px; }
        .status-online { background-color: #4CAF50; }
        .status-offline { background-color: #f44336; }
        .btn { padding: 8px 15px; margin-right: 10px; cursor: pointer; border: none; border-radius: 3px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 3px; overflow-x: auto; max-height: 300px; }
    </style>
</head>
<body>

<?php
$pluginDir = "/opt/fpp/plugins/fpp-lof-audio-sync";
$settingsFile = "$pluginDir/settings.json";

// Load settings
$settings = json_decode(file_get_contents($settingsFile), true);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'save_settings') {
            // Save settings
            $settings['enabled'] = isset($_POST['enabled']);
            $settings['destination_host'] = $_POST['destination_host'] ?? '';
            $settings['destination_path'] = $_POST['destination_path'] ?? '/var/www/lof-audio';
            $settings['ssh_user'] = $_POST['ssh_user'] ?? 'www-data';
            $settings['sync_delay'] = (int)($_POST['sync_delay'] ?? 1);
            $settings['source_path'] = $_POST['source_path'] ?? '/home/fpp/media/music';
            
            file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));
            
            // Restart lsyncd if enabled
            if ($settings['enabled'] && !empty($settings['destination_host'])) {
                exec("sudo $pluginDir/start_sync.sh > /dev/null 2>&1 &");
            } else {
                exec("sudo systemctl stop lsyncd 2>&1");
            }
            
            echo '<div class="alert alert-success">Settings saved and sync restarted!</div>';
        } elseif ($action === 'test_ssh') {
            // Test SSH connection
            $host = $settings['destination_host'];
            $user = $settings['ssh_user'];
            
            if (!empty($host) && !empty($user)) {
                $output = [];
                exec("ssh -o BatchMode=yes -o ConnectTimeout=5 $user@$host 'echo SUCCESS' 2>&1", $output, $return_var);
                
                if ($return_var === 0 && in_array('SUCCESS', $output)) {
                    echo '<div class="alert alert-success">✓ SSH connection successful!</div>';
                } else {
                    echo '<div class="alert alert-error">✗ SSH connection failed. Output:</div>';
                    echo '<pre>' . htmlspecialchars(implode("\n", $output)) . '</pre>';
                }
            } else {
                echo '<div class="alert alert-error">Please configure destination host first.</div>';
            }
        } elseif ($action === 'sync_now') {
            // Manual sync
            $src = $settings['source_path'];
            $dest = $settings['ssh_user'] . '@' . $settings['destination_host'] . ':' . $settings['destination_path'];
            
            if (!empty($settings['destination_host'])) {
                $output = [];
                exec("rsync -avz --delete $src/ $dest/ 2>&1", $output, $return_var);
                
                if ($return_var === 0) {
                    echo '<div class="alert alert-success">✓ Manual sync completed!</div>';
                } else {
                    echo '<div class="alert alert-error">✗ Sync failed</div>';
                }
                echo '<pre>' . htmlspecialchars(implode("\n", $output)) . '</pre>';
            }
        }
    }
}

// Get status
$lsyncdRunning = false;
exec("systemctl is-active lsyncd 2>&1", $output, $return_var);
if ($return_var === 0) {
    $lsyncdRunning = true;
}

$fileCount = 0;
if (is_dir($settings['source_path'])) {
    $files = glob($settings['source_path'] . '/*.mp3');
    $fileCount = count($files);
}
?>

<h2>LOF Audio File Sync - Configuration</h2>

<!-- Status Section -->
<div class="config-section">
    <h3>Current Status</h3>
    <p>
        <span class="status-indicator <?php echo $lsyncdRunning ? 'status-online' : 'status-offline'; ?>"></span>
        <strong>Sync Service:</strong> <?php echo $lsyncdRunning ? 'Running' : 'Stopped'; ?>
    </p>
    <p><strong>Audio Files:</strong> <?php echo $fileCount; ?> MP3 files in <?php echo htmlspecialchars($settings['source_path']); ?></p>
</div>

<!-- Settings Form -->
<div class="config-section">
    <h3>Settings</h3>
    <form method="post">
        <input type="hidden" name="action" value="save_settings">
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="enabled" value="1" <?php echo $settings['enabled'] ? 'checked' : ''; ?>>
                Enable automatic sync
            </label>
            <div class="help-text">Files will automatically sync when changed</div>
        </div>
        
        <div class="form-group">
            <label for="destination_host">WordPress Server IP/Hostname:</label>
            <input type="text" id="destination_host" name="destination_host" 
                   value="<?php echo htmlspecialchars($settings['destination_host']); ?>" 
                   placeholder="192.168.1.100" required>
        </div>
        
        <div class="form-group">
            <label for="destination_path">Destination Path:</label>
            <input type="text" id="destination_path" name="destination_path" 
                   value="<?php echo htmlspecialchars($settings['destination_path']); ?>">
            <div class="help-text">Path on WordPress server where audio files are stored</div>
        </div>
        
        <div class="form-group">
            <label for="ssh_user">SSH User:</label>
            <input type="text" id="ssh_user" name="ssh_user" 
                   value="<?php echo htmlspecialchars($settings['ssh_user']); ?>">
            <div class="help-text">User on WordPress server (typically www-data)</div>
        </div>
        
        <div class="form-group">
            <label for="sync_delay">Sync Delay (seconds):</label>
            <input type="number" id="sync_delay" name="sync_delay" 
                   value="<?php echo $settings['sync_delay']; ?>" min="1" max="60">
            <div class="help-text">How long to wait after file changes before syncing</div>
        </div>
        
        <div class="form-group">
            <label for="source_path">Source Path (FPP):</label>
            <input type="text" id="source_path" name="source_path" 
                   value="<?php echo htmlspecialchars($settings['source_path']); ?>">
        </div>
        
        <button type="submit" class="btn btn-primary">Save Settings</button>
    </form>
</div>

<!-- Manual Actions -->
<div class="config-section">
    <h3>Manual Actions</h3>
    <form method="post" style="display:inline;">
        <input type="hidden" name="action" value="test_ssh">
        <button type="submit" class="btn btn-secondary">Test SSH Connection</button>
    </form>
    
    <form method="post" style="display:inline;">
        <input type="hidden" name="action" value="sync_now">
        <button type="submit" class="btn btn-warning">Sync Now (Manual)</button>
    </form>
</div>

<!-- SSH Setup Instructions -->
<div class="config-section">
    <h3>SSH Key Setup (One-Time)</h3>
    <p>For automatic sync to work, you need to set up SSH key authentication:</p>
    <pre>
# On FPP (via SSH):
ssh fpp@10.9.7.102

# Generate SSH key (if not exists):
ssh-keygen -t rsa -b 4096 -N "" -f ~/.ssh/id_rsa

# Copy key to WordPress server:
ssh-copy-id <?php echo $settings['ssh_user'] . '@' . $settings['destination_host']; ?>

# Test (should work without password):
ssh <?php echo $settings['ssh_user'] . '@' . $settings['destination_host']; ?> "echo 'Works'"</pre>
</div>

<!-- Logs -->
<div class="config-section">
    <h3>Sync Log (Last 50 Lines)</h3>
    <pre><?php
    $logFile = '/var/log/lsyncd/lsyncd.log';
    if (file_exists($logFile)) {
        $lines = file($logFile);
        $recentLines = array_slice($lines, -50);
        echo htmlspecialchars(implode('', $recentLines));
    } else {
        echo 'No log file found. Sync may not have run yet.';
    }
    ?></pre>
</div>

</body>
</html>
