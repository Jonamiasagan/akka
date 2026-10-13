<?php

declare(strict_types=1);

/**
 * Step 1: Extract Unicode Hindi text from input_hindi.pdf and convert to Kruti Dev 010 text.
 *
 * Requirements:
 * - smalot/pdfparser
 * - UnicodeToKrutiDev.php
 *
 * Output: output_krutidev.txt
 */

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    fwrite(STDERR, "Error: Composer dependencies not found. Please run 'composer install' first.\n");
    exit(1);
}
require_once $autoloadPath;

$converterPath = __DIR__ . '/UnicodeToKrutiDev.php';
if (!file_exists($converterPath)) {
    fwrite(STDERR, "Error: UnicodeToKrutiDev.php not found at: {$converterPath}\n");
    exit(1);
}
require_once $converterPath;

// Determine input and output paths
$inputFile = $argv[1] ?? (__DIR__ . '/input_hindi.pdf');
$outputFile = __DIR__ . '/output_krutidev.txt';

if (!file_exists($inputFile)) {
    fwrite(STDERR, "Error: Input PDF file not found: {$inputFile}\n");
    fwrite(STDERR, "Usage: php 1_extract_and_convert.php [path/to/input_hindi.pdf]\n");
    exit(1);
}

echo "=========================================================\n";
echo " Step 1: Extract & Convert Hindi PDF -> Kruti Dev 010 TXT\n";
echo "=========================================================\n";
echo "Parsing input PDF: {$inputFile} ...\n";

try {
    $parser = new \Smalot\PdfParser\Parser();
    $pdf = $parser->parseFile($inputFile);
    $unicodeText = $pdf->getText();

    $charCount = mb_strlen($unicodeText, 'UTF-8');
    echo "Extracted {$charCount} Unicode characters from PDF.\n";

    echo "Converting Unicode Devanagari to Kruti Dev 010 ...\n";
    $krutiDevText = UnicodeToKrutiDev::convert($unicodeText);

    // Save only to the plain text file
    $bytesWritten = file_put_contents($outputFile, $krutiDevText);
    if ($bytesWritten === false) {
        throw new \RuntimeException("Failed to write output file: {$outputFile}");
    }

    echo "Success! Output saved to: {$outputFile} ({$bytesWritten} bytes)\n";
    echo "You can now inspect 'output_krutidev.txt' independently.\n";
    echo "=========================================================\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "Extraction / Conversion Error: " . $e->getMessage() . "\n");
    exit(1);
}
