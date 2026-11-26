<?php

declare(strict_types=1);

/**
 * @var \App\Models\User $user
 * @var array<int, string> $supportedLocales
 * @var callable $e
 * @var callable $t
 * @var callable $url
 * @var callable $csrfField
 */
?>
<div class="container app-shell" style="max-width:760px;">
    <div class="page-top">
        <div>
            <h1><?= $e($t('settings.title')) ?></h1>
            <p style="margin:0;color:var(--ink-600);"><?= $e($t('settings.subtitle')) ?></p>
        </div>
    </div>

    <div class="alert alert-info" style="margin-bottom:20px;">
        <?= $e($t('settings.founding_info')) ?>
    </div>

    <form method="post" action="<?= $e($url('/app/settings')) ?>" class="card">
        <?= $csrfField() ?>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label" for="s-business"><?= $e($t('auth.business_name')) ?></label>
                <input id="s-business" class="form-control" type="text" name="business_name" required value="<?= $e($user->businessName) ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="s-sector"><?= $e($t('auth.sector')) ?></label>
                <select id="s-sector" class="form-select" name="sector">
                    <?php foreach (['traiteur', 'slager', 'bakker', 'vishandel', 'other'] as $sec): ?>
                        <option value="<?= $e($sec) ?>" <?= $user->sector === $sec ? 'selected' : '' ?>><?= $e($t('auth.sector_' . $sec)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="s-locale"><?= $e($t('settings.locale')) ?></label>
            <select id="s-locale" class="form-select" name="locale">
                <?php foreach ($supportedLocales as $loc): ?>
                    <option value="<?= $e($loc) ?>" <?= $user->locale === $loc ? 'selected' : '' ?>><?= $e(strtoupper($loc)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label" for="s-dates"><?= $e($t('settings.pickup_dates')) ?></label>
                <textarea id="s-dates" class="form-textarea" name="pickup_dates" rows="6"><?= $e(implode("\n", $user->pickupDates)) ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="s-slots"><?= $e($t('settings.time_slots')) ?></label>
                <textarea id="s-slots" class="form-textarea" name="time_slots" rows="6"><?= $e(implode("\n", $user->timeSlots)) ?></textarea>
            </div>
        </div>

        <button type="submit" class="btn btn-primary"><?= $e($t('settings.save_btn')) ?></button>
    </form>
</div>
