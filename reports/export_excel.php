<?php
// १. आउटपुट बफरिंग सुरू करणे (फाईल करप्ट होण्यापासून वाचवण्यासाठी अत्यंत महत्त्वाचे)
ob_start();

require '../vendor/autoload.php';
include '../config/database.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// २. डेटाबेस कनेक्शन चेक करणे
if (!$conn) {
    die("डेटाबेस कनेक्शन अयशस्वी झाले.");
}

// ३. फक्त आवश्यक डेटा क्वेरी करणे (Performance Optimization)
$query = "SELECT student_id, attendance_date, status FROM attendance";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    ob_end_clean();
    die("डाउनलोड करण्यासाठी कोणताही हजेरीचा डेटा उपलब्ध नाही.");
}

// ४. नवीन स्प्रेडशीट ऑब्जेक्ट तयार करणे
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Attendance Report');

// ५. टेबल हेडर सेट करणे
$sheet->setCellValue('A1', 'Student ID');
$sheet->setCellValue('B1', 'Date');
$sheet->setCellValue('C1', 'Status');

// ६. डेटा लूप फिरवून रो-बाय-रो डेटा भरणे
$rowNum = 2;
while ($row = mysqli_fetch_assoc($result)) {
    // डेटा क्लीन करून व्हॅल्यू सेट करणे
    $sheet->setCellValue('A' . $rowNum, $row['student_id']);
    $sheet->setCellValue('B' . $rowNum, $row['attendance_date']);
    $sheet->setCellValue('C' . $rowNum, ucfirst(strtolower($row['status']))); // Status चे पहिले अक्षर Capital दिसेल
    $rowNum++;
}

// ७. रायटर ऑब्जेक्ट तयार करणे
$writer = new Xlsx($spreadsheet);

// ८. जुना बफर डेटा क्लीन करून हेडर सेट करणे
ob_end_clean();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="Attendance_Report_' . date('Y-m-d') . '.xlsx"');
header('Cache-Control: max-age=0');
header('Cache-Control: max-age=1'); // IE ब्राउझरसाठी

// ९. फाईल आउटपुटला पाठवणे
$writer->save("php://output");
exit;
?>