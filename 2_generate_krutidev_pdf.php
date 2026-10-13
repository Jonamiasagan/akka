<?php

declare(strict_types=1);

/**
 * Step 2: Read output_krutidev.txt, load local Krutidev010.ttf font,
 * and generate output_krutidev.pdf using tecnickcom/tcpdf.
 *
 * Requirements:
 * - tecnickcom/tcpdf
 * - Krutidev010.ttf (in current directory or ./fonts/)
 * - output_krutidev.txt
 *
 * Output: output_krutidev.pdf
 */

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    fwrite(STDERR, "Error: Composer dependencies not found. Please run 'composer install' first.\n");
    exit(1);
}
require_once $autoloadPath;

// Input text file
$inputFile = $argv[1] ?? (__DIR__ . '/output_krutidev.txt');
$outputPdf = __DIR__ . '/output_krutidev.pdf';

if (!file_exists($inputFile)) {
    fwrite(STDERR, "Error: Input text file not found: {$inputFile}\n");
    fwrite(STDERR, "Please run 'php 1_extract_and_convert.php' first to generate output_krutidev.txt.\n");
    exit(1);
}

// Locate Kruti Dev 010 TTF font
$fontCandidates = [
    __DIR__ . '/Krutidev010.ttf',
    __DIR__ . '/fonts/Krutidev010.ttf',
    __DIR__ . '/Kruti_Dev_010.ttf',
    __DIR__ . '/Kruti Dev 010.ttf',
    __DIR__ . '/Kruti Dev 010 Regular.ttf',
];

$fontFile = null;
foreach ($fontCandidates as $candidate) {
    if (file_exists($candidate)) {
        $fontFile = $candidate;
        break;
    }
}

if ($fontFile === null) {
    fwrite(STDERR, "Error: Krutidev010.ttf font not found.\n");
    fwrite(STDERR, "Searched locations:\n - " . implode("\n - ", $fontCandidates) . "\n");
    fwrite(STDERR, "Please place 'Krutidev010.ttf' in the project directory.\n");
    exit(1);
}

echo "=========================================================\n";
echo " Step 2: Generate Kruti Dev 010 PDF from Text File\n";
echo "=========================================================\n";
echo "Reading input text: {$inputFile} ...\n";
$krutiDevContent = file_get_contents($inputFile);

if ($krutiDevContent === false || trim($krutiDevContent) === '') {
    fwrite(STDERR, "Warning: Input text file is empty.\n");
}

echo "Loading font from: {$fontFile} ...\n";

try {
    // Initialize TCPDF (Portrait, mm, A4, UTF-8)
    $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Document metadata
    $pdf->SetCreator('Kruti Dev 010 PDF Generator');
    $pdf->SetAuthor('PDF Processor');
    $pdf->SetTitle('Kruti Dev 010 Document');
    $pdf->SetSubject('Converted Hindi PDF');

    // Clean page margins & disable default header/footer for document fidelity
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 15);

    // Convert and register TrueType font with TCPDF
    // 'TrueTypeUnicode' ensures full ASCII and extended-ASCII glyph range is mapped
    $fontName = \TCPDF_FONTS::addTTFfont($fontFile, 'TrueTypeUnicode', '', 96);
    if (!$fontName) {
        throw new \RuntimeException("TCPDF failed to process font: {$fontFile}");
    }
    echo "Font registered successfully with TCPDF as: '{$fontName}'\n";

    // Add page and apply Kruti Dev 010 font
    $pdf->AddPage();
    $pdf->SetFont($fontName, '', 14);

    // Write converted Kruti Dev text
    // MultiCell accurately handles multi-line content, line breaks, and page wrapping
    $pdf->MultiCell(
        $w = 0,
        $h = 7,
        $txt = $krutiDevContent,
        $border = 0,
        $align = 'L',
        $fill = false,
        $ln = 1,
        $x = '',
        $y = '',
        $reseth = true,
        $stretch = 0,
        $ishtml = false,
        $autopadding = true,
        $maxh = 0
    );

    // Output final PDF to file
    $pdf->Output($outputPdf, 'F');

    $fileSize = filesize($outputPdf);
    echo "Success! Output PDF generated: {$outputPdf} ({$fileSize} bytes)\n";
    echo "You can now open and inspect 'output_krutidev.pdf'.\n";
    echo "=========================================================\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "PDF Generation Error: " . $e->getMessage() . "\n");
    exit(1);
}
