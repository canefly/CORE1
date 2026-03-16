<?php
// CORE1/client/include/check_penalties.php

// 1. Kunin ang Penalty Rate at Grace Period mula sa system_settings
$sysSettings = [];
$stmtSet = $conn->query("SELECT setting_key, setting_value FROM system_settings");
if ($stmtSet) {
    while ($row = $stmtSet->fetch_assoc()) {
        $sysSettings[$row['setting_key']] = $row['setting_value'];
    }
}

$penalty_rate = (float)($sysSettings['penalty_rate'] ?? 5.0);
$grace_period = (int)($sysSettings['grace_period'] ?? 3);

// 2. Hanapin lahat ng ACTIVE loans na may utang pa
$sql = "SELECT id, monthly_due, outstanding, next_payment, last_penalty_date FROM loans WHERE status = 'ACTIVE' AND outstanding > 0";
$res = $conn->query($sql);

if ($res && $res->num_rows > 0) {
    $today = date('Y-m-d');
    
    // Ihanda ang Update command
    $update_stmt = $conn->prepare("UPDATE loans SET outstanding = outstanding + ?, last_penalty_date = ? WHERE id = ?");

    while ($loan = $res->fetch_assoc()) {
        $next_payment = $loan['next_payment'];
        $last_penalty = $loan['last_penalty_date'];

        // Compute kung kailan ang exact expiration ng Grace Period
        $overdue_date = date('Y-m-d', strtotime($next_payment . " + {$grace_period} days"));

        // Kapag ang araw ngayon ay lagpas na sa Grace Period...
        if ($today > $overdue_date) {
            
            // I-check kung HINDI PA siya napaparusahan para sa current due date na ito
            if (!$last_penalty || strtotime($last_penalty) < strtotime($next_payment)) {
                
                // SEC Rule: Ang penalty ay naka-base lang sa Monthly Due (past due amount), hindi sa buong utang.
                $penalty_amount = $loan['monthly_due'] * ($penalty_rate / 100);

                // I-apply ang penalty at i-update ang last_penalty_date para hindi ma-doble bukas
                $update_stmt->bind_param("dsi", $penalty_amount, $today, $loan['id']);
                $update_stmt->execute();
                
                // (Optional) Pwede ka ring mag-insert dito sa notifications table para ma-notify si client na na-late siya.
            }
        }
    }
    $update_stmt->close();
}
?>