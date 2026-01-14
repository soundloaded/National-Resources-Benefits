<?php
/**
 * Laravel Production Deployment & Management Script
 * 
 * This script allows running artisan, composer, and npm commands
 * from a web interface when terminal access is not available.
 * 
 * SECURITY: Change the secret key below before deploying!
 * Access: yoursite.com/deploy.php?key=YOUR_SECRET_KEY
 */

// ============================================
// CONFIGURATION - CHANGE THESE VALUES!
// ============================================
$config = [
    // Secret key for authentication (CHANGE THIS!)
    'secret_key' => 'NRB_DEPLOY_2026_CHANGE_THIS_KEY',
    
    // Allowed IPs (empty array = allow all with correct key)
    'allowed_ips' => [],
    
    // PHP binary path (adjust if needed)
    'php_binary' => 'php',
    
    // Composer path (adjust if needed)  
    'composer_path' => 'composer',
    
    // NPM path (adjust if needed)
    'npm_path' => 'npm',
    
    // Node path (adjust if needed)
    'node_path' => 'node',
    
    // Base path (Laravel root directory)
    'base_path' => dirname(__DIR__),
    
    // Maximum execution time (seconds)
    'max_execution_time' => 300,
    
    // Enable dangerous commands (migrate:fresh, db:wipe, etc.)
    'allow_dangerous' => false,
];

// ============================================
// SECURITY CHECKS
// ============================================
set_time_limit($config['max_execution_time']);
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Check secret key
$providedKey = $_GET['key'] ?? $_POST['key'] ?? '';
if ($providedKey !== $config['secret_key']) {
    http_response_code(403);
    die('Access Denied');
}

// Check IP whitelist
if (!empty($config['allowed_ips']) && !in_array($_SERVER['REMOTE_ADDR'], $config['allowed_ips'])) {
    http_response_code(403);
    die('IP Not Allowed');
}

// ============================================
// PRESET COMMANDS
// ============================================
$presets = [
    'setup' => [
        'name' => '🚀 Initial Setup',
        'description' => 'Run initial setup (composer install, npm install, migrations)',
        'commands' => [
            ['type' => 'composer', 'cmd' => 'install --no-dev --optimize-autoloader'],
            ['type' => 'npm', 'cmd' => 'install'],
            ['type' => 'npm', 'cmd' => 'run build'],
            ['type' => 'artisan', 'cmd' => 'migrate --force'],
            ['type' => 'artisan', 'cmd' => 'storage:link'],
            ['type' => 'artisan', 'cmd' => 'config:cache'],
            ['type' => 'artisan', 'cmd' => 'route:cache'],
            ['type' => 'artisan', 'cmd' => 'view:cache'],
        ],
    ],
    'deploy' => [
        'name' => '📦 Deploy Update',
        'description' => 'Standard deployment after git pull',
        'commands' => [
            ['type' => 'composer', 'cmd' => 'install --no-dev --optimize-autoloader'],
            ['type' => 'npm', 'cmd' => 'install'],
            ['type' => 'npm', 'cmd' => 'run build'],
            ['type' => 'artisan', 'cmd' => 'migrate --force'],
            ['type' => 'artisan', 'cmd' => 'config:cache'],
            ['type' => 'artisan', 'cmd' => 'route:cache'],
            ['type' => 'artisan', 'cmd' => 'view:cache'],
        ],
    ],
    'cache_clear' => [
        'name' => '🧹 Clear All Caches',
        'description' => 'Clear all application caches',
        'commands' => [
            ['type' => 'artisan', 'cmd' => 'config:clear'],
            ['type' => 'artisan', 'cmd' => 'route:clear'],
            ['type' => 'artisan', 'cmd' => 'view:clear'],
            ['type' => 'artisan', 'cmd' => 'cache:clear'],
            ['type' => 'artisan', 'cmd' => 'event:clear'],
        ],
    ],
    'optimize' => [
        'name' => '⚡ Optimize for Production',
        'description' => 'Cache config, routes, and views',
        'commands' => [
            ['type' => 'artisan', 'cmd' => 'config:cache'],
            ['type' => 'artisan', 'cmd' => 'route:cache'],
            ['type' => 'artisan', 'cmd' => 'view:cache'],
            ['type' => 'artisan', 'cmd' => 'event:cache'],
        ],
    ],
    'maintenance_on' => [
        'name' => '🔒 Maintenance Mode ON',
        'description' => 'Put application in maintenance mode',
        'commands' => [
            ['type' => 'artisan', 'cmd' => 'down --retry=60'],
        ],
    ],
    'maintenance_off' => [
        'name' => '🔓 Maintenance Mode OFF',
        'description' => 'Bring application back online',
        'commands' => [
            ['type' => 'artisan', 'cmd' => 'up'],
        ],
    ],
    'db_seed' => [
        'name' => '🌱 Run Seeders',
        'description' => 'Run database seeders',
        'commands' => [
            ['type' => 'artisan', 'cmd' => 'db:seed --force'],
        ],
    ],
    'queue_restart' => [
        'name' => '🔄 Restart Queue Workers',
        'description' => 'Signal queue workers to restart',
        'commands' => [
            ['type' => 'artisan', 'cmd' => 'queue:restart'],
        ],
    ],
];

// Dangerous commands (only if enabled)
if ($config['allow_dangerous']) {
    $presets['fresh_migrate'] = [
        'name' => '⚠️ Fresh Migration (DANGEROUS)',
        'description' => 'Drop all tables and re-run migrations',
        'commands' => [
            ['type' => 'artisan', 'cmd' => 'migrate:fresh --force'],
        ],
    ];
}

// ============================================
// COMMAND EXECUTION
// ============================================
function runCommand($type, $cmd, $config) {
    $basePath = $config['base_path'];
    
    switch ($type) {
        case 'artisan':
            $fullCmd = sprintf(
                'cd %s && %s artisan %s 2>&1',
                escapeshellarg($basePath),
                $config['php_binary'],
                $cmd
            );
            break;
        case 'composer':
            $fullCmd = sprintf(
                'cd %s && %s %s 2>&1',
                escapeshellarg($basePath),
                $config['composer_path'],
                $cmd
            );
            break;
        case 'npm':
            $fullCmd = sprintf(
                'cd %s && %s %s 2>&1',
                escapeshellarg($basePath),
                $config['npm_path'],
                $cmd
            );
            break;
        case 'node':
            $fullCmd = sprintf(
                'cd %s && %s %s 2>&1',
                escapeshellarg($basePath),
                $config['node_path'],
                $cmd
            );
            break;
        case 'shell':
            $fullCmd = sprintf(
                'cd %s && %s 2>&1',
                escapeshellarg($basePath),
                $cmd
            );
            break;
        default:
            return ['success' => false, 'output' => 'Unknown command type'];
    }
    
    $output = [];
    $returnCode = 0;
    exec($fullCmd, $output, $returnCode);
    
    return [
        'success' => $returnCode === 0,
        'output' => implode("\n", $output),
        'command' => $fullCmd,
        'return_code' => $returnCode,
    ];
}

// ============================================
// HANDLE REQUESTS
// ============================================
$results = [];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'run_preset' && isset($_POST['preset'])) {
    $presetKey = $_POST['preset'];
    if (isset($presets[$presetKey])) {
        foreach ($presets[$presetKey]['commands'] as $command) {
            $result = runCommand($command['type'], $command['cmd'], $config);
            $result['type'] = $command['type'];
            $result['cmd'] = $command['cmd'];
            $results[] = $result;
        }
    }
}

if ($action === 'run_custom' && isset($_POST['type']) && isset($_POST['command'])) {
    $type = $_POST['type'];
    $cmd = $_POST['command'];
    
    // Block dangerous commands if not enabled
    $dangerousPatterns = ['migrate:fresh', 'db:wipe', 'migrate:reset', 'migrate:rollback'];
    $isDangerous = false;
    foreach ($dangerousPatterns as $pattern) {
        if (stripos($cmd, $pattern) !== false) {
            $isDangerous = true;
            break;
        }
    }
    
    if ($isDangerous && !$config['allow_dangerous']) {
        $results[] = [
            'success' => false,
            'output' => 'Dangerous command blocked. Enable allow_dangerous in config to use this.',
            'type' => $type,
            'cmd' => $cmd,
        ];
    } else {
        $result = runCommand($type, $cmd, $config);
        $result['type'] = $type;
        $result['cmd'] = $cmd;
        $results[] = $result;
    }
}

if ($action === 'phpinfo') {
    phpinfo();
    exit;
}

if ($action === 'check_paths') {
    $checks = [
        'PHP Version' => PHP_VERSION,
        'Base Path' => $config['base_path'],
        'Base Path Exists' => is_dir($config['base_path']) ? 'Yes' : 'No',
        'Artisan Exists' => file_exists($config['base_path'] . '/artisan') ? 'Yes' : 'No',
        'Composer.json Exists' => file_exists($config['base_path'] . '/composer.json') ? 'Yes' : 'No',
        'Package.json Exists' => file_exists($config['base_path'] . '/package.json') ? 'Yes' : 'No',
        '.env Exists' => file_exists($config['base_path'] . '/.env') ? 'Yes' : 'No',
        'Storage Writable' => is_writable($config['base_path'] . '/storage') ? 'Yes' : 'No',
        'Bootstrap/Cache Writable' => is_writable($config['base_path'] . '/bootstrap/cache') ? 'Yes' : 'No',
    ];
    
    // Check binaries
    exec('which php 2>&1', $phpPath);
    exec('which composer 2>&1', $composerPath);
    exec('which npm 2>&1', $npmPath);
    exec('which node 2>&1', $nodePath);
    
    $checks['PHP Path'] = $phpPath[0] ?? 'Not found';
    $checks['Composer Path'] = $composerPath[0] ?? 'Not found';
    $checks['NPM Path'] = $npmPath[0] ?? 'Not found';
    $checks['Node Path'] = $nodePath[0] ?? 'Not found';
    
    $results[] = [
        'success' => true,
        'output' => print_r($checks, true),
        'type' => 'system',
        'cmd' => 'Path Check',
    ];
}

// ============================================
// HTML OUTPUT
// ============================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Deployment Manager</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            color: #e4e4e7;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { 
            text-align: center; 
            margin-bottom: 30px; 
            color: #22c55e;
            font-size: 2rem;
        }
        .warning-banner {
            background: #7c2d12;
            border: 1px solid #ea580c;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card:hover { 
            transform: translateY(-2px);
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        .card h3 { color: #22c55e; margin-bottom: 10px; }
        .card p { color: #a1a1aa; font-size: 0.9rem; margin-bottom: 15px; }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #22c55e;
            color: #000;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s;
            text-decoration: none;
        }
        .btn:hover { background: #16a34a; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-secondary { background: #3b82f6; color: #fff; }
        .btn-secondary:hover { background: #2563eb; }
        .btn-warning { background: #f59e0b; color: #000; }
        .btn-warning:hover { background: #d97706; }
        .section { margin-bottom: 30px; }
        .section h2 { 
            color: #e4e4e7; 
            margin-bottom: 15px; 
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #a1a1aa; }
        .form-group select, .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px;
            background: rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            color: #e4e4e7;
            font-size: 1rem;
        }
        .form-group textarea { min-height: 100px; font-family: monospace; }
        .results {
            background: rgba(0,0,0,0.4);
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
        }
        .result-item {
            background: rgba(0,0,0,0.3);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #22c55e;
        }
        .result-item.error { border-left-color: #dc2626; }
        .result-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            margin-bottom: 10px;
        }
        .result-type { 
            background: #22c55e; 
            color: #000; 
            padding: 4px 12px; 
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .result-item.error .result-type { background: #dc2626; color: #fff; }
        .result-output {
            background: rgba(0,0,0,0.5);
            padding: 15px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.85rem;
            white-space: pre-wrap;
            word-break: break-all;
            max-height: 300px;
            overflow-y: auto;
        }
        .quick-links { 
            display: flex; 
            gap: 10px; 
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-success { background: #22c55e; color: #000; }
        .status-error { background: #dc2626; color: #fff; }
        form { display: inline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Laravel Deployment Manager</h1>
        
        <div class="warning-banner">
            ⚠️ <strong>Security Warning:</strong> Delete or rename this file after use. Keep your secret key safe!
        </div>
        
        <div class="quick-links">
            <a href="?key=<?= htmlspecialchars($providedKey) ?>&action=check_paths" class="btn btn-secondary">🔍 Check Paths</a>
            <a href="?key=<?= htmlspecialchars($providedKey) ?>&action=phpinfo" class="btn btn-secondary" target="_blank">📋 PHP Info</a>
        </div>
        
        <?php if (!empty($results)): ?>
        <div class="results">
            <h2>📊 Execution Results</h2>
            <?php foreach ($results as $result): ?>
            <div class="result-item <?= $result['success'] ? '' : 'error' ?>">
                <div class="result-header">
                    <span class="result-type"><?= strtoupper(htmlspecialchars($result['type'])) ?></span>
                    <span class="status-badge <?= $result['success'] ? 'status-success' : 'status-error' ?>">
                        <?= $result['success'] ? '✓ Success' : '✗ Failed' ?>
                    </span>
                </div>
                <p><strong>Command:</strong> <?= htmlspecialchars($result['cmd']) ?></p>
                <div class="result-output"><?= htmlspecialchars($result['output'] ?: 'No output') ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div class="section">
            <h2>⚡ Quick Actions</h2>
            <div class="grid">
                <?php foreach ($presets as $key => $preset): ?>
                <div class="card">
                    <h3><?= $preset['name'] ?></h3>
                    <p><?= $preset['description'] ?></p>
                    <form method="POST">
                        <input type="hidden" name="key" value="<?= htmlspecialchars($providedKey) ?>">
                        <input type="hidden" name="action" value="run_preset">
                        <input type="hidden" name="preset" value="<?= $key ?>">
                        <button type="submit" class="btn <?= strpos($preset['name'], 'DANGEROUS') !== false ? 'btn-danger' : '' ?>" 
                                onclick="return confirm('Run <?= addslashes($preset['name']) ?>?')">
                            Run
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="section">
            <h2>🛠️ Custom Command</h2>
            <div class="card">
                <form method="POST">
                    <input type="hidden" name="key" value="<?= htmlspecialchars($providedKey) ?>">
                    <input type="hidden" name="action" value="run_custom">
                    
                    <div class="form-group">
                        <label for="type">Command Type</label>
                        <select name="type" id="type">
                            <option value="artisan">Artisan (php artisan ...)</option>
                            <option value="composer">Composer (composer ...)</option>
                            <option value="npm">NPM (npm ...)</option>
                            <option value="node">Node (node ...)</option>
                            <option value="shell">Shell (direct command)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="command">Command (without prefix)</label>
                        <input type="text" name="command" id="command" placeholder="e.g., migrate:status" required>
                    </div>
                    
                    <button type="submit" class="btn btn-warning" onclick="return confirm('Run this custom command?')">
                        Execute Command
                    </button>
                </form>
            </div>
        </div>
        
        <div class="section">
            <h2>📋 Common Commands Reference</h2>
            <div class="card">
                <h3>Artisan Commands</h3>
                <div class="result-output">
migrate:status          - Check migration status
migrate --force         - Run pending migrations
db:seed --force         - Run seeders
queue:work              - Start queue worker
queue:restart           - Restart queue workers
schedule:run            - Run scheduled tasks
storage:link            - Create storage symlink
key:generate            - Generate app key
config:cache            - Cache configuration
route:cache             - Cache routes
view:cache              - Cache views
optimize                - Cache config, routes, events
optimize:clear          - Clear all caches
tinker                  - Interactive shell
                </div>
                
                <h3 style="margin-top: 20px;">Composer Commands</h3>
                <div class="result-output">
install                 - Install dependencies
install --no-dev        - Install without dev dependencies
update                  - Update dependencies
dump-autoload           - Regenerate autoloader
                </div>
                
                <h3 style="margin-top: 20px;">NPM Commands</h3>
                <div class="result-output">
install                 - Install node modules
run build               - Build for production
run dev                 - Build for development
                </div>
            </div>
        </div>
        
        <div style="text-align: center; color: #71717a; margin-top: 40px; padding: 20px;">
            <p>Laravel Deployment Manager v1.0</p>
            <p style="margin-top: 10px; font-size: 0.9rem;">
                🔒 Remember to delete this file or restrict access after deployment!
            </p>
        </div>
    </div>
</body>
</html>
