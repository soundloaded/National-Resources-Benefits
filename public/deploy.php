<?php
/**
 * Laravel Production Deployment & Management Script (Standalone)
 * 
 * This script is COMPLETELY STANDALONE - no vendor or node_modules required.
 * It can bootstrap a fresh Laravel installation from scratch.
 * 
 * SECURITY: Change the secret key below before deploying!
 * Access: yoursite.com/deploy.php?key=YOUR_SECRET_KEY
 */

// ============================================
// CONFIGURATION - CHANGE THESE VALUES!
// ============================================
$config = [
    // Secret key for authentication (CHANGE THIS!)
    'secret_key' => '123456',
    
    // Allowed IPs (empty array = allow all with correct key)
    'allowed_ips' => [],
    
    // PHP binary path (try multiple common paths)
    'php_paths' => ['php', '/usr/bin/php', '/usr/local/bin/php', '/opt/cpanel/ea-php82/root/usr/bin/php', '/opt/cpanel/ea-php81/root/usr/bin/php', '/opt/cpanel/ea-php80/root/usr/bin/php'],
    
    // Composer paths to try
    'composer_paths' => ['composer', '/usr/bin/composer', '/usr/local/bin/composer', '~/composer.phar', 'php composer.phar'],
    
    // NPM paths to try
    'npm_paths' => ['npm', '/usr/bin/npm', '/usr/local/bin/npm', '/opt/cpanel/ea-nodejs16/root/usr/bin/npm', '/opt/cpanel/ea-nodejs18/root/usr/bin/npm', '~/.nvm/versions/node/*/bin/npm'],
    
    // Node paths to try
    'node_paths' => ['node', '/usr/bin/node', '/usr/local/bin/node', '/opt/cpanel/ea-nodejs16/root/usr/bin/node', '/opt/cpanel/ea-nodejs18/root/usr/bin/node'],
    
    // Base path (Laravel root directory - parent of public folder)
    'base_path' => dirname(__DIR__),
    
    // Maximum execution time (seconds)
    'max_execution_time' => 600,
    
    // Enable dangerous commands (migrate:fresh, db:wipe, etc.)
    'allow_dangerous' => false,
    
    // Memory limit
    'memory_limit' => '512M',
];

// ============================================
// BOOTSTRAP - NO DEPENDENCIES NEEDED
// ============================================
@ini_set('max_execution_time', $config['max_execution_time']);
@ini_set('memory_limit', $config['memory_limit']);
@set_time_limit($config['max_execution_time']);
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================
// SECURITY CHECKS
// ============================================
$providedKey = $_GET['key'] ?? $_POST['key'] ?? '';
if ($providedKey !== $config['secret_key']) {
    http_response_code(403);
    die('<!DOCTYPE html><html><head><title>Access Denied</title></head><body style="font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;background:#1a1a2e;color:#fff;"><div style="text-align:center;"><h1>🔒 Access Denied</h1><p>Invalid or missing secret key.</p></div></body></html>');
}

// Check IP whitelist
if (!empty($config['allowed_ips']) && !in_array($_SERVER['REMOTE_ADDR'], $config['allowed_ips'])) {
    http_response_code(403);
    die('IP Not Allowed');
}

// ============================================
// HELPER FUNCTIONS
// ============================================
function findBinary($paths, $basePath) {
    foreach ($paths as $path) {
        // Expand home directory
        $path = str_replace('~', getenv('HOME') ?: '/home/' . get_current_user(), $path);
        
        // Handle glob patterns
        if (strpos($path, '*') !== false) {
            $matches = glob($path);
            if (!empty($matches)) {
                $path = $matches[0];
            }
        }
        
        // Check if it's a direct command or a path
        if (strpos($path, '/') === false) {
            // It's a command, check if it exists
            $output = [];
            @exec("which $path 2>/dev/null", $output);
            if (!empty($output[0])) {
                return $path;
            }
        } else {
            // It's a path, check if file exists
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }
    }
    return null;
}

function runCommand($cmd, $basePath, $timeout = 300) {
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    
    $fullCmd = "cd " . escapeshellarg($basePath) . " && $cmd 2>&1";
    
    $process = @proc_open($fullCmd, $descriptors, $pipes);
    
    if (!is_resource($process)) {
        // Fallback to exec
        $output = [];
        $returnCode = 0;
        @exec($fullCmd, $output, $returnCode);
        return [
            'success' => $returnCode === 0,
            'output' => implode("\n", $output),
            'return_code' => $returnCode,
        ];
    }
    
    fclose($pipes[0]);
    
    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    
    $returnCode = proc_close($process);
    
    return [
        'success' => $returnCode === 0,
        'output' => $output . ($error ? "\n$error" : ''),
        'return_code' => $returnCode,
    ];
}

function downloadComposer($basePath) {
    $installerUrl = 'https://getcomposer.org/installer';
    $installerPath = $basePath . '/composer-setup.php';
    $composerPath = $basePath . '/composer.phar';
    
    // Download installer
    $installer = @file_get_contents($installerUrl);
    if ($installer === false) {
        // Try with curl
        $ch = curl_init($installerUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $installer = curl_exec($ch);
        curl_close($ch);
    }
    
    if (empty($installer)) {
        return ['success' => false, 'output' => 'Failed to download Composer installer'];
    }
    
    file_put_contents($installerPath, $installer);
    
    // Run installer
    $result = runCommand("php $installerPath --install-dir=" . escapeshellarg($basePath) . " --filename=composer.phar", $basePath);
    
    // Cleanup
    @unlink($installerPath);
    
    if (file_exists($composerPath)) {
        return ['success' => true, 'output' => "Composer downloaded to $composerPath\n" . $result['output'], 'path' => $composerPath];
    }
    
    return ['success' => false, 'output' => 'Failed to install Composer: ' . $result['output']];
}

function checkEnvironment($config) {
    $basePath = $config['base_path'];
    $checks = [];
    
    // PHP
    $checks['PHP Version'] = PHP_VERSION;
    $checks['PHP SAPI'] = php_sapi_name();
    $checks['Memory Limit'] = ini_get('memory_limit');
    $checks['Max Execution Time'] = ini_get('max_execution_time');
    
    // Paths
    $checks['Base Path'] = $basePath;
    $checks['Base Path Exists'] = is_dir($basePath) ? '✅ Yes' : '❌ No';
    $checks['Base Path Writable'] = is_writable($basePath) ? '✅ Yes' : '❌ No';
    
    // Laravel files
    $checks['artisan'] = file_exists("$basePath/artisan") ? '✅ Yes' : '❌ No';
    $checks['composer.json'] = file_exists("$basePath/composer.json") ? '✅ Yes' : '❌ No';
    $checks['package.json'] = file_exists("$basePath/package.json") ? '✅ Yes' : '❌ No';
    $checks['.env'] = file_exists("$basePath/.env") ? '✅ Yes' : '❌ No';
    $checks['.env.example'] = file_exists("$basePath/.env.example") ? '✅ Yes' : '❌ No';
    
    // Directories
    $checks['vendor/'] = is_dir("$basePath/vendor") ? '✅ Yes' : '❌ No (run composer install)';
    $checks['node_modules/'] = is_dir("$basePath/node_modules") ? '✅ Yes' : '❌ No (run npm install)';
    $checks['public/build/'] = is_dir("$basePath/public/build") ? '✅ Yes' : '❌ No (run npm run build)';
    $checks['storage/'] = is_dir("$basePath/storage") ? '✅ Yes' : '❌ No';
    $checks['storage/ writable'] = is_writable("$basePath/storage") ? '✅ Yes' : '❌ No';
    $checks['bootstrap/cache/'] = is_dir("$basePath/bootstrap/cache") ? '✅ Yes' : '❌ No';
    $checks['bootstrap/cache/ writable'] = is_writable("$basePath/bootstrap/cache") ? '✅ Yes' : '❌ No';
    
    // Find binaries
    $phpBin = findBinary($config['php_paths'], $basePath);
    $composerBin = findBinary($config['composer_paths'], $basePath);
    $npmBin = findBinary($config['npm_paths'], $basePath);
    $nodeBin = findBinary($config['node_paths'], $basePath);
    
    // Check for local composer.phar
    if (!$composerBin && file_exists("$basePath/composer.phar")) {
        $composerBin = "$phpBin $basePath/composer.phar";
    }
    
    $checks['PHP Binary'] = $phpBin ?: '❌ Not found';
    $checks['Composer Binary'] = $composerBin ?: '❌ Not found (click "Download Composer")';
    $checks['NPM Binary'] = $npmBin ?: '❌ Not found';
    $checks['Node Binary'] = $nodeBin ?: '❌ Not found';
    
    // Get versions
    if ($phpBin) {
        $result = runCommand("$phpBin -v | head -1", $basePath);
        $checks['PHP Binary Version'] = trim($result['output']) ?: 'Unknown';
    }
    if ($composerBin) {
        $result = runCommand("$composerBin --version 2>/dev/null | head -1", $basePath);
        $checks['Composer Version'] = trim($result['output']) ?: 'Unknown';
    }
    if ($nodeBin) {
        $result = runCommand("$nodeBin --version 2>/dev/null", $basePath);
        $checks['Node Version'] = trim($result['output']) ?: 'Unknown';
    }
    if ($npmBin) {
        $result = runCommand("$npmBin --version 2>/dev/null", $basePath);
        $checks['NPM Version'] = trim($result['output']) ?: 'Unknown';
    }
    
    return [
        'checks' => $checks,
        'php' => $phpBin,
        'composer' => $composerBin,
        'npm' => $npmBin,
        'node' => $nodeBin,
    ];
}

// ============================================
// GET ENVIRONMENT INFO
// ============================================
$env = checkEnvironment($config);
$phpBin = $env['php'] ?: 'php';
$composerBin = $env['composer'];
$npmBin = $env['npm'];
$nodeBin = $env['node'];
$basePath = $config['base_path'];

// Check for local composer.phar
if (!$composerBin && file_exists("$basePath/composer.phar")) {
    $composerBin = "$phpBin " . escapeshellarg("$basePath/composer.phar");
}

// ============================================
// PRESET COMMANDS
// ============================================
$presets = [
    'env_setup' => [
        'name' => '📋 Setup .env File',
        'description' => 'Copy .env.example to .env if not exists',
        'commands' => [
            ['type' => 'shell', 'cmd' => 'cp -n .env.example .env 2>/dev/null || echo ".env already exists or .env.example not found"'],
        ],
        'requires' => [],
    ],
    'download_composer' => [
        'name' => '📥 Download Composer',
        'description' => 'Download composer.phar to project root',
        'commands' => [
            ['type' => 'download_composer', 'cmd' => ''],
        ],
        'requires' => [],
    ],
    'composer_install' => [
        'name' => '📦 Composer Install',
        'description' => 'Install PHP dependencies (vendor folder)',
        'commands' => [
            ['type' => 'composer', 'cmd' => 'install --no-dev --optimize-autoloader --no-interaction'],
        ],
        'requires' => ['composer'],
    ],
    'npm_install' => [
        'name' => '📦 NPM Install',
        'description' => 'Install Node dependencies (node_modules folder)',
        'commands' => [
            ['type' => 'npm', 'cmd' => 'install'],
        ],
        'requires' => ['npm'],
    ],
    'npm_build' => [
        'name' => '🔨 NPM Build',
        'description' => 'Build frontend assets (public/build folder)',
        'commands' => [
            ['type' => 'npm', 'cmd' => 'run build'],
        ],
        'requires' => ['npm'],
    ],
    'generate_key' => [
        'name' => '🔑 Generate App Key',
        'description' => 'Generate Laravel application key',
        'commands' => [
            ['type' => 'artisan', 'cmd' => 'key:generate --force'],
        ],
        'requires' => ['vendor'],
    ],
    'storage_link' => [
        'name' => '🔗 Storage Link',
        'description' => 'Create public storage symlink',
        'commands' => [
            ['type' => 'artisan', 'cmd' => 'storage:link'],
        ],
        'requires' => ['vendor'],
    ],
    'migrate' => [
        'name' => '🗃️ Run Migrations',
        'description' => 'Run database migrations',
        'commands' => [
            ['type' => 'artisan', 'cmd' => 'migrate --force'],
        ],
        'requires' => ['vendor'],
    ],
    'db_seed' => [
        'name' => '🌱 Run Seeders',
        'description' => 'Seed the database',
        'commands' => [
            ['type' => 'artisan', 'cmd' => 'db:seed --force'],
        ],
        'requires' => ['vendor'],
    ],
    'cache_clear' => [
        'name' => '🧹 Clear All Caches',
        'description' => 'Clear all application caches',
        'commands' => [
            ['type' => 'artisan', 'cmd' => 'config:clear'],
            ['type' => 'artisan', 'cmd' => 'route:clear'],
            ['type' => 'artisan', 'cmd' => 'view:clear'],
            ['type' => 'artisan', 'cmd' => 'cache:clear'],
        ],
        'requires' => ['vendor'],
    ],
    'optimize' => [
        'name' => '⚡ Optimize for Production',
        'description' => 'Cache config, routes, and views',
        'commands' => [
            ['type' => 'artisan', 'cmd' => 'config:cache'],
            ['type' => 'artisan', 'cmd' => 'route:cache'],
            ['type' => 'artisan', 'cmd' => 'view:cache'],
        ],
        'requires' => ['vendor'],
    ],
    'full_setup' => [
        'name' => '🚀 FULL SETUP (Fresh Install)',
        'description' => 'Complete setup: env, composer, npm, build, migrate, optimize',
        'commands' => [
            ['type' => 'shell', 'cmd' => 'cp -n .env.example .env 2>/dev/null || true'],
            ['type' => 'composer', 'cmd' => 'install --no-dev --optimize-autoloader --no-interaction'],
            ['type' => 'artisan', 'cmd' => 'key:generate --force'],
            ['type' => 'npm', 'cmd' => 'install'],
            ['type' => 'npm', 'cmd' => 'run build'],
            ['type' => 'artisan', 'cmd' => 'storage:link'],
            ['type' => 'artisan', 'cmd' => 'migrate --force'],
            ['type' => 'artisan', 'cmd' => 'config:cache'],
            ['type' => 'artisan', 'cmd' => 'route:cache'],
            ['type' => 'artisan', 'cmd' => 'view:cache'],
        ],
        'requires' => ['composer', 'npm'],
    ],
    'deploy_update' => [
        'name' => '📦 Deploy Update',
        'description' => 'Standard deployment after code update',
        'commands' => [
            ['type' => 'composer', 'cmd' => 'install --no-dev --optimize-autoloader --no-interaction'],
            ['type' => 'npm', 'cmd' => 'install'],
            ['type' => 'npm', 'cmd' => 'run build'],
            ['type' => 'artisan', 'cmd' => 'migrate --force'],
            ['type' => 'artisan', 'cmd' => 'config:cache'],
            ['type' => 'artisan', 'cmd' => 'route:cache'],
            ['type' => 'artisan', 'cmd' => 'view:cache'],
        ],
        'requires' => ['vendor', 'composer', 'npm'],
    ],
    'maintenance_on' => [
        'name' => '🔒 Maintenance ON',
        'description' => 'Put site in maintenance mode',
        'commands' => [
            ['type' => 'artisan', 'cmd' => 'down --retry=60'],
        ],
        'requires' => ['vendor'],
    ],
    'maintenance_off' => [
        'name' => '🔓 Maintenance OFF',
        'description' => 'Bring site back online',
        'commands' => [
            ['type' => 'artisan', 'cmd' => 'up'],
        ],
        'requires' => ['vendor'],
    ],
    'permissions' => [
        'name' => '🔐 Fix Permissions',
        'description' => 'Set correct permissions on storage and cache',
        'commands' => [
            ['type' => 'shell', 'cmd' => 'chmod -R 775 storage bootstrap/cache 2>/dev/null || echo "Could not change permissions"'],
            ['type' => 'shell', 'cmd' => 'mkdir -p storage/framework/{sessions,views,cache} 2>/dev/null || true'],
            ['type' => 'shell', 'cmd' => 'mkdir -p storage/logs 2>/dev/null || true'],
        ],
        'requires' => [],
    ],
];

// ============================================
// COMMAND EXECUTION
// ============================================
function executeCommand($type, $cmd, $config, $env) {
    $basePath = $config['base_path'];
    $phpBin = $env['php'] ?: 'php';
    $composerBin = $env['composer'];
    $npmBin = $env['npm'];
    $nodeBin = $env['node'];
    
    // Check for local composer.phar
    if (!$composerBin && file_exists("$basePath/composer.phar")) {
        $composerBin = "$phpBin " . escapeshellarg("$basePath/composer.phar");
    }
    
    switch ($type) {
        case 'artisan':
            if (!file_exists("$basePath/vendor/autoload.php")) {
                return ['success' => false, 'output' => 'Vendor not installed. Run Composer Install first.'];
            }
            $fullCmd = "$phpBin artisan $cmd";
            break;
            
        case 'composer':
            if (!$composerBin) {
                return ['success' => false, 'output' => 'Composer not found. Click "Download Composer" first.'];
            }
            $fullCmd = "$composerBin $cmd";
            break;
            
        case 'npm':
            if (!$npmBin) {
                return ['success' => false, 'output' => 'NPM not found on this server. You may need to build assets locally and upload.'];
            }
            $fullCmd = "$npmBin $cmd";
            break;
            
        case 'node':
            if (!$nodeBin) {
                return ['success' => false, 'output' => 'Node not found on this server.'];
            }
            $fullCmd = "$nodeBin $cmd";
            break;
            
        case 'shell':
            $fullCmd = $cmd;
            break;
            
        case 'download_composer':
            return downloadComposer($basePath);
            
        default:
            return ['success' => false, 'output' => 'Unknown command type'];
    }
    
    return runCommand($fullCmd, $basePath, $config['max_execution_time']);
}

// ============================================
// HANDLE REQUESTS
// ============================================
$results = [];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'run_preset' && isset($_POST['preset'])) {
    $presetKey = $_POST['preset'];
    if (isset($presets[$presetKey])) {
        $preset = $presets[$presetKey];
        
        // Check requirements
        $canRun = true;
        $missingReqs = [];
        
        foreach ($preset['requires'] as $req) {
            if ($req === 'vendor' && !is_dir("$basePath/vendor")) {
                $canRun = false;
                $missingReqs[] = 'vendor (run Composer Install)';
            }
            if ($req === 'composer' && !$composerBin && !file_exists("$basePath/composer.phar")) {
                $canRun = false;
                $missingReqs[] = 'composer (click Download Composer)';
            }
            if ($req === 'npm' && !$npmBin) {
                $canRun = false;
                $missingReqs[] = 'npm';
            }
        }
        
        if (!$canRun) {
            $results[] = [
                'success' => false,
                'output' => 'Missing requirements: ' . implode(', ', $missingReqs),
                'type' => 'check',
                'cmd' => $preset['name'],
            ];
        } else {
            foreach ($preset['commands'] as $command) {
                // Re-check environment for each command (composer might have been installed)
                $env = checkEnvironment($config);
                $result = executeCommand($command['type'], $command['cmd'], $config, $env);
                $result['type'] = $command['type'];
                $result['cmd'] = $command['cmd'];
                $results[] = $result;
                
                // Stop on error for critical commands
                if (!$result['success'] && in_array($command['type'], ['composer', 'artisan'])) {
                    break;
                }
            }
        }
    }
}

if ($action === 'run_custom' && isset($_POST['type']) && isset($_POST['command'])) {
    $type = $_POST['type'];
    $cmd = trim($_POST['command']);
    
    // Block dangerous commands
    $dangerousPatterns = ['migrate:fresh', 'db:wipe', 'migrate:reset', 'rm -rf', 'rmdir'];
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
            'output' => 'Dangerous command blocked.',
            'type' => $type,
            'cmd' => $cmd,
        ];
    } else {
        $result = executeCommand($type, $cmd, $config, $env);
        $result['type'] = $type;
        $result['cmd'] = $cmd;
        $results[] = $result;
    }
}

if ($action === 'check_env') {
    $env = checkEnvironment($config);
    $output = "=== Environment Check ===\n\n";
    foreach ($env['checks'] as $key => $value) {
        $output .= str_pad($key, 30) . ": $value\n";
    }
    $results[] = [
        'success' => true,
        'output' => $output,
        'type' => 'system',
        'cmd' => 'Environment Check',
    ];
}

if ($action === 'phpinfo') {
    phpinfo();
    exit;
}

// Refresh environment after commands
$env = checkEnvironment($config);
$hasVendor = is_dir("$basePath/vendor");
$hasNodeModules = is_dir("$basePath/node_modules");
$hasBuild = is_dir("$basePath/public/build");
$hasEnv = file_exists("$basePath/.env");
$hasComposer = $env['composer'] || file_exists("$basePath/composer.phar");

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
        .container { max-width: 1400px; margin: 0 auto; }
        h1 { text-align: center; margin-bottom: 10px; color: #22c55e; font-size: 2rem; }
        .subtitle { text-align: center; color: #71717a; margin-bottom: 30px; }
        
        .status-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-bottom: 20px;
            padding: 15px;
            background: rgba(0,0,0,0.3);
            border-radius: 12px;
        }
        .status-item {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-ok { background: #22c55e; color: #000; }
        .status-missing { background: #dc2626; color: #fff; }
        .status-warn { background: #f59e0b; color: #000; }
        
        .warning-banner {
            background: #7c2d12;
            border: 1px solid #ea580c;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .quick-links { 
            display: flex; 
            gap: 10px; 
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px; margin-bottom: 30px; }
        
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
        .card h3 { color: #22c55e; margin-bottom: 8px; font-size: 1rem; }
        .card p { color: #a1a1aa; font-size: 0.85rem; margin-bottom: 15px; }
        .card.disabled { opacity: 0.5; }
        .card.highlight { border-color: #22c55e; background: rgba(34, 197, 94, 0.1); }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #22c55e;
            color: #000;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: background 0.2s;
            text-decoration: none;
        }
        .btn:hover { background: #16a34a; }
        .btn:disabled { background: #374151; color: #9ca3af; cursor: not-allowed; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-secondary { background: #3b82f6; color: #fff; }
        .btn-secondary:hover { background: #2563eb; }
        .btn-warning { background: #f59e0b; color: #000; }
        .btn-warning:hover { background: #d97706; }
        .btn-sm { padding: 6px 12px; font-size: 0.8rem; }
        
        .section { margin-bottom: 30px; }
        .section h2 { 
            color: #e4e4e7; 
            margin-bottom: 15px; 
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            font-size: 1.2rem;
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
            flex-wrap: wrap;
            gap: 10px;
        }
        .result-type { 
            background: #22c55e; 
            color: #000; 
            padding: 4px 12px; 
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .result-item.error .result-type { background: #dc2626; color: #fff; }
        .result-output {
            background: rgba(0,0,0,0.5);
            padding: 15px;
            border-radius: 6px;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 0.8rem;
            white-space: pre-wrap;
            word-break: break-all;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .step-number {
            display: inline-block;
            width: 24px;
            height: 24px;
            background: #22c55e;
            color: #000;
            border-radius: 50%;
            text-align: center;
            line-height: 24px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-right: 8px;
        }
        
        form { display: inline; }
        
        @media (max-width: 768px) {
            .grid { grid-template-columns: 1fr; }
            h1 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Laravel Deployment Manager</h1>
        <p class="subtitle">Standalone deployment tool - No vendor required</p>
        
        <!-- Status Bar -->
        <div class="status-bar">
            <span class="status-item <?= $hasEnv ? 'status-ok' : 'status-missing' ?>"><?= $hasEnv ? '✓' : '✗' ?> .env</span>
            <span class="status-item <?= $hasComposer ? 'status-ok' : 'status-missing' ?>"><?= $hasComposer ? '✓' : '✗' ?> Composer</span>
            <span class="status-item <?= $hasVendor ? 'status-ok' : 'status-missing' ?>"><?= $hasVendor ? '✓' : '✗' ?> vendor/</span>
            <span class="status-item <?= $npmBin ? 'status-ok' : 'status-warn' ?>"><?= $npmBin ? '✓' : '⚠' ?> NPM</span>
            <span class="status-item <?= $hasNodeModules ? 'status-ok' : 'status-missing' ?>"><?= $hasNodeModules ? '✓' : '✗' ?> node_modules/</span>
            <span class="status-item <?= $hasBuild ? 'status-ok' : 'status-missing' ?>"><?= $hasBuild ? '✓' : '✗' ?> public/build/</span>
        </div>
        
        <div class="warning-banner">
            ⚠️ <strong>Security:</strong> Delete this file after deployment or change the secret key!
        </div>
        
        <div class="quick-links">
            <a href="?key=<?= htmlspecialchars($providedKey) ?>&action=check_env" class="btn btn-secondary btn-sm">🔍 Check Environment</a>
            <a href="?key=<?= htmlspecialchars($providedKey) ?>&action=phpinfo" class="btn btn-secondary btn-sm" target="_blank">📋 PHP Info</a>
        </div>
        
        <?php if (!empty($results)): ?>
        <div class="results">
            <h2>📊 Execution Results</h2>
            <?php foreach ($results as $result): ?>
            <div class="result-item <?= $result['success'] ? '' : 'error' ?>">
                <div class="result-header">
                    <div>
                        <span class="result-type"><?= strtoupper(htmlspecialchars($result['type'])) ?></span>
                        <strong style="margin-left: 10px;"><?= htmlspecialchars($result['cmd']) ?></strong>
                    </div>
                    <span><?= $result['success'] ? '✅ Success' : '❌ Failed' ?></span>
                </div>
                <div class="result-output"><?= htmlspecialchars($result['output'] ?: 'No output') ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Step by Step Guide for Fresh Install -->
        <?php if (!$hasVendor): ?>
        <div class="section">
            <h2>📋 Fresh Install Steps</h2>
            <div class="grid">
                <div class="card highlight">
                    <h3><span class="step-number">1</span> Setup Environment</h3>
                    <p>Copy .env.example to .env</p>
                    <form method="POST">
                        <input type="hidden" name="key" value="<?= htmlspecialchars($providedKey) ?>">
                        <input type="hidden" name="action" value="run_preset">
                        <input type="hidden" name="preset" value="env_setup">
                        <button type="submit" class="btn">Run</button>
                    </form>
                </div>
                
                <?php if (!$hasComposer): ?>
                <div class="card highlight">
                    <h3><span class="step-number">2</span> Download Composer</h3>
                    <p>Get composer.phar for this project</p>
                    <form method="POST">
                        <input type="hidden" name="key" value="<?= htmlspecialchars($providedKey) ?>">
                        <input type="hidden" name="action" value="run_preset">
                        <input type="hidden" name="preset" value="download_composer">
                        <button type="submit" class="btn">Download</button>
                    </form>
                </div>
                <?php endif; ?>
                
                <div class="card <?= $hasComposer ? 'highlight' : 'disabled' ?>">
                    <h3><span class="step-number"><?= $hasComposer ? '2' : '3' ?></span> Composer Install</h3>
                    <p>Install PHP dependencies</p>
                    <form method="POST">
                        <input type="hidden" name="key" value="<?= htmlspecialchars($providedKey) ?>">
                        <input type="hidden" name="action" value="run_preset">
                        <input type="hidden" name="preset" value="composer_install">
                        <button type="submit" class="btn" <?= !$hasComposer ? 'disabled' : '' ?>>Run</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- All Actions -->
        <div class="section">
            <h2>⚡ Available Actions</h2>
            <div class="grid">
                <?php 
                $orderedPresets = [
                    'env_setup', 'permissions', 'download_composer', 'composer_install',
                    'generate_key', 'npm_install', 'npm_build', 'storage_link',
                    'migrate', 'db_seed', 'optimize', 'cache_clear',
                    'full_setup', 'deploy_update', 'maintenance_on', 'maintenance_off'
                ];
                foreach ($orderedPresets as $key): 
                    if (!isset($presets[$key])) continue;
                    $preset = $presets[$key];
                    $canRun = true;
                    foreach ($preset['requires'] as $req) {
                        if ($req === 'vendor' && !$hasVendor) $canRun = false;
                        if ($req === 'composer' && !$hasComposer) $canRun = false;
                        if ($req === 'npm' && !$npmBin) $canRun = false;
                    }
                ?>
                <div class="card <?= !$canRun ? 'disabled' : '' ?>">
                    <h3><?= $preset['name'] ?></h3>
                    <p><?= $preset['description'] ?></p>
                    <form method="POST">
                        <input type="hidden" name="key" value="<?= htmlspecialchars($providedKey) ?>">
                        <input type="hidden" name="action" value="run_preset">
                        <input type="hidden" name="preset" value="<?= $key ?>">
                        <button type="submit" class="btn <?= strpos($preset['name'], 'DANGEROUS') !== false ? 'btn-danger' : '' ?>" 
                                <?= !$canRun ? 'disabled' : '' ?>
                                onclick="return confirm('Run: <?= addslashes($preset['name']) ?>?')">
                            Run
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Custom Command -->
        <div class="section">
            <h2>🛠️ Custom Command</h2>
            <div class="card">
                <form method="POST">
                    <input type="hidden" name="key" value="<?= htmlspecialchars($providedKey) ?>">
                    <input type="hidden" name="action" value="run_custom">
                    
                    <div class="form-group">
                        <label>Command Type</label>
                        <select name="type">
                            <option value="artisan">Artisan (php artisan ...)</option>
                            <option value="composer">Composer</option>
                            <option value="npm">NPM</option>
                            <option value="shell">Shell</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Command (without prefix)</label>
                        <input type="text" name="command" placeholder="e.g., migrate:status" required>
                    </div>
                    
                    <button type="submit" class="btn btn-warning" onclick="return confirm('Run this command?')">
                        Execute
                    </button>
                </form>
            </div>
        </div>
        
        <div style="text-align: center; color: #71717a; margin-top: 40px; padding: 20px; font-size: 0.85rem;">
            <p>Laravel Deployment Manager v2.0 (Standalone)</p>
            <p style="margin-top: 5px;">🔒 Delete this file after deployment!</p>
        </div>
    </div>
</body>
</html>
