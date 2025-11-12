<?php
/**
 * Household Detail View
 * Shows single household with members, stats, and actions
 */
$household = $data['household'] ?? [];
$members = $household['members'] ?? [];
$stats = $data['stats'] ?? [];
$invitations = $data['invitations'] ?? [];
$currentUserRole = $data['current_user_role'] ?? 'viewer';
$canManageMembers = in_array($currentUserRole, ['owner', 'partner']);
$isOwner = $currentUserRole === 'owner';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($household['name']) ?> - Household</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex justify-between items-start mb-8">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <a href="/household" class="text-gray-600 hover:text-gray-900 dark:text-gray-400">← Back to Households</a>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($household['name']) ?></h1>
                <?php if ($household['description']): ?>
                    <p class="text-gray-600 dark:text-gray-400 mt-2"><?= htmlspecialchars($household['description']) ?></p>
                <?php endif; ?>
            </div>

            <?php if ($isOwner): ?>
            <div class="flex gap-3">
                <button onclick="editHousehold()" class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50">
                    ⚙️ Settings
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Members</div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white"><?= $stats['member_count'] ?? count($members) ?></div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Shared Transactions</div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white"><?= $stats['shared_transactions'] ?? 0 ?></div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Shared Accounts</div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white"><?= $stats['shared_accounts'] ?? 0 ?></div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Balance</div>
                <div class="text-3xl font-bold text-blue-600"><?= number_format($stats['total_shared_balance'] ?? 0, 2) ?> <?= $household['currency'] ?></div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
            <nav class="flex gap-8">
                <button onclick="showTab('members')" id="tab-members" class="tab-button active pb-4 border-b-2 border-blue-600 text-blue-600 font-medium">
                    Members
                </button>
                <button onclick="showTab('activity')" id="tab-activity" class="tab-button pb-4 border-b-2 border-transparent text-gray-600 hover:text-gray-900">
                    Activity
                </button>
                <button onclick="showTab('settings')" id="tab-settings" class="tab-button pb-4 border-b-2 border-transparent text-gray-600 hover:text-gray-900">
                    Settings
                </button>
            </nav>
        </div>

        <!-- Members Tab -->
        <div id="content-members" class="tab-content">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <!-- Invite Section -->
                <?php if ($canManageMembers): ?>
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <button onclick="showInviteModal()" class="w-full sm:w-auto px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                        ➕ Invite Member
                    </button>
                </div>
                <?php endif; ?>

                <!-- Members List -->
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($members as $member): ?>
                    <div class="p-6 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center text-xl">
                                <?= strtoupper(substr($member['username'] ?? $member['name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($member['username'] ?? $member['name']) ?></div>
                                <div class="text-sm text-gray-600 dark:text-gray-400"><?= htmlspecialchars($member['email'] ?? '') ?></div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <!-- Role Badge -->
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                <?php
                                    switch($member['role']) {
                                        case 'owner': echo 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'; break;
                                        case 'partner': echo 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'; break;
                                        case 'viewer': echo 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'; break;
                                        case 'child': echo 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'; break;
                                    }
                                ?>">
                                <?php
                                    $icons = ['owner' => '👑', 'partner' => '🤝', 'viewer' => '👁️', 'child' => '👶'];
                                    echo ($icons[$member['role']] ?? '') . ' ' . ucfirst($member['role']);
                                ?>
                            </span>

                            <!-- Actions -->
                            <?php if ($canManageMembers && $member['role'] !== 'owner'): ?>
                            <div class="relative">
                                <button onclick="toggleMemberMenu(<?= $member['id'] ?>)" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
                                    ⋮
                                </button>
                                <div id="member-menu-<?= $member['id'] ?>" class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-10">
                                    <button onclick="changeRole(<?= $member['id'] ?>)" class="w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 text-sm">
                                        Change Role
                                    </button>
                                    <button onclick="removeMember(<?= $member['id'] ?>)" class="w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 text-red-600 text-sm">
                                        Remove
                                    </button>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <?php if (empty($members)): ?>
                    <div class="p-12 text-center text-gray-500">
                        No members yet
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Pending Invitations -->
                <?php if (!empty($invitations)): ?>
                <div class="p-6 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="font-medium text-gray-900 dark:text-white mb-4">Pending Invitations</h3>
                    <div class="space-y-3">
                        <?php foreach ($invitations as $inv): ?>
                        <div class="flex items-center justify-between p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($inv['invitee_email']) ?></div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    Invited as <?= ucfirst($inv['role']) ?> • Expires <?= date('M j', strtotime($inv['expires_at'])) ?>
                                </div>
                            </div>
                            <?php if ($canManageMembers): ?>
                            <button onclick="cancelInvitation(<?= $inv['id'] ?>)" class="text-red-600 hover:text-red-700 text-sm">
                                Cancel
                            </button>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Activity Tab -->
        <div id="content-activity" class="tab-content hidden">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <p class="text-gray-600 dark:text-gray-400 text-center py-8">
                    Activity feed coming soon...
                </p>
            </div>
        </div>

        <!-- Settings Tab -->
        <div id="content-settings" class="tab-content hidden">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <p class="text-gray-600 dark:text-gray-400 text-center py-8">
                    Settings panel coming soon...
                </p>
            </div>
        </div>
    </div>

    <!-- Invite Modal -->
    <div id="inviteModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Invite Member</h3>
                <form id="inviteForm" onsubmit="sendInvitation(event)">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email*</label>
                        <input type="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="partner@example.com">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Role*</label>
                        <select name="role" class="w-full px-3 py-2 border border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="partner">🤝 Partner - Can manage finances</option>
                            <option value="viewer">👁️ Viewer - Read-only access</option>
                            <option value="child">👶 Child - Limited access</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Message (optional)</label>
                        <textarea name="message" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Join our household to manage finances together"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" onclick="hideInviteModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Send Invitation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Tab switching
        function showTab(tab) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-button').forEach(el => {
                el.classList.remove('active', 'border-blue-600', 'text-blue-600');
                el.classList.add('border-transparent', 'text-gray-600');
            });

            // Show selected tab
            document.getElementById('content-' + tab).classList.remove('hidden');
            document.getElementById('tab-' + tab).classList.add('active', 'border-blue-600', 'text-blue-600');
            document.getElementById('tab-' + tab).classList.remove('border-transparent', 'text-gray-600');
        }

        // Modal functions
        function showInviteModal() {
            document.getElementById('inviteModal').classList.remove('hidden');
        }

        function hideInviteModal() {
            document.getElementById('inviteModal').classList.add('hidden');
            document.getElementById('inviteForm').reset();
        }

        // Member menu toggle
        function toggleMemberMenu(memberId) {
            const menu = document.getElementById('member-menu-' + memberId);
            menu.classList.toggle('hidden');
        }

        // Close menus when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('[onclick^="toggleMemberMenu"]')) {
                document.querySelectorAll('[id^="member-menu-"]').forEach(el => el.classList.add('hidden'));
            }
        });

        // Send invitation
        async function sendInvitation(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);

            try {
                const response = await fetch('/household/<?= $household['id'] ?>/invite', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    hideInviteModal();
                    showToast('Invitation sent successfully! 📧', 'success');
                    // Reload page to show pending invitation
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.error || 'Failed to send invitation', 'error');
                }
            } catch (error) {
                showToast('An error occurred. Please try again.', 'error');
            }
        }

        // Change role
        async function changeRole(memberId) {
            const newRole = prompt('Enter new role (partner, viewer, child):');
            if (!newRole) return;

            try {
                const response = await fetch('/household/<?= $household['id'] ?>/member/' + memberId + '/role', {
                    method: 'PUT',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'role=' + encodeURIComponent(newRole)
                });

                const data = await response.json();

                if (data.success) {
                    showToast('Role updated successfully', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.error || 'Failed to update role', 'error');
                }
            } catch (error) {
                showToast('An error occurred', 'error');
            }
        }

        // Remove member
        async function removeMember(memberId) {
            if (!confirm('Are you sure you want to remove this member?')) return;

            try {
                const response = await fetch('/household/<?= $household['id'] ?>/member/' + memberId, {
                    method: 'DELETE'
                });

                const data = await response.json();

                if (data.success) {
                    showToast('Member removed successfully', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.error || 'Failed to remove member', 'error');
                }
            } catch (error) {
                showToast('An error occurred', 'error');
            }
        }

        // Cancel invitation
        async function cancelInvitation(invitationId) {
            if (!confirm('Cancel this invitation?')) return;

            try {
                const response = await fetch('/invitation/' + invitationId + '/cancel', {
                    method: 'POST'
                });

                const data = await response.json();

                if (data.success) {
                    showToast('Invitation cancelled', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.error || 'Failed to cancel invitation', 'error');
                }
            } catch (error) {
                showToast('An error occurred', 'error');
            }
        }

        // Toast notification
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white z-50 ${
                type === 'success' ? 'bg-green-500' :
                type === 'error' ? 'bg-red-500' :
                type === 'warning' ? 'bg-yellow-500' :
                'bg-blue-500'
            }`;
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>
