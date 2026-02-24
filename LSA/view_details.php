<?php 
include 'includes/db_connect.php'; 

if (!isset($_GET['id'])) {
    header("Location: returned.php");
    exit();
}

$application_id = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch client and application info
$query = "SELECT la.*, u.fullname, u.email 
          FROM loan_applications la 
          JOIN users u ON la.user_id = u.id 
          WHERE la.id = '$application_id'";
$result = $conn->query($query);
$app = $result->fetch_assoc();

if (!$app) {
    echo "Application not found.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LSA | Review Details</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/view_details.css">
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <a href="returned.php" class="back-link" style="color: #10b981; text-decoration: none; font-size: 14px; display: flex; align-items: center; gap: 5px; margin-bottom: 15px;">
                <i class="bi bi-arrow-left"></i> Back to Returned List
            </a>
            <h1 style="font-size: 24px; color: #fff;">Application Review: #LA-<?php echo $app['id']; ?></h1>
        </div>

        <div class="details-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div class="info-card" style="background: #1f2937; border: 1px solid #374151; border-radius: 12px; padding: 20px;">
                <div class="card-title" style="color: #fff; font-weight: 700; margin-bottom: 15px; border-bottom: 1px solid #374151; padding-bottom: 10px;">
                    <i class="bi bi-person-circle"></i> Client Information
                </div>
                <div class="data-row" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: #94a3b8;">Full Name</span>
                    <span style="color: #fff;"><?php echo htmlspecialchars($app['fullname']); ?></span>
                </div>
                <div class="data-row" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: #94a3b8;">Email</span>
                    <span style="color: #fff;"><?php echo htmlspecialchars($app['email']); ?></span>
                </div>
                <div class="data-row" style="display: flex; justify-content: space-between;">
                    <span style="color: #94a3b8;">Amount Requested</span>
                    <span style="color: #10b981; font-weight: 700;">₱<?php echo number_format($app['principal_amount'], 2); ?></span>
                </div>
            </div>

            <div class="info-card" style="background: #1f2937; border: 1px solid #374151; border-radius: 12px; padding: 20px;">
                <div class="card-title" style="color: #fff; font-weight: 700; margin-bottom: 15px; border-bottom: 1px solid #374151; padding-bottom: 10px;">
                    <i class="bi bi-chat-left-dots"></i> LSA Feedback
                </div>
                <div class="data-row" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: #94a3b8;">Current Status</span>
                    <span class="badge-red" style="color: #ef4444; background: rgba(239, 68, 68, 0.1); padding: 2px 10px; border-radius: 20px; font-size: 11px;"><?php echo $app['status']; ?></span>
                </div>
                <label style="display:block; margin-bottom:8px; font-size:12px; color: #94a3b8;">REASON FOR RETURN:</label>
                <div class="remarks-display" style="background: rgba(239, 68, 68, 0.05); color: #f87171; padding: 15px; border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.2); font-style: italic;">
                    "<?php echo htmlspecialchars($app['remarks']); ?>"
                </div>
            </div>
        </div>

<div class="doc-list" style="background: #1f2937; border: 1px solid #374151; border-radius: 12px; padding: 25px; margin-top: 20px;">
    <div class="card-title" style="font-size: 16px; font-weight: 700; color: #fff; margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
        <i class="bi bi-images"></i> Submitted Documents Gallery
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
        <?php 
        
        $docQuery = "SELECT * FROM loan_documents WHERE loan_application_id = '$application_id'";
        $docResult = $conn->query($docQuery);

        if ($docResult && $docResult->num_rows > 0): 
            while($doc = $docResult->fetch_assoc()): 
                $label = ucwords(str_replace('_', ' ', $doc['doc_type']));
      
                $filePath = "../client/" . $doc['file_path']; 
        ?>
            <div class="doc-preview-card" style="background: #111827; border: 1px solid #374151; border-radius: 10px; overflow: hidden; display: flex; flex-direction: column; transition: transform 0.2s;">
                <div style="padding: 10px 15px; border-bottom: 1px solid #374151; display: flex; justify-content: space-between; align-items: center; background: #1f2937;">
                    <span style="color: #fff; font-size: 13px; font-weight: 600;"><?php echo $label; ?></span>
                    <a href="<?php echo $filePath; ?>" target="_blank" style="color: #34d399; font-size: 16px;"><i class="bi bi-box-arrow-up-right"></i></a>
                </div>
                
                <div style="width: 100%; height: 220px; background: #000; display: flex; align-items: center; justify-content: center; overflow: hidden; padding: 5px;">
                    <?php 
                    $ext = pathinfo($doc['file_path'], PATHINFO_EXTENSION);
                    if(in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'webp'])): ?>
                        <img src="<?php echo $filePath; ?>" alt="<?php echo $label; ?>" 
                             style="max-width: 100%; max-height: 100%; object-fit: contain; cursor: pointer;" 
                             onerror="this.src='https://placehold.co/400x300/111827/94a3b8?text=Image+Not+Found'"
                             onclick="window.open(this.src)">
                    <?php else: ?>
                        <div style="text-align: center; color: #94a3b8;">
                            <i class="bi bi-file-earmark-pdf" style="font-size: 50px;"></i>
                            <p style="font-size: 12px; margin-top: 5px;">PDF File</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div style="padding: 10px; text-align: center; font-size: 11px; color: #6b7280; background: rgba(0,0,0,0.2);">
                    Uploaded on <?php echo date('M d, Y', strtotime($doc['uploaded_at'])); ?>
                </div>
            </div>
        <?php endwhile; else: ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #94a3b8;">
                <i class="bi bi-folder-x" style="font-size: 40px; display: block; margin-bottom: 10px;"></i>
                <p>No documents found in DB for Application ID: <?php echo $application_id; ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>
    </div>

</body>
</html>