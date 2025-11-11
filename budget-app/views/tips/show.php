<?php
// Tip Detail View
?>

<div class="max-w-4xl mx-auto">
    <a href="/tips" class="text-blue-600 hover:text-blue-800 font-medium mb-6 inline-block">← Zpět na tipy</a>

    <article class="bg-white rounded-lg shadow p-8 mb-8">
        <div class="mb-6">
            <h1 class="text-4xl font-bold text-gray-900 mb-3"><?php echo htmlspecialchars($tip['title']); ?></h1>
            <div class="flex items-center space-x-4 text-sm text-gray-600">
                <span><?php echo date('d. m. Y', strtotime($tip['created_at'])); ?></span>
                <span>•</span>
                <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full">
                    <?php echo htmlspecialchars($tip['category']); ?>
                </span>
            </div>
        </div>

        <div class="prose prose-sm max-w-none mb-8 text-gray-700 leading-relaxed">
            <?php echo $tip['content']; ?>
        </div>

        <?php if ($tip['tags']): ?>
            <div class="mt-8 pt-8 border-t border-gray-200">
                <p class="text-sm font-semibold text-gray-700 mb-3">Štítky:</p>
                <div class="flex flex-wrap gap-2">
                    <?php foreach (explode(',', $tip['tags']) as $tag): ?>
                        <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm">
                            <?php echo htmlspecialchars(trim($tag)); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </article>

    <!-- Related Tips -->
    <?php if (!empty($related)): ?>
        <section class="mt-12">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Související tipy</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($related as $relatedTip): ?>
                    <a href="/tips/<?php echo $relatedTip['id']; ?>" class="bg-white rounded-lg shadow hover:shadow-lg transition p-6">
                        <h3 class="font-bold text-gray-800 mb-2 line-clamp-2">
                            <?php echo htmlspecialchars($relatedTip['title']); ?>
                        </h3>
                        <p class="text-gray-600 text-sm line-clamp-2">
                            <?php echo htmlspecialchars(strip_tags(substr($relatedTip['content'], 0, 100))); ?>
                        </p>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.prose {
    font-size: 1rem;
    line-height: 1.6;
}

.prose h2 {
    font-size: 1.5rem;
    font-weight: bold;
    margin-top: 1.5rem;
    margin-bottom: 1rem;
    color: #1f2937;
}

.prose h3 {
    font-size: 1.25rem;
    font-weight: bold;
    margin-top: 1.25rem;
    margin-bottom: 0.75rem;
    color: #374151;
}

.prose p {
    margin-bottom: 1rem;
}

.prose ul, .prose ol {
    margin-left: 1.5rem;
    margin-bottom: 1rem;
}

.prose li {
    margin-bottom: 0.5rem;
}

.prose strong {
    font-weight: bold;
    color: #111827;
}

.prose em {
    font-style: italic;
}
</style>
