<?php
include 'functions.php';

$filter = $_POST['filter'] ?? 'daily';
$dateRange = getDateRange($filter);
$data = readExpenseData($dateRange);

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="expenses.csv"');

$fp = fopen('php://output', 'w');

$header = array_merge(['Expense Name'], $dateRange);
fputcsv($fp, $header);

foreach ($data as $expense => $dates) {
    $row = [$expense];
    foreach ($dateRange as $date) {
        $row[] = $dates[$date] ?? 0;
    }
    fputcsv($fp, $row);
}

fclose($fp);
exit;
?>
