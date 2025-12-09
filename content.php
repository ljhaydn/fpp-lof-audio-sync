<?php
/**
 * LOF Audio File Sync - Configuration UI
 * Displays in FPP web interface under Content Setup → Plugins
 */

$pluginDir = "/opt/fpp/plugins/lof-audio-sync";
$configFile = "$pluginDir/config.json";
$config = json_decode(file_get_contents($configFile), true);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_config') {
        $config['enabled'] = isset($_POST['enabled']);
        $config['destination_host'] = $_POST['destination_host'] ?? '';
        $config['destination_path'] = $_POST['destination_path'] ?? '/var/www/lof-audio';
        $config['ssh_user'] = $_POST['ssh_user'] ?? 'www-data';
        $config['sync_delay'] = (int)($_POST['sync_delay'] ?? 1);
        $config['source_path'] = $_POST['source_path'] ?? '/home/fpp/media/music';
        
        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
        
        // Restart lsyncd if enabled
        if ($config['enabled']) {
            exec("/opt/fpp/plugins/lof-audio-sync/start-sync.sh > /dev/null 2>&1 &");
        } else {
            exec("systemctl stop lsyncd");
        }
        
        echo '<div class="alert alert-success">Settings saved!</div>';
    } elseif ($_POST['action'] === 'sync_now') {
        // Manual sync
        $src = $config['source_path'];
        $dest = $config['ssh_user'] . '@' . $config['destination_host'] . ':' . $config['destination_path'];
        
        $output = [];
        exec("rsync -avz --delete $src/ $dest/ 2>&1", $output, $return_var);
        
        if ($return_var === 0) {
            echo '<div class="alert alert-success">Sync completed successfully!</div>';
        } else {
            echo '<div class="alert alert-error">Sync failed. Check logs below.</div>';
        }
        
        echo '<pre>' . htmlspecialchars(implode("\n", $output)) . '</pre>';
    } elseif ($_POST['action'] === 'test_ssh') {
        // Test SSH connection
        $host = $config['destination_host'];
        $user = $config['ssh_user'];
        
        $output = [];
        exec("ssh -o BatchMode=yes -o ConnectTimeout=5 $user@$host 'echo Connection successful' 2>&1", $output, $return_var);
        
        if ($return_var === 0) {
            echo '<div class="alert alert-success">SSH connection successful!</div>';
        } else {
            echo '<div class="alert alert-error">SSH connection failed. Setup SSH key first.</div>';
        }
        
        echo '<pre>' . htmlspecialchars(implode("\n", $output)) . '</pre>';
    }
}

// Get sync status
$syncStatus = 'Stopped';
$lsyncdRunning = false;
exec("systemctl is-active lsyncd 2>&1", $output, $return_var);
if ($return_var === 0) {
    $syncStatus = 'Running';
    $lsyncdRunning = true;
}

// Get file count
$fileCount = 0;
if (is_dir($config['source_path'])) {
    $files = glob($config['source_path'] . '/*.mp3');
    $fileCount = count($files);
}

?>

<h2>LOF Audio File Sync</h2>
<p>Automatically syncs audio files from FPP to your WordPress server for streaming.</p>

<!-- Status Panel -->
<div class="card">
    <div class="card-header">
        <h3>Sync Status</h3>
    </div>
    <div class="card-body">
        <p><strong>Service Status:</strong> 
            <span class="badge badge-<?php echo $lsyncdRunning ? 'success' : 'secondary'; ?>">
                <?php echo $syncStatus; ?>
            </span>
        </p>
        <p><strong>Audio Files in FPP:</strong> <?php echo $fileCount; ?> MP3 files</p>
        <p><strong>Source Directory:</strong> <code><?php echo htmlspecialchars($config['source_path']); ?></code></p>
        <?php if ($config['enabled'] && !empty($config['destination_host'])): ?>
        <p><strong>Destination:</strong> <code><?php echo htmlspecialchars($config['ssh_user'] . '@' . $config['destination_host'] . ':' . $config['destination_path']); ?></code></p>
        <?php endif; ?>
    </div>
</div>

<!-- Configuration Form -->
<div class="card mt-3">
    <div class="card-header">
        <h3>Configuration</h3>
    </div>
    <div class="card-body">
        <form method="post">
            <input type="hidden" name="action" value="save_config">
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="enabled" value="1" <?php echo $config['enabled'] ? 'checked' : ''; ?>>
                    Enable automatic sync
                </label>
                <p class="help-text">When enabled, audio files sync automatically when changed</p>
            </div>
            
            <div class="form-group">
                <label for="destination_host">WordPress Server IP/Hostname:</label>
                <input type="text" 
                       id="destination_host" 
                       name="destination_host" 
                       value="<?php echo htmlspecialchars($config['destination_host']); ?>" 
                       class="form-control"
                       placeholder="192.168.1.100"
                       required>
            </div>
            
            <div class="form-group">
                <label for="destination_path">Destination Path:</label>
                <input type="text" 
                       id="destination_path" 
                       name="destination_path" 
                       value="<?php echo htmlspecialchars($config['destination_path']); ?>" 
                       class="form-control"
                       placeholder="/var/www/lof-audio">
            </div>
            
            <div class="form-group">
                <label for="ssh_user">SSH User:</label>
                <input type="text" 
                       id="ssh_user" 
                       name="ssh_user" 
                       value="<?php echo htmlspecialchars($config['ssh_user']); ?>" 
                       class="form-control"
                       placeholder="www-data">
            </div>
            
            <div class="form-group">
                <label for="sync_delay">Sync Delay (seconds):</label>
                <input type="number" 
                       id="sync_delay" 
                       name="sync_delay" 
                       value="<?php echo $config['sync_delay']; ?>" 
                       min="1" 
                       max="60" 
                       class="form-control">
                <p class="help-text">How long to wait after file change before syncing</p>
            </div>
            
            <div class="form-group">
                <label for="source_path">Source Path (FPP):</label>
                <input type="text" 
                       id="source_path" 
                       name="source_path" 
                       value="<?php echo htmlspecialchars($config['source_path']); ?>" 
                       class="form-control"
                       placeholder="/home/fpp/media/music">
            </div>
            
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </form>
    </div>
</div>

<!-- Manual Actions -->
<div class="card mt-3">
    <div class="card-header">
        <h3>Manual Actions</h3>
    </div>
    <div class="card-body">
        <form method="post" style="display:inline;">
            <input type="hidden" name="action" value="test_ssh">
            <button type="submit" class="btn btn-secondary">Test SSH Connection</button>
        </form>
        
        <form method="post" style="display:inline;">
            <input type="hidden" name="action" value="sync_now">
            <button type="submit" class="btn btn-warning">Sync Now (Manual)</button>
        </form>
        
        <p class="help-text mt-2">
            <strong>Note:</strong> Before enabling automatic sync, you must set up SSH key authentication. 
            See the setup guide below.
        </p>
    </div>
</div>

<!-- Setup Instructions -->
<div class="card mt-3">
    <div class="card-header">
        <h3>SSH Key Setup Instructions</h3>
    </div>
    <div class="card-body">
        <p>For automatic sync to work, FPP needs passwordless SSH access to the WordPress server.</p>
        
        <h4>Step 1: Generate SSH Key (if not exists)</h4>
        <pre>ssh-keygen -t rsa -b 4096 -N "" -f ~/.ssh/id_rsa</pre>
        
        <h4>Step 2: Copy Key to WordPress Server</h4>
        <pre>ssh-copy-id <?php echo htmlspecialchars($config['ssh_user']); ?>@<?php echo htmlspecialchars($config['destination_host'] ?: 'WORDPRESS_IP'); ?></pre>
        
        <h4>Step 3: Test Connection</h4>
        <pre>ssh <?php echo htmlspecialchars($config['ssh_user']); ?>@<?php echo htmlspecialchars($config['destination_host'] ?: 'WORDPRESS_IP'); ?> "echo 'Success'"</pre>
        
        <p class="mt-2">Once this works without password prompt, you can enable automatic sync above.</p>
    </div>
</div>

<!-- Logs -->
<div class="card mt-3">
    <div class="card-header">
        <h3>Sync Log (Last 50 Lines)</h3>
    </div>
    <div class="card-body">
        <pre style="max-height: 300px; overflow-y: scroll;"><?php
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
</div>

<style>
.card {
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 15px;
}
.card-header {
    background: #f5f5f5;
    padding: 10px 15px;
    border-bottom: 1px solid #ddd;
}
.card-header h3 {
    margin: 0;
    font-size: 18px;
}
.card-body {
    padding: 15px;
}
.form-group {
    margin-bottom: 15px;
}
.form-group label {
    display: block;
    font-weight: bold;
    margin-bottom: 5px;
}
.form-control {
    width: 100%;
    max-width: 500px;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}
.help-text {
    font-size: 12px;
    color: #666;
    margin: 5px 0 0 0;
}
.btn {
    padding: 8px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    margin-right: 10px;
}
.btn-primary {
    background: #007bff;
    color: white;
}
.btn-secondary {
    background: #6c757d;
    color: white;
}
.btn-warning {
    background: #ffc107;
    color: black;
}
.badge {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
}
.badge-success {
    background: #28a745;
    color: white;
}
.badge-secondary {
    background: #6c757d;
    color: white;
}
.alert {
    padding: 12px;
    border-radius: 4px;
    margin-bottom: 15px;
}
.alert-success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}
.alert-error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}
</style>
