<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance | Dashboard</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
                <div class="kpi-value">₱ 845,000</div>
                <div class="kpi-label">Total Collections</div>
            </div>

            <div class="kpi-card card-gold">
                <div class="kpi-icon"><i class="bi bi-piggy-bank"></i></div>
                <div class="kpi-value">₱ 124,500</div>
                <div class="kpi-label">Net Profit (YTD)</div>
            </div>

            <div class="kpi-card card-red">
                <div class="kpi-icon"><i class="bi bi-exclamation-circle"></i></div>
                <div class="kpi-value">₱ 350,000</div>
                <div class="kpi-label">Outstanding Principal</div>
            </div>

            <div class="kpi-card card-blue">
                <div class="kpi-icon"><i class="bi bi-cash-stack"></i></div>
                <div class="kpi-value">₱ 1.2M</div>
                <div class="kpi-label">Total Disbursed</div>
            </div>

        </div>

        <div class="mid-section">
            
            <div class="chart-container">
                <div class="section-head">
                    <h3>Cash Flow (Last 6 Months)</h3>
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
                    <div class="rate-sub">Monthly / Diminishing</div>
                </div>

                <div style="margin-top: 10px; padding-top:10px; border-top:1px solid #334155; font-size:12px; color:#cbd5e1;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
                        <span>Penalty Rate:</span>
                        <span style="color:#f87171; font-weight:700;">5.0%</span>
                    </div>
                    <div style="display:flex; justify-content:space-between;">
                        <span>Processing Fee:</span>
                        <span style="color:#fff; font-weight:700;">₱ 500.00</span>
                    </div>
                </div>

                <a href="settings.php" class="btn-config">
                    Configure Rates
                </a>
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
                        <th>Type</th>
                        <th>Method</th>
                        <th>Date</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>#TRX-8801</td>
                        <td>Maria Clara</td>
                        <td><span class="badge-in">Loan Payment</span></td>
                        <td>GCash</td>
                        <td>Oct 20, 2025 - 10:30 AM</td>
                        <td class="amount-positive">+ ₱ 5,600.00</td>
                    </tr>
                    <tr>
                        <td>#TRX-8802</td>
                        <td>Gary Thompson</td>
                        <td><span class="badge-out">Disbursement</span></td>
                        <td>Bank Transfer</td>
                        <td>Oct 20, 2025 - 09:15 AM</td>
                        <td class="amount-negative">- ₱ 25,000.00</td>
                    </tr>
                    <tr>
                        <td>#TRX-8803</td>
                        <td>Juan Dela Cruz</td>
                        <td><span class="badge-in">Loan Payment</span></td>
                        <td>Cash / OTC</td>
                        <td>Oct 19, 2025 - 04:45 PM</td>
                        <td class="amount-positive">+ ₱ 3,200.00</td>
                    </tr>
                    <tr>
                        <td>#TRX-8804</td>
                        <td>Pedro Penduko</td>
                        <td><span class="badge-in">Loan Payment</span></td>
                        <td>GCash</td>
                        <td>Oct 19, 2025 - 02:20 PM</td>
                        <td class="amount-positive">+ ₱ 4,150.00</td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>

    <script>
        const ctx = document.getElementById('cashFlowChart').getContext('2d');
        const myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                datasets: [
                    {
                        label: 'Collections (In)',
                        data: [120000, 150000, 180000, 200000, 210000, 240000],
                        backgroundColor: '#10b981', // Green
                        borderRadius: 4
                    },
                    {
                        label: 'Disbursements (Out)',
                        data: [80000, 100000, 120000, 90000, 150000, 110000],
                        backgroundColor: '#60a5fa', // Blue
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { labels: { color: '#94a3b8' } }
                },
                scales: {
                    y: {
                        ticks: { color: '#64748b' },
                        grid: { color: '#334155' },
                        beginAtZero: true
                    },
                    x: {
                        ticks: { color: '#64748b' },
                        grid: { display: false }
                    }
                }
            }
        });
    </script>

</body>
</html>