<?php
function getDataFilePath($date) {
    $folder = 'data';
    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }
    $monthKey = date('Y-m', strtotime($date));
    return $folder . '/' . $monthKey . '.json';
}

function getDateRange($filter) {
    $dates = [];
    $today = date('Y-m-d');

    switch ($filter) {
        case 'weekly':
            $start = date('Y-m-d', strtotime('monday this week'));
            $end = date('Y-m-d', strtotime('sunday this week'));
            break;
        case 'monthly':
            $start = date('Y-m-01');
            $end = date('Y-m-t');
            break;
        case 'yearly':
            $start = date('Y-01-01');
            $end = date('Y-12-31');
            break;
        default: // daily
            $start = $today;
            $end = $today;
    }

    $current = strtotime($start);
    $endTime = strtotime($end);

    while ($current <= $endTime) {
        $dates[] = date('Y-m-d', $current);
        $current = strtotime('+1 day', $current);
    }

    return $dates;
}

function readExpenseData($dateRange) {
    $allData = [];

    foreach ($dateRange as $date) {
        $filePath = getDataFilePath($date);
        if (!file_exists($filePath)) continue;

        $json = file_get_contents($filePath);
        $monthlyData = json_decode($json, true) ?? [];

        foreach ($monthlyData as $expenseName => $dates) {
            foreach ($dates as $d => $value) {
                if (!in_array($d, $dateRange)) continue;
                if (!isset($allData[$expenseName])) {
                    $allData[$expenseName] = [];
                }
                $allData[$expenseName][$d] = floatval($value);
            }
        }
    }

    return $allData;
}
