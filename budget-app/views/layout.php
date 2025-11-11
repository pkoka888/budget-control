<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? htmlspecialchars($title) . ' - ' : ''; ?>Budget Control</title>
    <!-- Tailwind CSS (Pre-compiled) -->
    <link rel="stylesheet" href="/assets/css/tailwind.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0/dist/chartjs-plugin-datalabels.min.js"></script>
    <script src="https://d3js.org/d3.v7.min.js"></script>
</head>
<body class="bg-slate-gray-50">
    <!-- Skip Navigation Link -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-google-blue-600 text-white px-4 py-2 rounded z-50">
        Přeskočit na hlavní obsah
    </a>

    <!-- Mobile Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebar-overlay" aria-hidden="true"></div>

    <div class="flex h-screen">
        <!-- Sidebar -->
        <nav class="w-64 bg-white border-r border-slate-gray-200 text-slate-gray-900 flex flex-col shadow-sm" role="navigation" aria-label="Hlavní navigace">
            <div class="p-6 border-b border-slate-gray-200">
                <h1 class="text-2xl font-bold text-google-blue-600">Budget Control</h1>
                <p class="text-slate-gray-600 text-sm mt-2">Správce osobních financí</p>
            </div>

            <ul class="flex-1 p-4 space-y-2" role="menubar">
                <li role="none">
                    <a href="/" class="block px-4 py-2 rounded hover:bg-slate-gray-100 focus:bg-slate-gray-100 focus:outline-none focus:ring-2 focus:ring-google-blue-400 text-slate-gray-700" role="menuitem" aria-current="<?php echo $_SERVER['REQUEST_URI'] === '/' ? 'page' : 'false'; ?>">
                        <span class="text-lg" aria-hidden="true">📊</span> Dashboard
                    </a>
                </li>
                <li role="none">
                    <a href="/accounts" class="block px-4 py-2 rounded hover:bg-slate-gray-100 focus:bg-slate-gray-100 focus:outline-none focus:ring-2 focus:ring-google-blue-400 text-slate-gray-700" role="menuitem" aria-current="<?php echo strpos($_SERVER['REQUEST_URI'], '/accounts') === 0 ? 'page' : 'false'; ?>">
                        <span class="text-lg" aria-hidden="true">🏦</span> Účty
                    </a>
                </li>
                <li role="none">
                    <a href="/transactions" class="block px-4 py-2 rounded hover:bg-slate-gray-100 focus:bg-slate-gray-100 focus:outline-none focus:ring-2 focus:ring-google-blue-400 text-slate-gray-700" role="menuitem" aria-current="<?php echo strpos($_SERVER['REQUEST_URI'], '/transactions') === 0 ? 'page' : 'false'; ?>">
                        <span class="text-lg" aria-hidden="true">💰</span> Transakce
                    </a>
                </li>
                <li role="none">
                    <a href="/categories" class="block px-4 py-2 rounded hover:bg-slate-gray-100 focus:bg-slate-gray-100 focus:outline-none focus:ring-2 focus:ring-google-blue-400 text-slate-gray-700" role="menuitem" aria-current="<?php echo strpos($_SERVER['REQUEST_URI'], '/categories') === 0 ? 'page' : 'false'; ?>">
                        <span class="text-lg" aria-hidden="true">🏷️</span> Kategorie
                    </a>
                </li>
                <li role="none">
                    <a href="/budgets" class="block px-4 py-2 rounded hover:bg-slate-gray-100 focus:bg-slate-gray-100 focus:outline-none focus:ring-2 focus:ring-google-blue-400 text-slate-gray-700" role="menuitem" aria-current="<?php echo strpos($_SERVER['REQUEST_URI'], '/budgets') === 0 ? 'page' : 'false'; ?>">
                        <span class="text-lg" aria-hidden="true">📈</span> Rozpočty
                    </a>
                </li>
                <li role="none">
                    <a href="/investments" class="block px-4 py-2 rounded hover:bg-slate-gray-100 focus:bg-slate-gray-100 focus:outline-none focus:ring-2 focus:ring-google-blue-400 text-slate-gray-700" role="menuitem" aria-current="<?php echo strpos($_SERVER['REQUEST_URI'], '/investments') === 0 ? 'page' : 'false'; ?>">
                        <span class="text-lg" aria-hidden="true">📈</span> Investice
                    </a>
                </li>
                <li role="none">
                    <a href="/import" class="block px-4 py-2 rounded hover:bg-slate-gray-100 focus:bg-slate-gray-100 focus:outline-none focus:ring-2 focus:ring-google-blue-400 text-slate-gray-700" role="menuitem" aria-current="<?php echo strpos($_SERVER['REQUEST_URI'], '/import') === 0 ? 'page' : 'false'; ?>">
                        <span class="text-lg" aria-hidden="true">📥</span> Import CSV
                    </a>
                </li>
                <li role="none">
                    <a href="/goals" class="block px-4 py-2 rounded hover:bg-slate-gray-100 focus:bg-slate-gray-100 focus:outline-none focus:ring-2 focus:ring-google-blue-400 text-slate-gray-700" role="menuitem" aria-current="<?php echo strpos($_SERVER['REQUEST_URI'], '/goals') === 0 ? 'page' : 'false'; ?>">
                        <span class="text-lg" aria-hidden="true">🎯</span> Cíle
                    </a>
                </li>
                <li role="none">
                    <a href="/reports/monthly" class="block px-4 py-2 rounded hover:bg-slate-gray-100 focus:bg-slate-gray-100 focus:outline-none focus:ring-2 focus:ring-google-blue-400 text-slate-gray-700" role="menuitem" aria-current="<?php echo strpos($_SERVER['REQUEST_URI'], '/reports') === 0 ? 'page' : 'false'; ?>">
                        <span class="text-lg" aria-hidden="true">📋</span> Zprávy
                    </a>
                </li>
                <li role="none">
                    <a href="/tips" class="block px-4 py-2 rounded hover:bg-slate-gray-100 focus:bg-slate-gray-100 focus:outline-none focus:ring-2 focus:ring-google-blue-400 text-slate-gray-700" role="menuitem" aria-current="<?php echo strpos($_SERVER['REQUEST_URI'], '/tips') === 0 ? 'page' : 'false'; ?>">
                        <span class="text-lg" aria-hidden="true">💡</span> Tipy & Průvodce
                    </a>
                </li>
            </ul>

            <div class="border-t border-slate-gray-200 p-4">
                <a href="/settings" class="block px-4 py-2 rounded hover:bg-slate-gray-100 focus:bg-slate-gray-100 focus:outline-none focus:ring-2 focus:ring-google-blue-400 mb-2 text-slate-gray-700" role="menuitem" aria-current="<?php echo strpos($_SERVER['REQUEST_URI'], '/settings') === 0 ? 'page' : 'false'; ?>">
                    <span class="text-lg" aria-hidden="true">⚙️</span> Nastavení
                </a>
                <a href="/logout" class="block px-4 py-2 rounded hover:bg-slate-gray-100 focus:bg-slate-gray-100 focus:outline-none focus:ring-2 focus:ring-google-blue-400 text-google-red-600 hover:text-google-red-700 font-medium" role="menuitem">
                    <span class="text-lg" aria-hidden="true">🚪</span> Odhlásit se
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Bar -->
            <header class="bg-white border-b border-slate-gray-200 px-4 md:px-8 py-4 flex justify-between items-center shadow-sm" role="banner">
                <div class="flex items-center space-x-4">
                    <!-- Hamburger Menu Button (Mobile) -->
                    <button id="sidebar-toggle" class="md:hidden p-2 rounded-lg hover:bg-slate-gray-100 focus:outline-none focus:ring-2 focus:ring-google-blue-500" aria-label="Otevřít menu" aria-expanded="false">
                        <svg class="w-6 h-6 text-slate-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <h1 class="text-xl md:text-2xl font-bold text-slate-gray-900"><?php echo $title ?? 'Dashboard'; ?></h1>
                </div>
                <div class="flex items-center space-x-4" role="toolbar" aria-label="Uživatelské akce">
                    <!-- Theme Toggle Button -->
                    <button id="theme-toggle" class="p-2 rounded-lg hover:bg-slate-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-google-blue-500" aria-label="Přepnout mezi světlým a tmavým tématem">
                        <svg class="w-5 h-5 text-slate-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </button>
                    <span class="text-slate-gray-600 hidden sm:block font-medium" aria-label="Přihlášený uživatel"><?php echo htmlspecialchars($user['name'] ?? 'Uživatel'); ?></span>
                    <div class="w-10 h-10 bg-google-blue-600 rounded-full flex items-center justify-center text-white font-bold text-sm" role="img" aria-label="Avatar uživatele <?php echo htmlspecialchars($user['name'] ?? 'Uživatel'); ?>">
                        <?php echo strtoupper(substr($user['name'] ?? 'U', 0, 1)); ?>
                    </div>
                </div>
            </header>

            <!-- Flash Messages -->
            <?php if (isset($flash) && $flash): ?>
                <div class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : 'error'; ?> animate-slide-in-down mx-4 mt-4 mb-4" role="alert" aria-live="polite">
                    <?php echo $flash['type'] === 'success' ? '✓' : '✗'; ?> <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>

            <!-- Content Area -->
            <main id="main-content" class="flex-1 overflow-auto px-8 py-6" role="main">
                <?php echo $content ?? ''; ?>
            </main>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
</body>
</html>
