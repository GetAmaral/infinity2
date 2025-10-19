#!/usr/bin/env php
<?php

require __DIR__ . '/app/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Hyperlink;

// Create new spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set headers
$sheet->setCellValue('A1', 'email');
$sheet->setCellValue('B1', 'name');
$sheet->setCellValue('C1', 'password');
$sheet->setCellValue('D1', 'roles');
$sheet->setCellValue('E1', 'openai_api_key');

// Test data rows with HYPERLINKS
echo "Creating test file with email hyperlinks...\n\n";

// Row 2: Email as hyperlink (simulating Excel auto-conversion)
$email1 = 'hyperlink1@example.com';
$sheet->setCellValue('A2', $email1);
$sheet->getCell('A2')->getHyperlink()->setUrl('mailto:' . $email1);
$sheet->setCellValue('B2', 'Hyperlink User One');
$sheet->setCellValue('C2', 'Password123');
$sheet->setCellValue('D2', 'instructor');
echo "Row 2: Email as mailto: hyperlink - {$email1}\n";

// Row 3: Email as hyperlink with query parameters
$email2 = 'hyperlink2@example.com';
$sheet->setCellValue('A3', $email2);
$sheet->getCell('A3')->getHyperlink()->setUrl('mailto:' . $email2 . '?subject=Test');
$sheet->setCellValue('B3', 'Hyperlink User Two');
$sheet->setCellValue('C3', 'Password456');
$sheet->setCellValue('D3', 'admin');
echo "Row 3: Email as mailto: hyperlink with query params - {$email2}\n";

// Row 4: Plain text email (no hyperlink)
$email3 = 'plaintext@example.com';
$sheet->setCellValue('A4', $email3);
$sheet->setCellValue('B4', 'Plain Text User');
$sheet->setCellValue('C4', 'Password789');
$sheet->setCellValue('D4', '');
echo "Row 4: Plain text email (no hyperlink) - {$email3}\n";

// Row 5: Email as hyperlink but with text display value
$email4 = 'display@example.com';
$sheet->setCellValue('A5', 'Click here'); // Display text
$sheet->getCell('A5')->getHyperlink()->setUrl('mailto:' . $email4);
$sheet->setCellValue('B5', 'Display Text User');
$sheet->setCellValue('C5', 'Password000');
$sheet->setCellValue('D5', 'instructor');
echo "Row 5: Email hyperlink with display text - {$email4}\n";

// Write to file
$writer = new Xlsx($spreadsheet);
$filename = __DIR__ . '/test_hyperlink_emails.xlsx';
$writer->save($filename);

echo "\n✓ Test file created: {$filename}\n";
echo "\nExpected results:\n";
echo "  Row 2: SUCCEED - Extract from mailto:hyperlink1@example.com\n";
echo "  Row 3: SUCCEED - Extract from mailto:hyperlink2@example.com?subject=Test (ignore query)\n";
echo "  Row 4: SUCCEED - Use plain text email\n";
echo "  Row 5: SUCCEED - Extract from mailto:display@example.com (ignore display text)\n";
echo "\nAll 4 rows should import successfully with correct email extraction!\n";
