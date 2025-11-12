<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    <title><?php echo $title ?? 'Budget Control'; ?> - Budget Control</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/assets/logo.svg">
    <link rel="alternate icon" href="/assets/favicon.ico">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- Custom Styles -->
    <style>
        [x-cloak] { display: none !important; }

        .logo-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        @media (prefers-color-scheme: dark) {
            body {
                background-color: #1a202c;
                color: #e2e8f0;
            }

            .logo-img-light {
                display: none;
            }

            .logo-img-dark {
                display: inline-block;
            }
        }

        @media (prefers-color-scheme: light) {
            .logo-img-dark {
                display: none;
            }

            .logo-img-light {
                display: inline-block;
            }
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <!-- Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo & Brand -->
                <div class="flex items-center">
                    <a href="/" class="flex items-center space-x-3">
                        <img src="/assets/logo.svg" alt="Budget Control" class="h-10 w-10 logo-img-light">
                        <img src="/assets/logo-dark.svg" alt="Budget Control" class="h-10 w-10 logo-img-dark">
                        <span class="text-xl font-bold logo-text">Budget Control</span>
                    </a>
                </div>

                <!-- Main Navigation -->
                <div class="hidden md:flex items-center space-x-4">
                    <a href="/" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        Dashboard
                    </a>
                    <a href="/transactions" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        Transactions
                    </a>
                    <a href="/budgets" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        Budgets
                    </a>
                    <a href="/goals" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        Goals
                    </a>
                    <a href="/reports/monthly" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        Reports
                    </a>

                    <?php if (isset($_SESSION['household_id']) && $_SESSION['household_id'] > 0): ?>
                    <a href="/household/<?= $_SESSION['household_id'] ?>" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        👨‍👩‍👧 Household
                    </a>
                    <?php endif; ?>

                    <!-- Dropdown for More -->
                    <div class="relative group">
                        <button class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            More ▾
                        </button>
                        <div class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg py-1 hidden group-hover:block z-10">
                            <a href="/investments/portfolio" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                Investments
                            </a>
                            <a href="/opportunities" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                Opportunities
                            </a>
                            <a href="/scenario" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                Scenario Planning
                            </a>
                            <a href="/automation" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                Automation
                            </a>
                            <a href="/bank-import" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                Import
                            </a>
                        </div>
                    </div>
                </div>

                <!-- User Menu -->
                <div class="flex items-center space-x-4">
                    <?php if (isset($_SESSION['household_id']) && $_SESSION['household_id'] > 0): ?>
                    <!-- Notification Bell -->
                    <?php
                    $data['unread_count'] = $data['unread_count'] ?? 0;
                    include __DIR__ . '/partials/notification-bell.php';
                    ?>

                    <!-- Approval Badge -->
                    <?php if (isset($_SESSION['can_approve']) && $_SESSION['can_approve']): ?>
                    <a href="/approval/household/<?= $_SESSION['household_id'] ?>"
                       class="relative p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg"
                       title="Pending Approvals">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span id="approval-count-badge" class="hidden absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-yellow-600 rounded-full min-w-[20px]"></span>
                    </a>
                    <?php endif; ?>
                    <?php endif; ?>

                    <a href="/settings" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </a>

                    <div class="relative group">
                        <button class="flex items-center space-x-2 text-gray-700 dark:text-gray-300">
                            <div class="h-8 w-8 rounded-full bg-gradient-to-r from-purple-500 to-indigo-500 flex items-center justify-center text-white font-semibold">
                                <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                            </div>
                            <span class="hidden md:inline"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                        </button>

                        <div class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg py-1 hidden group-hover:block z-10">
                            <a href="/settings/profile" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                Profile
                            </a>
                            <a href="/settings/two-factor" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                Security
                            </a>
                            <hr class="my-1 border-gray-200 dark:border-gray-700">
                            <a href="/logout" class="block px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                                Logout
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button id="mobile-menu-button" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div id="mobile-menu" class="md:hidden hidden">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="/" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Dashboard</a>
                <a href="/transactions" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Transactions</a>
                <a href="/budgets" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Budgets</a>
                <a href="/goals" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Goals</a>
                <a href="/reports/monthly" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Reports</a>
                <?php if (isset($_SESSION['household_id']) && $_SESSION['household_id'] > 0): ?>
                <a href="/household/<?= $_SESSION['household_id'] ?>" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">👨‍👩‍👧 Household</a>
                <a href="/notifications" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">🔔 Notifications</a>
                <?php if (isset($_SESSION['can_approve']) && $_SESSION['can_approve']): ?>
                <a href="/approval/household/<?= $_SESSION['household_id'] ?>" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">✓ Approvals</a>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php if (isset($flash)): ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        <?php if (isset($flash['success'])): ?>
        <div class="bg-green-50 dark:bg-green-900 border-l-4 border-green-500 p-4 rounded">
            <p class="text-green-800 dark:text-green-200"><?php echo htmlspecialchars($flash['success']); ?></p>
        </div>
        <?php endif; ?>

        <?php if (isset($flash['error'])): ?>
        <div class="bg-red-50 dark:bg-red-900 border-l-4 border-red-500 p-4 rounded">
            <p class="text-red-800 dark:text-red-200"><?php echo htmlspecialchars($flash['error']); ?></p>
        </div>
        <?php endif; ?>

        <?php if (isset($flash['info'])): ?>
        <div class="bg-blue-50 dark:bg-blue-900 border-l-4 border-blue-500 p-4 rounded">
            <p class="text-blue-800 dark:text-blue-200"><?php echo htmlspecialchars($flash['info']); ?></p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php echo $content; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    © <?php echo date('Y'); ?> Budget Control. All rights reserved.
                </div>
                <div class="flex space-x-4 text-sm text-gray-600 dark:text-gray-400">
                    <a href="/privacy" class="hover:text-gray-900 dark:hover:text-gray-200">Privacy</a>
                    <a href="/terms" class="hover:text-gray-900 dark:hover:text-gray-200">Terms</a>
                    <a href="/help" class="hover:text-gray-900 dark:hover:text-gray-200">Help</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Mobile Menu Toggle Script -->
    <script>
        document.getElementById('mobile-menu-button')?.addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });
    </script>
</body>
</html>
