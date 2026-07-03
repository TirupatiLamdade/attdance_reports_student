<?php
// 1. डोमपीडीएफ (Dompdf) आणि डेटाबेस कॉन्फिगरेशन लोड करणे
require '../vendor/autoload.php';
include '../config/database.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$error_message = ""; 

// 2. जर युझरने फॉर्म सबमिट केला असेल तर
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'download_pdf') {
    
    $report_type = mysqli_real_escape_string($conn, $_POST['report_type']);
    $branch      = mysqli_real_escape_string($conn, $_POST['branch']);
    $semester    = mysqli_real_escape_string($conn, $_POST['semester']);
    
    $title_date_string = "";
    $date_filter = "";

    if ($report_type === 'daily') {
        $daily_date = mysqli_real_escape_string($conn, $_POST['daily_date']);
        $date_filter = " AND a.attendance_date = '$daily_date'";
        $title_date_string = "Date: " . date('d-m-Y', strtotime($daily_date));

        // डेली रिपोर्टसाठी साधी Query
        $query = "SELECT a.id, a.student_id, a.attendance_date, a.status, s.name 
                  FROM attendance a 
                  INNER JOIN students s ON a.student_id = s.student_id 
                  WHERE s.branch = '$branch' AND (s.sem = '$semester' OR s.sem LIKE '%$semester%') $date_filter
                  ORDER BY s.student_id ASC";
    } else {
        $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
        $end_date   = mysqli_real_escape_string($conn, $_POST['end_date']);
        $date_filter = " AND a.attendance_date BETWEEN '$start_date' AND '$end_date'";
        $title_date_string = "Period: " . date('d-m-Y', strtotime($start_date)) . " To " . date('d-m-Y', strtotime($end_date));

        // 🟢 मंथली रिपोर्टसाठी GROUP BY आणि COUNT असलेली प्रगत Query
        $query = "SELECT 
                    s.student_id, 
                    s.name, 
                    COUNT(a.id) as total_days,
                    SUM(CASE WHEN LOWER(TRIM(a.status)) = 'present' THEN 1 ELSE 0 END) as present_days,
                    SUM(CASE WHEN LOWER(TRIM(a.status)) = 'absent' THEN 1 ELSE 0 END) as absent_days
                  FROM students s
                  LEFT JOIN attendance a ON s.student_id = a.student_id $date_filter
                  WHERE s.branch = '$branch' AND (s.sem = '$semester' OR s.sem LIKE '%$semester%')
                  GROUP BY s.student_id
                  ORDER BY s.student_id ASC";
    }

    $result = mysqli_query($conn, $query);

    if (!$result || mysqli_num_rows($result) === 0) {
        $error_message = "No attendance records found for the selected filters.";
    } else {
        // प्रिमियम पीडीएफ डिझाईन टेम्पलेट (A4 Landscape जेणेकरून ग्राफ व्यवस्थित बसेल)
        $html = "
        <html>
        <head>
            <style>
                @page { margin: 30px 40px; }
                body { font-family: sans-serif; color: #1e293b; line-height: 1.4; }
                .header { text-align: center; margin-bottom: 20px; border-bottom: 3px solid #4f46e5; padding-bottom: 10px; }
                .header h2 { margin: 0; color: #1e293b; text-transform: uppercase; font-size: 20px; }
                .header p { margin: 4px 0 0 0; color: #64748b; font-size: 11px; }
                
                .info-table { width: 100%; margin-bottom: 20px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px; }
                .info-table td { font-size: 12px; color: #334155; padding: 3px 6px; }
                
                .report-table { width: 100%; border-collapse: collapse; margin-top: 5px; }
                .report-table th { background-color: #4f46e5; color: white; padding: 8px 10px; font-size: 11px; text-transform: uppercase; text-align: left; }
                .report-table td { border-bottom: 1px solid #e2e8f0; padding: 8px 10px; font-size: 11px; color: #334155; }
                .report-table tr:nth-child(even) { background-color: #f8fafc; }
                
                .text-center { text-align: center; }
                .badge-present { color: #10b981; font-weight: bold; }
                .badge-absent { color: #ef4444; font-weight: bold; }
                
                /* 📊 प्रोग्रेस बार ग्राफ स्टाईल्स */
                .graph-container { background-color: #e2e8f0; border-radius: 4px; width: 100%; height: 12px; display: block; overflow: hidden; }
                .graph-bar-success { background-color: #10b981; height: 100%; border-radius: 4px; }
                .graph-bar-danger { background-color: #ef4444; height: 100%; border-radius: 4px; }
                
                .footer { position: fixed; bottom: -10px; left: 0; right: 0; text-align: center; font-size: 10px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 5px; }
            </style>
        </head>
        <body>

        <div class='header'>
            <h2>" . ($report_type === 'daily' ? 'Daily Attendance Ledger' : 'Monthly Attendance Analytics') . "</h2>
            <p>Generated via Academic Portal on " . date('d-m-Y h:i A') . "</p>
        </div>

        <table class='info-table'>
            <tr>
                <td><strong>Branch:</strong> " . htmlspecialchars($branch) . "</td>
                <td align='right'><strong>Semester:</strong> Semester " . htmlspecialchars($semester) . "</td>
            </tr>
            <tr>
                <td><strong>Report Scope:</strong> " . ucfirst($report_type) . " Registry</td>
                <td align='right'><strong>" . $title_date_string . "</strong></td>
            </tr>
        </table>

        <table class='report-table'>
            <thead>
                <tr>";
                if ($report_type === 'daily') {
                    $html .= "
                    <th width='15%'>Log ID</th>
                    <th width='25%'>Student ID</th>
                    <th width='35%'>Student Name</th>
                    <th width='25%'>Attendance Status</th>";
                } else {
                    $html .= "
                    <th width='15%'>Student ID</th>
                    <th width='25%'>Student Name</th>
                    <th width='10%' class='text-center'>Total Days</th>
                    <th width='10%' class='text-center'>Present</th>
                    <th width='10%' class='text-center'>Absent</th>
                    <th width='12%' class='text-center'>Attendance %</th>
                    <th width='18%'>Visual Graph</th>";
                }
                $html .= "
                </tr>
            </thead>
            <tbody>";

        while ($row = mysqli_fetch_assoc($result)) {
            if ($report_type === 'daily') {
                // डेली रो मांडणी
                $status_class = (trim(strtolower($row['status'])) == 'present') ? 'badge-present' : 'badge-absent';
                $status_text  = strtoupper($row['status']);
                $html .= "
                <tr>
                    <td>" . $row['id'] . "</td>
                    <td>" . htmlspecialchars($row['student_id']) . "</td>
                    <td>" . htmlspecialchars($row['name']) . "</td>
                    <td><span class='" . $status_class . "'>" . $status_text . "</span></td>
                </tr>";
            } else {
                // 🟢 मंथली रो मांडणी (गणित + प्रोग्रेस ग्राफ)
                $total_days   = (int)$row['total_days'];
                $present_days = (int)$row['present_days'];
                $absent_days  = (int)$row['absent_days'];
                
                $percentage = ($total_days > 0) ? round(($present_days / $total_days) * 100) : 0;
                $bar_class = ($percentage >= 75) ? 'graph-bar-success' : 'graph-bar-danger';
                $text_class = ($percentage >= 75) ? 'badge-present' : 'badge-absent';

                $html .= "
                <tr>
                    <td>" . htmlspecialchars($row['student_id']) . "</td>
                    <td><strong>" . htmlspecialchars($row['name']) . "</strong></td>
                    <td class='text-center'>" . $total_days . "</td>
                    <td class='text-center text-success' style='color:#10b981; font-weight:bold;'>" . $present_days . "</td>
                    <td class='text-center text-danger' style='color:#ef4444; font-weight:bold;'>" . $absent_days . "</td>
                    <td class='text-center " . $text_class . "'>" . $percentage . "%</td>
                    <td>
                        <div class='graph-container'>
                            <div class='" . $bar_class . "' style='width: " . $percentage . "%;'></div>
                        </div>
                    </td>
                </tr>";
            }
        }

        $html .= "
            </tbody>
        </table>

        <div class='footer'>Official Academic Record — Page 1</div>
        </body>
        </html>";

        // Dompdf संरचना लोड करणे
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $pdf = new Dompdf($options);
        $pdf->loadHtml($html);
        
        // डेटा कॉलम व्यवस्थित दिसण्यासाठी मंथलीला Landscape ठेवणे अधिक चांगले ठरेल
        if ($report_type === 'monthly') {
            $pdf->setPaper('A4', 'landscape');
        } else {
            $pdf->setPaper('A4', 'portrait');
        }
        
        $pdf->render();

        $filename = $report_type . "_report_" . str_replace(' ', '_', $branch) . "_Sem" . $semester . ".pdf";
        $pdf->stream($filename, array("Attachment" => true));
        exit();
    }
}

// 3. लेआउट हेडर आणि नॅव्हबार लोड करा
include '../includes/header.php';
include '../includes/navbar.php'; 

$standard_branches = ['Computer Science', 'Information Technology', 'Mechanical', 'Electrical', 'Civil', 'Electronics'];
?>

<style>
    .report-wrapper {
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
    }
    .report-card {
        background: rgba(17, 24, 39, 0.7);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        padding: 30px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        width: 100%;
        max-width: 600px;
        color: #f8fafc;
    }
    .btn-toggle {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #94a3b8;
        font-weight: 600;
        padding: 12px 20px;
        transition: all 0.3s ease;
    }
    .btn-toggle.active {
        background: #4f46e5 !important;
        color: #fff !important;
        box-shadow: 0 0 15px rgba(79, 70, 229, 0.3);
        border-color: #4f46e5;
    }
    .form-control, .form-select {
        background: rgba(7, 10, 19, 0.6) !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        color: #fff !important;
    }
    .form-control:focus, .form-select:focus {
        border-color: #4f46e5 !important;
        box-shadow: 0 0 10px rgba(79, 70, 229, 0.2) !important;
        color: #fff !important;
    }
    .form-select option {
        background: #111827;
        color: #fff;
    }
    label { color: #cbd5e1; font-size: 13px; margin-bottom: 6px; font-weight: 500; }
    .btn-download {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        border: none; color: white; font-weight: 600; padding: 12px;
    }
</style>

<div class="report-wrapper">
    <div class="report-card">
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show border-0 bg-danger bg-opacity-25 text-white mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_message; ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <h4 class="text-center mb-4 fw-bold"><i class="bi bi-file-earmark-pdf-fill text-danger me-2"></i>Attendance PDF Generetar </h4>
        
        <div class="d-flex gap-2 mb-4">
            <button type="button" id="btn-daily" class="btn btn-toggle active w-50" onclick="toggleReportType('daily')">
                <i class="bi bi-calendar-event me-2"></i>Daily Report
            </button>
            <button type="button" id="btn-monthly" class="btn btn-toggle w-50" onclick="toggleReportType('monthly')">
                <i class="bi bi-calendar-range me-2"></i>Monthly Report
            </button>
        </div>

        <form action="" method="POST">
            <input type="hidden" name="action" value="download_pdf">
            <input type="hidden" name="report_type" id="report_type" value="daily">

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label><i class="bi bi-mortarboard-fill me-1"></i> Branch</label>
                    <select name="branch" class="form-select" required>
                        <option value="">-- Select Branch --</option>
                        <?php foreach ($standard_branches as $b) {
                            echo "<option value='".htmlspecialchars($b)."'>".htmlspecialchars($b)."</option>";
                        } ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label><i class="bi bi-hash me-1"></i> Semester</label>
                    <select name="semester" class="form-select" required>
                        <option value="">-- Select Sem --</option>
                        <?php for($i=1; $i<=6; $i++) { echo "<option value='$i'>Semester $i</option>"; } ?>
                    </select>
                </div>
            </div>

            <div id="daily-date-group" class="mb-4">
                <label><i class="bi bi-calendar-check me-1"></i> Select Date</label>
                <input type="date" name="daily_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
            </div>

            <div id="monthly-date-group" class="row g-3 mb-4 d-none">
                <div class="col-md-6">
                    <label><i class="bi bi-calendar-plus me-1"></i> Start Date</label>
                    <input type="date" name="start_date" id="start_date" class="form-control">
                </div>
                <div class="col-md-6">
                    <label><i class="bi bi-calendar-minus me-1"></i> End Date</label>
                    <input type="date" name="end_date" id="end_date" class="form-control">
                </div>
            </div>

            <button type="submit" class="btn btn-download w-100 rounded-3">
                <i class="bi bi-cloud-arrow-down-fill me-2"></i>Download PDF Report
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleReportType(type) {
    document.getElementById('report_type').value = type;
    const btnDaily = document.getElementById('btn-daily');
    const btnMonthly = document.getElementById('btn-monthly');
    const dailyGroup = document.getElementById('daily-date-group');
    const monthlyGroup = document.getElementById('monthly-date-group');
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');

    if (type === 'daily') {
        btnDaily.classList.add('active');
        btnMonthly.classList.remove('active');
        dailyGroup.classList.remove('d-none');
        monthlyGroup.classList.add('d-none');
        startDate.required = false;
        endDate.required = false;
    } else {
        btnMonthly.classList.add('active');
        btnDaily.classList.remove('active');
        monthlyGroup.classList.remove('d-none');
        dailyGroup.classList.add('d-none');
        startDate.required = true;
        endDate.required = true;
    }
}
</script>

<?php 
include '../includes/footer.php'; 
?>