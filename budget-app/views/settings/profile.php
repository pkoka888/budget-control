<?php /** Settings - Profile */ ?>
<div class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Nastavení profilu</h1>
    <div class="bg-white rounded-lg shadow-md p-6">
        <form method="POST" action="/settings/profile">
            <div class="space-y-4">
                <div>
                    <label class="form-label">Jméno</label>
                    <input type="text" name="name" class="form-input" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                </div>
                <div>
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Měna</label>
                        <select name="currency" class="form-input">
                            <option value="CZK">CZK - Koruna</option>
                            <option value="EUR">EUR - Euro</option>
                            <option value="USD">USD - Dolar</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Časové pásmo</label>
                        <select name="timezone" class="form-input">
                            <option value="Europe/Prague">Praha</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Uložit změny</button>
            </div>
        </form>
    </div>
</div>
