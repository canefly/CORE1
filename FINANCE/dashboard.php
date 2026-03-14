<?php
// 1. Establish Database Connection with absolute pathing
$connection_file = __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/session_checker.php';

if (file_exists($connection_file)) {
    require_once $connection_file;
}
else {
    die("Error: Connection file not found at " . $connection_file);
}

// 2. Immediate check if $pdo exists
if (!isset($pdo)) {
    die("Fatal Error: \$pdo variable is not defined in includes/db_connect.php.");
}

// 3. Fetch Real-time Data
try {
    // Total Collections
    $totalCollections = $pdo->query("SELECT SUM(amount) FROM transactions WHERE status = 'SUCCESS'")->fetchColumn() ?: 0;

    // Outstanding Principal
    $outstandingPrincipal = $pdo->query("SELECT SUM(outstanding) FROM loans WHERE status = 'ACTIVE'")->fetchColumn() ?: 0;

    // Total Disbursed
    $totalDisbursed = $pdo->query("SELECT SUM(loan_amount) FROM loans")->fetchColumn() ?: 0;

    // Recent Transactions with User Names
    $stmt = $pdo->query("SELECT t.*, u.fullname FROM transactions t 
                         LEFT JOIN users u ON t.user_id = u.id 
                         ORDER BY t.trans_date DESC LIMIT 4");
    $recentTransactions = $stmt->fetchAll();

}
catch (PDOException $e) {
    die("Query Failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance | Dashboard</title>
        <script>
        // THE ANTI-FLASHBANG PROTOCOL 
        if (localStorage.getItem('theme') === null) {
            localStorage.setItem('theme', 'dark'); 
        }
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark-mode');
        }
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>


    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <div>
                <h1>Financial Overview</h1>
                <p>Real-time monitoring of disbursement, collections, and profitability.</p>
            </div>
            <div class="current-date">
                <i class="bi bi-calendar-event"></i> &nbsp; <?php echo date("F j, Y"); ?>
            </div>
        </div>

        <div class="kpi-grid">
            <div class="kpi-card card-green">
                <div class="kpi-icon"><i class="bi bi-graph-up-arrow"></i></div>
                <div class="kpi-value">₱ <?php echo number_format($totalCollections, 2); ?></div>
                <div class="kpi-label">Total Collections</div>
            </div>

            <div class="kpi-card card-gold">
                <div class="kpi-icon"><i class="bi bi-piggy-bank"></i></div>
                <div class="kpi-value">₱ <?php echo number_format($totalCollections * 0.15, 2); ?></div> <div class="kpi-label">Net Profit (Est.)</div>
            </div>

            <div class="kpi-card card-red">
                <div class="kpi-icon"><i class="bi bi-exclamation-circle"></i></div>
                <div class="kpi-value">₱ <?php echo number_format($outstandingPrincipal, 2); ?></div>
                <div class="kpi-label">Outstanding Principal</div>
            </div>

            <div class="kpi-card card-blue">
                <div class="kpi-icon"><i class="bi bi-cash-stack"></i></div>
                <div class="kpi-value">₱ <?php echo number_format($totalDisbursed, 2); ?></div>
                <div class="kpi-label">Total Disbursed</div>
            </div>
        </div>

        <div class="mid-section">
            <div class="chart-container">
                <div class="section-head">
                    <h3>Cash Flow (Real-time)</h3>
                </div>
                <canvas id="cashFlowChart" height="120"></canvas>
            </div>

            <div class="rate-card">
                <div class="section-head">
                    <h3>Active Interest Rate</h3>
                    <i class="bi bi-gear-fill" style="color:#94a3b8;"></i>
                </div>
                <div class="current-rate-box">
                    <div class="rate-big">3.5%</div>
                    <div class="rate-sub">Monthly / Flat</div>
                </div>
                <a href="settings.php" class="btn-config">Configure Rates</a>
            </div>
        </div>

        <div class="table-container">
            <div class="section-head">
                <h3>Recent Transactions</h3>
                <a href="ledger.php" class="btn-view-all">View All Ledger <i class="bi bi-arrow-right"></i></a>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Client Name</th>
                        <th>Method</th>
                        <th>Date</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentTransactions as $row): ?>
                    <tr>
                        <td>#TRX-<?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['fullname'] ?? 'System'); ?></td>
                        <td><?php echo htmlspecialchars($row['provider_method'] ?? 'Cash'); ?></td>
                        <td><?php echo date("M j, Y", strtotime($row['trans_date'])); ?></td>
                        <td class="amount-positive">+ ₱ <?php echo number_format($row['amount'], 2); ?></td>
                    </tr>
                    <?php
endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('cashFlowChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Past Months Avg', 'Current'],
                datasets: [
                    {
                        label: 'Collections',
                        data: [150000, <?php echo $totalCollections; ?>],
                        backgroundColor: '#10b981',
                        borderRadius: 4
                    },
                    {
                        label: 'Disbursements',
                        data: [100000, <?php echo $totalDisbursed; ?>],
                        backgroundColor: '#60a5fa',
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { labels: { color: '#94a3b8' } } },
                scales: {
                    y: { ticks: { color: '#64748b' }, grid: { color: '#334155' }, beginAtZero: true },
                    x: { ticks: { color: '#64748b' }, grid: { display: false } }
                }
            }
        });
    </script>
</body>
</html>