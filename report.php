<?php
session_start();

if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("Location: login.html");
    exit();
}

require_once "db.php";

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$selectedClientId = filter_input(INPUT_GET, "client_id", FILTER_VALIDATE_INT);
$clientData = null;
$clientLoans = [];

try {
    // Fetch all clients for the dropdown selector
    $clientsList = $pdo->query("SELECT id, name, phone FROM clients ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

    // If a specific customer is selected, fetch their details and loans
    if ($selectedClientId) {
        $clientStmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
        $clientStmt->execute([$selectedClientId]);
        $clientData = $clientStmt->fetch(PDO::FETCH_ASSOC);

        $loanStmt = $pdo->prepare("
            SELECT * FROM loans 
            WHERE client_id = ? 
            ORDER BY loan_date DESC, id DESC
        ");
        $loanStmt->execute([$selectedClientId]);
        $clientLoans = $loanStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $clientsList = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Statement - Loan Manager</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background: #f4f6f9; color: #222; }
        .sidebar { position: fixed; left: 0; top: 0; width: 240px; height: 100vh; background: #172554; color: white; padding: 25px 15px; }
        .logo { text-align: center; margin-bottom: 35px; }
        .logo i { font-size: 35px; margin-bottom: 8px; }
        .logo h2 { font-size: 22px; }
        .menu a { display: flex; align-items: center; gap: 15px; color: white; text-decoration: none; padding: 14px 15px; border-radius: 8px; margin-bottom: 8px; transition: 0.3s; }
        .menu a:hover, .menu a.active { background: #2563eb; }
        .menu i { width: 22px; }
        .logout { position: absolute; bottom: 25px; left: 15px; right: 15px; }
        .logout a { display: flex; align-items: center; gap: 15px; color: white; text-decoration: none; padding: 14px 15px; background: #dc2626; border-radius: 8px; }
        
        .main { margin-left: 240px; padding: 30px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .topbar h1 { font-size: 28px; color: #1e3a8a; }

        .selector-box { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 25px; display: flex; gap: 15px; align-items: flex-end; }
        .form-group { flex: 1; }
        label { display: block; margin-bottom: 6px; font-weight: bold; font-size: 13px; color: #555; }
        select { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none; }
        .btn { background: #2563eb; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; height: 41px; }
        .btn:hover { background: #1d4ed8; }

        .statement-card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .statement-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 20px; }
        .client-info h3 { font-size: 22px; color: #1e3a8a; margin-bottom: 5px; }
        .client-info p { color: #666; font-size: 14px; margin-bottom: 3px; }
        
        .print-btn { background: #16a34a; color: white; border: none; padding: 10px 18px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; }
        .print-btn:hover { background: #15803d; }

        table { width: 100%; border-collapse: collapse; font-size: 14px; margin-top: 15px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8fafc; color: #555; font-weight: bold; }
        
        .totals-summary { margin-top: 20px; text-align: right; font-size: 16px; }
        .totals-summary p { margin-bottom: 6px; }
        .totals-summary .balance-due { font-size: 20px; font-weight: bold; color: #dc2626; }

        @media print {
            .sidebar, .selector-box, .print-btn, .topbar { display: none; }
            .main { margin-left: 0; padding: 0; }
            .statement-card { box-shadow: none; padding: 0; }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo">
        <i class="fa-solid fa-hand-holding-dollar"></i>
        <h2>Loan Manager</h2>
    </div>
    <div class="menu">
        <a href="dashboard.php"><i class="fa-solid fa-gauge"></i><span>Dashboard</span></a>
        <a href="customers.php"><i class="fa-solid fa-users"></i><span>Clients</span></a>
        <a href="sale1.php"><i class="fa-solid fa-file-invoice-dollar"></i><span>New Loan</span></a>
        <a href="payment.php"><i class="fa-solid fa-money-bill-wave"></i><span>Payments</span></a>
        <a href="reports.php" class="active"><i class="fa-solid fa-chart-line"></i><span>Reports</span></a>
    </div>
    <div class="logout">
        <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <h1>Individual Customer Statement</h1>
    </div>

    <!-- Select Customer Form -->
    <form method="GET" class="selector-box">
        <div class="form-group">
            <label for="client_id">Select Customer</label>
            <select name="client_id" id="client_id" required>
                <option value="">-- Choose a customer --</option>
                <?php foreach ($clientsList as $client): ?>
                    <option value="<?= (int)$client['id'] ?>" <?= $selectedClientId == $client['id'] ? 'selected' : '' ?>>
                        <?= h($client['name']) ?> <?= !empty($client['phone']) ? '('.h($client['phone']).')' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn"><i class="fa-solid fa-magnifying-glass"></i> View Statement</button>
    </form>

    <?php if ($clientData): ?>
        <div class="statement-card">
            <div class="statement-header">
                <div class="client-info">
                    <h3><?= h($clientData['name']) ?></h3>
                    <p><i class="fa-solid fa-phone"></i> Phone: <?= !empty($clientData['phone']) ? h($clientData['phone']) : 'N/A' ?></p>
                    <p><i class="fa-regular fa-calendar"></i> Statement Date: <?= date('Y-m-d') ?></p>
                </div>
                <button onclick="window.print()" class="print-btn"><i class="fa-solid fa-print"></i> Print Statement</button>
            </div>

            <h4 style="margin-bottom: 10px; color: #334155;">Purchase &amp; Loan History</h4>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Item / Product</th>
                        <th>Qty</th>
                        <th>Original Total</th>
                        <th>Amount Paid</th>
                        <th>Balance Due</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sumOriginal = 0;
                    $sumPaid = 0;
                    $sumBalance = 0;
                    if (empty($clientLoans)): 
                    ?>
                        <tr><td colspan="6" style="text-align: center; color: #777; padding: 20px;">No loan or purchase history found for this client.</td></tr>
                    <?php else: ?>
                        <?php foreach ($clientLoans as $loan): 
                            $sumOriginal += $loan['loan_amount'];
                            $sumPaid += $loan['amount_paid'];
                            $sumBalance += $loan['balance'];
                        ?>
                            <tr>
                                <td><?= h($loan['loan_date']) ?></td>
                                <td><strong><?= h($loan['item']) ?></strong></td>
                                <td><?= number_format($loan['quantity'], 2) ?> <?= h($loan['unit']) ?></td>
                                <td><?= number_format($loan['loan_amount']) ?> RWF</td>
                                <td style="color: #16a34a; font-weight: bold;"><?= number_format($loan['amount_paid']) ?> RWF</td>
                                <td style="color: #dc2626; font-weight: bold;"><?= number_format($loan['balance']) ?> RWF</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if (!empty($clientLoans)): ?>
                <div class="totals-summary">
                    <p>Total Credit Issued: <strong><?= number_format($sumOriginal) ?> RWF</strong></p>
                    <p>Total Amount Paid: <strong style="color: #16a34a;"><?= number_format($sumPaid) ?> RWF</strong></p>
                    <p class="balance-due">Total Remaining Balance: <span><?= number_format($sumBalance) ?> RWF</span></p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>