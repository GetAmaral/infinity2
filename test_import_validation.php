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

// Test data rows
$testRows = [
    // Row 2: Valid user - should SUCCEED
    ['test1@example.com', 'Test User One', 'Password123', 'instructor', ''],

    // Row 3: Valid user with multiple roles - should SUCCEED
    ['test2@example.com', 'Test User Two', 'Password456', 'instructor,admin', 'sk-test-123'],

    // Row 4: Invalid email - should FAIL with "Invalid email format"
    ['not-an-email', 'Bad Email User', 'Password789', '', ''],

    // Row 5: Valid email but missing name - should FAIL with "Name is required"
    ['test3@example.com', '', 'Password999', '', ''],

    // Row 6: Valid email but missing password - should FAIL with "Password is required"
    ['test4@example.com', 'Missing Password', '', '', ''],

    // Row 7: Completely empty row - should be IGNORED
    ['', '', '', '', ''],

    // Row 8: Text in column A (like "Instructions:") - should be IGNORED
    ['Instructions:', 'Some text', 'More text', '', ''],

    // Row 9: Another valid user - should SUCCEED
    ['test5@example.com', 'Test User Five', 'Password000', '', ''],
];

$row = 2;
foreach ($testRows as $rowData) {
    $sheet->setCellValue('A' . $row, $rowData[0]);
    $sheet->setCellValue('B' . $row, $rowData[1]);
    $sheet->setCellValue('C' . $row, $rowData[2]);
    $sheet->setCellValue('D' . $row, $rowData[3]);
    $sheet->setCellValue('E' . $row, $rowData[4]);
    $row++;
}

// Write to file
$writer = new Xlsx($spreadsheet);
$filename = __DIR__ . '/test_user_import_validation.xlsx';
$writer->save($filename);

echo "✓ Test import file created: {$filename}\n";
echo "\nExpected results:\n";
echo "  Row 2: SUCCEED - Valid user\n";
echo "  Row 3: SUCCEED - Valid user with multiple roles\n";
echo "  Row 4: FAIL - Invalid email format\n";
echo "  Row 5: FAIL - Name is required\n";
echo "  Row 6: FAIL - Password is required\n";
echo "  Row 7: IGNORED - Empty row\n";
echo "  Row 8: IGNORED - No valid email (text in column A)\n";
echo "  Row 9: SUCCEED - Valid user\n";
echo "\nTotal expected: 3 valid users, 3 errors, 2 ignored rows\n";
