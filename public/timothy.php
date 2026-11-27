<?php
session_start();

// Initialiseer de inkooplijst in de sessie als deze nog niet bestaat
if (!isset($_SESSION['inkooplijst'])) {
    $_SESSION['inkooplijst'] = [];
}

// Verwerk formulieracties (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actie = $_POST['actie'] ?? '';

    // 1. Nieuw product toevoegen
    if ($actie === 'toevoegen') {
        $product = trim($_POST['product'] ?? '');
        $aantal = (int)($_POST['aantal'] ?? 1);
        $prijs = (float)str_replace(',', '.', $_POST['prijs'] ?? 0);

        if ($product !== '' && $aantal > 0) {
            $_SESSION['inkooplijst'][] = [
                'id' => uniqid(),
                'product' => $product,
                'aantal' => $aantal,
                'prijs' => $prijs,
                'gekocht' => false
            ];
        }
    }

    // 2. Status wisselen (afvinken / terugzetten)
    if ($actie === 'toggle' && isset($_POST['id'])) {
        foreach ($_SESSION['inkooplijst'] as &$item) {
            if ($item['id'] === $_POST['id']) {
                $item['gekocht'] = !$item['gekocht'];
                break;
            }
        }
        unset($item); // Referentie opruimen
    }

    // 3. Eén product verwijderen
    if ($actie === 'verwijderen' && isset($_POST['id'])) {
        $_SESSION['inkooplijst'] = array_filter($_SESSION['inkooplijst'], function ($item) {
            return $item['id'] !== $_POST['id'];
        });
    }

    // 4. Gehele lijst leegmaken
    if ($actie === 'legen') {
        $_SESSION['inkooplijst'] = [];
    }

    // Redirect om te voorkomen dat het formulier opnieuw wordt verzonden bij vernieuwen (F5)
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Bereken het totaalbedrag
$totaalBedrag = 0;
foreach ($_SESSION['inkooplijst'] as $item) {
    $totaalBedrag += $item['aantal'] * $item['prijs'];
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inkooplijst Klant</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
            color: #333;
            max-width: 700px;
            margin: 40px auto;
            padding: 20px;
        }
        .container {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
        }
        h1 {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 10px;
        }
        .form-groep {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        input[type="text"], input[type="number"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }
        input[name="product"] { flex: 2; min-width: 180px; }
        input[name="aantal"] { width: 70px; }
        input[name="prijs"] { width: 100px; }
        
        button {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
        }
        .btn-toevoegen { background-color: #27ae60; color: white; }
        .btn-toevoegen:hover { background-color: #219150; }
        .btn-actie { background-color: #ecf0f1; color: #333; padding: 5px 10px; }
        .btn-verwijder { background-color: #e74c3c; color: white; padding: 5px 10px; }
        .btn-legen { background-color: #95a5a6; color: white; margin-top: 15px; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th { background-color: #f9fafb; }
        .gekocht {
            text-decoration: line-through;
            color: #95a5a6;
        }
        .totaal-rij {
            font-weight: bold;
            font-size: 16px;
            background-color: #f9fafb;
        }
        .leeg-bericht {
            text-align: center;
            color: #7f8c8d;
            padding: 20px;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>🛒 Mijn Inkooplijst</h1>

    <!-- Formulier om product toe te voegen -->
    <form method="POST" class="form-groep">
        <input type="hidden" name="actie" value="toevoegen">
        <input type="text" name="product" placeholder="Productnaam (bijv. Melk)" required>
        <input type="number" name="aantal" value="1" min="1" title="Aantal" required>
        <input type="number" name="prijs" step="0.01" min="0" placeholder="Prijs (€)" title="Prijs per stuk">
        <button type="submit" class="btn-toevoegen">+ Toevoegen</button>
    </form>

    <!-- Overzicht van de lijst -->
    <?php if (empty($_SESSION['inkooplijst'])): ?>
        <p class="leeg-bericht">Je inkooplijst is momenteel leeg. Voeg hierboven een product toe!</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Aantal</th>
                    <th>Prijs p/s</th>
                    <th>Subtotaal</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($_SESSION['inkooplijst'] as $item): ?>
                    <?php $subtotaal = $item['aantal'] * $item['prijs']; ?>
                    <tr class="<?= $item['gekocht'] ? 'gekocht' : '' ?>">
                        <td><?= htmlspecialchars($item['product'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int)$item['aantal'] ?></td>
                        <td>€ <?= number_format($item['prijs'], 2, ',', '.') ?></td>
                        <td>€ <?= number_format($subtotaal, 2, ',', '.') ?></td>
                        <td style="display: flex; gap: 5px;">
                            <!-- Afvinken knop -->
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="actie" value="toggle">
                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                <button type="submit" class="btn-actie" title="Afvinken">
                                    <?= $item['gekocht'] ? '↩️' : '✔️' ?>
                                </button>
                            </form>
                            <!-- Verwijderen knop -->
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="actie" value="verwijderen">
                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                <button type="submit" class="btn-verwijder" title="Verwijderen">🗑️</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="totaal-rij">
                    <td colspan="3">Geschat totaalbedrag:</td>
                    <td colspan="2">€ <?= number_format($totaalBedrag, 2, ',', '.') ?></td>
                </tr>
            </tfoot>
        </table>

        <!-- Knop om hele lijst te wissen -->
        <form method="POST" onsubmit="return confirm('Weet je zeker dat je de hele lijst wilt wissen?');">
            <input type="hidden" name="actie" value="legen">
            <button type="submit" class="btn-legen">Lijst volledig wissen</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
