<?php
/**
 * Two-Factor Authentication Controller
 * 
 * Handles 2FA setup, verification, and management
 */

$pageTitle = 'Two-Factor Authentication';
$twoFactorService = new \App\Services\TwoFactorAuthService();
$userId = $_SESSION['user_id'];

// Check if 2FA is already enabled
$isEnabled = $twoFactorService->isEnabled($userId);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'setup') {
        // Generate new secret
        $secret = $twoFactorService->generateSecret();
        $backupCodes = $twoFactorService->generateBackupCodes();
        
        // Store in session temporarily
        $_SESSION['2fa_setup'] = [
            'secret' => $secret,
            'backup_codes' => $backupCodes
        ];
        
    } elseif ($action === 'verify_setup') {
        // Verify code and enable 2FA
        $code = $_POST['code'] ?? '';
        $setup = $_SESSION['2fa_setup'] ?? null;
        
        if ($setup && $twoFactorService->verifyCode($setup['secret'], $code)) {
            $twoFactorService->enable($userId, $setup['secret'], $setup['backup_codes']);
            unset($_SESSION['2fa_setup']);
            $_SESSION['success'] = '✅ Two-Factor Authentication enabled successfully!';
            header('Location: /settings/2fa');
            exit;
        } else {
            $_SESSION['error'] = '❌ Invalid verification code. Please try again.';
        }
        
    } elseif ($action === 'disable') {
        // Disable 2FA
        $twoFactorService->disable($userId);
        $_SESSION['success'] = 'Two-Factor Authentication disabled.';
        header('Location: /settings/2fa');
        exit;
    }
}

$setup = $_SESSION['2fa_setup'] ?? null;
$username = $_SESSION['username'] ?? 'user';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Drugmuk</title>
    <link rel="stylesheet" href="/css/main.css">
    <style>
        .security-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .security-card {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .status-enabled {
            background: #d4edda;
            color: #155724;
        }
        
        .status-disabled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .qr-code {
            text-align: center;
            margin: 30px 0;
        }
        
        .qr-code img {
            max-width: 250px;
            border: 2px solid #ddd;
            padding: 10px;
            background: white;
        }
        
        .backup-codes {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .backup-codes-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-top: 15px;
        }
        
        .backup-code {
            font-family: 'Courier New', monospace;
            font-size: 14px;
            padding: 8px;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            text-align: center;
        }
        
        .setup-steps {
            counter-reset: step;
        }
        
        .setup-step {
            margin: 25px 0;
            padding-left: 40px;
            position: relative;
        }
        
        .setup-step::before {
            counter-increment: step;
            content: counter(step);
            position: absolute;
            left: 0;
            top: 0;
            width: 30px;
            height: 30px;
            background: #007bff;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .form-group {
            margin: 20px 0;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="security-container">
        <h1>🔐 Two-Factor Authentication</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <div class="security-card">
            <h2>Status</h2>
            <p>
                Two-Factor Authentication is currently: 
                <span class="status-badge <?= $isEnabled ? 'status-enabled' : 'status-disabled' ?>">
                    <?= $isEnabled ? '✓ Enabled' : '✗ Disabled' ?>
                </span>
            </p>
            
            <?php if (!$isEnabled && !$setup): ?>
                <p>Add an extra layer of security to your account by enabling two-factor authentication.</p>
                <form method="POST">
                    <input type="hidden" name="action" value="setup">
                    <button type="submit" class="btn btn-primary">🔒 Enable 2FA</button>
                </form>
            <?php endif; ?>
            
            <?php if ($isEnabled): ?>
                <p>Your account is protected with two-factor authentication.</p>
                <form method="POST" onsubmit="return confirm('Are you sure you want to disable 2FA?');">
                    <input type="hidden" name="action" value="disable">
                    <button type="submit" class="btn btn-danger">Disable 2FA</button>
                </form>
            <?php endif; ?>
        </div>
        
        <?php if ($setup): ?>
            <div class="security-card">
                <h2>Setup Two-Factor Authentication</h2>
                
                <div class="setup-steps">
                    <div class="setup-step">
                        <h3>Install Authenticator App</h3>
                        <p>Download and install an authenticator app on your mobile device:</p>
                        <ul>
                            <li>Google Authenticator (iOS/Android)</li>
                            <li>Microsoft Authenticator (iOS/Android)</li>
                            <li>Authy (iOS/Android)</li>
                        </ul>
                    </div>
                    
                    <div class="setup-step">
                        <h3>Scan QR Code</h3>
                        <p>Open your authenticator app and scan this QR code:</p>
                        <div class="qr-code">
                            <img src="<?= $twoFactorService->getQRCodeUrl($setup['secret'], $username) ?>" 
                                 alt="QR Code">
                        </div>
                        <p><small>Or enter this code manually: <strong><?= $setup['secret'] ?></strong></small></p>
                    </div>
                    
                    <div class="setup-step">
                        <h3>Save Backup Codes</h3>
                        <p>Save these backup codes in a safe place. You can use them to access your account if you lose your device.</p>
                        <div class="backup-codes">
                            <strong>⚠️ Important: Save these codes now!</strong>
                            <div class="backup-codes-grid">
                                <?php foreach ($setup['backup_codes'] as $code): ?>
                                    <div class="backup-code"><?= $code ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="setup-step">
                        <h3>Verify Setup</h3>
                        <p>Enter the 6-digit code from your authenticator app to complete setup:</p>
                        <form method="POST">
                            <input type="hidden" name="action" value="verify_setup">
                            <div class="form-group">
                                <input type="text" 
                                       name="code" 
                                       class="form-control" 
                                       placeholder="000000"
                                       pattern="[0-9]{6}"
                                       maxlength="6"
                                       required
                                       autofocus>
                            </div>
                            <button type="submit" class="btn btn-primary">✓ Verify and Enable</button>
                            <a href="/settings/2fa" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="security-card">
            <h2>About Two-Factor Authentication</h2>
            <p>Two-factor authentication (2FA) adds an extra layer of security to your account. When enabled, you'll need to enter both your password and a verification code from your mobile device to log in.</p>
            
            <h3>Benefits:</h3>
            <ul>
                <li>✓ Protects against password theft</li>
                <li>✓ Prevents unauthorized access</li>
                <li>✓ Meets security compliance requirements</li>
                <li>✓ Peace of mind for sensitive data</li>
            </ul>
        </div>
    </div>
</body>
</html>
