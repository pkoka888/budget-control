<div class="max-w-6xl mx-auto p-6">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Automatizace & Pokročilé Funkce</h1>

        <!-- Automation Actions -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Automatické Akce</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="automation-actions">
                <!-- Actions will be loaded here -->
            </div>
            <button id="execute-actions" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Spustit automatické akce
            </button>
        </div>

        <!-- Job Market -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Trh Práce</h2>
            <div class="bg-gray-50 p-4 rounded-lg">
                <button id="fetch-jobs" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 mb-4">
                    Načíst pracovní příležitosti
                </button>
                <div id="job-opportunities" class="space-y-4">
                    <!-- Job opportunities will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Czech Benefits -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">České Dávky a Příspěvky</h2>
            <div class="bg-gray-50 p-4 rounded-lg">
                <button id="check-benefits" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 mb-4">
                    Zkontrolovat nárok na dávky
                </button>
                <div id="benefits-list" class="space-y-4">
                    <!-- Benefits will be loaded here -->
                </div>
            </div>
        </div>

        <!-- AI Recommendations Feedback -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Zpětná Vazba na AI Doporučení</h2>
            <div class="bg-gray-50 p-4 rounded-lg">
                <div class="mb-4">
                    <h3 class="font-medium text-gray-700">Statistiky zpětné vazby:</h3>
                    <div id="feedback-stats" class="mt-2 text-sm text-gray-600">
                        <!-- Feedback stats will be loaded here -->
                    </div>
                </div>
                <button id="view-history" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                    Zobrazit historii doporučení
                </button>
                <div id="recommendation-history" class="mt-4 space-y-2 hidden">
                    <!-- Recommendation history will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Performance & Security -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Performance Dashboard -->
            <div>
                <h2 class="text-xl font-semibold text-gray-700 mb-4">Výkon Aplikace</h2>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <button id="load-performance" class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700 mb-4">
                        Načíst data o výkonu
                    </button>
                    <div id="performance-data" class="space-y-2 text-sm">
                        <!-- Performance data will be loaded here -->
                    </div>
                </div>
            </div>

            <!-- Security Logs -->
            <div>
                <h2 class="text-xl font-semibold text-gray-700 mb-4">Bezpečnostní Protokoly</h2>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <button id="load-security" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 mb-4">
                        Načíst bezpečnostní protokoly
                    </button>
                    <div id="security-logs" class="space-y-2 text-sm max-h-64 overflow-y-auto">
                        <!-- Security logs will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Execute automation actions
    document.getElementById('execute-actions').addEventListener('click', async function() {
        try {
            const response = await fetch('/api/automation/execute', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            const data = await response.json();
            alert(data.success ? 'Automatické akce byly úspěšně spuštěny' : 'Chyba při spouštění akcí');
        } catch (error) {
            console.error('Error:', error);
        }
    });

    // Fetch job opportunities
    document.getElementById('fetch-jobs').addEventListener('click', async function() {
        try {
            const response = await fetch('/api/automation/jobs');
            const data = await response.json();
            displayJobOpportunities(data.opportunities);
        } catch (error) {
            console.error('Error:', error);
        }
    });

    // Check benefits
    document.getElementById('check-benefits').addEventListener('click', async function() {
        try {
            const response = await fetch('/api/automation/benefits');
            const data = await response.json();
            displayBenefits(data.benefits);
        } catch (error) {
            console.error('Error:', error);
        }
    });

    // View recommendation history
    document.getElementById('view-history').addEventListener('click', async function() {
        try {
            const response = await fetch('/api/automation/recommendations/history');
            const data = await response.json();
            displayRecommendationHistory(data.history);
            document.getElementById('recommendation-history').classList.remove('hidden');
        } catch (error) {
            console.error('Error:', error);
        }
    });

    // Load performance data
    document.getElementById('load-performance').addEventListener('click', async function() {
        try {
            const response = await fetch('/api/automation/performance');
            const data = await response.json();
            displayPerformanceData(data.dashboard);
        } catch (error) {
            console.error('Error:', error);
        }
    });

    // Load security logs
    document.getElementById('load-security').addEventListener('click', async function() {
        try {
            const response = await fetch('/api/automation/security/logs');
            const data = await response.json();
            displaySecurityLogs(data.logs);
        } catch (error) {
            console.error('Error:', error);
        }
    });

    // Load initial data
    loadFeedbackStats();
    loadAutomationActions();
});

async function loadFeedbackStats() {
    try {
        const response = await fetch('/api/automation/feedback/stats');
        const data = await response.json();
        document.getElementById('feedback-stats').innerHTML = `
            <div>Celkem zpětných vazeb: ${data.stats.total_feedback}</div>
            <div>Průměrné hodnocení: ${data.stats.average_rating}/5</div>
            <div>Pomocných odpovědí: ${data.stats.helpful_count}</div>
            <div>Implementovaných doporučení: ${data.stats.implemented_count}</div>
        `;
    } catch (error) {
        console.error('Error loading feedback stats:', error);
    }
}

async function loadAutomationActions() {
    try {
        const response = await fetch('/api/automation/actions');
        const data = await response.json();
        displayAutomationActions(data.actions);
    } catch (error) {
        console.error('Error loading automation actions:', error);
    }
}

function displayAutomationActions(actions) {
    const container = document.getElementById('automation-actions');
    container.innerHTML = actions.map(action => `
        <div class="bg-white p-4 rounded border">
            <h3 class="font-medium">${action.action_type}</h3>
            <p class="text-sm text-gray-600">Poslední spuštění: ${action.last_executed_at || 'Nikdy'}</p>
            <p class="text-sm text-gray-600">Úspěchů: ${action.success_count}/${action.execution_count}</p>
        </div>
    `).join('');
}

function displayJobOpportunities(opportunities) {
    const container = document.getElementById('job-opportunities');
    container.innerHTML = opportunities.map(job => `
        <div class="bg-white p-4 rounded border">
            <h3 class="font-medium">${job.title}</h3>
            <p class="text-sm text-gray-600">${job.company} - ${job.location}</p>
            <p class="text-sm">${job.salary_range}</p>
            <div class="mt-2">
                <button onclick="saveJob(${job.id})" class="bg-blue-500 text-white px-3 py-1 rounded text-sm mr-2">Uložit</button>
                <button onclick="applyToJob(${job.id})" class="bg-green-500 text-white px-3 py-1 rounded text-sm">Přihlásit se</button>
            </div>
        </div>
    `).join('');
}

function displayBenefits(benefits) {
    const container = document.getElementById('benefits-list');
    container.innerHTML = benefits.map(benefit => `
        <div class="bg-white p-4 rounded border">
            <h3 class="font-medium">${benefit.name}</h3>
            <p class="text-sm text-gray-600">${benefit.description}</p>
            <p class="text-sm">Kontakt: <a href="${benefit.website_url}" class="text-blue-600">${benefit.contact_info}</a></p>
            <button onclick="applyForBenefit(${benefit.id})" class="mt-2 bg-purple-500 text-white px-3 py-1 rounded text-sm">
                Zájem o přihlášku
            </button>
        </div>
    `).join('');
}

function displayRecommendationHistory(history) {
    const container = document.getElementById('recommendation-history');
    container.innerHTML = history.map(rec => `
        <div class="bg-white p-3 rounded border">
            <div class="flex justify-between">
                <span class="font-medium">${rec.title}</span>
                <span class="text-sm text-gray-500">${rec.created_at}</span>
            </div>
            <p class="text-sm text-gray-600">${rec.description}</p>
            <div class="mt-2">
                <button onclick="submitFeedback(${rec.recommendation_id}, 'helpful')" class="bg-green-500 text-white px-2 py-1 rounded text-xs mr-1">Pomocné</button>
                <button onclick="submitFeedback(${rec.recommendation_id}, 'not_helpful')" class="bg-red-500 text-white px-2 py-1 rounded text-xs mr-1">Nepomocné</button>
                <button onclick="markImplemented(${rec.recommendation_id})" class="bg-blue-500 text-white px-2 py-1 rounded text-xs">Implementováno</button>
            </div>
        </div>
    `).join('');
}

function displayPerformanceData(dashboard) {
    const container = document.getElementById('performance-data');
    container.innerHTML = `
        <div>Page Load (p95): ${dashboard.page_load_stats.p95}ms</div>
        <div>API Response (p95): ${dashboard.api_stats.p95}ms</div>
        <div>Database Query (p95): ${dashboard.db_stats.p95}ms</div>
        <div>Pomalých stránek: ${dashboard.issues.slow_pages.length}</div>
        <div>Pomalých API: ${dashboard.issues.slow_apis.length}</div>
    `;
}

function displaySecurityLogs(logs) {
    const container = document.getElementById('security-logs');
    container.innerHTML = logs.map(log => `
        <div class="text-xs">
            <span class="font-medium">${log.action_type}</span> -
            <span class="text-gray-600">${log.created_at}</span>
            ${log.risk_level !== 'low' ? `<span class="text-red-600">(${log.risk_level})</span>` : ''}
        </div>
    `).join('');
}

// Action handlers
async function saveJob(jobId) {
    await fetch('/api/automation/jobs/status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ opportunity_id: jobId, status: 'saved' })
    });
    alert('Pracovní příležitost uložena');
}

async function applyToJob(jobId) {
    const appliedAt = prompt('Kdy jste se přihlásili? (YYYY-MM-DD)', new Date().toISOString().split('T')[0]);
    if (appliedAt) {
        await fetch('/api/automation/jobs/status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ opportunity_id: jobId, status: 'applied', applied_at: appliedAt })
        });
        alert('Přihláška zaznamenána');
    }
}

async function applyForBenefit(benefitId) {
    await fetch('/api/automation/benefits/apply', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ benefit_id: benefitId, status: 'interested' })
    });
    alert('Zájem o dávku zaznamenán');
}

async function submitFeedback(recommendationId, feedbackType) {
    const rating = feedbackType === 'helpful' ? prompt('Hodnocení 1-5:', '5') : null;
    await fetch('/api/automation/feedback', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            recommendation_id: recommendationId,
            feedback_type: feedbackType,
            rating: rating
        })
    });
    alert('Zpětná vazba odeslána');
    loadFeedbackStats();
}

async function markImplemented(recommendationId) {
    await fetch('/api/automation/feedback', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            recommendation_id: recommendationId,
            feedback_type: 'implemented',
            implemented_at: new Date().toISOString()
        })
    });
    alert('Doporučení označeno jako implementované');
}
</script>