<?php
/**
 * Admin User Manager - Standalone Script
 * 
 * This script allows you to:
 * - Create new admin users
 * - View all users
 * - Delete any user
 * 
 * SECURITY: Change the secret key below and delete this file after use!
 */

// ============================================
// CONFIGURATION - CHANGE THIS SECRET KEY!
// ============================================
define('SECRET_KEY', '123456');
define('SESSION_TIMEOUT', 1800); // 30 minutes

// ============================================
// BOOTSTRAP LARAVEL
// ============================================
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

// ============================================
// SESSION HANDLING
// ============================================
session_start();

function isAuthenticated() {
    return isset($_SESSION['admin_manager_auth']) 
        && $_SESSION['admin_manager_auth'] === true
        && isset($_SESSION['admin_manager_time'])
        && (time() - $_SESSION['admin_manager_time']) < SESSION_TIMEOUT;
}

function authenticate($key) {
    if ($key === SECRET_KEY) {
        $_SESSION['admin_manager_auth'] = true;
        $_SESSION['admin_manager_time'] = time();
        return true;
    }
    return false;
}

function logout() {
    unset($_SESSION['admin_manager_auth']);
    unset($_SESSION['admin_manager_time']);
}

// ============================================
// HANDLE ACTIONS
// ============================================
$message = '';
$messageType = '';

// Handle logout
if (isset($_GET['logout'])) {
    logout();
    header('Location: admin-manager.php');
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    if (authenticate($_POST['secret_key'] ?? '')) {
        header('Location: admin-manager.php');
        exit;
    } else {
        $message = 'Invalid secret key!';
        $messageType = 'error';
    }
}

// Handle authenticated actions
if (isAuthenticated() && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'create_admin':
                $name = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                
                if (empty($name) || empty($email) || empty($password)) {
                    throw new Exception('All fields are required!');
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Invalid email format!');
                }
                
                if (strlen($password) < 8) {
                    throw new Exception('Password must be at least 8 characters!');
                }
                
                // Check if email exists
                $exists = DB::table('users')->where('email', $email)->exists();
                if ($exists) {
                    throw new Exception('A user with this email already exists!');
                }
                
                // Create user
                $userId = DB::table('users')->insertGetId([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                // Assign admin role if using Spatie permissions
                if (Schema::hasTable('roles') && Schema::hasTable('model_has_roles')) {
                    $adminRole = DB::table('roles')->where('name', 'admin')->first();
                    if ($adminRole) {
                        DB::table('model_has_roles')->insert([
                            'role_id' => $adminRole->id,
                            'model_type' => 'App\\Models\\User',
                            'model_id' => $userId,
                        ]);
                    }
                    
                    // Also try super_admin role
                    $superAdminRole = DB::table('roles')->where('name', 'super_admin')->first();
                    if ($superAdminRole) {
                        DB::table('model_has_roles')->insertOrIgnore([
                            'role_id' => $superAdminRole->id,
                            'model_type' => 'App\\Models\\User',
                            'model_id' => $userId,
                        ]);
                    }
                }
                
                $message = "Admin user '$name' created successfully with ID: $userId";
                $messageType = 'success';
                break;
                
            case 'edit_user':
                $userId = (int)($_POST['user_id'] ?? 0);
                $newName = trim($_POST['new_name'] ?? '');
                $newEmail = trim($_POST['new_email'] ?? '');
                $newPassword = $_POST['new_password'] ?? '';
                
                if ($userId <= 0) {
                    throw new Exception('Invalid user ID!');
                }
                
                $user = DB::table('users')->where('id', $userId)->first();
                if (!$user) {
                    throw new Exception('User not found!');
                }
                
                $updates = [];
                $changes = [];
                
                if (!empty($newName) && $newName !== $user->name) {
                    $updates['name'] = $newName;
                    $changes[] = "name changed to '$newName'";
                }
                
                if (!empty($newEmail) && $newEmail !== $user->email) {
                    if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                        throw new Exception('Invalid email format!');
                    }
                    $exists = DB::table('users')->where('email', $newEmail)->where('id', '!=', $userId)->exists();
                    if ($exists) {
                        throw new Exception('This email is already in use by another user!');
                    }
                    $updates['email'] = $newEmail;
                    $changes[] = "email changed to '$newEmail'";
                }
                
                if (!empty($newPassword)) {
                    if (strlen($newPassword) < 8) {
                        throw new Exception('Password must be at least 8 characters!');
                    }
                    $updates['password'] = Hash::make($newPassword);
                    $changes[] = "password updated";
                }
                
                if (empty($updates)) {
                    throw new Exception('No changes provided!');
                }
                
                $updates['updated_at'] = now();
                DB::table('users')->where('id', $userId)->update($updates);
                
                $message = "User updated: " . implode(', ', $changes);
                $messageType = 'success';
                break;
                
            case 'delete_user':
                $userId = (int)($_POST['user_id'] ?? 0);
                
                if ($userId <= 0) {
                    throw new Exception('Invalid user ID!');
                }
                
                $user = DB::table('users')->where('id', $userId)->first();
                if (!$user) {
                    throw new Exception('User not found!');
                }
                
                // First get user's account IDs for deleting transactions
                $accountIds = [];
                if (Schema::hasTable('accounts')) {
                    $accountIds = DB::table('accounts')->where('user_id', $userId)->pluck('id')->toArray();
                }
                
                // Delete transactions by account_id (not user_id)
                if (!empty($accountIds) && Schema::hasTable('transactions')) {
                    DB::table('transactions')->whereIn('account_id', $accountIds)->delete();
                }
                
                // Delete related records first (to avoid foreign key issues)
                // Only include tables that actually have user_id column
                $tablesWithUserId = [
                    'model_has_roles' => ['model_id', 'model_type', 'App\\Models\\User'],
                    'model_has_permissions' => ['model_id', 'model_type', 'App\\Models\\User'],
                    'accounts' => ['user_id', null, null],
                    'notifications' => ['notifiable_id', 'notifiable_type', 'App\\Models\\User'],
                    'support_tickets' => ['user_id', null, null],
                    'kyc_documents' => ['user_id', null, null],
                    'loans' => ['user_id', null, null],
                    'transfers' => ['user_id', null, null],
                    'referrals' => ['user_id', null, null],
                    'funding_applications' => ['user_id', null, null],
                    'linked_withdrawal_accounts' => ['user_id', null, null],
                ];
                
                foreach ($tablesWithUserId as $table => $config) {
                    if (Schema::hasTable($table)) {
                        // Check if the column exists before trying to delete
                        if (Schema::hasColumn($table, $config[0])) {
                            $query = DB::table($table)->where($config[0], $userId);
                            if ($config[1] !== null) {
                                $query->where($config[1], $config[2]);
                            }
                            $query->delete();
                        }
                    }
                }
                
                // Delete user
                DB::table('users')->where('id', $userId)->delete();
                
                $message = "User '{$user->name}' (ID: $userId) deleted successfully!";
                $messageType = 'success';
                break;
                
            case 'make_admin':
                $userId = (int)($_POST['user_id'] ?? 0);
                
                if ($userId <= 0) {
                    throw new Exception('Invalid user ID!');
                }
                
                $user = DB::table('users')->where('id', $userId)->first();
                if (!$user) {
                    throw new Exception('User not found!');
                }
                
                // Assign admin roles
                if (Schema::hasTable('roles') && Schema::hasTable('model_has_roles')) {
                    $roles = DB::table('roles')->whereIn('name', ['admin', 'super_admin'])->get();
                    foreach ($roles as $role) {
                        DB::table('model_has_roles')->insertOrIgnore([
                            'role_id' => $role->id,
                            'model_type' => 'App\\Models\\User',
                            'model_id' => $userId,
                        ]);
                    }
                }
                
                $message = "User '{$user->name}' is now an admin!";
                $messageType = 'success';
                break;
                
            case 'remove_admin':
                $userId = (int)($_POST['user_id'] ?? 0);
                
                if ($userId <= 0) {
                    throw new Exception('Invalid user ID!');
                }
                
                if (Schema::hasTable('model_has_roles')) {
                    DB::table('model_has_roles')
                        ->where('model_id', $userId)
                        ->where('model_type', 'App\\Models\\User')
                        ->delete();
                }
                
                $message = "Admin privileges removed from user ID: $userId";
                $messageType = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// ============================================
// FETCH DATA
// ============================================
$users = [];
$roles = [];

if (isAuthenticated()) {
    try {
        // Get all users with their roles
        $users = DB::table('users')
            ->select('users.*')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($user) {
                $user->roles = [];
                if (Schema::hasTable('model_has_roles') && Schema::hasTable('roles')) {
                    $user->roles = DB::table('model_has_roles')
                        ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                        ->where('model_has_roles.model_id', $user->id)
                        ->where('model_has_roles.model_type', 'App\\Models\\User')
                        ->pluck('roles.name')
                        ->toArray();
                }
                return $user;
            });
            
        // Get available roles
        if (Schema::hasTable('roles')) {
            $roles = DB::table('roles')->get();
        }
    } catch (Exception $e) {
        $message = 'Database error: ' . $e->getMessage();
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin User Manager</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh; color: #e4e4e7; padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { text-align: center; margin-bottom: 30px; color: #fff; font-size: 2rem; }
        h2 { margin-bottom: 20px; color: #a5b4fc; font-size: 1.25rem; border-bottom: 1px solid #374151; padding-bottom: 10px; }
        
        .card {
            background: rgba(30, 41, 59, 0.8); border-radius: 12px; padding: 25px;
            margin-bottom: 20px; border: 1px solid #374151;
        }
        
        .login-card { max-width: 400px; margin: 100px auto; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #94a3b8; font-size: 0.875rem; }
        .form-group input {
            width: 100%; padding: 12px 15px; border: 1px solid #374151; border-radius: 8px;
            background: #1e293b; color: #fff; font-size: 1rem;
        }
        .form-group input:focus { outline: none; border-color: #6366f1; }
        
        .btn {
            padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer;
            font-size: 0.875rem; font-weight: 600; transition: all 0.2s;
        }
        .btn-primary { background: #6366f1; color: white; }
        .btn-primary:hover { background: #4f46e5; }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }
        .btn-warning { background: #f59e0b; color: white; }
        .btn-warning:hover { background: #d97706; }
        .btn-sm { padding: 6px 12px; font-size: 0.75rem; }
        
        .alert {
            padding: 15px 20px; border-radius: 8px; margin-bottom: 20px;
        }
        .alert-success { background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; }
        .alert-error { background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #f87171; }
        
        .header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 30px; flex-wrap: wrap; gap: 15px;
        }
        .header h1 { margin: 0; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #374151; }
        th { background: #1e293b; color: #94a3b8; font-weight: 600; font-size: 0.75rem; text-transform: uppercase; }
        tr:hover { background: rgba(99, 102, 241, 0.1); }
        
        .badge {
            display: inline-block; padding: 4px 10px; border-radius: 20px;
            font-size: 0.7rem; font-weight: 600; margin-right: 5px;
        }
        .badge-admin { background: #6366f1; color: white; }
        .badge-super { background: #ec4899; color: white; }
        .badge-user { background: #374151; color: #9ca3af; }
        
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        
        .warning-box {
            background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444;
            border-radius: 8px; padding: 15px; margin-bottom: 20px;
        }
        .warning-box h3 { color: #f87171; margin-bottom: 10px; }
        .warning-box p { color: #fca5a5; font-size: 0.875rem; }
        
        .stat-box {
            background: #1e293b; border-radius: 8px; padding: 20px; text-align: center;
        }
        .stat-box .number { font-size: 2rem; font-weight: bold; color: #6366f1; }
        .stat-box .label { color: #94a3b8; font-size: 0.875rem; }
        
        @media (max-width: 768px) {
            table { display: block; overflow-x: auto; }
            .actions { flex-direction: column; }
        }
        
        /* Modal styles */
        .modal-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.7); z-index: 1000; justify-content: center; align-items: center;
        }
        .modal-overlay.active { display: flex; }
        .modal {
            background: #1e293b; border-radius: 12px; padding: 25px; max-width: 500px; width: 90%;
            border: 1px solid #374151; max-height: 90vh; overflow-y: auto;
        }
        .modal h3 { color: #fff; margin-bottom: 20px; }
        .modal-close {
            float: right; background: none; border: none; color: #94a3b8; font-size: 1.5rem;
            cursor: pointer; line-height: 1;
        }
        .modal-close:hover { color: #fff; }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!isAuthenticated()): ?>
            <!-- LOGIN FORM -->
            <div class="card login-card">
                <h2 style="text-align: center; border: none;">🔐 Admin Manager Login</h2>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <input type="hidden" name="action" value="login">
                    <div class="form-group">
                        <label>Secret Key</label>
                        <input type="password" name="secret_key" placeholder="Enter secret key" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
                </form>
            </div>
        <?php else: ?>
            <!-- MAIN INTERFACE -->
            <div class="header">
                <h1>👤 Admin User Manager</h1>
                <a href="?logout=1" class="btn btn-danger">Logout</a>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <div class="warning-box">
                <h3>⚠️ Security Warning</h3>
                <p>This tool provides direct database access. Delete this file (<code>admin-manager.php</code>) after use for security!</p>
            </div>
            
            <!-- STATS -->
            <div class="grid" style="margin-bottom: 20px;">
                <div class="stat-box">
                    <div class="number"><?php echo count($users); ?></div>
                    <div class="label">Total Users</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?php echo count(array_filter($users->toArray(), fn($u) => in_array('admin', $u->roles) || in_array('super_admin', $u->roles))); ?></div>
                    <div class="label">Admin Users</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?php echo count($roles); ?></div>
                    <div class="label">Available Roles</div>
                </div>
            </div>
            
            <!-- CREATE ADMIN FORM -->
            <div class="card">
                <h2>➕ Create New Admin User</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="create_admin">
                    <div class="grid">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="name" placeholder="John Doe" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" placeholder="admin@example.com" required>
                        </div>
                        <div class="form-group">
                            <label>Password (min 8 characters)</label>
                            <input type="password" name="password" placeholder="••••••••" minlength="8" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success">Create Admin User</button>
                </form>
            </div>
            
            <!-- USERS TABLE -->
            <div class="card">
                <h2>📋 All Users (<?php echo count($users); ?>)</h2>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Roles</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user->id; ?></td>
                                    <td><?php echo htmlspecialchars($user->name); ?></td>
                                    <td><?php echo htmlspecialchars($user->email); ?></td>
                                    <td>
                                        <?php if (empty($user->roles)): ?>
                                            <span class="badge badge-user">User</span>
                                        <?php else: ?>
                                            <?php foreach ($user->roles as $role): ?>
                                                <span class="badge <?php echo $role === 'super_admin' ? 'badge-super' : 'badge-admin'; ?>">
                                                    <?php echo htmlspecialchars($role); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($user->created_at)); ?></td>
                                    <td>
                                        <div class="actions">
                                            <button type="button" class="btn btn-primary btn-sm" onclick="openEditModal(<?php echo $user->id; ?>, '<?php echo htmlspecialchars(addslashes($user->name)); ?>', '<?php echo htmlspecialchars(addslashes($user->email)); ?>')">
                                                Edit
                                            </button>
                                            
                                            <?php if (empty($user->roles) || (!in_array('admin', $user->roles) && !in_array('super_admin', $user->roles))): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="make_admin">
                                                    <input type="hidden" name="user_id" value="<?php echo $user->id; ?>">
                                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Make this user an admin?')">
                                                        Make Admin
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="remove_admin">
                                                    <input type="hidden" name="user_id" value="<?php echo $user->id; ?>">
                                                    <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Remove admin privileges?')">
                                                        Remove Admin
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?php echo $user->id; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('⚠️ DELETE user <?php echo htmlspecialchars($user->name); ?>? This cannot be undone!')">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- EDIT USER MODAL -->
            <div class="modal-overlay" id="editModal">
                <div class="modal">
                    <button class="modal-close" onclick="closeEditModal()">&times;</button>
                    <h3>✏️ Edit User</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="edit_user">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="new_name" id="edit_name" placeholder="Leave blank to keep current">
                        </div>
                        
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="new_email" id="edit_email" placeholder="Leave blank to keep current">
                        </div>
                        
                        <div class="form-group">
                            <label>New Password (min 8 characters)</label>
                            <input type="password" name="new_password" id="edit_password" placeholder="Leave blank to keep current" minlength="8">
                        </div>
                        
                        <div style="display: flex; gap: 10px; margin-top: 20px;">
                            <button type="submit" class="btn btn-success">Save Changes</button>
                            <button type="button" class="btn btn-danger" onclick="closeEditModal()">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <script>
                function openEditModal(userId, name, email) {
                    document.getElementById('edit_user_id').value = userId;
                    document.getElementById('edit_name').value = name;
                    document.getElementById('edit_name').placeholder = name;
                    document.getElementById('edit_email').value = email;
                    document.getElementById('edit_email').placeholder = email;
                    document.getElementById('edit_password').value = '';
                    document.getElementById('editModal').classList.add('active');
                }
                
                function closeEditModal() {
                    document.getElementById('editModal').classList.remove('active');
                }
                
                // Close modal on escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') closeEditModal();
                });
                
                // Close modal when clicking outside
                document.getElementById('editModal').addEventListener('click', function(e) {
                    if (e.target === this) closeEditModal();
                });
            </script>
            
            <?php if (count($roles) > 0): ?>
            <div class="card">
                <h2>🎭 Available Roles</h2>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <?php foreach ($roles as $role): ?>
                        <span class="badge <?php echo $role->name === 'super_admin' ? 'badge-super' : ($role->name === 'admin' ? 'badge-admin' : 'badge-user'); ?>">
                            <?php echo htmlspecialchars($role->name); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
        <?php endif; ?>
    </div>
</body>
</html>
