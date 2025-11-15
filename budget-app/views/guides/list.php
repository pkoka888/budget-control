<?php /** Educational Guides */ ?>
<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold mb-2">Vzdělávací průvodce</h1>
        <p class="text-gray-600">Naučte se efektivně spravovat své finance</p>
    </div>

    <!-- Search and Filter -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <form method="GET" action="/guides" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <input type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" class="w-full border rounded px-4 py-2" placeholder="Hledat průvodce...">
            </div>
            <div>
                <select name="category" class="w-full border rounded px-4 py-2" onchange="this.form.submit()">
                    <option value="">Všechny kategorie</option>
                    <option value="budgeting" <?php echo ($_GET['category'] ?? '') === 'budgeting' ? 'selected' : ''; ?>>Rozpočtování</option>
                    <option value="saving" <?php echo ($_GET['category'] ?? '') === 'saving' ? 'selected' : ''; ?>>Spoření</option>
                    <option value="investing" <?php echo ($_GET['category'] ?? '') === 'investing' ? 'selected' : ''; ?>>Investování</option>
                    <option value="debt" <?php echo ($_GET['category'] ?? '') === 'debt' ? 'selected' : ''; ?>>Správa dluhů</option>
                    <option value="taxes" <?php echo ($_GET['category'] ?? '') === 'taxes' ? 'selected' : ''; ?>>Daně</option>
                </select>
            </div>
        </form>
    </div>

    <!-- Featured Guides -->
    <?php if (!empty($featured_guides)): ?>
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-4">Doporučené průvodce</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach ($featured_guides as $guide): ?>
                    <a href="/guides/<?php echo $guide['id']; ?>" class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white hover:shadow-xl transition">
                        <h3 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($guide['title'] ?? ''); ?></h3>
                        <p class="text-blue-100 mb-4"><?php echo htmlspecialchars($guide['description'] ?? ''); ?></p>
                        <div class="flex items-center text-sm text-blue-100">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path>
                            </svg>
                            <?php echo number_format($guide['views'] ?? 0); ?> zobrazení
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- All Guides -->
    <div>
        <h2 class="text-xl font-semibold mb-4">Všechny průvodce</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (!empty($guides)): ?>
                <?php foreach ($guides as $guide): ?>
                    <div class="bg-white rounded-lg shadow hover:shadow-lg transition">
                        <?php if (!empty($guide['image'])): ?>
                            <img src="<?php echo htmlspecialchars($guide['image']); ?>" alt="" class="w-full h-48 object-cover rounded-t-lg">
                        <?php else: ?>
                            <div class="w-full h-48 bg-gradient-to-br from-blue-400 to-blue-600 rounded-t-lg"></div>
                        <?php endif; ?>

                        <div class="p-6">
                            <div class="flex items-center text-xs text-gray-500 mb-2">
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded">
                                    <?php
                                        $categories = [
                                            'budgeting' => 'Rozpočtování',
                                            'saving' => 'Spoření',
                                            'investing' => 'Investování',
                                            'debt' => 'Dluhy',
                                            'taxes' => 'Daně'
                                        ];
                                        echo $categories[$guide['category']] ?? $guide['category'];
                                    ?>
                                </span>
                                <span class="ml-2"><?php echo $guide['read_time'] ?? 5; ?> min čtení</span>
                            </div>

                            <h3 class="text-lg font-semibold mb-2">
                                <a href="/guides/<?php echo $guide['id']; ?>" class="hover:text-blue-600">
                                    <?php echo htmlspecialchars($guide['title'] ?? ''); ?>
                                </a>
                            </h3>

                            <p class="text-gray-600 text-sm mb-4">
                                <?php echo htmlspecialchars(mb_substr($guide['description'] ?? '', 0, 100)); ?>...
                            </p>

                            <div class="flex items-center justify-between text-sm text-gray-500">
                                <span><?php echo number_format($guide['views'] ?? 0); ?> zobrazení</span>
                                <a href="/guides/<?php echo $guide['id']; ?>" class="text-blue-600 hover:text-blue-800">
                                    Číst více →
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-3 text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Žádné průvodce</h3>
                    <p class="text-gray-600">Nenašli jsme žádné průvodce odpovídající vašim kritériím.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Popular Topics -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mt-8">
        <h3 class="font-semibold text-blue-900 mb-3">Populární témata</h3>
        <div class="flex flex-wrap gap-2">
            <a href="/guides?tag=rozpocet" class="px-3 py-1 bg-white text-blue-800 rounded-full text-sm hover:bg-blue-100">Rozpočet</a>
            <a href="/guides?tag=sporeni" class="px-3 py-1 bg-white text-blue-800 rounded-full text-sm hover:bg-blue-100">Spoření</a>
            <a href="/guides?tag=investice" class="px-3 py-1 bg-white text-blue-800 rounded-full text-sm hover:bg-blue-100">Investice</a>
            <a href="/guides?tag=danovepriznani" class="px-3 py-1 bg-white text-blue-800 rounded-full text-sm hover:bg-blue-100">Daňové přiznání</a>
            <a href="/guides?tag=duchod" class="px-3 py-1 bg-white text-blue-800 rounded-full text-sm hover:bg-blue-100">Důchod</a>
            <a href="/guides?tag=pojisteni" class="px-3 py-1 bg-white text-blue-800 rounded-full text-sm hover:bg-blue-100">Pojištění</a>
        </div>
    </div>
</div>
