<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Split Expenses</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Manage shared expenses with friends and family</p>
        </div>
        <button onclick="showCreateGroupModal()" class="btn-primary">
            <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Create Group
        </button>
    </div>

    <!-- Groups Grid -->
    <?php if (!empty($groups)): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($groups as $group): ?>
                <a href="/expense-split/group?id=<?php echo $group['id']; ?>" class="block bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition p-6">
                    <!-- Group Image/Icon -->
                    <div class="flex items-start space-x-4">
                        <?php if ($group['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($group['image_url']); ?>" alt="<?php echo htmlspecialchars($group['name']); ?>" class="w-16 h-16 rounded-lg object-cover">
                        <?php else: ?>
                            <div class="w-16 h-16 rounded-lg bg-gradient-to-br from-purple-500 to-indigo-500 flex items-center justify-center text-white text-2xl font-bold">
                                <?php echo strtoupper(substr($group['name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>

                        <div class="flex-1 min-w-0">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate">
                                <?php echo htmlspecialchars($group['name']); ?>
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                <?php echo $group['member_count']; ?> members
                            </p>
                        </div>
                    </div>

                    <!-- Balance Summary -->
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <?php if ($group['user_balance'] > 0): ?>
                            <div class="text-green-600 dark:text-green-400 font-semibold">
                                You are owed <?php echo number_format($group['user_balance'], 2); ?> <?php echo $group['currency'] ?? 'CZK'; ?>
                            </div>
                        <?php elseif ($group['user_balance'] < 0): ?>
                            <div class="text-red-600 dark:text-red-400 font-semibold">
                                You owe <?php echo number_format(abs($group['user_balance']), 2); ?> <?php echo $group['currency'] ?? 'CZK'; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-gray-500 dark:text-gray-400">
                                All settled up
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Quick Stats -->
                    <div class="mt-3 flex justify-between text-sm text-gray-600 dark:text-gray-400">
                        <span><?php echo $group['expense_count'] ?? 0; ?> expenses</span>
                        <span>Updated <?php echo date('M j', strtotime($group['updated_at'])); ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <!-- Empty State -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
            <svg class="w-20 h-20 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No expense groups yet</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">Create a group to start splitting expenses with friends and family</p>
            <button onclick="showCreateGroupModal()" class="btn-primary">
                Create Your First Group
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Create Group Modal -->
<div id="create-group-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Create Expense Group</h2>
                <button onclick="hideCreateGroupModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form id="create-group-form" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Group Name
                    </label>
                    <input type="text" name="name" required
                           placeholder="e.g., Roommates, Vacation 2025"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Description (Optional)
                    </label>
                    <textarea name="description" rows="3"
                              placeholder="What is this group for?"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Invite Members (Optional)
                    </label>
                    <div id="member-emails" class="space-y-2">
                        <input type="email" name="member_emails[]"
                               placeholder="friend@example.com"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <button type="button" onclick="addEmailField()" class="mt-2 text-sm text-purple-600 dark:text-purple-400 hover:underline">
                        + Add another email
                    </button>
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="hideCreateGroupModal()" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                        Cancel
                    </button>
                    <button type="submit" class="btn-primary">
                        Create Group
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showCreateGroupModal() {
    document.getElementById('create-group-modal').classList.remove('hidden');
    document.getElementById('create-group-modal').classList.add('flex');
}

function hideCreateGroupModal() {
    document.getElementById('create-group-modal').classList.add('hidden');
    document.getElementById('create-group-modal').classList.remove('flex');
}

function addEmailField() {
    const container = document.getElementById('member-emails');
    const input = document.createElement('input');
    input.type = 'email';
    input.name = 'member_emails[]';
    input.placeholder = 'friend@example.com';
    input.className = 'w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white';
    container.appendChild(input);
}

document.getElementById('create-group-form').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const memberEmails = formData.getAll('member_emails[]').filter(email => email.trim());

    const data = {
        name: formData.get('name'),
        description: formData.get('description'),
        member_emails: memberEmails
    };

    try {
        const response = await fetch('/expense-split/store', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            window.location.href = '/expense-split/group?id=' + result.group.id;
        } else {
            alert('Error: ' + result.error);
        }
    } catch (error) {
        alert('Error creating group: ' + error.message);
    }
});
</script>

<style>
.btn-primary {
    @apply px-6 py-2 bg-gradient-to-r from-purple-500 to-indigo-500 text-white font-semibold rounded-lg shadow hover:from-purple-600 hover:to-indigo-600 transition;
}
</style>
