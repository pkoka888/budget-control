<?php /** Household Management */ ?>
<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold">Správa domácnosti</h1>
            <p class="text-gray-500">Sdílení financí s rodinou</p>
        </div>
        <a href="/household/create" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded">
            + Vytvořit domácnost
        </a>
    </div>

    <!-- Current Household -->
    <?php if (!empty($household)): ?>
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h2 class="text-2xl font-semibold"><?php echo htmlspecialchars($household['name'] ?? ''); ?></h2>
                    <p class="text-gray-600"><?php echo htmlspecialchars($household['description'] ?? ''); ?></p>
                </div>
                <a href="/household/<?php echo $household['id']; ?>/settings" class="text-blue-600 hover:text-blue-800">
                    Nastavení
                </a>
            </div>

            <!-- Household Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-gray-500 text-sm">Celkový zůstatek</h3>
                    <p class="text-2xl font-bold mt-2"><?php echo number_format($household['total_balance'] ?? 0, 2); ?> Kč</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-gray-500 text-sm">Členové</h3>
                    <p class="text-2xl font-bold mt-2"><?php echo $household['member_count'] ?? 0; ?></p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-gray-500 text-sm">Sdílené účty</h3>
                    <p class="text-2xl font-bold mt-2"><?php echo $household['shared_account_count'] ?? 0; ?></p>
                </div>
            </div>

            <!-- Members List -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4">Členové domácnosti</h3>
                <div class="space-y-3">
                    <?php if (!empty($members)): ?>
                        <?php foreach ($members as $member): ?>
                            <div class="flex justify-between items-center border-b pb-3">
                                <div>
                                    <p class="font-medium"><?php echo htmlspecialchars($member['name'] ?? ''); ?></p>
                                    <p class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($member['email'] ?? ''); ?>
                                        <?php if ($member['role'] === 'owner'): ?>
                                            <span class="ml-2 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">Vlastník</span>
                                        <?php elseif ($member['role'] === 'admin'): ?>
                                            <span class="ml-2 px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded">Admin</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <?php if ($member['user_id'] !== $_SESSION['user_id'] && ($household['is_owner'] ?? false)): ?>
                                    <button onclick="removeMember(<?php echo $member['user_id']; ?>)" class="text-red-600 hover:text-red-800 text-sm">
                                        Odebrat
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-gray-500 text-center py-4">Žádní členové</p>
                    <?php endif; ?>
                </div>
                <?php if ($household['is_owner'] ?? false): ?>
                    <a href="/household/<?php echo $household['id']; ?>/invite" class="inline-block mt-4 text-blue-600 hover:text-blue-800">
                        + Pozvat člena
                    </a>
                <?php endif; ?>
            </div>

            <!-- Shared Accounts -->
            <div>
                <h3 class="text-lg font-semibold mb-4">Sdílené účty</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php if (!empty($shared_accounts)): ?>
                        <?php foreach ($shared_accounts as $account): ?>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-medium"><?php echo htmlspecialchars($account['name'] ?? ''); ?></h4>
                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($account['type'] ?? ''); ?></p>
                                    </div>
                                    <p class="text-lg font-semibold"><?php echo number_format($account['balance'] ?? 0, 2); ?> Kč</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-gray-500">Žádné sdílené účty</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- No Household -->
        <div class="bg-white rounded-lg shadow p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <h2 class="text-2xl font-semibold text-gray-900 mb-2">Nejste členem žádné domácnosti</h2>
            <p class="text-gray-600 mb-6">Vytvořte domácnost a pozvěte členy rodiny ke sdílení financí</p>
            <a href="/household/create" class="inline-block bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded">
                Vytvořit domácnost
            </a>
        </div>
    <?php endif; ?>

    <!-- Pending Invitations -->
    <?php if (!empty($pending_invitations)): ?>
        <div class="bg-white rounded-lg shadow p-6 mt-8">
            <h2 class="text-xl font-semibold mb-4">Nevyřízené pozvánky</h2>
            <div class="space-y-3">
                <?php foreach ($pending_invitations as $invitation): ?>
                    <div class="flex justify-between items-center border-b pb-3">
                        <div>
                            <p class="font-medium">Pozvánka do domácnosti: <?php echo htmlspecialchars($invitation['household_name'] ?? ''); ?></p>
                            <p class="text-sm text-gray-500">Od: <?php echo htmlspecialchars($invitation['invited_by_name'] ?? ''); ?></p>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="acceptInvitation(<?php echo $invitation['id']; ?>)" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded text-sm">
                                Přijmout
                            </button>
                            <button onclick="declineInvitation(<?php echo $invitation['id']; ?>)" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded text-sm">
                                Odmítnout
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Pending Approvals (for admins/owners) -->
    <?php if (!empty($pending_approvals) && ($household['is_admin'] ?? false)): ?>
        <div class="bg-white rounded-lg shadow p-6 mt-8">
            <h2 class="text-xl font-semibold mb-4">Čekající na schválení</h2>
            <div class="space-y-3">
                <?php foreach ($pending_approvals as $approval): ?>
                    <div class="flex justify-between items-center border-b pb-3">
                        <div>
                            <p class="font-medium"><?php echo htmlspecialchars($approval['description'] ?? ''); ?></p>
                            <p class="text-sm text-gray-500">
                                Od: <?php echo htmlspecialchars($approval['requested_by_name'] ?? ''); ?> •
                                <?php echo date('d.m.Y H:i', strtotime($approval['created_at'])); ?>
                            </p>
                        </div>
                        <a href="/household/approvals/<?php echo $approval['id']; ?>" class="text-blue-600 hover:text-blue-800 text-sm">
                            Zobrazit
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function removeMember(userId) {
    if (confirm('Opravdu chcete odebrat tohoto člena z domácnosti?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/household/members/' + userId + '/remove';

        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = 'csrf_token';
        csrf.value = document.querySelector('meta[name="csrf-token"]').content;
        form.appendChild(csrf);

        document.body.appendChild(form);
        form.submit();
    }
}

function acceptInvitation(invitationId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/household/invitations/' + invitationId + '/accept';

    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = 'csrf_token';
    csrf.value = document.querySelector('meta[name="csrf-token"]').content;
    form.appendChild(csrf);

    document.body.appendChild(form);
    form.submit();
}

function declineInvitation(invitationId) {
    if (confirm('Opravdu chcete odmítnout tuto pozvánku?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/household/invitations/' + invitationId + '/decline';

        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = 'csrf_token';
        csrf.value = document.querySelector('meta[name="csrf-token"]').content;
        form.appendChild(csrf);

        document.body.appendChild(form);
        form.submit();
    }
}
</script>
