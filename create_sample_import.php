#!/usr/bin/env php
<?php

require __DIR__ . '/app/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Create new spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set headers
$sheet->setCellValue('A1', 'email');
$sheet->setCellValue('B1', 'name');
$sheet->setCellValue('C1', 'password');
$sheet->setCellValue('D1', 'roles');
$sheet->setCellValue('E1', 'openai_api_key');

// Add sample data
$users = [
    ['john.doe@example.com', 'John Doe', 'SecurePass123', 'instructor', ''],
    ['jane.smith@example.com', 'Jane Smith', 'AnotherPass456', 'student', ''],
    ['bob.jones@example.com', 'Bob Jones', 'BobPassword789', 'admin,instructor', 'sk-test-abc123'],
];

$row = 2;
foreach ($users as $user) {
    $sheet->setCellValue('A' . $row, $user[0]);
    $sheet->setCellValue('B' . $row, $user[1]);
    $sheet->setCellValue('C' . $row, $user[2]);
    $sheet->setCellValue('D' . $row, $user[3]);
    $sheet->setCellValue('E' . $row, $user[4]);
    $row++;
}

// Write to file
$writer = new Xlsx($spreadsheet);
$filename = __DIR__ . '/sample_users_import.xlsx';
$writer->save($filename);

echo "Sample XLSX file created: {$filename}\n";
echo "Contains " . count($users) . " sample users.\n";
