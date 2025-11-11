
<div class="flex-1 flex flex-col overflow-hidden">
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Kategorie</h1>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                <?php foreach ($categories as $category): ?>
                    <div class="bg-white rounded-lg shadow p-4 border-l-4" style="border-color: <?php echo htmlspecialchars($category['color']); ?>;">
                        <h3 class="font-bold text-gray-800"><?php echo htmlspecialchars($category['name']); ?></h3>
                        <p class="text-gray-600 text-sm">Celkem vydáno: <strong><?php echo number_format($category['total_spending'], 0, ',', ' '); ?> Kč</strong></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold mb-4">Přidat kategorii</h2>
                <form onsubmit="createCategory(event)" class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Jméno kategorie</label>
                        <input type="text" id="name" name="name" required class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label for="color" class="block text-sm font-medium text-gray-700">Barva</label>
                        <input type="color" id="color" name="color" value="#3b82f6" class="mt-1 h-10 w-20 border border-gray-300 rounded-lg">
                    </div>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        Přidat
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function createCategory(event) {
    event.preventDefault();
    const form = event.target;
    const data = new FormData(form);

    fetch('/categories', {
        method: 'POST',
        body: data
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) {
            alert('Chyba: ' + data.error);
        } else {
            location.reload();
        }
    })
    .catch(err => alert('Chyba: ' + err));
}
</script>
