<?php

declare(strict_types=1);

/**
 * Business Intelligence dashboard voor XOrder:
 * terugblik op prestaties en prognose/voorbereiding voor volgend jaar.
 *
 * @var \App\Core\App $app
 * @var \App\Core\Request $request
 * @var \App\Models\User|null $user
 * @var string $featureName
 * @var callable $asset
 * @var callable $e
 * @var callable $url
 */

if ($user === null) {
    ?>
    <link rel="stylesheet" href="<?= $e($asset('lily.css')) ?>">
    <div class="card">
        <h1>Business Intelligence</h1>
        <p class="lily-muted">Log eerst in om je omzet, topgerechten en prognose voor volgend jaar te bekijken.</p>
        <p><a href="<?= $e($url('/login')) ?>" class="btn btn-primary btn-sm">Inloggen</a></p>
    </div>
    <?php
    return;
}

$eur = static fn (float $value): string => '€ ' . number_format($value, 2, ',', '.');
$qtyFmt = static fn (float $value): string => fmod($value, 1.0) === 0.0
    ? (string) (int) $value
    : number_format($value, 2, ',', '.');

// Beschikbare jaren en GET-filters valideren
$yearRows = $app->db->fetchAll(
    "SELECT DISTINCT SUBSTR(pickup_date, 1, 4) AS y
     FROM orders
     WHERE user_id = ? AND pickup_date != ''
     ORDER BY y DESC",
    [$user->id]
);
$availableYears = array_values(array_filter(
    array_map(static fn (array $row): string => (string) ($row['y'] ?? ''), $yearRows),
    static fn (string $y): bool => $y !== ''
));

$year = (string) $request->query('year', 'all');
if ($year !== 'all' && !in_array($year, $availableYears, true)) {
    $year = 'all';
}

$allowedGrowth = [0, 5, 10, 15, 20, 30];
$growth = (int) $request->query('growth', 10);
if (!in_array($growth, $allowedGrowth, true)) {
    $growth = 10;
}

$yearSql = $year !== 'all' ? ' AND SUBSTR(o.pickup_date, 1, 4) = ?' : '';
$baseParams = $year !== 'all' ? [$user->id, $year] : [$user->id];

// 1. Kerncijfers (geannuleerde bestellingen apart tellen, niet meenemen in omzet)
$summary = $app->db->fetchOne(
    "SELECT
        COUNT(*) AS total_orders,
        SUM(CASE WHEN o.status != 'cancelled' THEN 1 ELSE 0 END) AS active_orders,
        SUM(CASE WHEN o.status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_orders,
        COALESCE(SUM(CASE WHEN o.status != 'cancelled' THEN o.total_amount ELSE 0 END), 0) AS revenue,
        COALESCE(SUM(
            CASE
                WHEN o.status != 'cancelled' AND o.payment_status != 'paid'
                    THEN CASE WHEN o.total_amount > o.deposit_amount THEN o.total_amount - o.deposit_amount ELSE 0 END
                ELSE 0
            END
        ), 0) AS open_balance,
        SUM(CASE WHEN o.status != 'cancelled' AND o.payment_status = 'paid' THEN 1 ELSE 0 END) AS paid_orders
     FROM orders o
     WHERE o.user_id = ?{$yearSql}",
    $baseParams
) ?? [];

// 2. Topgerechten op omzet en aantal
$topDishes = $app->db->fetchAll(
    "SELECT
        oi.product_name,
        oi.unit,
        SUM(oi.quantity) AS total_qty,
        SUM(oi.line_total) AS total_revenue,
        COUNT(DISTINCT o.id) AS order_count
     FROM order_items oi
     INNER JOIN orders o ON o.id = oi.order_id
     WHERE o.user_id = ? AND o.status != 'cancelled'{$yearSql}
     GROUP BY oi.product_name, oi.unit
     ORDER BY total_revenue DESC, total_qty DESC",
    $baseParams
);

// 3. Piekdagen en piektijdslots
$peakDays = $app->db->fetchAll(
    "SELECT
        o.pickup_date,
        COUNT(*) AS order_count,
        COALESCE(SUM(o.total_amount), 0) AS revenue
     FROM orders o
     WHERE o.user_id = ? AND o.status != 'cancelled'{$yearSql}
     GROUP BY o.pickup_date
     ORDER BY order_count DESC, revenue DESC",
    $baseParams
);

$peakSlots = $app->db->fetchAll(
    "SELECT
        COALESCE(NULLIF(TRIM(o.pickup_slot), ''), 'Geen tijdslot') AS slot,
        COUNT(*) AS order_count,
        COALESCE(SUM(o.total_amount), 0) AS revenue
     FROM orders o
     WHERE o.user_id = ? AND o.status != 'cancelled'{$yearSql}
     GROUP BY slot
     ORDER BY order_count DESC, revenue DESC",
    $baseParams
);

// 4. Actieve producten zonder bestellingen in de gekozen periode
$unsoldProducts = $app->db->fetchAll(
    "SELECT p.name
     FROM products p
     WHERE p.user_id = ?
       AND p.is_active = 1
       AND NOT EXISTS (
           SELECT 1
           FROM order_items oi
           INNER JOIN orders o ON o.id = oi.order_id
           WHERE o.user_id = p.user_id
             AND o.status != 'cancelled'{$yearSql}
             AND (oi.product_id = p.id OR LOWER(oi.product_name) = LOWER(p.name))
       )
     ORDER BY p.name ASC",
    $baseParams
);

// Berekeningen & prognose
$totalOrders = (int) ($summary['total_orders'] ?? 0);
$activeOrders = (int) ($summary['active_orders'] ?? 0);
$cancelledOrders = (int) ($summary['cancelled_orders'] ?? 0);
$revenue = (float) ($summary['revenue'] ?? 0.0);
$openBalance = (float) ($summary['open_balance'] ?? 0.0);
$paidOrders = (int) ($summary['paid_orders'] ?? 0);

$avgOrder = $activeOrders > 0 ? $revenue / $activeOrders : 0.0;
$paidRate = $activeOrders > 0 ? ($paidOrders / $activeOrders) * 100 : 0.0;
$cancelRate = $totalOrders > 0 ? ($cancelledOrders / $totalOrders) * 100 : 0.0;

$factor = 1.0 + ($growth / 100.0);
$projectedRevenue = $revenue * $factor;
$projectedOrders = (int) ceil($activeOrders * $factor);

$maxDayOrders = $peakDays !== [] ? max(1, (int) $peakDays[0]['order_count']) : 1;
$maxSlotOrders = $peakSlots !== [] ? max(1, (int) $peakSlots[0]['order_count']) : 1;

// Slim actieplan voor volgend jaar
$actions = [];
if ($peakDays !== []) {
    $busiestDay = $peakDays[0];
    $dayCount = (int) $busiestDay['order_count'];
    $dayProj = (int) ceil($dayCount * $factor);
    $actions[] = sprintf(
        'Extra personeel & mise-en-place voorzien rond %s: piek van %d bestellingen (prognose +%d%%: %d).',
        (string) $busiestDay['pickup_date'],
        $dayCount,
        $growth,
        $dayProj
    );
}

if ($peakSlots !== [] && $activeOrders > 0) {
    $busiestSlot = $peakSlots[0];
    $slotCount = (int) $busiestSlot['order_count'];
    $slotShare = (int) round(($slotCount / $activeOrders) * 100);
    $actions[] = sprintf(
        'Tijdslot "%s" vangt %d%% van alle afhalingen op (%d bestellingen): overweeg een extra afhaalbalie of limiet per slot.',
        (string) $busiestSlot['slot'],
        $slotShare,
        $slotCount
    );
}

if ($topDishes !== [] && $revenue > 0) {
    $top3Revenue = 0.0;
    $top3Names = [];
    foreach (array_slice($topDishes, 0, 3) as $dish) {
        $top3Revenue += (float) $dish['total_revenue'];
        $top3Names[] = (string) $dish['product_name'];
    }
    $top3Share = (int) round(($top3Revenue / $revenue) * 100);
    $actions[] = sprintf(
        'Top %d gerechten (%s) leveren %d%% van je omzet op: bestel hiervoor tijdig grootverpakkingen en maak vooraf klaar.',
        count($top3Names),
        implode(', ', $top3Names),
        $top3Share
    );
}

if ($openBalance > 0 || $paidRate < 80.0) {
    $actions[] = sprintf(
        'Er staat nog %s open (%d%% volledig betaald): vraag volgend jaar standaard een voorschot bij bestelling.',
        $eur($openBalance),
        (int) round($paidRate)
    );
} else {
    $actions[] = sprintf(
        'Sterke betalingsopvolging (%d%% volledig betaald): behoud je huidige afrekenflow voor volgend jaar.',
        (int) round($paidRate)
    );
}

if ($cancelRate >= 10.0) {
    $actions[] = sprintf(
        'Annulatiegraad ligt op %d%% (%d bestellingen): voer een vroegere bevestigingsdeadline of voorschot in.',
        (int) round($cancelRate),
        $cancelledOrders
    );
}

if ($unsoldProducts !== []) {
    $unsoldNames = array_map(static fn (array $row): string => (string) $row['name'], $unsoldProducts);
    $preview = implode(', ', array_slice($unsoldNames, 0, 6));
    if (count($unsoldNames) > 6) {
        $preview .= ', ...';
    }
    $actions[] = sprintf(
        '%d actieve gerechten kregen 0 bestellingen (%s): overweeg deze te schrappen voor een strakkere kaart.',
        count($unsoldNames),
        $preview
    );
}
?>
<link rel="stylesheet" href="<?= $e($asset('lily.css')) ?>">

<div class="page-top">
    <div>
        <span class="badge badge-gold">Business Intelligence</span>
        <h1>Overzicht &amp; voorbereiding volgend jaar</h1>
        <p class="lily-muted"><?= $e($user->businessName) ?> · periode: <?= $e($year === 'all' ? 'Alle jaren' : $year) ?></p>
    </div>

    <form method="get" action="<?= $e($url('/features')) ?>" class="lily-filters no-print">
        <input type="hidden" name="name" value="<?= $e($featureName) ?>">
        <?php if ($availableYears !== []): ?>
            <div class="lily-filter-field">
                <label class="form-label" for="lily-year">Jaar</label>
                <select id="lily-year" name="year" class="form-select">
                    <option value="all"<?= $year === 'all' ? ' selected' : '' ?>>Alle jaren</option>
                    <?php foreach ($availableYears as $y): ?>
                        <option value="<?= $e($y) ?>"<?= $year === $y ? ' selected' : '' ?>><?= $e($y) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <div class="lily-filter-field">
            <label class="form-label" for="lily-growth">Groei volgend jaar</label>
            <select id="lily-growth" name="growth" class="form-select">
                <?php foreach ($allowedGrowth as $pct): ?>
                    <option value="<?= $e((string) $pct) ?>"<?= $growth === $pct ? ' selected' : '' ?>>+<?= $e((string) $pct) ?>%</option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Toepassen</button>
    </form>
</div>

<?php if ($totalOrders === 0): ?>
    <div class="card">
        <h2>Nog geen bestellingen om te analyseren</h2>
        <p class="lily-muted">Zodra er bestellingen zijn geregistreerd, verschijnen hier je omzetcijfers, topgerechten, piekmomenten en het actieplan voor volgend jaar.</p>
        <p><a href="<?= $e($url('/app/order-new')) ?>" class="btn btn-primary btn-sm">Eerste bestelling toevoegen</a></p>
    </div>
    <?php return; ?>
<?php endif; ?>

<div class="grid-4 lily-section">
    <div class="stat-card">
        <div class="stat-label">Totale omzet</div>
        <div class="stat-value"><?= $e($eur($revenue)) ?></div>
        <div class="lily-stat-sub">Prognose (+<?= $e((string) $growth) ?>%): <?= $e($eur($projectedRevenue)) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Actieve bestellingen</div>
        <div class="stat-value"><?= $e((string) $activeOrders) ?></div>
        <div class="lily-stat-sub">Gem. <?= $e($eur($avgOrder)) ?> · prognose: <?= $e((string) $projectedOrders) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Volledig betaald</div>
        <div class="stat-value"><?= $e(number_format($paidRate, 0)) ?>%</div>
        <div class="lily-stat-sub">Openstaand saldo: <?= $e($eur($openBalance)) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Annulaties</div>
        <div class="stat-value"><?= $e(number_format($cancelRate, 0)) ?>%</div>
        <div class="lily-stat-sub"><?= $e((string) $cancelledOrders) ?> van <?= $e((string) $totalOrders) ?> bestellingen</div>
    </div>
</div>

<div class="card card-pine lily-section">
    <div class="lily-card-head">
        <h2>Actieplan voor volgend jaar</h2>
        <span class="badge badge-gold">Prognose +<?= $e((string) $growth) ?>%</span>
    </div>
    <?php if ($actions === []): ?>
        <p>Voeg bestellingen toe om automatische aanbevelingen voor volgend jaar te ontvangen.</p>
    <?php else: ?>
        <ul class="sheet-list">
            <?php foreach ($actions as $action): ?>
                <li><?= $e($action) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <?php if ($unsoldProducts !== []): ?>
        <p class="lily-unsold">
            <strong>Niet verkocht in deze periode:</strong>
            <?= $e(implode(', ', array_map(static fn (array $r): string => (string) $r['name'], $unsoldProducts))) ?>
        </p>
    <?php endif; ?>
</div>

<div class="card lily-section">
    <div class="lily-card-head">
        <h2>Topgerechten &amp; voorbereiding</h2>
        <span class="badge badge-pine">Prognose +<?= $e((string) $growth) ?>%</span>
    </div>

    <?php if ($topDishes === []): ?>
        <p class="lily-muted">Geen actieve gerechtregels gevonden voor deze periode.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Gerecht</th>
                        <th class="lily-num">Verkocht</th>
                        <th class="lily-num">Bestellingen</th>
                        <th class="lily-num">Omzet &amp; aandeel</th>
                        <th class="lily-num">Voorbereiden volgend jaar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topDishes as $dish): ?>
                        <?php
                        $qty = (float) $dish['total_qty'];
                        $dishRev = (float) $dish['total_revenue'];
                        $share = $revenue > 0 ? min(100, (int) round(($dishRev / $revenue) * 100)) : 0;
                        $prepQty = (int) ceil($qty * $factor);
                        $unit = (string) $dish['unit'];
                        ?>
                        <tr>
                            <td><strong><?= $e((string) $dish['product_name']) ?></strong></td>
                            <td class="lily-num"><?= $e($qtyFmt($qty)) ?> <?= $e($unit) ?></td>
                            <td class="lily-num"><?= $e((string) (int) $dish['order_count']) ?></td>
                            <td class="lily-num">
                                <div><?= $e($eur($dishRev)) ?> (<?= $e((string) $share) ?>%)</div>
                                <progress class="lily-progress lily-progress-gold" value="<?= $e((string) $share) ?>" max="100"></progress>
                            </td>
                            <td class="lily-num"><strong><?= $e((string) $prepQty) ?> <?= $e($unit) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="grid-2">
    <div class="card">
        <div class="lily-card-head">
            <h2>Drukste ophaaldagen</h2>
        </div>
        <?php if ($peakDays === []): ?>
            <p class="lily-muted">Geen actieve ophaaldagen.</p>
        <?php else: ?>
            <div class="lily-bar-list">
                <?php foreach ($peakDays as $day): ?>
                    <?php
                    $cnt = (int) $day['order_count'];
                    $projCnt = (int) ceil($cnt * $factor);
                    ?>
                    <div>
                        <div class="lily-bar-meta">
                            <strong><?= $e((string) $day['pickup_date']) ?></strong>
                            <span><?= $e((string) $cnt) ?> best. (volgend jaar: <?= $e((string) $projCnt) ?>) · <?= $e($eur((float) $day['revenue'])) ?></span>
                        </div>
                        <progress class="lily-progress" value="<?= $e((string) $cnt) ?>" max="<?= $e((string) $maxDayOrders) ?>"></progress>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="lily-card-head">
            <h2>Drukste tijdslots</h2>
        </div>
        <?php if ($peakSlots === []): ?>
            <p class="lily-muted">Geen actieve tijdslots.</p>
        <?php else: ?>
            <div class="lily-bar-list">
                <?php foreach ($peakSlots as $slot): ?>
                    <?php
                    $cnt = (int) $slot['order_count'];
                    $slotPct = $activeOrders > 0 ? (int) round(($cnt / $activeOrders) * 100) : 0;
                    ?>
                    <div>
                        <div class="lily-bar-meta">
                            <strong><?= $e((string) $slot['slot']) ?></strong>
                            <span><?= $e((string) $cnt) ?> best. (<?= $e((string) $slotPct) ?>%) · <?= $e($eur((float) $slot['revenue'])) ?></span>
                        </div>
                        <progress class="lily-progress lily-progress-gold" value="<?= $e((string) $cnt) ?>" max="<?= $e((string) $maxSlotOrders) ?>"></progress>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
