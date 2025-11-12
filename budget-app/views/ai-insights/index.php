<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">AI Financial Insights</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Smart recommendations powered by AI</p>
        </div>
        <button onclick="generateInsights()" class="btn-primary">
            ✨ Generate Insights
        </button>
    </div>

    <!-- Insights Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($insights as $insight): ?>
            <?php
            $severityColors = [
                'positive' => 'border-green-500 bg-green-50 dark:bg-green-900',
                'info' => 'border-blue-500 bg-blue-50 dark:bg-blue-900',
                'warning' => 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900',
                'critical' => 'border-red-500 bg-red-50 dark:bg-red-900'
            ];
            $borderClass = $severityColors[$insight['severity']] ?? 'border-gray-300 bg-white dark:bg-gray-800';

            $icons = [
                'spending_pattern' => '📊',
                'saving_opportunity' => '💰',
                'budget_alert' => '⚠️',
                'anomaly' => '🚨',
                'investment_suggestion' => '📈'
            ];
            $icon = $icons[$insight['insight_type']] ?? '💡';
            ?>
            <div class="border-l-4 <?php echo $borderClass; ?> rounded-lg shadow p-6">
                <div class="flex items-start justify-between">
                    <div class="text-3xl"><?php echo $icon; ?></div>
                    <?php if ($insight['confidence_score']): ?>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            <?php echo round($insight['confidence_score'] * 100); ?>% confidence
                        </span>
                    <?php endif; ?>
                </div>
                <h3 class="mt-3 text-lg font-semibold text-gray-900 dark:text-white">
                    <?php echo htmlspecialchars($insight['title']); ?>
                </h3>
                <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                    <?php echo htmlspecialchars($insight['description']); ?>
                </p>
                <?php if ($insight['category']): ?>
                    <div class="mt-3">
                        <span class="inline-block px-2 py-1 text-xs font-medium bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded">
                            <?php echo htmlspecialchars($insight['category']); ?>
                        </span>
                    </div>
                <?php endif; ?>
                <div class="mt-4 flex space-x-2">
                    <button onclick="dismissInsight(<?php echo $insight['id']; ?>)" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                        Dismiss
                    </button>
                    <?php if (!$insight['is_read']): ?>
                        <button onclick="markRead(<?php echo $insight['id']; ?>)" class="text-sm text-purple-600 dark:text-purple-400 hover:underline">
                            Mark Read
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($insights)): ?>
            <div class="col-span-full bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
                <div class="text-6xl mb-4">🤖</div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No insights yet</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">Generate AI-powered insights from your financial data</p>
                <button onclick="generateInsights()" class="btn-primary">Generate Insights</button>
            </div>
        <?php endif; ?>
    </div>

    <!-- AI Chat Assistant -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">💬 AI Financial Assistant</h2>
        <div id="chat-messages" class="space-y-3 mb-4 max-h-64 overflow-y-auto">
            <div class="text-sm text-gray-500 dark:text-gray-400 text-center">
                Ask me anything about your finances!
            </div>
        </div>
        <div class="flex space-x-3">
            <input type="text" id="chat-input" placeholder="Ask a question..."
                   class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                   onkeypress="if(event.key==='Enter') sendMessage()">
            <button onclick="sendMessage()" class="btn-primary">Send</button>
        </div>
    </div>
</div>

<script>
async function generateInsights() {
    try {
        const response = await fetch('/ai-insights/generate', { method: 'POST' });
        const result = await response.json();
        if (result.success) {
            alert('Generated ' + result.count + ' new insights!');
            location.reload();
        }
    } catch (error) {
        alert('Error generating insights: ' + error.message);
    }
}

async function sendMessage() {
    const input = document.getElementById('chat-input');
    const message = input.value.trim();
    if (!message) return;

    const messagesDiv = document.getElementById('chat-messages');
    messagesDiv.innerHTML += '<div class="text-right"><span class="inline-block bg-purple-100 dark:bg-purple-900 px-4 py-2 rounded-lg">' + message + '</span></div>';
    input.value = '';

    try {
        const response = await fetch('/ai-insights/chat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: message })
        });
        const result = await response.json();

        if (result.success) {
            messagesDiv.innerHTML += '<div class="text-left"><span class="inline-block bg-gray-100 dark:bg-gray-700 px-4 py-2 rounded-lg">' + result.response + '</span></div>';
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }
    } catch (error) {
        console.error('Chat error:', error);
    }
}

async function dismissInsight(id) {
    await fetch('/ai-insights/' + id + '/dismiss', { method: 'POST' });
    location.reload();
}
</script>

<style>
.btn-primary {
    @apply px-6 py-2 bg-gradient-to-r from-purple-500 to-indigo-500 text-white font-semibold rounded-lg shadow hover:from-purple-600 hover:to-indigo-600 transition;
}
</style>
