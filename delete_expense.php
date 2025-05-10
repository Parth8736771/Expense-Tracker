<?php
include 'functions.php';

$name = $_POST['name'];
$date = $_POST['date'];

$filePath = getDataFilePath($date);
$data = file_exists($filePath) ? json_decode(file_get_contents($filePath), true) : [];

if (isset($data[$name][$date])) {
    unset($data[$name][$date]);
    if (empty($data[$name])) {
        unset($data[$name]);
    }
    file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
    echo "Deleted successfully.";
} else {
    echo "Entry not found.";
}
?>
