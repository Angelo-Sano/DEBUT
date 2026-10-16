<?php
session_start();
require_once "db.php";

$error = "";
$success = isset($_GET["success"]);

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

// Fetch active clients and their combined totals/balances
try {
    $stmt = $pdo->query("
        SELECT clients.id AS client_id, clients.name, clients.phone,
               SUM(loans.loan_amount) AS total_original,
               SUM(loans.balance) AS total_balance
        FROM loans 
        JOIN clients ON loans.client_id = clients.id 
        WHERE loans.balance > 0 
        GROUP BY clients.id, clients.name, clients.phone
        ORDER BY clients.name ASC
    ");
    $activeClients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all individual unpaid items for the modal breakdown
    $itemStmt = $pdo->query("
        SELECT id, client_id, item, quantity, unit, unit_price, loan_amount, amount_paid, balance, loan_date
        FROM loans
        WHERE balance > 0
        ORDER BY loan_date ASC
    ");
    $allItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

    $clientItemsMap = [];
    foreach ($allItems as $it) {
        $clientItemsMap[$it['client_id']][] = $it;
    }

} catch (PDOException $e) {
    $activeClients = [];
    $clientItemsMap = [];
    $error = "Could not load data: " . $e->getMessage();
}

// Handle payment submission (supports both single-item and combined payment modes)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $paymentMode = $_POST["payment_mode"] ?? "";
    $paymentAmount = filter_input(INPUT_POST, "payment_amount", FILTER_VALIDATE_FLOAT);

    if ($paymentAmount === false || $paymentAmount <= 0) {
        $error = "Please enter a valid payment amount greater than zero.";
    } else {
        try {
            if ($paymentMode === "single") {
                $loanId = filter_input(INPUT_POST, "loan_id", FILTER_VALIDATE_INT);
                if (!$loanId) {
                    $error = "Please select a specific product item to pay for.";
                } else {
                    $checkStmt = $pdo->prepare("SELECT loan_amount, amount_paid, balance FROM loans WHERE id = ?");
                    $checkStmt->execute([$loanId]);
                    $loan = $checkStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$loan) {
                        $error = "The selected item record does not exist.";
                    } elseif ($paymentAmount > $loan['balance']) {
                        $error = "Payment amount cannot exceed the remaining balance for this item (" . number_format($loan['balance']) . " RWF).";
                    } else {
                        $newPaid = $loan['amount_paid'] + $paymentAmount;
                        $newBalance = $loan['loan_amount'] - $newPaid;

                        $updateStmt = $pdo->prepare("UPDATE loans SET amount_paid = ?, balance = ? WHERE id = ?");
                        $updateStmt->execute([$newPaid, $newBalance, $loanId]);

                        header("Location: payment.php?success=1");
                        exit;
                    }
                }
            } elseif ($paymentMode === "combined") {
                $clientId = filter_input(INPUT_POST, "client_id", FILTER_VALIDATE_INT);
                if (!$clientId) {
                    $error = "Invalid client selection.";
                } else {
                    $loanStmt = $pdo->prepare("SELECT id, balance, amount_paid FROM loans WHERE client_id = ? AND balance > 0 ORDER BY loan_date ASC, id ASC");
                    $loanStmt->execute([$clientId]);
                    $clientLoans = $loanStmt->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($clientLoans)) {
                        $error = "No outstanding loans found for this client.";
                    } else {
                        $totalBalance = array_sum(array_column($clientLoans, 'balance'));

                        if ($paymentAmount > $totalBalance) {
                            $error = "Payment amount cannot exceed the total remaining balance (" . number_format($totalBalance) . " RWF).";
                        } else {
                            $pdo->beginTransaction();
                            $remainingPayment = $paymentAmount;

                            foreach ($clientLoans as $loan) {
                                if ($remainingPayment <= 0) break;

                                $loanId = $loan['id'];
                                $currentBalance = $loan['balance'];
                                $currentPaid = $loan['amount_paid'];

                                if ($remainingPayment >= $currentBalance) {
                                    $paymentForThisLoan = $currentBalance;
                                    $newBalance = 0;
                                    $newPaid = $currentPaid + $paymentForThisLoan;
                                } else {
                                    $paymentForThisLoan = $remainingPayment;
                                    $newBalance = $currentBalance - $paymentForThisLoan;
                                    $newPaid = $currentPaid + $paymentForThisLoan;
                                }

                                $updateStmt = $pdo->prepare("UPDATE loans SET amount_paid = ?, balance = ? WHERE id = ?");
                                $updateStmt->execute([$newPaid, $newBalance, $loanId]);

                                $remainingPayment -= $paymentForThisLoan;
                            }

                            $pdo->commit();
                            header("Location: payment.php?success=1");
                            exit;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Could not process payment: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Record Payment - Loan Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 40px; background-color: #f4f6f9; color: #333; }
        .container { background: #fff; padding: 30px; border-radius: 12px; max-width: 950px; margin: auto; box-shadow: 0 4px 15px rgba(0,0,0,0.06); }
        .header-nav { display: flex; align-items: center; justify-content: space-between; gap: 15px; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #eef2f6; }
        .back-btn { padding: 10px 18px; border-radius: 8px; background: #eef2f6; color: #1e3a8a; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; font-weight: bold; font-size: 14px; transition: 0.2s; }
        .back-btn:hover { background: #e2e8f0; }
        h2 { margin: 0; color: #1e3a8a; font-size: 24px; }
        .subtitle { color: #64748b; font-size: 14px; margin-top: 4px; }
        
        .search-box { margin-bottom: 20px; position: relative; }
        .search-box input { width: 100%; padding: 12px 15px 12px 42px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none; transition: 0.2s; }
        .search-box input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .search-box i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; }

        .client-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 15px; max-height: 440px; overflow-y: auto; margin-bottom: 25px; padding-right: 5px; }
        
        .client-box { background: #f8fafc; border: 2px solid #cbd5e1; border-radius: 10px; padding: 18px; display: flex; flex-direction: column; justify-content: space-between; }
        .client-info h4 { color: #1e293b; font-size: 17px; margin-bottom: 4px; }
        .client-info .phone-num { color: #64748b; font-size: 13px; display: block; margin-bottom: 12px; }
        
        .view-items-btn { background: #e0f2fe; color: #0284c7; border: none; padding: 8px 14px; border-radius: 6px; font-size: 13px; font-weight: bold; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 15px; transition: 0.2s; width: 100%; justify-content: center; }
        .view-items-btn:hover { background: #bae6fd; }

        .client-balance-container { border-top: 1px dashed #cbd5e1; padding-top: 10px; display: flex; flex-direction: column; gap: 4px; }
        .balance-row { display: flex; justify-content: space-between; align-items: center; font-size: 13px; }
        .balance-row.original { color: #64748b; }
        .balance-row.current { color: #dc2626; font-weight: bold; margin-top: 2px; }
        .balance-row.current .amount { font-size: 16px; }

        /* Modal Styling */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal-card { background: white; padding: 25px; border-radius: 12px; width: 90%; max-width: 750px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); animation: fadeIn 0.2s ease; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; }
        .modal-header h3 { color: #1e3a8a; font-size: 18px; }
        .close-modal { background: none; border: none; font-size: 20px; color: #64748b; cursor: pointer; }
        
        /* Tabs inside modal */
        .modal-tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
        .tab-btn { padding: 8px 16px; border: none; border-radius: 6px; background: #e2e8f0; color: #475569; font-weight: bold; cursor: pointer; font-size: 13px; transition: 0.2s; }
        .tab-btn.active { background: #2563eb; color: white; }

        .tab-content { display: none; }
        .tab-content.active { display: block; }

        .modal-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 13px; }
        .modal-table th, .modal-table td { padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        .modal-table th { background: #f8fafc; color: #475569; font-weight: bold; }
        .modal-table td { color: #334155; }
        
        .modal-item-row { cursor: pointer; }
        .modal-item-row:hover { background: #f1f5f9; }
        .modal-item-row.selected { background: #eff6ff; }

        .payment-modal-section { background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #cbd5e1; display: flex; gap: 15px; align-items: flex-end; }
        .payment-modal-section .form-group { flex: 1; margin-bottom: 0; }
        label { display: block; margin-bottom: 6px; font-weight: bold; font-size: 13px; color: #334155; }
        .required { color: #dc2626; }
        input[type="number"] { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 15px; font-weight: bold; background: white; outline: none; color: #0f172a; }
        
        button.pay-btn { padding: 10px 20px; border: none; border-radius: 6px; background: #16a34a; color: white; font-weight: bold; cursor: pointer; font-size: 14px; display: inline-flex; align-items: center; gap: 6px; }
        button.pay-btn:hover { background: #15803d; }

        .message { margin-bottom: 20px; padding: 14px; border-radius: 8px; font-size: 14px; font-weight: bold; display: flex; align-items: center; gap: 10px; }
        .success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .info-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 20px; text-align: center; color: #166534; font-size: 15px; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

<div class="container">
    <div class="header-nav">
        <div>
            <h2>Record Payment</h2>
            <p class="subtitle">Select a customer to pay for individual items or pay combined total</p>
        </div>
        <a href="dashboard.php" class="back-btn" title="Back to Dashboard">
            <i class="fa-solid fa-arrow-left"></i> Dashboard
        </a>
    </div>

    <?php if ($success): ?>
        <div class="message success"><i class="fa-solid fa-circle-check fa-lg"></i> Payment recorded successfully!</div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="message error"><i class="fa-solid fa-triangle-exclamation fa-lg"></i> <?= h($error) ?></div>
    <?php endif; ?>

    <?php if (empty($activeClients)): ?>
        <div class="info-box">
            <i class="fa-solid fa-face-smile fa-2x" style="margin-bottom: 10px; display: block;"></i>
            No outstanding client balances found. All loans are fully paid!
        </div>
    <?php else: ?>
        <!-- Search Bar -->
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="clientSearch" placeholder="Search customer by name...">
        </div>

        <!-- Customer Boxes Grid -->
        <div class="client-grid" id="clientGrid">
            <?php foreach ($activeClients as $client): 
                $clientId = $client['client_id'];
                $clientItems = $clientItemsMap[$clientId] ?? [];
            ?>
                <div class="client-box" data-name="<?= strtolower(h($client['name'])) ?>">
                    <div class="client-info">
                        <h4><?= h($client['name']) ?></h4>
                        <span class="phone-num"><i class="fa-solid fa-phone" style="font-size:11px;"></i> <?= !empty($client['phone']) ? h($client['phone']) : 'No phone' ?></span>
                        
                        <button type="button" class="view-items-btn" onclick="openModal('modal-<?= $clientId ?>')">
                            <i class="fa-solid fa-wallet"></i> Choose Payment Option (<?= count($clientItems) ?> items)
                        </button>
                    </div>

                    <div class="client-balance-container">
                        <div class="balance-row original">
                            <span>Original Total:</span>
                            <span><?= number_format($client['total_original']) ?> RWF</span>
                        </div>
                        <div class="balance-row current">
                            <span>Current Balance:</span>
                            <span class="amount"><?= number_format($client['total_balance']) ?> RWF</span>
                        </div>
                    </div>
                </div>

                <!-- Modal Popup with Tabs (Single Item vs Combined Payment) -->
                <div id="modal-<?= $clientId ?>" class="modal-overlay" onclick="closeModal('modal-<?= $clientId ?>')">
                    <div class="modal-card" onclick="event.stopPropagation()">
                        <div class="modal-header">
                            <h3><?= h($client['name']) ?> — Payment Options</h3>
                            <button type="button" class="close-modal" onclick="closeModal('modal-<?= $clientId ?>')">&times;</button>
                        </div>

                        <!-- Tab Selection -->
                        <div class="modal-tabs">
                            <button type="button" class="tab-btn active" onclick="switchTab(event, 'tab-single-<?= $clientId ?>')">Pay Specific Product Item</button>
                            <button type="button" class="tab-btn" onclick="switchTab(event, 'tab-combined-<?= $clientId ?>')">Pay Combined Total (<?= number_format($client['total_balance']) ?> RWF)</button>
                        </div>

                        <!-- TAB 1: Pay Specific Item -->
                        <div id="tab-single-<?= $clientId ?>" class="tab-content active">
                            <form method="POST">
                                <input type="hidden" name="payment_mode" value="single">
                                <p style="font-size: 13px; color: #64748b; margin-bottom: 12px;">Select the specific product item this payment is for:</p>
                                
                                <table class="modal-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 40px;">Select</th>
                                            <th>Date</th>
                                            <th>Item / Product</th>
                                            <th>Qty</th>
                                            <th>Balance Due</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($clientItems as $item): ?>
                                            <tr class="modal-item-row" onclick="selectModalRow(this)">
                                                <td>
                                                    <input type="radio" name="loan_id" value="<?= (int)$item['id'] ?>" required style="accent-color: #2563eb;">
                                                </td>
                                                <td><?= h($item['loan_date']) ?></td>
                                                <td><strong><?= h($item['item']) ?></strong></td>
                                                <td><?= h($item['quantity']) ?> <?= h($item['unit']) ?></td>
                                                <td style="color: #dc2626; font-weight: bold;"><?= number_format($item['balance']) ?> RWF</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>

                                <div class="payment-modal-section">
                                    <div class="form-group">
                                        <label>Payment Amount for Selected Item (RWF) <span class="required">*</span></label>
                                        <input type="number" step="0.01" name="payment_amount" min="0.01" placeholder="e.g. 5000" required>
                                    </div>
                                    <button type="submit" class="pay-btn"><i class="fa-solid fa-money-bill-wave"></i> Submit Payment</button>
                                </div>
                            </form>
                        </div>

                        <!-- TAB 2: Pay Combined Total -->
                        <div id="tab-combined-<?= $clientId ?>" class="tab-content">
                            <form method="POST">
                                <input type="hidden" name="payment_mode" value="combined">
                                <input type="hidden" name="client_id" value="<?= (int)$clientId ?>">
                                
                                <div style="background: #eff6ff; border: 1px solid #bfdbfe; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                                    <p style="font-size: 14px; color: #1e40af; margin-bottom: 5px;"><strong>Combined Payment Mode:</strong></p>
                                    <p style="font-size: 13px; color: #3b82f6;">The payment amount you enter will be automatically distributed across all <?= count($clientItems) ?> items, starting from the oldest purchases first.</p>
                                    <p style="font-size: 14px; color: #1e293b; margin-top: 8px;"><strong>Total Balance Due:</strong> <span style="color: #dc2626; font-weight: bold;"><?= number_format($client['total_balance']) ?> RWF</span></p>
                                </div>

                                <div class="payment-modal-section">
                                    <div class="form-group">
                                        <label>Total Payment Amount (RWF) <span class="required">*</span></label>
                                        <input type="number" step="0.01" name="payment_amount" min="0.01" max="<?= $client['total_balance'] ?>" placeholder="e.g. 15000" required>
                                    </div>
                                    <button type="submit" class="pay-btn"><i class="fa-solid fa-money-bill-wave"></i> Submit Combined Payment</button>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function switchTab(event, tabId) {
    const modalCard = event.target.closest('.modal-card');
    modalCard.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    modalCard.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    event.target.classList.add('active');
    document.getElementById(tabId).classList.add('active');
}

function selectModalRow(rowElement) {
    const table = rowElement.closest('table');
    table.querySelectorAll('.modal-item-row').forEach(r => r.classList.remove('selected'));
    rowElement.classList.add('selected');
    const radio = rowElement.querySelector('input[type="radio"]');
    radio.checked = true;
}

const searchInput = document.getElementById('clientSearch');
if (searchInput) {
    searchInput.addEventListener('input', (e) => {
        const term = e.target.value.toLowerCase();
        document.querySelectorAll('.client-box').forEach(box => {
            const name = box.getAttribute('data-name');
            if (name.includes(term)) {
                box.style.display = 'flex';
            } else {
                box.style.display = 'none';
            }
        });
    });
}
</script>

</body>
</html>