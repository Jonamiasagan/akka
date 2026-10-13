<?php

declare(strict_types=1);

/**
 * Hindi Unicode to Kruti Dev 010 Web Application
 * Handles file upload, PDF extraction, conversion, preview, and download.
 */

// Route static TTF font request if served by PHP built-in server
if (isset($_SERVER['REQUEST_URI']) && str_ends_with(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), 'Krutidev010.ttf')) {
    $fontPath = __DIR__ . '/Krutidev010.ttf';
    if (file_exists($fontPath)) {
        header('Content-Type: font/ttf');
        header('Content-Length: ' . filesize($fontPath));
        readfile($fontPath);
        exit;
    }
}

// Handle file downloads
if (isset($_GET['download'])) {
    $type = $_GET['download'];
    if ($type === 'txt') {
        $file = __DIR__ . '/output_krutidev.txt';
        if (file_exists($file)) {
            header('Content-Type: text/plain; charset=utf-8');
            header('Content-Disposition: attachment; filename="output_krutidev.txt"');
            header('Content-Length: ' . filesize($file));
            readfile($file);
            exit;
        }
    } elseif ($type === 'pdf') {
        $file = __DIR__ . '/output_krutidev.pdf';
        if (file_exists($file)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="output_krutidev.pdf"');
            header('Content-Length: ' . filesize($file));
            readfile($file);
            exit;
        }
    }
    http_response_code(404);
    echo "File not found.";
    exit;
}

// Handle AJAX Upload & Conversion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_GET['action']) && $_GET['action'] === 'convert')) {
    header('Content-Type: application/json; charset=utf-8');

    try {
        if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
            $errorCode = $_FILES['pdf_file']['error'] ?? UPLOAD_ERR_NO_FILE;
            throw new RuntimeException("File upload failed with error code: {$errorCode}");
        }

        $uploadedTmp = $_FILES['pdf_file']['tmp_name'];
        $uploadedName = $_FILES['pdf_file']['name'];

        // Validate PDF extension/type
        if (strtolower(pathinfo($uploadedName, PATHINFO_EXTENSION)) !== 'pdf') {
            throw new RuntimeException("Uploaded file must be a PDF document (.pdf).");
        }

        require_once __DIR__ . '/vendor/autoload.php';
        require_once __DIR__ . '/UnicodeToKrutiDev.php';

        // Step 1: Extract Unicode Text
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($uploadedTmp);
        $unicodeText = $pdf->getText();

        if (trim($unicodeText) === '') {
            throw new RuntimeException("No text could be extracted from the uploaded PDF. Ensure the PDF contains selectable text and is not a scanned image.");
        }

        // Step 2: Convert to Kruti Dev 010
        $krutiDevText = UnicodeToKrutiDev::convert($unicodeText);

        // Save output text file
        $txtOutputPath = __DIR__ . '/output_krutidev.txt';
        file_put_contents($txtOutputPath, $krutiDevText);

        // Step 3: Generate Kruti Dev PDF
        $fontFile = __DIR__ . '/Krutidev010.ttf';
        if (!file_exists($fontFile)) {
            throw new RuntimeException("Krutidev010.ttf font file not found in the project root.");
        }

        $pdfOutput = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdfOutput->SetCreator('Kruti Dev Converter');
        $pdfOutput->SetTitle('Converted Kruti Dev 010 Document');
        $pdfOutput->setPrintHeader(false);
        $pdfOutput->setPrintFooter(false);
        $pdfOutput->SetMargins(15, 15, 15);
        $pdfOutput->SetAutoPageBreak(true, 15);

        $fontName = \TCPDF_FONTS::addTTFfont($fontFile, 'TrueTypeUnicode', '', 96);
        $pdfOutput->AddPage();
        $pdfOutput->SetFont($fontName, '', 14);
        $pdfOutput->MultiCell(0, 7, $krutiDevText, 0, 'L');

        $pdfOutputPath = __DIR__ . '/output_krutidev.pdf';
        $pdfOutput->Output($pdfOutputPath, 'F');

        $pagesCount = count($pdf->getPages());

        echo json_encode([
            'success' => true,
            'message' => 'Conversion completed successfully!',
            'fileName' => $uploadedName,
            'unicodeText' => $unicodeText,
            'krutiDevText' => $krutiDevText,
            'stats' => [
                'pages' => $pagesCount,
                'unicodeChars' => mb_strlen($unicodeText, 'UTF-8'),
                'krutiDevChars' => strlen($krutiDevText),
                'pdfSize' => filesize($pdfOutputPath),
                'txtSize' => filesize($txtOutputPath),
            ],
            'downloadTxtUrl' => 'index.php?download=txt&t=' . time(),
            'downloadPdfUrl' => 'index.php?download=pdf&t=' . time(),
        ]);
        exit;
    } catch (\Throwable $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
        ]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="hi-IN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hindi Unicode to Kruti Dev 010 PDF Converter</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Noto+Sans+Devanagari:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    
    <style>
        @font-face {
            font-family: 'KrutiDev010';
            src: url('Krutidev010.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        :root {
            --bg-base: #0a0d14;
            --bg-surface: #121722;
            --bg-card: rgba(23, 29, 43, 0.75);
            --bg-card-hover: rgba(30, 38, 56, 0.9);
            --border-subtle: rgba(255, 255, 255, 0.08);
            --border-focus: rgba(99, 102, 241, 0.5);
            
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.25);
            --primary-light: #818cf8;
            --accent: #06b6d4;
            --accent-glow: rgba(6, 182, 212, 0.2);
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;

            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --text-dim: #64748b;

            --radius-sm: 8px;
            --radius-md: 14px;
            --radius-lg: 20px;
            --radius-full: 9999px;
            
            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-base);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 15% 15%, rgba(99, 102, 241, 0.12) 0%, transparent 40%),
                radial-gradient(circle at 85% 25%, rgba(6, 182, 212, 0.1) 0%, transparent 45%),
                radial-gradient(circle at 50% 85%, rgba(139, 92, 246, 0.08) 0%, transparent 50%);
            background-attachment: fixed;
        }

        header {
            padding: 2.5rem 1.5rem 1.5rem;
            text-align: center;
        }

        .header-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.9rem;
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: var(--radius-full);
            color: var(--primary-light);
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-bottom: 1rem;
            box-shadow: 0 0 20px var(--primary-glow);
        }

        .header-badge .dot {
            width: 6px;
            height: 6px;
            background: var(--accent);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.85); }
        }

        h1 {
            font-size: 2.5rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, #ffffff 30%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.6rem;
        }

        .subtitle {
            color: var(--text-muted);
            font-size: 1.05rem;
            max-width: 650px;
            margin: 0 auto;
            line-height: 1.6;
        }

        main {
            flex: 1;
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        /* Glassmorphism Card */
        .glass-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-lg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
        }

        /* Upload Zone */
        .upload-area {
            border: 2px dashed rgba(99, 102, 241, 0.35);
            border-radius: var(--radius-md);
            background: rgba(18, 23, 34, 0.5);
            padding: 3rem 2rem;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
        }

        .upload-area:hover, .upload-area.dragover {
            border-color: var(--primary-light);
            background: rgba(99, 102, 241, 0.06);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px var(--primary-glow);
        }

        .upload-icon {
            width: 68px;
            height: 68px;
            margin: 0 auto 1.25rem;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(6, 182, 212, 0.2));
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-light);
            font-size: 2rem;
            transition: var(--transition);
        }

        .upload-area:hover .upload-icon {
            transform: scale(1.08);
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.35), rgba(6, 182, 212, 0.35));
        }

        .upload-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 0.4rem;
        }

        .upload-desc {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 1.25rem;
        }

        .upload-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, var(--primary), #4f46e5);
            color: #ffffff;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 0.75rem 1.6rem;
            border-radius: var(--radius-full);
            border: none;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
        }

        .upload-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.6);
        }

        #fileInput {
            display: none;
        }

        /* File Selected State */
        .file-info-bar {
            display: none;
            margin-top: 1.5rem;
            padding: 1rem 1.25rem;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            align-items: center;
            justify-content: space-between;
        }

        .file-info-left {
            display: flex;
            align-items: center;
            gap: 0.9rem;
        }

        .file-icon {
            width: 40px;
            height: 40px;
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: var(--radius-sm);
            color: #f87171;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: 700;
        }

        .file-name {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-main);
        }

        .file-size {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .convert-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, #10b981, #059669);
            color: #ffffff;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 0.75rem 1.75rem;
            border-radius: var(--radius-full);
            border: none;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.35);
        }

        .convert-action-btn:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.5);
        }

        .convert-action-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            filter: grayscale(0.6);
        }

        /* Progress / Loader */
        .progress-box {
            display: none;
            margin-top: 1.5rem;
            text-align: center;
        }

        .spinner {
            width: 38px;
            height: 38px;
            border: 3px solid rgba(99, 102, 241, 0.2);
            border-top-color: var(--primary-light);
            border-radius: 50%;
            animation: spin 0.9s linear infinite;
            margin: 0 auto 0.75rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .progress-text {
            font-size: 0.95rem;
            color: var(--text-muted);
        }

        /* Results Container */
        .results-section {
            display: none;
            animation: fadeIn 0.4s ease-out forwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .results-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid var(--border-subtle);
        }

        .results-title {
            font-size: 1.4rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .results-title .check {
            width: 26px;
            height: 26px;
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid var(--success);
            border-radius: 50%;
            color: var(--success);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }

        .download-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .btn-download {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.65rem 1.2rem;
            border-radius: var(--radius-sm);
            font-size: 0.88rem;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
        }

        .btn-download-pdf {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: #fff;
            box-shadow: 0 4px 14px rgba(239, 68, 68, 0.35);
        }

        .btn-download-pdf:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(239, 68, 68, 0.5);
        }

        .btn-download-txt {
            background: rgba(255, 255, 255, 0.08);
            color: var(--text-main);
            border: 1px solid var(--border-subtle);
        }

        .btn-download-txt:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.2);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 1.75rem;
        }

        .stat-card {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            padding: 1rem;
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.78rem;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Preview Tabs */
        .tabs-header {
            display: flex;
            gap: 0.5rem;
            background: rgba(15, 23, 42, 0.7);
            padding: 0.35rem;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-subtle);
            margin-bottom: 1.25rem;
        }

        .tab-btn {
            flex: 1;
            padding: 0.65rem 1rem;
            border: none;
            background: transparent;
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 600;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .tab-btn.active {
            background: var(--primary);
            color: #ffffff;
            box-shadow: 0 4px 12px var(--primary-glow);
        }

        .tab-content {
            display: none;
            position: relative;
        }

        .tab-content.active {
            display: block;
        }

        .preview-box {
            width: 100%;
            height: 380px;
            background: rgba(10, 13, 20, 0.85);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            padding: 1.25rem;
            color: var(--text-main);
            overflow-y: auto;
            white-space: pre-wrap;
            word-break: break-word;
            line-height: 1.8;
            font-size: 1.05rem;
        }

        .preview-box.krutidev-styled {
            font-family: 'KrutiDev010', serif;
            font-size: 1.35rem;
            letter-spacing: 0.02em;
        }

        .preview-box.raw-ascii {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.95rem;
            color: #38bdf8;
        }

        .preview-box.unicode-styled {
            font-family: 'Noto Sans Devanagari', sans-serif;
            font-size: 1.15rem;
        }

        .copy-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(30, 41, 59, 0.85);
            border: 1px solid var(--border-subtle);
            color: var(--text-muted);
            padding: 0.4rem 0.8rem;
            border-radius: var(--radius-sm);
            font-size: 0.78rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.35rem;
            backdrop-filter: blur(8px);
        }

        .copy-btn:hover {
            color: #fff;
            background: rgba(51, 65, 85, 0.9);
            border-color: rgba(255, 255, 255, 0.2);
        }

        /* Error alert */
        .error-alert {
            display: none;
            margin-top: 1.5rem;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: var(--radius-md);
            padding: 1rem 1.25rem;
            color: #fca5a5;
            font-size: 0.95rem;
            align-items: center;
            gap: 0.75rem;
        }

        footer {
            text-align: center;
            padding: 2rem 1.5rem;
            font-size: 0.85rem;
            color: var(--text-dim);
            border-top: 1px solid var(--border-subtle);
        }

        @media (max-width: 640px) {
            h1 { font-size: 1.85rem; }
            .glass-card { padding: 1.25rem; }
            .results-header { flex-direction: column; align-items: flex-start; }
            .download-actions { width: 100%; }
            .btn-download { flex: 1; justify-content: center; }
        }
    </style>
</head>
<body>

    <header>
        <div class="header-badge">
            <span class="dot"></span> Unicode Devanagari to Kruti Dev 010
        </div>
        <h1>हिंदी PDF परिवर्तक (Hindi PDF Converter)</h1>
        <p class="subtitle">
            Upload your Hindi Unicode PDF to instantly extract the Devanagari content, convert all conjuncts & matras into Kruti Dev 010, and download both the converted text file and the compiled Kruti Dev PDF.
        </p>
    </header>

    <main>
        <!-- Upload Card -->
        <section class="glass-card">
            <div class="upload-area" id="dropZone">
                <div class="upload-icon">
                    <svg width="34" height="34" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                </div>
                <h3 class="upload-title">Choose a Hindi PDF file or Drag & Drop here</h3>
                <p class="upload-desc">Supports standard Hindi Unicode Devanagari PDF documents (up to 20MB)</p>
                <button type="button" class="upload-btn" id="browseBtn">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Browse PDF
                </button>
                <input type="file" id="fileInput" accept=".pdf,application/pdf">
            </div>

            <!-- File Selected Status Bar -->
            <div class="file-info-bar" id="fileInfoBar">
                <div class="file-info-left">
                    <div class="file-icon">PDF</div>
                    <div>
                        <div class="file-name" id="fileNameLabel">filename.pdf</div>
                        <div class="file-size" id="fileSizeLabel">0 KB</div>
                    </div>
                </div>
                <button type="button" class="convert-action-btn" id="convertBtn">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Convert to Kruti Dev
                </button>
            </div>

            <!-- Processing Spinner -->
            <div class="progress-box" id="progressBox">
                <div class="spinner"></div>
                <div class="progress-text" id="progressText">Extracting Hindi text and mapping Devanagari glyphs...</div>
            </div>

            <!-- Error Banner -->
            <div class="error-alert" id="errorAlert">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span id="errorMessage">An error occurred during conversion.</span>
            </div>
        </section>

        <!-- Results Section -->
        <section class="glass-card results-section" id="resultsSection">
            <div class="results-header">
                <div class="results-title">
                    <div class="check">✓</div>
                    <span>Conversion Complete</span>
                </div>
                <div class="download-actions">
                    <a href="index.php?download=pdf" class="btn-download btn-download-pdf" id="dlPdfBtn">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download Kruti Dev PDF
                    </a>
                    <a href="index.php?download=txt" class="btn-download btn-download-txt" id="dlTxtBtn">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Download output_krutidev.txt
                    </a>
                </div>
            </div>

            <!-- Metrics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value" id="statPages">0</div>
                    <div class="stat-label">Pages Processed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="statUnicodeChars">0</div>
                    <div class="stat-label">Unicode Characters</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="statKrutiChars">0</div>
                    <div class="stat-label">Kruti Dev Keystrokes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="statPdfSize">0 KB</div>
                    <div class="stat-label">Generated PDF Size</div>
                </div>
            </div>

            <!-- Tab Switcher -->
            <div class="tabs-header">
                <button type="button" class="tab-btn active" data-tab="tabKrutiVisual">
                    <span>🔤</span> Kruti Dev 010 Font Rendered
                </button>
                <button type="button" class="tab-btn" data-tab="tabKrutiRaw">
                    <span>💻</span> Kruti Dev Raw ASCII Text
                </button>
                <button type="button" class="tab-btn" data-tab="tabUnicode">
                    <span>📄</span> Extracted Unicode Hindi
                </button>
            </div>

            <!-- Tab 1: Rendered in Kruti Dev Font -->
            <div class="tab-content active" id="tabKrutiVisual">
                <button type="button" class="copy-btn" onclick="copyContent('previewKrutiVisual')">
                    Copy Text
                </button>
                <div class="preview-box krutidev-styled" id="previewKrutiVisual"></div>
            </div>

            <!-- Tab 2: Raw ASCII Text -->
            <div class="tab-content" id="tabKrutiRaw">
                <button type="button" class="copy-btn" onclick="copyContent('previewKrutiRaw')">
                    Copy ASCII
                </button>
                <div class="preview-box raw-ascii" id="previewKrutiRaw"></div>
            </div>

            <!-- Tab 3: Unicode Original -->
            <div class="tab-content" id="tabUnicode">
                <button type="button" class="copy-btn" onclick="copyContent('previewUnicode')">
                    Copy Unicode
                </button>
                <div class="preview-box unicode-styled" id="previewUnicode"></div>
            </div>
        </section>
    </main>

    <footer>
        Hindi PDF to Kruti Dev 010 Engine • PHP 8.x • smalot/pdfparser & tecnickcom/tcpdf
    </footer>

    <script>
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const browseBtn = document.getElementById('browseBtn');
        const fileInfoBar = document.getElementById('fileInfoBar');
        const fileNameLabel = document.getElementById('fileNameLabel');
        const fileSizeLabel = document.getElementById('fileSizeLabel');
        const convertBtn = document.getElementById('convertBtn');
        const progressBox = document.getElementById('progressBox');
        const errorAlert = document.getElementById('errorAlert');
        const errorMessage = document.getElementById('errorMessage');
        const resultsSection = document.getElementById('resultsSection');

        let selectedFile = null;

        // Trigger file browser
        browseBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            fileInput.click();
        });

        dropZone.addEventListener('click', () => {
            fileInput.click();
        });

        fileInput.addEventListener('change', (e) => {
            if (e.target.files && e.target.files[0]) {
                handleFileSelect(e.target.files[0]);
            }
        });

        // Drag and Drop
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.remove('dragover');
            });
        });

        dropZone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files && files[0]) {
                handleFileSelect(files[0]);
            }
        });

        function handleFileSelect(file) {
            hideError();
            if (!file.name.toLowerCase().endsWith('.pdf')) {
                showError('Please select a valid PDF file (.pdf).');
                return;
            }

            selectedFile = file;
            fileNameLabel.textContent = file.name;
            fileSizeLabel.textContent = formatBytes(file.size);
            fileInfoBar.style.display = 'flex';
        }

        function formatBytes(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function showError(msg) {
            errorMessage.textContent = msg;
            errorAlert.style.display = 'flex';
        }

        function hideError() {
            errorAlert.style.display = 'none';
        }

        // Convert Button Click handler
        convertBtn.addEventListener('click', async () => {
            if (!selectedFile) return;

            hideError();
            convertBtn.disabled = true;
            progressBox.style.display = 'block';

            const formData = new FormData();
            formData.append('pdf_file', selectedFile);

            try {
                const response = await fetch('index.php?action=convert', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.error || 'Server error occurred during PDF conversion.');
                }

                // Populate UI Results
                document.getElementById('statPages').textContent = data.stats.pages;
                document.getElementById('statUnicodeChars').textContent = data.stats.unicodeChars.toLocaleString();
                document.getElementById('statKrutiChars').textContent = data.stats.krutiDevChars.toLocaleString();
                document.getElementById('statPdfSize').textContent = formatBytes(data.stats.pdfSize);

                document.getElementById('previewKrutiVisual').textContent = data.krutiDevText;
                document.getElementById('previewKrutiRaw').textContent = data.krutiDevText;
                document.getElementById('previewUnicode').textContent = data.unicodeText;

                document.getElementById('dlPdfBtn').href = data.downloadPdfUrl;
                document.getElementById('dlTxtBtn').href = data.downloadTxtUrl;

                resultsSection.style.display = 'block';
                resultsSection.scrollIntoView({ behavior: 'smooth' });
            } catch (err) {
                showError(err.message || 'An error occurred while uploading/processing the file.');
            } finally {
                convertBtn.disabled = false;
                progressBox.style.display = 'none';
            }
        });

        // Tab Switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

                btn.classList.add('active');
                const targetId = btn.getAttribute('data-tab');
                document.getElementById(targetId).classList.add('active');
            });
        });

        // Copy content helper
        function copyContent(elementId) {
            const text = document.getElementById(elementId).textContent;
            navigator.clipboard.writeText(text).then(() => {
                alert('Copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy text: ', err);
            });
        }
    </script>
</body>
</html>
