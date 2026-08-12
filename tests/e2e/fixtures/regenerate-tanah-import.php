<?php
// Dipanggil dari reset-db.sh: menyalin template xlsx import tanah dan
// mengganti nomor persil agar unik per run (mencegah error duplikat saat suite dijalankan ulang).
require '/var/www/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$src = '/tmp/tanah-import-template.xlsx';
$dst = '/tmp/tanah-import.xlsx';
$suffix = (int) getenv('E2E_RAND_SUFFIX');

$sp = IOFactory::load($src);
$sh = $sp->getActiveSheet();
$sh->setCellValue('F7', 9 * 100000 + $suffix);
$sh->setCellValue('F8', 8 * 100000 + $suffix);
$sh->setCellValue('F9', 7 * 100000 + $suffix);
(new Xlsx($sp))->save($dst);
echo "OK: " . $dst . "\n";