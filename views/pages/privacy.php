<?php

declare(strict_types=1);

/**
 * @var callable $e
 * @var callable $t
 */
?>
<section class="section">
    <div class="container prose">
        <h1 style="margin:0 0 6px;"><?= $e($t('privacy.title')) ?></h1>
        <p><small><?= $e($t('privacy.updated')) ?></small></p>
        <p><?= $e($t('privacy.intro')) ?></p>

        <?php foreach ([1, 2, 3, 4, 5, 6] as $i): ?>
            <h2><?= $e($t('privacy.s' . $i . '_title')) ?></h2>
            <p><?= $e($t('privacy.s' . $i . '_body')) ?></p>
        <?php endforeach; ?>
    </div>
</section>
