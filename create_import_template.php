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
    'Email (Required)',
    'Name (Required)',
    'Password (Required)',
    'Roles (Optional)',
    'OpenAI API Key (Optional)'
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

// Add instructions in a comment
$sheet->getComment('A1')->getText()->createTextRun(
    "INSTRUCTIONS:\n\n" .
    "1. Fill in the required fields: email, name, password\n" .
    "2. Roles are optional - use comma-separated values (e.g., instructor,admin)\n" .
    "3. Delete the example row (row 2) before uploading\n" .
    "4. Maximum file size: 5MB\n" .
    "5. All imported users will be verified automatically"
);

// Resize comment box
$sheet->getComment('A1')->setWidth('400px');
$sheet->getComment('A1')->setHeight('150px');

// Add instructions below the data
$sheet->setCellValue('A4', 'Instructions:');
$sheet->getStyle('A4')->getFont()->setBold(true)->setSize(12);

$instructions = [
    '• Email: Must be unique and valid email format',
    '• Name: Full name of the user (2-255 characters)',
    '• Password: Minimum 6 characters',
    '• Roles: Optional, comma-separated (e.g., "instructor,admin")',
    '• OpenAI API Key: Optional, user\'s OpenAI API key',
    '',
    'Notes:',
    '• All imported users will be set as verified',
    '• Users must accept terms on first login',
    '• Delete row 2 (example) before uploading',
    '• Maximum 1000 users per import recommended'
];

$row = 5;
foreach ($instructions as $instruction) {
    $sheet->setCellValue('A' . $row, $instruction);
    $sheet->getStyle('A' . $row)->getFont()->setSize(9);
    $sheet->mergeCells('A' . $row . ':E' . $row);
    $row++;
}

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
