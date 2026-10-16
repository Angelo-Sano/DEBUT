<?php
session_start();

/* Protect dashboard */
if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("Location: login.html");
    exit();
}

/* Include database connection */
require_once "db.php";

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

try {
    /* 1. Get Total Clients Count */
    $stmt_clients = $pdo->query("SELECT COUNT(*) FROM clients");
    $total_clients = $stmt_clients->fetchColumn();

    /* 2. Get Loan Totals & Financial Metrics from loans table */
    $total_loans = $pdo->query("SELECT COUNT(*) FROM loans")->fetchColumn() ?? 0;
    $total_owed = $pdo->query("SELECT SUM(loan_amount) FROM loans")->fetchColumn() ?? 0;
    $total_paid = $pdo->query("SELECT SUM(amount_paid) FROM loans")->fetchColumn() ?? 0;
    $outstanding = $pdo->query("SELECT SUM(balance) FROM loans")->fetchColumn() ?? 0;

    /* 3. Fetch Recent Sales/Loans Records */
    $stmt_recent = $pdo->query("
        SELECT loans.id, loans.item, loans.quantity, loans.unit, loans.loan_amount, loans.amount_paid, loans.balance, loans.loan_date, clients.name AS client_name 
        FROM loans 
        JOIN clients ON loans.client_id = clients.id 
        ORDER BY loans.loan_date DESC, loans.id DESC 
        LIMIT 10
    ");
    $recentLoans = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $total_clients = 0;
    $total_loans = 0;
    $total_owed = 0;
    $total_paid = 0;
    $outstanding = 0;
    $recentLoans = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Loan Manager</title>
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
        .logout a:hover { background: #b91c1c; }
        .main { margin-left: 240px; padding: 30px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .topbar h1 { font-size: 30px; }
        .admin { background: white; padding: 10px 18px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        
        .cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .card { background: white; padding: 22px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .card-header i { font-size: 25px; color: #2563eb; }
        .card h3 { color: #666; font-size: 14px; }
        .card .number { font-size: 24px; font-weight: bold; }
        .card .number.owing { color: #dc2626; }

        .client-section { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .section-header h2 { font-size: 22px; color: #1e3a8a; }
        .view-btn { background: #2563eb; color: white; text-decoration: none; padding: 10px 17px; border-radius: 7px; display: inline-flex; align-items: center; gap: 8px; font-weight: bold; font-size: 14px; }
        .view-btn:hover { background: #1d4ed8; }
        
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { padding: 14px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8fafc; color: #555; font-weight: bold; }
        tr:hover { background-color: #f8fafc; }

        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; display: inline-block; }
        .status-unpaid { background: #fee2e2; color: #991b1b; }
        .status-partial { background: #fef3c7; color: #92400e; }
        .status-paid { background: #dcfce7; color: #166534; }

        .paid { color: #16a34a; font-weight: bold; }
        .owing { color: #dc2626; font-weight: bold; }
        .no-data { text-align: center; color: #777; padding: 30px; }

        /* Delete action button */
        .delete-btn { background: #fee2e2; color: #dc2626; border: none; padding: 6px 10px; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; font-size: 13px; font-weight: bold; transition: 0.2s; }
        .delete-btn:hover { background: #fecaca; }

        @media(max-width: 1100px) { .cards { grid-template-columns: repeat(2, 1fr); } }
        @media(max-width: 700px) {
            .sidebar { width: 70px; }
            .logo h2, .menu span, .logout span { display: none; }
            .main { margin-left: 70px; }
            .cards { grid-template-columns: 1fr; }
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
        <a href="dashboard.php" class="active"><i class="fa-solid fa-gauge"></i><span>Dashboard</span></a>
        <a href="customers.php"><i class="fa-solid fa-users"></i><span>Clients</span></a>
        <a href="sale1.php"><i class="fa-solid fa-file-invoice-dollar"></i><span>New Loan</span></a>
        <a href="payment.php"><i class="fa-solid fa-money-bill-wave"></i><span>Payments</span></a>
        <a href="report.php"><i class="fa-solid fa-chart-line"></i><span>Reports</span></a>
    </div>
    <div class="logout">
        <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <div>
            <h1>Dashboard</h1>
            <p>Manage your loans and clients</p>
        </div>
        <div class="admin">
            <i class="fa-solid fa-user"></i> Admin
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="cards">
        <div class="card">
            <div class="card-header">
                <h3>Total Clients</h3>
                <i class="fa-solid fa-users"></i>
            </div>
            <div class="number"><?php echo number_format($total_clients); ?></div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Total Loans</h3>
                <i class="fa-solid fa-file-invoice-dollar"></i>
            </div>
            <div class="number"><?php echo number_format($total_loans); ?></div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Total Amount</h3>
                <i class="fa-solid fa-money-bill"></i>
            </div>
            <div class="number"><?php echo number_format($total_owed); ?> RWF</div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Outstanding</h3>
                <i class="fa-solid fa-wallet"></i>
            </div>
            <div class="number owing"><?php echo number_format($outstanding); ?> RWF</div>
        </div>
    </div>

    <!-- Recent Sales & Loans Table -->
    <div class="client-section">
        <div class="section-header">
            <h2><i class="fa-solid fa-chart-line"></i> Recent Sales &amp; Loans</h2>
            <a href="sale1.php" class="view-btn"><i class="fa-solid fa-plus"></i> New Loan / Sale</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Client Name</th>
                    <th>Item / Product</th>
                    <th>Qty</th>
                    <th>Total</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($recentLoans)): ?>
                <tr>
                    <td colspan="9" class="no-data">No recent sales or loans found in the database.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($recentLoans as $loan): ?>
                    <tr>
                        <td><?= h($loan['loan_date']) ?></td>
                        <td><strong><?= h($loan['client_name']) ?></strong></td>
                        <td><?= h($loan['item']) ?></td>
                        <td><?= number_format($loan['quantity'], 2) ?> <?= h($loan['unit']) ?></td>
                        <td><?= number_format($loan['loan_amount']) ?> RWF</td>
                        <td class="paid"><?= number_format($loan['amount_paid']) ?> RWF</td>
                        <td class="owing"><?= number_format($loan['balance']) ?> RWF</td>
                        <td>
                            <?php if ($loan['balance'] <= 0): ?>
                                <span class="status-badge status-paid">Paid</span>
                            <?php elseif ($loan['amount_paid'] > 0): ?>
                                <span class="status-badge status-partial">Partial</span>
                            <?php else: ?>
                                <span class="status-badge status-unpaid">Unpaid</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="delete_loan.php?id=<?= (int)$loan['id'] ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this record?');">
                                <i class="fa-solid fa-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>