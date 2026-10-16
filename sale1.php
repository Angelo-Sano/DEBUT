<?php
session_start();
require_once "db.php";

$error = "";
$saved = isset($_GET["saved"]);

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$stmt = $pdo->query("SELECT id, name, phone FROM clients ORDER BY name ASC");
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
$clientIds = array_map('intval', array_column($clients, "id"));

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $clientId = filter_input(INPUT_POST, "client_id", FILTER_VALIDATE_INT);
    $loanDate = $_POST["loan_date"] ?? "";
    $totalPaidInput = filter_input(INPUT_POST, "total_paid", FILTER_VALIDATE_FLOAT) ?? 0;
    $notes = trim($_POST["notes"] ?? "");

    $items = $_POST["item"] ?? [];
    $quantities = $_POST["quantity"] ?? [];
    $units = $_POST["unit"] ?? [];
    $unitPrices = $_POST["unit_price"] ?? [];

    if (!$clientId || !in_array($clientId, $clientIds, true)) {
        $error = "Please select a valid client.";
    } elseif (!$loanDate || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $loanDate)) {
        $error = "Please select a valid loan date.";
    } elseif (empty($items) || count($items) === 0) {
        $error = "Please add at least one item.";
    } else {
        // Validate rows and compute grand total
        $grandTotal = 0;
        $validRows = [];

        for ($i = 0; $i < count($items); $i++) {
            $itemName = trim($items[$i]);
            $qty = filter_var($quantities[$i] ?? 0, FILTER_VALIDATE_FLOAT);
            $u = $units[$i] ?? "kg";
            $price = filter_var($unitPrices[$i] ?? 0, FILTER_VALIDATE_FLOAT);

            if ($itemName === "" || $qty === false || $qty <= 0 || $price === false || $price <= 0) {
                $error = "Please ensure all item fields, quantities, and unit prices are filled correctly.";
                break;
            }

            $subtotal = round($qty * $price, 2);
            $grandTotal += $subtotal;

            $validRows[] = [
                'item' => $itemName,
                'quantity' => $qty,
                'unit' => $u,
                'unit_price' => $price,
                'subtotal' => $subtotal
            ];
        }

        if ($error === "") {
            if ($totalPaidInput < 0 || $totalPaidInput > $grandTotal) {
                $error = "Total amount paid cannot be negative or exceed the grand total (" . number_format($grandTotal) . " RWF).";
            } else {
                try {
                    $pdo->beginTransaction();

                    $insert = $pdo->prepare(
                        "INSERT INTO loans (client_id, loan_amount, loan_date, description, item, quantity, unit, unit_price, amount_paid, balance, created_at) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
                    );

                    foreach ($validRows as $row) {
                        // Distribute total paid proportionally across items if partial payment is made
                        $itemPaid = ($grandTotal > 0) ? round(($row['subtotal'] / $grandTotal) * $totalPaidInput, 2) : 0;
                        $itemBalance = round($row['subtotal'] - $itemPaid, 2);

                        $insert->execute([
                            $clientId,
                            $row['subtotal'],
                            $loanDate,
                            $notes,
                            $row['item'],
                            $row['quantity'],
                            $row['unit'],
                            $row['unit_price'],
                            $itemPaid,
                            $itemBalance
                        ]);
                    }

                    $pdo->commit();
                    header("Location: sale1.php?saved=1");
                    exit;
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "Could not save the loan: " . $e->getMessage();
                }
            }
        }
    }
}
$today = date("Y-m-d");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Loan / Multi-Item Sale - Loan Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; margin: 40px; background-color: #f4f6f9; color: #333; }
        .container { background: #fff; padding: 25px; border-radius: 8px; max-width: 850px; margin: auto; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .header-nav { display: flex; align-items: center; justify-content: space-between; gap: 15px; margin-bottom: 20px; }
        .back-btn { padding: 10px 16px; border-radius: 8px; background: #e8f2ff; color: #006edc; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; font-weight: bold; font-size: 14px; transition: 0.2s; }
        .back-btn:hover { background: #d0e5ff; }
        h2 { margin: 0; color: #1e3a8a; }
        .subtitle { color: #777; font-size: 14px; }
        .form-row { display: flex; gap: 15px; margin-bottom: 15px; }
        .form-group { flex: 1; margin-bottom: 15px; }
        .form-group.full { flex: 100%; }
        label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 14px; color: #444; }
        .required { color: red; }
        select, input, textarea { width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; background: white; }
        textarea { resize: vertical; height: 80px; }

        /* Items Table Styling */
        .items-section { margin-top: 20px; margin-bottom: 20px; }
        .items-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .items-header h3 { color: #1e3a8a; font-size: 16px; }
        .add-item-btn { background: #10b981; color: white; border: none; padding: 6px 14px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; }
        .add-item-btn:hover { background: #059669; }

        .item-row { display: flex; gap: 10px; align-items: center; background: #f8fafc; padding: 12px; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 10px; }
        .item-row .col-item { flex: 2; }
        .item-row .col-qty { flex: 1; }
        .item-row .col-unit { flex: 1; }
        .item-row .col-price { flex: 1.2; }
        .item-row .col-action { flex: 0 0 40px; text-align: center; }
        .remove-row-btn { background: #fee2e2; color: #dc2626; border: none; width: 36px; height: 36px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; }
        .remove-row-btn:hover { background: #fecaca; }

        .calculation-box { background: #f5f8fc; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
        .calculation-box h3 { margin-top: 0; margin-bottom: 15px; font-size: 16px; color: #1e3a8a; display: flex; align-items: center; gap: 8px; }
        .calculation-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; color: #666; border-bottom: 1px dashed #e2e6eb; }
        .calculation-row strong { color: #222; }
        .calculation-row.total-row { background-color: #eff6ff; margin: 6px -20px; padding: 10px 20px; border-bottom: none; }
        .calculation-row.total-row strong { color: #2563eb; font-size: 16px; }
        .calculation-row.balance-row { background-color: #fef2f2; margin: 6px -20px -20px -20px; padding: 12px 20px; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; border-bottom: none; }
        .calculation-row.balance-row strong { color: #dc2626; font-size: 17px; }
        
        .actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px; }
        button[type="submit"] { padding: 12px 24px; border: none; border-radius: 6px; background: #2563eb; color: white; font-weight: bold; cursor: pointer; font-size: 14px; }
        button[type="submit"]:hover { background: #1d4ed8; }
        .cancel-btn { background: #e5e7eb; color: #374151; text-decoration: none; display: inline-flex; align-items: center; padding: 12px 24px; border-radius: 6px; font-weight: bold; font-size: 14px; }
        
        .message { margin-bottom: 20px; padding: 12px; border-radius: 6px; font-size: 14px; font-weight: bold; }
        .success { background: #e8f8ef; color: #158447; }
        .error { background: #fdeaea; color: #a32121; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-nav">
        <div>
            <h2>New Loan / Multi-Item Sale</h2>
            <p class="subtitle">Record multiple items purchased by a client</p>
        </div>
        <a href="dashboard.php" class="back-btn" title="Back to Dashboard">
            <i class="fa-solid fa-arrow-left"></i> Dashboard
        </a>
    </div>

    <?php if ($saved): ?>
        <div class="message success"><i class="fa-solid fa-circle-check"></i> Loan records saved successfully.</div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="message error"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="POST" id="loanForm">
        <div class="form-row">
            <div class="form-group">
                <label for="client_id">Customer Name <span class="required">*</span></label>
                <select id="client_id" name="client_id" required>
                    <option value="">-- Select Customer --</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?= (int)$client['id'] ?>"
                            <?= isset($_POST["client_id"]) && (int)$_POST["client_id"] === (int)$client["id"] ? "selected" : "" ?>>
                            <?= h($client['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="loan_date">Date <span class="required">*</span></label>
                <input type="date" id="loan_date" name="loan_date" value="<?= h($_POST["loan_date"] ?? $today) ?>" required>
            </div>
        </div>

        <div class="items-section">
            <div class="items-header">
                <h3>Items / Products List</h3>
                <button type="button" class="add-item-btn" id="addItemBtn"><i class="fa-solid fa-plus"></i> Add Another Item</button>
            </div>

            <div id="itemsContainer">
                <!-- Initial Item Row -->
                <div class="item-row">
                    <div class="col-item">
                        <label style="font-size:12px;">Item Name *</label>
                        <input type="text" name="item[]" placeholder="e.g. Ibigori" required>
                    </div>
                    <div class="col-qty">
                        <label style="font-size:12px;">Quantity *</label>
                        <input type="number" step="0.01" min="0.01" name="quantity[]" placeholder="Qty" class="calc-trigger" required>
                    </div>
                    <div class="col-unit">
                        <label style="font-size:12px;">Unit</label>
                        <select name="unit[]">
                            <option value="kg">kg</option>
                            <option value="pcs">pcs</option>
                            <option value="litre">litre</option>
                            <option value="bag">bag</option>
                            <option value="box">box</option>
                            <option value="other">other</option>
                        </select>
                    </div>
                    <div class="col-price">
                        <label style="font-size:12px;">Unit Price *</label>
                        <input type="number" step="0.01" min="0.01" name="unit_price[]" placeholder="Price" class="calc-trigger" required>
                    </div>
                    <div class="col-action">
                        <label style="font-size:12px; opacity:0;">Action</label>
                        <button type="button" class="remove-row-btn" title="Remove Item" style="visibility:hidden;"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="total_paid">Total Amount Paid (RWF)</label>
                <input type="number" step="0.01" id="total_paid" name="total_paid" min="0" value="<?= h($_POST["total_paid"] ?? "0") ?>">
            </div>
        </div>

        <div class="form-group full">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" placeholder="Optional notes for this transaction..."><?= h($_POST["notes"] ?? "") ?></textarea>
        </div>

        <div class="calculation-box">
            <h3><i class="fa-solid fa-calculator"></i> Transaction Summary</h3>
            <div class="calculation-row total-row"><span>Grand Total Amount</span><strong id="grandTotalAmount">0 RWF</strong></div>
            <div class="calculation-row"><span>Total Amount Paid</span><strong id="displayPaid">0 RWF</strong></div>
            <div class="calculation-row balance-row"><span>Total Remaining Balance</span><strong id="grandBalanceAmount">0 RWF</strong></div>
        </div>

        <div class="actions">
            <a href="dashboard.php" class="cancel-btn">Cancel</a>
            <button type="submit"><i class="fa-solid fa-floppy-disk"></i> Save All Items</button>
        </div>
    </form>
</div>

<script>
const itemsContainer = document.getElementById('itemsContainer');
const addItemBtn = document.getElementById('addItemBtn');
const totalPaidInput = document.getElementById('total_paid');

function formatMoney(amount) {
    return new Intl.NumberFormat("en-RW", { maximumFractionDigits: 2 }).format(amount) + " RWF";
}

function calculateTotals() {
    let grandTotal = 0;
    const rows = itemsContainer.querySelectorAll('.item-row');

    rows.forEach(row => {
        const qty = parseFloat(row.querySelector('input[name="quantity[]"]').value) || 0;
        const price = parseFloat(row.querySelector('input[name="unit_price[]"]').value) || 0;
        grandTotal += (qty * price);
    });

    const paid = parseFloat(totalPaidInput.value) || 0;
    const balance = grandTotal - paid;

    document.getElementById('grandTotalAmount').textContent = formatMoney(grandTotal);
    document.getElementById('displayPaid').textContent = formatMoney(paid);
    document.getElementById('grandBalanceAmount').textContent = formatMoney(balance);
}

function updateRemoveButtons() {
    const rows = itemsContainer.querySelectorAll('.item-row');
    rows.forEach((row, index) => {
        const btn = row.querySelector('.remove-row-btn');
        if (rows.length > 1) {
            btn.style.visibility = 'visible';
        } else {
            btn.style.visibility = 'hidden';
        }
    });
}

addItemBtn.addEventListener('click', () => {
    const firstRow = itemsContainer.querySelector('.item-row');
    const newRow = firstRow.cloneNode(true);
    
    // Clear inputs in new row
    newRow.querySelectorAll('input').forEach(input => input.value = '');
    newRow.querySelector('select').selectedIndex = 0;

    itemsContainer.appendChild(newRow);
    attachRowListeners(newRow);
    updateRemoveButtons();
    calculateTotals();
});

function attachRowListeners(row) {
    row.querySelectorAll('.calc-trigger').forEach(input => {
        input.addEventListener('input', calculateTotals);
    });

    const removeBtn = row.querySelector('.remove-row-btn');
    removeBtn.addEventListener('click', () => {
        if (itemsContainer.querySelectorAll('.item-row').length > 1) {
            row.remove();
            updateRemoveButtons();
            calculateTotals();
        }
    });
}

// Initialize listeners on first row
itemsContainer.querySelectorAll('.item-row').forEach(row => attachRowListeners(row));
totalPaidInput.addEventListener('input', calculateTotals);

calculateTotals();
updateRemoveButtons();
</script>
</body>
</html>