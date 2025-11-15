<?php /** Categories List */ ?>
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Kategorie</h1>
        <a href="/categories/create" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg">
            + Nová kategorie
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Expense Categories -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Výdajové kategorie</h2>
            <div class="space-y-2">
                <?php if (!empty($expense_categories)): ?>
                    <?php foreach ($expense_categories as $category): ?>
                        <div class="flex justify-between items-center p-3 hover:bg-gray-50 rounded">
                            <span><?php echo htmlspecialchars($category['name']); ?></span>
                            <div class="flex gap-2">
                                <a href="/categories/<?php echo $category['id']; ?>/edit" class="text-blue-600 hover:text-blue-800 text-sm">Upravit</a>
                                <button onclick="deleteCategory(<?php echo $category['id']; ?>)" class="text-red-600 hover:text-red-800 text-sm">Smazat</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-500 text-sm">Žádné výdajové kategorie</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Income Categories -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Příjmové kategorie</h2>
            <div class="space-y-2">
                <?php if (!empty($income_categories)): ?>
                    <?php foreach ($income_categories as $category): ?>
                        <div class="flex justify-between items-center p-3 hover:bg-gray-50 rounded">
                            <span><?php echo htmlspecialchars($category['name']); ?></span>
                            <div class="flex gap-2">
                                <a href="/categories/<?php echo $category['id']; ?>/edit" class="text-blue-600 hover:text-blue-800 text-sm">Upravit</a>
                                <button onclick="deleteCategory(<?php echo $category['id']; ?>)" class="text-red-600 hover:text-red-800 text-sm">Smazat</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-500 text-sm">Žádné příjmové kategorie</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function deleteCategory(id) {
    if (confirm('Opravdu chcete smazat tuto kategorii?')) {
        fetch('/categories/' + id, {
            method: 'DELETE',
            headers: {
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            }
        }).then(() => location.reload());
    }
}
</script>
