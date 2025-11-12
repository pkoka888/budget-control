<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accept Invitation - Budget Control</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-purple-50 dark:from-gray-900 dark:to-gray-800 min-h-screen flex items-center justify-center p-4">
    <?php
    $token = $_GET['token'] ?? '';
    $invitation = $data['invitation'] ?? null;
    $household = $data['household'] ?? null;
    $inviter = $data['inviter'] ?? null;
    $error = $data['error'] ?? null;
    $expired = $invitation && strtotime($invitation['expires_at']) < time();
    ?>

    <div class="max-w-md w-full">
        <?php if ($error): ?>
        <!-- Error State -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl p-8 text-center">
            <div class="text-6xl mb-4">❌</div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Invalid Invitation</h1>
            <p class="text-gray-600 dark:text-gray-400 mb-6"><?= htmlspecialchars($error) ?></p>
            <a href="/login" class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                Go to Login
            </a>
        </div>

        <?php elseif ($expired): ?>
        <!-- Expired State -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl p-8 text-center">
            <div class="text-6xl mb-4">⏰</div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Invitation Expired</h1>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                This invitation expired on <?= date('F j, Y', strtotime($invitation['expires_at'])) ?>.
                Please contact the household owner for a new invitation.
            </p>
            <a href="/login" class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                Go to Login
            </a>
        </div>

        <?php elseif ($invitation && $household): ?>
        <!-- Valid Invitation -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 p-8 text-white text-center">
                <div class="text-6xl mb-4">👨‍👩‍👧</div>
                <h1 class="text-2xl font-bold mb-2">You're Invited!</h1>
                <p class="text-blue-100">Join a household to manage finances together</p>
            </div>

            <!-- Content -->
            <div class="p-8">
                <!-- Invitation Details -->
                <div class="mb-6">
                    <div class="flex items-start gap-3 mb-4">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center text-2xl flex-shrink-0">
                            <?= strtoupper(substr($inviter['name'] ?? 'U', 0, 1)) ?>
                        </div>
                        <div class="flex-1">
                            <p class="text-gray-900 dark:text-white mb-1">
                                <strong><?= htmlspecialchars($inviter['name'] ?? $inviter['username'] ?? 'Someone') ?></strong> has invited you to join
                            </p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                <?= htmlspecialchars($household['name']) ?>
                            </p>
                        </div>
                    </div>

                    <?php if (!empty($household['description'])): ?>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        <?= htmlspecialchars($household['description']) ?>
                    </p>
                    <?php endif; ?>

                    <!-- Role Badge -->
                    <div class="flex items-center gap-2 mb-4">
                        <span class="text-sm text-gray-600 dark:text-gray-400">You'll join as:</span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                            <?php
                                switch($invitation['role']) {
                                    case 'partner': echo 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'; break;
                                    case 'viewer': echo 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'; break;
                                    case 'child': echo 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'; break;
                                }
                            ?>">
                            <?php
                                $icons = ['partner' => '🤝', 'viewer' => '👁️', 'child' => '👶'];
                                echo ($icons[$invitation['role']] ?? '') . ' ' . ucfirst($invitation['role']);
                            ?>
                        </span>
                    </div>

                    <!-- Personal Message -->
                    <?php if (!empty($invitation['message'])): ?>
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 mb-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Personal message:</p>
                        <p class="text-gray-900 dark:text-white italic">
                            "<?= htmlspecialchars($invitation['message']) ?>"
                        </p>
                    </div>
                    <?php endif; ?>

                    <!-- What You Can Do -->
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 mb-6">
                        <p class="font-medium text-gray-900 dark:text-white mb-2">
                            <?php
                                switch($invitation['role']) {
                                    case 'partner':
                                        echo '🤝 As a Partner, you can:';
                                        break;
                                    case 'viewer':
                                        echo '👁️ As a Viewer, you can:';
                                        break;
                                    case 'child':
                                        echo '👶 As a Child account, you can:';
                                        break;
                                }
                            ?>
                        </p>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                            <?php if ($invitation['role'] === 'partner'): ?>
                                <li>✓ View shared transactions and budgets</li>
                                <li>✓ Create and manage shared finances</li>
                                <li>✓ Approve spending requests</li>
                                <li>✓ Comment on transactions</li>
                            <?php elseif ($invitation['role'] === 'viewer'): ?>
                                <li>✓ View shared transactions and budgets</li>
                                <li>✓ View activity feed</li>
                                <li>✓ Comment on transactions</li>
                                <li>✗ Cannot create or modify data</li>
                            <?php else: ?>
                                <li>✓ Track your allowance</li>
                                <li>✓ Complete chores for rewards</li>
                                <li>✓ Request money from parents</li>
                                <li>✓ Learn financial responsibility</li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <!-- Expiry Notice -->
                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center mb-6">
                        ⏰ This invitation expires on <?= date('F j, Y', strtotime($invitation['expires_at'])) ?>
                    </p>
                </div>

                <!-- Actions -->
                <div class="space-y-3">
                    <button onclick="acceptInvitation()" id="acceptBtn" class="w-full px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium text-lg">
                        ✓ Accept Invitation
                    </button>
                    <button onclick="declineInvitation()" id="declineBtn" class="w-full px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-medium dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                        Decline
                    </button>
                </div>

                <!-- Login Notice -->
                <p class="text-center text-sm text-gray-600 dark:text-gray-400 mt-6">
                    Don't have an account? <a href="/register?email=<?= urlencode($invitation['invitee_email']) ?>" class="text-blue-600 hover:text-blue-700 font-medium">Create one first</a>
                </p>
            </div>
        </div>

        <?php else: ?>
        <!-- Loading State -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl p-8 text-center">
            <div class="animate-spin text-6xl mb-4">⏳</div>
            <p class="text-gray-600 dark:text-gray-400">Loading invitation...</p>
        </div>
        <?php endif; ?>
    </div>

    <script>
        async function acceptInvitation() {
            const btn = document.getElementById('acceptBtn');
            btn.disabled = true;
            btn.innerHTML = '⏳ Accepting...';

            try {
                const response = await fetch('/invitation/accept', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'token=<?= htmlspecialchars($token) ?>'
                });

                const data = await response.json();

                if (data.success) {
                    // Show success state
                    document.body.innerHTML = `
                        <div class="min-h-screen flex items-center justify-center p-4 bg-gradient-to-br from-green-50 to-blue-50">
                            <div class="max-w-md w-full bg-white rounded-lg shadow-2xl p-8 text-center">
                                <div class="text-6xl mb-4">🎉</div>
                                <h1 class="text-2xl font-bold text-gray-900 mb-4">Welcome to the Household!</h1>
                                <p class="text-gray-600 mb-6">You've successfully joined <?= htmlspecialchars($household['name']) ?>.</p>
                                <a href="/household/<?= $invitation['household_id'] ?>" class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                                    Go to Household Dashboard
                                </a>
                            </div>
                        </div>
                    `;
                } else {
                    alert(data.error || 'Failed to accept invitation');
                    btn.disabled = false;
                    btn.innerHTML = '✓ Accept Invitation';
                }
            } catch (error) {
                alert('An error occurred. Please try again.');
                btn.disabled = false;
                btn.innerHTML = '✓ Accept Invitation';
            }
        }

        async function declineInvitation() {
            if (!confirm('Are you sure you want to decline this invitation?')) return;

            const btn = document.getElementById('declineBtn');
            btn.disabled = true;
            btn.innerHTML = '⏳ Declining...';

            try {
                const response = await fetch('/invitation/decline', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'token=<?= htmlspecialchars($token) ?>'
                });

                const data = await response.json();

                if (data.success) {
                    document.body.innerHTML = `
                        <div class="min-h-screen flex items-center justify-center p-4 bg-gradient-to-br from-gray-50 to-blue-50">
                            <div class="max-w-md w-full bg-white rounded-lg shadow-2xl p-8 text-center">
                                <div class="text-6xl mb-4">👋</div>
                                <h1 class="text-2xl font-bold text-gray-900 mb-4">Invitation Declined</h1>
                                <p class="text-gray-600 mb-6">You have declined the invitation to join this household.</p>
                                <a href="/" class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                                    Go to Homepage
                                </a>
                            </div>
                        </div>
                    `;
                } else {
                    alert(data.error || 'Failed to decline invitation');
                    btn.disabled = false;
                    btn.innerHTML = 'Decline';
                }
            } catch (error) {
                alert('An error occurred. Please try again.');
                btn.disabled = false;
                btn.innerHTML = 'Decline';
            }
        }
    </script>
</body>
</html>
