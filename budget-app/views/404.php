
<div class="flex-1 flex flex-col overflow-hidden">
    <div class="flex-1 flex items-center justify-center p-6">
        <div class="text-center">
            <h1 class="text-6xl font-bold text-gray-800 mb-4">404</h1>
            <p class="text-xl text-gray-600 mb-8">Stránka není k dispozici</p>
            <a href="/" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 inline-block">
                Zpět na domovskou stránku
            </a>
        </div>
    </div>
</div>

<?php echo $this->app->render('layout', ['content' => ob_get_clean(), 'title' => 'Page Not Found']); ?>
