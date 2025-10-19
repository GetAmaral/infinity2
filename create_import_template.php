#!/usr/bin/env php
<?php

require __DIR__ . '/app/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Create new spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set sheet title
$sheet->setTitle('User Import Template');

// Set headers with styling
$headers = ['email', 'name', 'password', 'roles', 'openai_api_key'];
$headerLabels = [
    'email',
    'name',
    'password',
    'roles',
    'openai_api_key'
];

// Apply headers
foreach ($headerLabels as $index => $label) {
    $column = chr(65 + $index); // A, B, C, D, E
    $cell = $column . '1';

    $sheet->setCellValue($cell, $label);

    // Style header cells
    $sheet->getStyle($cell)->applyFromArray([
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 11
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '4F46E5'] // Indigo
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ]
    ]);

    // Set column width
    $sheet->getColumnDimension($column)->setWidth(25);
}

// Set row height for header
$sheet->getRowDimension(1)->setRowHeight(25);

// Add example data row
$exampleData = [
    'john.doe@example.com',
    'John Doe',
    'SecurePass123',
    'instructor',
    ''
];

foreach ($exampleData as $index => $value) {
    $column = chr(65 + $index);
    $sheet->setCellValue($column . '2', $value);

    // Style example row
    $sheet->getStyle($column . '2')->applyFromArray([
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'F3F4F6'] // Light gray
        ],
        'font' => [
            'italic' => true,
            'color' => ['rgb' => '6B7280'] // Gray
        ]
    ]);
}

// Add comment to header with instructions
$comment = $sheet->getComment('A1');
$comment->getText()->createTextRun(
    "USER IMPORT TEMPLATE\n\n" .
    "Required fields:\n" .
    "• email - Must be unique and valid\n" .
    "• name - Full name (2-255 chars)\n" .
    "• password - Minimum 6 characters\n\n" .
    "Optional fields:\n" .
    "• roles - Comma-separated (e.g., instructor,admin)\n" .
    "• openai_api_key - User's OpenAI API key\n\n" .
    "Notes:\n" .
    "• Row 2 is an example - you can keep it or delete it\n" .
    "• Empty rows (no email) are ignored\n" .
    "• All imported users are verified\n" .
    "• Max file size: 5MB"
);

// Resize comment box
$comment->setWidth('450px');
$comment->setHeight('250px');

// Write to file in public directory
$publicDir = __DIR__ . '/app/public/templates';
if (!is_dir($publicDir)) {
    mkdir($publicDir, 0755, true);
}

$writer = new Xlsx($spreadsheet);
$filename = $publicDir . '/user_import_template.xlsx';
$writer->save($filename);

echo "✓ User import template created: {$filename}\n";
echo "  Download URL: /templates/user_import_template.xlsx\n";
echo "  Template has clean structure - only header and example row\n";
