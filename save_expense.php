<?php
include 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $names = $_POST['expense_names'] ?? [];
    $values = $_POST['expense_values'] ?? [];
    $filter = $_POST['filter'] ?? 'daily'; // NEW: Track selected filter

    $filePath = getDataFilePath($date);
    $existingData = [];

    if (file_exists($filePath)) {
        $existingData = json_decode(file_get_contents($filePath), true) ?? [];
    }

    for ($i = 0; $i < count($names); $i++) {
        $name = trim($names[$i]);
        $amount = floatval($values[$i]);

       if (!$name || !is_numeric($amount)) continue; // allow positive or negative

        if (!isset($existingData[$name])) {
            $existingData[$name] = [];
        }

        $existingData[$name][$date] = $amount;
    }

    file_put_contents($filePath, json_encode($existingData, JSON_PRETTY_PRINT));

    // Redirect back with same filter
    header('Location: index.php?filter=' . urlencode($filter));
    exit;
}
