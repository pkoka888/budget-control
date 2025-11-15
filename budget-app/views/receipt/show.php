<?php /** Receipt Details */ ?>
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Detail účtenky</h1>
        <a href="/transactions/<?php echo $receipt['transaction_id'] ?? ''; ?>" class="text-blue-600 hover:text-blue-800">
            ← Zpět na transakci
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Receipt Image -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Naskenovaná účtenka</h2>

            <?php if (!empty($receipt['image_path'])): ?>
                <img src="<?php echo htmlspecialchars($receipt['image_path']); ?>" alt="Receipt" class="w-full rounded-lg border">

                <div class="mt-4 flex gap-2">
                    <a href="<?php echo htmlspecialchars($receipt['image_path']); ?>" download class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded text-center text-sm">
                        Stáhnout obrázek
                    </a>
                    <button onclick="window.print()" class="flex-1 bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded text-sm">
                        Vytisknout
                    </button>
                </div>
            <?php else: ?>
                <div class="bg-gray-100 h-96 rounded-lg flex items-center justify-center">
                    <p class="text-gray-500">Žádný obrázek účtenky</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Receipt Data -->
        <div class="space-y-6">
            <!-- Merchant Information -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Informace o obchodníkovi</h2>

                <div class="space-y-3">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-1">Název</h3>
                        <p class="text-lg"><?php echo htmlspecialchars($receipt['merchant_name'] ?? 'Neuvedeno'); ?></p>
                    </div>

                    <?php if (!empty($receipt['merchant_address'])): ?>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-1">Adresa</h3>
                        <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($receipt['merchant_address'])); ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($receipt['merchant_tax_id'])): ?>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-1">IČO</h3>
                        <p class="text-gray-700"><?php echo htmlspecialchars($receipt['merchant_tax_id']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Receipt Details -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Detaily účtenky</h2>

                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Datum</span>
                        <span class="font-medium"><?php echo isset($receipt['receipt_date']) ? date('d.m.Y H:i', strtotime($receipt['receipt_date'])) : '—'; ?></span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Číslo účtenky</span>
                        <span class="font-medium"><?php echo htmlspecialchars($receipt['receipt_number'] ?? '—'); ?></span>
                    </div>

                    <div class="flex justify-between pt-3 border-t">
                        <span class="text-gray-600">Mezisoučet</span>
                        <span class="font-medium"><?php echo number_format($receipt['subtotal'] ?? 0, 2); ?> Kč</span>
                    </div>

                    <?php if (!empty($receipt['tax_amount'])): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">DPH</span>
                        <span class="font-medium"><?php echo number_format($receipt['tax_amount'], 2); ?> Kč</span>
                    </div>
                    <?php endif; ?>

                    <div class="flex justify-between pt-3 border-t">
                        <span class="font-semibold text-lg">Celkem</span>
                        <span class="font-bold text-lg"><?php echo number_format($receipt['total_amount'] ?? 0, 2); ?> Kč</span>
                    </div>

                    <?php if (!empty($receipt['payment_method'])): ?>
                    <div class="flex justify-between pt-3 border-t">
                        <span class="text-gray-600">Způsob platby</span>
                        <span class="font-medium"><?php echo htmlspecialchars($receipt['payment_method']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Line Items -->
            <?php if (!empty($receipt['items'])): ?>
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Položky</h2>

                <div class="space-y-2">
                    <?php foreach (json_decode($receipt['items'], true) ?? [] as $item): ?>
                        <div class="flex justify-between items-start border-b pb-2">
                            <div class="flex-1">
                                <p class="font-medium"><?php echo htmlspecialchars($item['name'] ?? ''); ?></p>
                                <?php if (!empty($item['quantity'])): ?>
                                    <p class="text-sm text-gray-500"><?php echo $item['quantity']; ?> × <?php echo number_format($item['unit_price'] ?? 0, 2); ?> Kč</p>
                                <?php endif; ?>
                            </div>
                            <span class="font-medium"><?php echo number_format($item['amount'] ?? 0, 2); ?> Kč</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- OCR Confidence -->
            <?php if (!empty($receipt['ocr_confidence'])): ?>
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Přesnost OCR rozpoznání</span>
                    <span class="font-medium"><?php echo number_format($receipt['ocr_confidence'], 1); ?>%</span>
                </div>
                <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-500 h-2 rounded-full" style="width: <?php echo $receipt['ocr_confidence']; ?>%"></div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Actions -->
            <div class="flex gap-3">
                <a href="/receipt/<?php echo $receipt['id']; ?>/edit" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded text-center">
                    Upravit
                </a>
                <button onclick="deleteReceipt()" class="flex-1 bg-red-500 hover:bg-red-600 text-white px-6 py-2 rounded">
                    Smazat
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function deleteReceipt() {
    if (confirm('Opravdu chcete smazat tuto účtenku? Transakce zůstane zachována.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/receipt/<?php echo $receipt['id']; ?>/delete';

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
