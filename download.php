<?php
session_start();

/* CEK LOGIN */
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Akses ditolak.');
}

/* VALIDASI PARAMETER */
if (!isset($_GET['file'])) {
    http_response_code(400);
    exit('File tidak valid.');
}

/* KONFIGURASI */
$baseDir  = realpath(__DIR__ . '/uploads');
$fileName = basename($_GET['file']); // anti ../
$filePath = realpath($baseDir . '/' . $fileName);

/* VALIDASI PATH */
if ($filePath === false || strpos($filePath, $baseDir) !== 0) {
    http_response_code(403);
    exit('Akses file tidak diizinkan.');
}

/* CEK FILE */
if (!file_exists($filePath)) {
    http_response_code(404);
    exit('File tidak ditemukan.');
}

/* ALLOWED MIME TYPE (PREVIEW ONLY) */

$allowedMime = [
    'application/pdf',
    'image/png',
    'image/jpeg',

    // DOCX (Microsoft Word)
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/msword'
];


$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $filePath);
finfo_close($finfo);

if (!in_array($mime, $allowedMime)) {
    http_response_code(403);
    exit('Tipe file tidak dapat dipratinjau.');
}

/* HEADER UNTUK PREVIEW (INLINE) */
header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . $fileName . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

/* TAMPILKAN FILE */
readfile($filePath);
exit;
