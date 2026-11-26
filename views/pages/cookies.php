<?php

declare(strict_types=1);

/**
 * @var callable $e
 * @var callable $t
 */
?>
<section class="section">
    <div class="container prose">
        <h1 style="margin:0 0 12px;"><?= $e($t('cookies.title')) ?></h1>
        <p><?= $e($t('cookies.intro')) ?></p>

        <div class="table-wrap" style="margin:18px 0;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?= $e($t('cookies.col_name')) ?></th>
                        <th><?= $e($t('cookies.col_purpose')) ?></th>
                        <th><?= $e($t('cookies.col_duration')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ([1, 2, 3] as $i): ?>
                        <tr>
                            <td><code><?= $e($t('cookies.row' . $i . '_name')) ?></code></td>
                            <td><?= $e($t('cookies.row' . $i . '_purpose')) ?></td>
                            <td><?= $e($t('cookies.row' . $i . '_duration')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <p><?= $e($t('cookies.outro')) ?></p>
    </div>
</section>
