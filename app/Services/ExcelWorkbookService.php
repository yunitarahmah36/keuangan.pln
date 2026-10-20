<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use SimpleXMLElement;
use XMLReader;
use ZipArchive;
use RuntimeException;

class ExcelWorkbookService
{
    /**
     * Dapatkan semua nama sheet dan target XML-nya dari workbook .xlsx.
     */
    public function getSheetList(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException("File tidak ditemukan: {$filePath}");
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new RuntimeException("File Excel tidak dapat dibuka.");
        }

        try {
            $wbXml = $zip->getFromName('xl/workbook.xml');
            $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

            if ($wbXml === false) {
                throw new RuntimeException("Struktur xl/workbook.xml tidak ditemukan.");
            }

            $wbDom = new DOMDocument();
            $wbDom->loadXML($wbXml, LIBXML_NONET);
            $wbXp = new DOMXPath($wbDom);

            $relsMap = [];
            if ($relsXml !== false) {
                $relsDom = new DOMDocument();
                $relsDom->loadXML($relsXml, LIBXML_NONET);
                $relsXp = new DOMXPath($relsDom);
                foreach ($relsXp->query('//*[local-name()="Relationship"]') as $rel) {
                    $id = $rel->getAttribute('Id');
                    $target = $rel->getAttribute('Target');
                    $relsMap[$id] = 'xl/' . ltrim($target, '/');
                }
            }

            $sheets = [];
            $sheetNodes = $wbXp->query('//*[local-name()="sheets"]/*[local-name()="sheet"]');
            foreach ($sheetNodes as $node) {
                $name = $node->getAttribute('name');
                $state = $node->getAttribute('state');
                $rId = $node->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
                $target = $relsMap[$rId] ?? null;

                $sheets[] = [
                    'name' => $name,
                    'rId' => $rId,
                    'target' => $target,
                    'hidden' => strtolower((string)$state) === 'hidden',
                ];
            }

            return $sheets;
        } finally {
            $zip->close();
        }
    }

    /**
     * Kategorisasi sheet secara cerdas dan dinamis.
     */
    public function categorizeSheets(array $sheetNames): array
    {
        $categories = [
            'Laporan Utama' => [],
            'Buku Besar' => [],
            'Persediaan dan Material' => [],
            'Penjualan dan Pembelian' => [],
            'Pendapatan dan Transaksi Intercompany' => [],
            'Biaya Operasional dan Beban' => [],
            'Informasi & Pengaturan' => [],
            'Belum Dikategorikan' => [],
        ];

        // Daftar pola kata kunci pencocokan untuk laporan keuangan PLN
        $patterns = [
            'Laporan Utama' => [
                '/^neraca/i', '/^income/i', '/^labarugi/i', '/laba\s*rugi/i',
                '/cashflow/i', '/cash\s*flow/i', '/arus\s*kas/i', '/posisi\s*keuangan/i',
            ],
            'Buku Besar' => [
                '/bukubesar/i', '/buku\s*besar/i', '/general\s*ledger/i', '/^gl/i', '/jurnal/i',
            ],
            'Persediaan dan Material' => [
                '/persd/i', '/persediaan/i', '/material/i', '/harmat/i',
                '/penyisihanmat/i', '/reals-mp/i', '/reals\s*mp/i', '/stok/i', '/atk/i',
            ],
            'Penjualan dan Pembelian' => [
                '/jual.*bl/i', '/jual\s*beli/i', '/pembelian/i', '/penjualan/i',
                '/tarif\s*listrik/i', '/sales/i', '/purchase/i',
            ],
            'Pendapatan dan Transaksi Intercompany' => [
                '/pendop/i', '/pendapatan.*lain/i', '/po\s*lain/i', '/interco/i',
                '/unrealised/i', '/profit\s*interco/i', '/eliminasi/i',
            ],
            'Biaya Operasional dan Beban' => [
                '/biop/i', '/ikhtisar/i', '/bbm/i', '/bpeg/i', '/pegawai/i',
                '/harjabor/i', '/bolain/i', '/non\s*allw/i', '/beluop/i',
                '/pendaluop/i', '/bepinjaman/i', '/beban/i', '/biaya/i',
            ],
            'Informasi & Pengaturan' => [
                '/^menu$/i', '/^periode$/i', '/^cover$/i', '/petunjuk/i',
                '/informasi/i', '/daftar\s*isi/i',
            ],
        ];

        foreach ($sheetNames as $sheet) {
            $sheetClean = trim($sheet);
            $matched = false;

            foreach ($patterns as $category => $regexList) {
                foreach ($regexList as $regex) {
                    if (preg_match($regex, $sheetClean)) {
                        $categories[$category][] = $sheet;
                        $matched = true;
                        break 2;
                    }
                }
            }

            if (!$matched) {
                $categories['Belum Dikategorikan'][] = $sheet;
            }
        }

        // Hapus kategori yang kosong agar antarmuka bersih
        $result = [];
        foreach ($categories as $catName => $items) {
            if (!empty($items)) {
                $result[$catName] = $items;
            }
        }

        // Jika semua sheet belum dikategorikan atau tidak ada kategori lain, pastikan minimal 1 kategori ada
        if (empty($result) && !empty($sheetNames)) {
            $result['Belum Dikategorikan'] = $sheetNames;
        }

        return $result;
    }

    /**
     * Memeriksa dan memvalidasi apakah struktur file Excel (.xlsx) sesuai dengan
     * format laporan keuangan yang didukung (nama sheet, header kolom, dan struktur data).
     */
    public function validateWorkbookStructure(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [
                'valid' => false,
                'message' => 'File Excel tidak ditemukan pada server.',
            ];
        }

        try {
            $sheets = $this->getSheetList($filePath);
        } catch (\Throwable $e) {
            return [
                'valid' => false,
                'message' => 'File Excel tidak dapat dibaca atau format file rusak: ' . $e->getMessage(),
            ];
        }

        if (empty($sheets)) {
            return [
                'valid' => false,
                'message' => 'Struktur file Excel tidak valid: workbook tidak memiliki worksheet.',
            ];
        }

        $sheetNames = array_column($sheets, 'name');

        // 1. Validasi Nama Sheet: Kategorikan sheet berdasarkan aturan sistem
        $categorized = $this->categorizeSheets($sheetNames);

        $hasLaporanUtama = !empty($categorized['Laporan Utama']);
        $hasBukuBesar = !empty($categorized['Buku Besar']);

        // File wajib memiliki minimal sheet Laporan Utama atau Buku Besar
        if (!$hasLaporanUtama && !$hasBukuBesar) {
            $preview = implode(', ', array_slice($sheetNames, 0, 5));
            if (count($sheetNames) > 5) {
                $preview .= ', ...';
            }
            return [
                'valid' => false,
                'message' => "Struktur file tidak sesuai format laporan keuangan yang didukung. File wajib memuat sheet laporan keuangan utama (seperti NERACA, INCOME, LabaRugi, CashFlow, atau Buku Besar). Sheet yang terdeteksi: [{$preview}].",
            ];
        }

        // 2. Validasi Header Kolom dan Struktur Data pada sheet utama
        $sheetsToCheck = [];
        if ($hasLaporanUtama) {
            $sheetsToCheck = array_merge($sheetsToCheck, $categorized['Laporan Utama']);
        }
        if ($hasBukuBesar) {
            $sheetsToCheck = array_merge($sheetsToCheck, $categorized['Buku Besar']);
        }

        $checkedAtLeastOne = false;
        $errors = [];

        foreach ($sheetsToCheck as $sheetName) {
            try {
                $sheetData = $this->readSheetData($filePath, $sheetName, 25);
                $rows = $sheetData['rows'] ?? [];

                if (empty($rows)) {
                    $errors[] = "Sheet '{$sheetName}' kosong tanpa baris data.";
                    continue;
                }

                $allTexts = [];
                $hasNumericData = false;

                foreach ($rows as $r) {
                    foreach ($r['cells'] as $cell) {
                        $val = trim((string)($cell['val'] ?? ''));
                        if ($val !== '') {
                            $allTexts[] = strtolower($val);
                            if (is_numeric(str_replace([',', '.', ' '], '', $val)) || !empty($cell['formula'])) {
                                $hasNumericData = true;
                            }
                        }
                    }
                }

                $combinedText = implode(' ', $allTexts);

                // Validasi Header Kolom & Pos Berdasarkan Tipe Sheet
                if (preg_match('/bukubesar|buku\s*besar|general\s*ledger|^gl/i', $sheetName)) {
                    $hasKodeAkun = preg_match('/kode\s*akun|kode|no\.\s*rek|no\s*akun/i', $combinedText);
                    $hasKeterangan = preg_match('/keterangan|uraian|nama\s*akun/i', $combinedText);

                    if (!$hasKodeAkun || !$hasKeterangan) {
                        $errors[] = "Sheet '{$sheetName}' tidak memuat header kolom standar Buku Besar (Kode Akun dan Keterangan).";
                        continue;
                    }
                } elseif (preg_match('/^neraca|posisi\s*keuangan/i', $sheetName)) {
                    $hasNeracaKeywords = preg_match('/aset|aktiva|kewajiban|liabilitas|ekuitas|modal|laporan\s*posisi\s*keuangan/i', $combinedText);
                    if (!$hasNeracaKeywords) {
                        $errors[] = "Sheet '{$sheetName}' tidak memuat struktur pos Neraca / Posisi Keuangan (Aset/Kewajiban/Ekuitas).";
                        continue;
                    }
                } elseif (preg_match('/^income|labarugi|laba\s*rugi/i', $sheetName)) {
                    $hasIncomeKeywords = preg_match('/pendapatan|beban|biaya|laba|rugi|revenue|operating/i', $combinedText);
                    if (!$hasIncomeKeywords) {
                        $errors[] = "Sheet '{$sheetName}' tidak memuat struktur pos Laba Rugi / Income (Pendapatan/Beban/Laba).";
                        continue;
                    }
                } elseif (preg_match('/cashflow|cash\s*flow|arus\s*kas/i', $sheetName)) {
                    $hasCashFlowKeywords = preg_match('/arus\s*kas|kas|operasi|investasi|pendanaan|cash\s*flow/i', $combinedText);
                    if (!$hasCashFlowKeywords) {
                        $errors[] = "Sheet '{$sheetName}' tidak memuat struktur pos Laporan Arus Kas (Cash Flow).";
                        continue;
                    }
                }

                // Validasi Struktur Data Keuangan (harus ada sel numerik/formula)
                if (!$hasNumericData) {
                    $errors[] = "Sheet '{$sheetName}' tidak memuat data nilai angka atau transaksi laporan keuangan.";
                    continue;
                }

                $checkedAtLeastOne = true;
                break; // Minimal satu sheet laporan utama terverifikasi valid dan berstruktur benar
            } catch (\Throwable $e) {
                $errors[] = "Gagal memverifikasi sheet '{$sheetName}': " . $e->getMessage();
            }
        }

        if (!$checkedAtLeastOne) {
            $detailError = !empty($errors) ? implode(' ', $errors) : 'Struktur header kolom dan data tidak sesuai format laporan keuangan.';
            return [
                'valid' => false,
                'message' => "Validasi struktur file gagal. {$detailError}",
            ];
        }

        return [
            'valid' => true,
            'message' => 'Struktur file sesuai dengan format laporan keuangan yang didukung.',
            'sheets' => $sheetNames,
            'categories' => $categorized,
        ];
    }

    /**
     * Membaca isi suatu sheet secara cepat dari .xlsx.
     */
    public function readSheetData(string $filePath, string $sheetName, int $maxRows = 2500): array
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new RuntimeException("File Excel tidak dapat dibuka.");
        }

        try {
            $sheets = $this->getSheetList($filePath);
            $targetPath = null;
            $targetSheetInfo = null;
            foreach ($sheets as $s) {
                if (strcasecmp($s['name'], $sheetName) === 0) {
                    $targetPath = $s['target'];
                    $targetSheetInfo = $s;
                    break;
                }
            }

            if (!$targetPath || $zip->locateName($targetPath) === false) {
                throw new RuntimeException("Worksheet '{$sheetName}' tidak ditemukan dalam file.");
            }

            // 1. Baca shared strings jika ada
            $sharedStrings = [];
            $ssXml = $zip->getFromName('xl/sharedStrings.xml');
            if ($ssXml !== false) {
                $xmlReader = new XMLReader();
                $xmlReader->XML($ssXml);
                while ($xmlReader->read()) {
                    if ($xmlReader->nodeType == XMLReader::ELEMENT && $xmlReader->localName === 'si') {
                        $node = new SimpleXMLElement($xmlReader->readOuterXml());
                        $text = '';
                        if (isset($node->t)) {
                            $text = (string)$node->t;
                        } else {
                            foreach ($node->r as $r) {
                                $text .= (string)$r->t;
                            }
                        }
                        $sharedStrings[] = $text;
                    }
                }
                $xmlReader->close();
            }

            // 2. Baca styles workbook
            $styles = $this->readWorkbookStyles($zip);

            // 3. Baca sheet XML
            $sheetXml = $zip->getFromName($targetPath);
            $meta = $this->parseSheetMeta($sheetXml);

            // Baca relasi hyperlink worksheet
            $relsPath = dirname($targetPath) . '/_rels/' . basename($targetPath) . '.rels';
            $relsXml = $zip->getFromName($relsPath);
            $hyperlinkRels = [];
            if ($relsXml !== false) {
                if (preg_match_all('/<Relationship\s+([^>]+)\/>/', $relsXml, $hRels)) {
                    foreach ($hRels[1] as $rAttr) {
                        if (strpos($rAttr, 'hyperlink') !== false) {
                            $rId = preg_match('/Id="([^"]+)"/', $rAttr, $m) ? $m[1] : null;
                            $target = preg_match('/Target="([^"]+)"/', $rAttr, $m) ? $m[1] : null;
                            if ($rId && $target) {
                                $hyperlinkRels[$rId] = html_entity_decode($target, ENT_QUOTES | ENT_XML1, 'UTF-8');
                            }
                        }
                    }
                }
            }
            $hyperlinks = [];
            if (preg_match('/<hyperlinks>(.*?)<\/hyperlinks>/s', $sheetXml, $hlM)) {
                if (preg_match_all('/<hyperlink\s+ref="([A-Z0-9]+)"\s+r:id="([^"]+)"/i', $hlM[1], $hlItems, PREG_SET_ORDER)) {
                    foreach ($hlItems as $hli) {
                        if (isset($hyperlinkRels[$hli[2]])) {
                            $hyperlinks[$hli[1]] = $hyperlinkRels[$hli[2]];
                        }
                    }
                }
            }

            $xmlReader = new XMLReader();
            $xmlReader->XML($sheetXml);

            $rows = [];
            $rowHeights = [];
            $hiddenRows = [];
            $maxColIndex = 8; // Default minimal A-H
            $rowCount = 0;

            while ($xmlReader->read()) {
                if ($xmlReader->nodeType == XMLReader::ELEMENT && $xmlReader->localName === 'row') {
                    $rNum = (int)$xmlReader->getAttribute('r');
                    $rHt = (float)$xmlReader->getAttribute('ht');
                    if ($rHt > 0) {
                        $rowHeights[(string)$rNum] = $rHt;
                    }
                    $rHidden = $xmlReader->getAttribute('hidden') === '1' || $xmlReader->getAttribute('hidden') === 'true';
                    if ($rHidden) {
                        $hiddenRows[] = $rNum;
                    }

                    $rowXml = new SimpleXMLElement($xmlReader->readOuterXml());
                    $cells = [];

                    foreach ($rowXml->c as $c) {
                        $ref = (string)$c['r'];
                        $type = (string)$c['t'];
                        $formula = isset($c->f) ? (string)$c->f : null;
                        $rawVal = isset($c->v) ? (string)$c->v : '';
                        $sIdx = isset($c['s']) ? (int)$c['s'] : null;

                        // Tentukan nilai tampilan
                        if ($type === 's' && isset($sharedStrings[(int)$rawVal])) {
                            $displayVal = $sharedStrings[(int)$rawVal];
                        } elseif ($type === 'inlineStr' && isset($c->is->t)) {
                            $displayVal = (string)$c->is->t;
                        } elseif ($type === 'b') {
                            $displayVal = $rawVal === '1' ? 'TRUE' : 'FALSE';
                        } else {
                            $displayVal = $rawVal;
                        }

                        // Ekstrak huruf kolom
                        preg_match('/^([A-Z]+)/', $ref, $matches);
                        $colLetter = $matches[1] ?? 'A';
                        $colIdx = $this->columnLetterToIndex($colLetter);
                        if ($colIdx > $maxColIndex) {
                            $maxColIndex = $colIdx;
                        }

                        $cellStyle = $this->resolveCellStyle($sIdx, $styles);

                        $cells[$ref] = [
                            'ref' => $ref,
                            'col' => $colLetter,
                            'row' => $rNum,
                            'val' => $displayVal,
                            'formula' => $formula ? '=' . $formula : null,
                            'type' => $type,
                            'style' => $cellStyle,
                            'hyperlink' => $hyperlinks[$ref] ?? null,
                        ];
                    }

                    $rows[$rNum] = [
                        'row' => $rNum,
                        'cells' => $cells,
                    ];

                    $rowCount++;
                    if ($rowCount >= $maxRows) {
                        break;
                    }
                }
            }
            $xmlReader->close();

            // Bangun daftar kolom
            $columns = [];
            for ($i = 1; $i <= $maxColIndex; $i++) {
                $columns[] = $this->indexToColumnLetter($i);
            }

            return [
                'sheet' => $sheetName,
                'hidden' => !empty($targetSheetInfo['hidden']),
                'columns' => $columns,
                'rows' => array_values($rows),
                'totalRows' => count($rows),
                'maxColIndex' => $maxColIndex,
                'cols' => $meta['cols'],
                'merges' => $meta['merges'],
                'rowHeights' => $rowHeights,
                'hiddenRows' => $hiddenRows,
                'freeze' => $meta['freeze'],
                'activeCell' => $meta['activeCell'],
                'activeRow' => $meta['activeRow'],
                'activeCol' => $meta['activeCol'],
                'tabColor' => $meta['tabColor'],
                'showGridlines' => $meta['showGridlines'],
                'zoomRatio' => $meta['zoomRatio'],
                'autoFilter' => $meta['autoFilter'],
                'dataValidations' => $meta['dataValidations'],
            ];
        } finally {
            $zip->close();
        }
    }

    /**
     * Simpan sel dan rumus baru ke dalam sheet workbook asli.
     */
    public function saveCellsToWorkbook(string $filePath, string $sheetName, array $cellsToUpdate): array
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException("File tidak ditemukan: {$filePath}");
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new RuntimeException("Gagal membuka file Excel untuk menyimpan.");
        }

        try {
            $sheets = $this->getSheetList($filePath);
            $targetPath = null;
            foreach ($sheets as $s) {
                if (strcasecmp($s['name'], $sheetName) === 0) {
                    $targetPath = $s['target'];
                    break;
                }
            }

            if (!$targetPath || $zip->locateName($targetPath) === false) {
                throw new RuntimeException("Worksheet '{$sheetName}' tidak ditemukan.");
            }

            $sheetXml = $zip->getFromName($targetPath);
            $dom = new DOMDocument();
            $dom->loadXML($sheetXml, LIBXML_NONET);
            $xp = new DOMXPath($dom);

            $sheetDataNodes = $xp->query('//*[local-name()="sheetData"]');
            if ($sheetDataNodes->length === 0) {
                throw new RuntimeException("Struktur sheetData tidak ditemukan.");
            }
            $sheetData = $sheetDataNodes->item(0);

            $updatedCells = [];

            foreach ($cellsToUpdate as $item) {
                $ref = strtoupper(trim($item['ref'] ?? ''));
                if (!$ref || !preg_match('/^([A-Z]+)(\d+)$/', $ref, $matches)) {
                    continue;
                }

                $col = $matches[1];
                $rowNum = (int)$matches[2];
                $value = $item['value'] ?? '';
                $formula = $item['formula'] ?? null;

                // Jika formula dikirimkan dengan '=', bersihkan agar sesuai standar XML Office Open XML (<f>SUM(...)</f>)
                if ($formula && str_starts_with($formula, '=')) {
                    $formula = ltrim($formula, '=');
                }

                // Jika value diawali dengan '=', perlakukan juga sebagai formula
                if (is_string($value) && str_starts_with($value, '=')) {
                    $formula = ltrim($value, '=');
                    $value = $item['calculatedValue'] ?? '0';
                }

                // Cari baris
                $rowNodes = $xp->query('./*[local-name()="row"][@r="' . $rowNum . '"]', $sheetData);
                if ($rowNodes->length > 0) {
                    $rowNode = $rowNodes->item(0);
                } else {
                    $rowNode = $dom->createElement('row');
                    $rowNode->setAttribute('r', (string)$rowNum);
                    $sheetData->appendChild($rowNode);
                }

                // Cari sel
                $cellNodes = $xp->query('./*[local-name()="c"][@r="' . $ref . '"]', $rowNode);
                if ($cellNodes->length > 0) {
                    $cNode = $cellNodes->item(0);
                } else {
                    $cNode = $dom->createElement('c');
                    $cNode->setAttribute('r', $ref);
                    $rowNode->appendChild($cNode);
                }

                // Bersihkan tipe string jika menjadi formula atau angka
                if ($cNode->hasAttribute('t') && ($formula || is_numeric($value))) {
                    $cNode->removeAttribute('t');
                }

                // Update atau buat elemen formula <f>
                $fNodes = $xp->query('./*[local-name()="f"]', $cNode);
                if ($formula) {
                    if ($fNodes->length > 0) {
                        $fNodes->item(0)->nodeValue = $formula;
                    } else {
                        $fElem = $dom->createElement('f', htmlspecialchars($formula));
                        $cNode->insertBefore($fElem, $cNode->firstChild);
                    }
                } else {
                    // Jika formula dihapus
                    foreach ($fNodes as $fNode) {
                        $cNode->removeChild($fNode);
                    }
                }

                // Update atau buat elemen value <v>
                $vNodes = $xp->query('./*[local-name()="v"]', $cNode);
                if ($vNodes->length > 0) {
                    $vNodes->item(0)->nodeValue = (string)$value;
                } else {
                    $vElem = $dom->createElement('v', (string)$value);
                    $cNode->appendChild($vElem);
                }

                $updatedCells[] = [
                    'ref' => $ref,
                    'value' => $value,
                    'formula' => $formula ? '=' . $formula : null,
                ];
            }

            // Simpan kembali XML sheet ke dalam file zip
            $zip->addFromString($targetPath, $dom->saveXML());
            $zip->close();

            return [
                'success' => true,
                'sheet' => $sheetName,
                'updatedCount' => count($updatedCells),
                'updatedCells' => $updatedCells,
            ];
        } catch (\Throwable $e) {
            $zip->close();
            throw $e;
        }
    }

    /**
     * Membaca seluruh sheet di dalam workbook .xlsx dalam 1 sesi pembacaan cepat.
     */
    public function readAllSheetsData(string $filePath, int $maxRows = 2500): array
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException("File tidak ditemukan: {$filePath}");
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new RuntimeException("File Excel tidak dapat dibuka.");
        }

        try {
            $sheetList = $this->getSheetList($filePath);

            // 1. Baca shared strings sekali saja untuk seluruh sheet
            $sharedStrings = [];
            $ssXml = $zip->getFromName('xl/sharedStrings.xml');
            if ($ssXml !== false) {
                $xmlReader = new XMLReader();
                $xmlReader->XML($ssXml);
                while ($xmlReader->read()) {
                    if ($xmlReader->nodeType == XMLReader::ELEMENT && $xmlReader->localName === 'si') {
                        $node = new SimpleXMLElement($xmlReader->readOuterXml());
                        $text = '';
                        if (isset($node->t)) {
                            $text = (string)$node->t;
                        } else {
                            foreach ($node->r as $r) {
                                $text .= (string)$r->t;
                            }
                        }
                        $sharedStrings[] = $text;
                    }
                }
                $xmlReader->close();
            }

            // 2. Baca styles workbook sekali saja
            $styles = $this->readWorkbookStyles($zip);

            $allSheets = [];

            // 3. Baca data dari setiap sheet
            foreach ($sheetList as $sheetInfo) {
                $sName = $sheetInfo['name'];
                $targetPath = $sheetInfo['target'];

                if (!$targetPath || $zip->locateName($targetPath) === false) {
                    continue;
                }

                $sheetXml = $zip->getFromName($targetPath);
                $meta = $this->parseSheetMeta($sheetXml);

                // Baca relasi hyperlink worksheet
                $relsPath = dirname($targetPath) . '/_rels/' . basename($targetPath) . '.rels';
                $relsXml = $zip->getFromName($relsPath);
                $hyperlinkRels = [];
                if ($relsXml !== false) {
                    if (preg_match_all('/<Relationship\s+([^>]+)\/>/', $relsXml, $hRels)) {
                        foreach ($hRels[1] as $rAttr) {
                            if (strpos($rAttr, 'hyperlink') !== false) {
                                $rId = preg_match('/Id="([^"]+)"/', $rAttr, $m) ? $m[1] : null;
                                $target = preg_match('/Target="([^"]+)"/', $rAttr, $m) ? $m[1] : null;
                                if ($rId && $target) {
                                    $hyperlinkRels[$rId] = html_entity_decode($target, ENT_QUOTES | ENT_XML1, 'UTF-8');
                                }
                            }
                        }
                    }
                }
                $hyperlinks = [];
                if (preg_match('/<hyperlinks>(.*?)<\/hyperlinks>/s', $sheetXml, $hlM)) {
                    if (preg_match_all('/<hyperlink\s+ref="([A-Z0-9]+)"\s+r:id="([^"]+)"/i', $hlM[1], $hlItems, PREG_SET_ORDER)) {
                        foreach ($hlItems as $hli) {
                            if (isset($hyperlinkRels[$hli[2]])) {
                                $hyperlinks[$hli[1]] = $hyperlinkRels[$hli[2]];
                            }
                        }
                    }
                }

                $xmlReader = new XMLReader();
                $xmlReader->XML($sheetXml);

                $rows = [];
                $rowHeights = [];
                $hiddenRows = [];
                $maxColIndex = 8;
                $rowCount = 0;

                while ($xmlReader->read()) {
                    if ($xmlReader->nodeType == XMLReader::ELEMENT && $xmlReader->localName === 'row') {
                        $rNum = (int)$xmlReader->getAttribute('r');
                        $rHt = (float)$xmlReader->getAttribute('ht');
                        if ($rHt > 0) {
                            $rowHeights[(string)$rNum] = $rHt;
                        }
                        $rHidden = $xmlReader->getAttribute('hidden') === '1' || $xmlReader->getAttribute('hidden') === 'true';
                        if ($rHidden) {
                            $hiddenRows[] = $rNum;
                        }

                        $rowXml = new SimpleXMLElement($xmlReader->readOuterXml());
                        $cells = [];

                        foreach ($rowXml->c as $c) {
                            $ref = (string)$c['r'];
                            $type = (string)$c['t'];
                            $formula = isset($c->f) ? (string)$c->f : null;
                            $rawVal = isset($c->v) ? (string)$c->v : '';
                            $sIdx = isset($c['s']) ? (int)$c['s'] : null;

                            if ($type === 's' && isset($sharedStrings[(int)$rawVal])) {
                                $displayVal = $sharedStrings[(int)$rawVal];
                            } elseif ($type === 'inlineStr' && isset($c->is->t)) {
                                $displayVal = (string)$c->is->t;
                            } elseif ($type === 'b') {
                                $displayVal = $rawVal === '1' ? 'TRUE' : 'FALSE';
                            } else {
                                $displayVal = $rawVal;
                            }

                            preg_match('/^([A-Z]+)/', $ref, $matches);
                            $colLetter = $matches[1] ?? 'A';
                            $colIdx = $this->columnLetterToIndex($colLetter);
                            if ($colIdx > $maxColIndex) {
                                $maxColIndex = $colIdx;
                            }

                            $cellStyle = $this->resolveCellStyle($sIdx, $styles);

                            $cells[$ref] = [
                                'ref' => $ref,
                                'col' => $colLetter,
                                'row' => $rNum,
                                'val' => $displayVal,
                                'formula' => $formula ? '=' . $formula : null,
                                'type' => $type,
                                'style' => $cellStyle,
                                'hyperlink' => $hyperlinks[$ref] ?? null,
                            ];
                        }

                        $rows[$rNum] = [
                            'row' => $rNum,
                            'cells' => $cells,
                        ];

                        $rowCount++;
                        if ($rowCount >= $maxRows) {
                            break;
                        }
                    }
                }
                $xmlReader->close();

                $columns = [];
                for ($i = 1; $i <= $maxColIndex; $i++) {
                    $columns[] = $this->indexToColumnLetter($i);
                }

                $allSheets[] = [
                    'sheet' => $sName,
                    'hidden' => !empty($sheetInfo['hidden']),
                    'columns' => $columns,
                    'rows' => array_values($rows),
                    'totalRows' => count($rows),
                    'maxColIndex' => $maxColIndex,
                    'cols' => $meta['cols'],
                    'merges' => $meta['merges'],
                    'rowHeights' => $rowHeights,
                    'hiddenRows' => $hiddenRows,
                    'freeze' => $meta['freeze'],
                    'activeCell' => $meta['activeCell'],
                    'activeRow' => $meta['activeRow'],
                    'activeCol' => $meta['activeCol'],
                    'tabColor' => $meta['tabColor'],
                    'showGridlines' => $meta['showGridlines'],
                    'zoomRatio' => $meta['zoomRatio'],
                    'autoFilter' => $meta['autoFilter'],
                    'dataValidations' => $meta['dataValidations'],
                ];
            }

            return [
                'fileName' => basename($filePath),
                'sheets' => $allSheets,
                'totalSheets' => count($allSheets),
            ];
        } finally {
            $zip->close();
        }
    }

    /**
     * Simpan sel dan rumus baru ke dalam multiple sheet workbook sekaligus.
     */
    public function saveMultiSheetCellsToWorkbook(string $filePath, array $sheetsToUpdate): array
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException("File tidak ditemukan: {$filePath}");
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new RuntimeException("Gagal membuka file Excel untuk menyimpan.");
        }

        try {
            $sheetsList = $this->getSheetList($filePath);
            $targetMap = [];
            foreach ($sheetsList as $s) {
                $targetMap[strtolower($s['name'])] = $s['target'];
            }

            $totalUpdatedCount = 0;
            $results = [];

            foreach ($sheetsToUpdate as $sheetUpdate) {
                $sName = $sheetUpdate['sheet'] ?? '';
                $cellsToUpdate = $sheetUpdate['cells'] ?? [];

                if (!$sName || empty($cellsToUpdate)) {
                    continue;
                }

                $lowerName = strtolower($sName);
                $targetPath = $targetMap[$lowerName] ?? null;

                if (!$targetPath || $zip->locateName($targetPath) === false) {
                    continue;
                }

                $sheetXml = $zip->getFromName($targetPath);
                $dom = new DOMDocument();
                $dom->loadXML($sheetXml, LIBXML_NONET);
                $xp = new DOMXPath($dom);

                $sheetDataNodes = $xp->query('//*[local-name()="sheetData"]');
                if ($sheetDataNodes->length === 0) {
                    continue;
                }
                $sheetData = $sheetDataNodes->item(0);

                $updatedCells = [];

                foreach ($cellsToUpdate as $item) {
                    $ref = strtoupper(trim($item['ref'] ?? ''));
                    if (!$ref || !preg_match('/^([A-Z]+)(\d+)$/', $ref, $matches)) {
                        continue;
                    }

                    $rowNum = (int)$matches[2];
                    $value = $item['value'] ?? '';
                    $formula = $item['formula'] ?? null;

                    if ($formula && str_starts_with($formula, '=')) {
                        $formula = ltrim($formula, '=');
                    }

                    if (is_string($value) && str_starts_with($value, '=')) {
                        $formula = ltrim($value, '=');
                        $value = $item['calculatedValue'] ?? '0';
                    }

                    $rowNodes = $xp->query('./*[local-name()="row"][@r="' . $rowNum . '"]', $sheetData);
                    if ($rowNodes->length > 0) {
                        $rowNode = $rowNodes->item(0);
                    } else {
                        $rowNode = $dom->createElement('row');
                        $rowNode->setAttribute('r', (string)$rowNum);
                        $sheetData->appendChild($rowNode);
                    }

                    $cellNodes = $xp->query('./*[local-name()="c"][@r="' . $ref . '"]', $rowNode);
                    if ($cellNodes->length > 0) {
                        $cNode = $cellNodes->item(0);
                    } else {
                        $cNode = $dom->createElement('c');
                        $cNode->setAttribute('r', $ref);
                        $rowNode->appendChild($cNode);
                    }

                    if ($cNode->hasAttribute('t') && ($formula || is_numeric($value))) {
                        $cNode->removeAttribute('t');
                    }

                    $fNodes = $xp->query('./*[local-name()="f"]', $cNode);
                    if ($formula) {
                        if ($fNodes->length > 0) {
                            $fNodes->item(0)->nodeValue = $formula;
                        } else {
                            $fElem = $dom->createElement('f', htmlspecialchars($formula));
                            $cNode->insertBefore($fElem, $cNode->firstChild);
                        }
                    } else {
                        foreach ($fNodes as $fNode) {
                            $cNode->removeChild($fNode);
                        }
                    }

                    $vNodes = $xp->query('./*[local-name()="v"]', $cNode);
                    if ($vNodes->length > 0) {
                        $vNodes->item(0)->nodeValue = (string)$value;
                    } else {
                        $vElem = $dom->createElement('v', (string)$value);
                        $cNode->appendChild($vElem);
                    }

                    $updatedCells[] = [
                        'ref' => $ref,
                        'value' => $value,
                        'formula' => $formula ? '=' . $formula : null,
                    ];
                }

                $zip->addFromString($targetPath, $dom->saveXML());
                $totalUpdatedCount += count($updatedCells);
                $results[] = [
                    'sheet' => $sName,
                    'updatedCount' => count($updatedCells),
                    'updatedCells' => $updatedCells,
                ];
            }

            $zip->close();

            return [
                'success' => true,
                'totalUpdated' => $totalUpdatedCount,
                'sheets' => $results,
            ];
        } catch (\Throwable $e) {
            $zip->close();
            throw $e;
        }
    }

    /**
     * Membaca styles (font, fill, border, numFmts, alignment) dari xl/styles.xml secara cepat dengan regex parser.
     */
    protected function readWorkbookStyles(ZipArchive $zip): array
    {
        $stylesXml = $zip->getFromName('xl/styles.xml');
        if (!$stylesXml) {
            return [
                'numFmts' => [],
                'fonts' => [],
                'fills' => [],
                'borders' => [],
                'cellXfs' => [],
            ];
        }

        // 1. Built-in Excel Number Formats
        $numFmts = [
            0 => 'General',
            1 => '0',
            2 => '0.00',
            3 => '#,##0',
            4 => '#,##0.00',
            9 => '0%',
            10 => '0.00%',
            11 => '0.00E+00',
            12 => '# ?/?',
            13 => '# ??/??',
            14 => 'yyyy-mm-dd',
            15 => 'd-mmm-yy',
            16 => 'd-mmm',
            17 => 'mmm-yy',
            18 => 'h:mm AM/PM',
            19 => 'h:mm:ss AM/PM',
            20 => 'h:mm',
            21 => 'h:mm:ss',
            22 => 'm/d/yy h:mm',
            37 => '#,##0 ;(#,##0)',
            38 => '#,##0 ;[Red](#,##0)',
            39 => '#,##0.00;(#,##0.00)',
            40 => '#,##0.00;[Red](#,##0.00)',
            41 => '_(* #,##0_);_(* (#,##0);_(* "-"_);_(@_)',
            42 => '_($* #,##0_);_($* (#,##0);_($* "-"_);_(@_)',
            43 => '_(* #,##0.00_);_(* (#,##0.00);_(* "-"??_);_(@_)',
            44 => '_($* #,##0.00_);_($* (#,##0.00);_($* "-"??_);_(@_)',
            45 => 'mm:ss',
            46 => '[h]:mm:ss',
            47 => 'mmss.0',
            48 => '##0.0E+0',
            49 => '@',
        ];

        // Custom numFmts dari styles.xml
        if (preg_match('/<numFmts[^>]*>(.*?)<\/numFmts>/s', $stylesXml, $nfSection)) {
            if (preg_match_all('/<numFmt\s+numFmtId="(\d+)"\s+formatCode="([^"]+)"/i', $nfSection[1], $nfMatches, PREG_SET_ORDER)) {
                foreach ($nfMatches as $m) {
                    $numFmts[(int)$m[1]] = html_entity_decode($m[2], ENT_QUOTES | ENT_XML1, 'UTF-8');
                }
            }
        }

        // Standard Theme Colors Palette (Office default theme)
        $themePalette = [
            0 => '#ffffff', 1 => '#000000', 2 => '#e7e6e6', 3 => '#44546a',
            4 => '#4472c4', 5 => '#ed7d31', 6 => '#a5a5a5', 7 => '#ffc000',
            8 => '#5b9bd5', 9 => '#70ad47'
        ];

        // 2. Parse Fonts
        $fonts = [];
        if (preg_match('/<fonts[^>]*>(.*?)<\/fonts>/s', $stylesXml, $fontSection)) {
            if (preg_match_all('/<font(?:\s+[^>]*)?>(.*?)<\/font>/s', $fontSection[1], $fMatches)) {
                $fIdx = 0;
                foreach ($fMatches[1] as $fContent) {
                    $fontName = null;
                    if (preg_match('/<name\s+val="([^"]+)"/i', $fContent, $m)) {
                        $fontName = $m[1];
                    }
                    $fontSize = null;
                    if (preg_match('/<sz\s+val="([0-9.]+)"/i', $fContent, $m)) {
                        $fontSize = (float)$m[1];
                    }
                    $color = null;
                    if (preg_match('/<color\s+[^>]*rgb="([A-Fa-f0-9]{6,8})"/i', $fContent, $m)) {
                        $color = '#' . substr($m[1], -6);
                    } elseif (preg_match('/<color\s+[^>]*theme="(\d+)"/i', $fContent, $m)) {
                        $color = $themePalette[(int)$m[1]] ?? null;
                    }

                    $fonts[$fIdx++] = [
                        'name' => $fontName,
                        'size' => $fontSize,
                        'bold' => (bool)preg_match('/<b(?:\s|\/|>)/i', $fContent),
                        'italic' => (bool)preg_match('/<i(?:\s|\/|>)/i', $fContent),
                        'underline' => (bool)preg_match('/<u(?:\s|\/|>)/i', $fContent),
                        'strike' => (bool)preg_match('/<strike(?:\s|\/|>)/i', $fContent),
                        'color' => $color,
                    ];
                }
            }
        }

        // 3. Parse Fills
        $fills = [];
        if (preg_match('/<fills[^>]*>(.*?)<\/fills>/s', $stylesXml, $fillSection)) {
            if (preg_match_all('/<fill(?:\s+[^>]*)?>(.*?)<\/fill>/s', $fillSection[1], $flMatches)) {
                $fillIdx = 0;
                foreach ($flMatches[1] as $flContent) {
                    $rgb = null;
                    if (!preg_match('/patternType="none"/i', $flContent)) {
                        if (preg_match('/<fgColor\s+[^>]*rgb="([A-Fa-f0-9]{6,8})"/i', $flContent, $m)) {
                            $rgb = '#' . substr($m[1], -6);
                        } elseif (preg_match('/<fgColor\s+[^>]*theme="(\d+)"/i', $flContent, $m)) {
                            $rgb = $themePalette[(int)$m[1]] ?? null;
                        }
                    }
                    $fills[$fillIdx++] = [
                        'rgb' => $rgb,
                    ];
                }
            }
        }

        // 4. Parse Borders (Map ke Univer BorderStyleTypes: 1=thin, 2=hair, 3=dotted, 4=dashed, 7=double, 8=medium, 13=thick)
        $borderStyleMap = [
            'none' => 0,
            'thin' => 1,
            'hair' => 2,
            'dotted' => 3,
            'dashed' => 4,
            'dashdot' => 5,
            'dashdotdot' => 6,
            'double' => 7,
            'medium' => 8,
            'mediumdashed' => 9,
            'mediumdashdot' => 10,
            'mediumdashdotdot' => 11,
            'slantdashdot' => 12,
            'thick' => 13,
        ];

        $borders = [];
        if (preg_match('/<borders[^>]*>(.*?)<\/borders>/s', $stylesXml, $borderSection)) {
            if (preg_match_all('/<border(?:\s+[^>]*)?>(.*?)<\/border>/s', $borderSection[1], $bMatches)) {
                $bIdx = 0;
                $edges = [
                    'left' => 'l',
                    'right' => 'r',
                    'top' => 't',
                    'bottom' => 'b',
                ];

                foreach ($bMatches[1] as $bContent) {
                    $borderData = [];
                    foreach ($edges as $edge => $uKey) {
                        if (preg_match('/<' . $edge . '(?:\s+style="([^"]+)")?[^>]*>(.*?)<\/' . $edge . '>/s', $bContent, $em)) {
                            $styleName = strtolower($em[1] ?? '');
                            if ($styleName && isset($borderStyleMap[$styleName]) && $borderStyleMap[$styleName] > 0) {
                                $inner = $em[2];
                                $bColor = '#000000';
                                if (preg_match('/<color\s+[^>]*rgb="([A-Fa-f0-9]{6,8})"/i', $inner, $cm)) {
                                    $bColor = '#' . substr($cm[1], -6);
                                } elseif (preg_match('/<color\s+[^>]*theme="(\d+)"/i', $inner, $cm)) {
                                    $bColor = $themePalette[(int)$cm[1]] ?? '#000000';
                                }
                                $borderData[$uKey] = [
                                    's' => $borderStyleMap[$styleName],
                                    'cl' => ['rgb' => $bColor],
                                ];
                            }
                        }
                    }
                    $borders[$bIdx++] = $borderData;
                }
            }
        }

        // 5. Parse CellXfs
        $cellXfs = [];
        if (preg_match('/<cellXfs[^>]*>(.*?)<\/cellXfs>/s', $stylesXml, $xfSection)) {
            if (preg_match_all('/<xf\s+([^>]+?)(?:\s*\/>|>(.*?)<\/xf>)/s', $xfSection[1], $xfMatches, PREG_SET_ORDER)) {
                $xfIdx = 0;
                foreach ($xfMatches as $xf) {
                    $attrs = $xf[1];
                    $inner = $xf[2] ?? '';
                    $all = $attrs . ' ' . $inner;

                    $numFmtId = preg_match('/numFmtId="(\d+)"/i', $attrs, $m) ? (int)$m[1] : null;
                    $fontId = preg_match('/fontId="(\d+)"/i', $attrs, $m) ? (int)$m[1] : null;
                    $fillId = preg_match('/fillId="(\d+)"/i', $attrs, $m) ? (int)$m[1] : null;
                    $borderId = preg_match('/borderId="(\d+)"/i', $attrs, $m) ? (int)$m[1] : null;

                    $align = null;
                    if (strpos($all, '<alignment') !== false || strpos($attrs, 'horizontal=') !== false) {
                        $align = [];
                        if (preg_match('/horizontal="([^"]+)"/i', $all, $m)) {
                            $align['h'] = strtolower($m[1]);
                        }
                        if (preg_match('/vertical="([^"]+)"/i', $all, $m)) {
                            $align['v'] = strtolower($m[1]);
                        }
                        if (preg_match('/wrapText="1"/i', $all)) {
                            $align['wrap'] = true;
                        }
                    }

                    $cellXfs[$xfIdx++] = [
                        'numFmtId' => $numFmtId,
                        'fontId' => $fontId,
                        'fillId' => $fillId,
                        'borderId' => $borderId,
                        'align' => $align,
                    ];
                }
            }
        }

        return [
            'numFmts' => $numFmts,
            'fonts' => $fonts,
            'fills' => $fills,
            'borders' => $borders,
            'cellXfs' => $cellXfs,
        ];
    }

    /**
     * Membaca metadata sheet (kolom, merge, freeze panes, tab color, gridlines, zoom, filter, data validations).
     */
    protected function parseSheetMeta(string $sheetXml, int $maxCols = 150): array
    {
        $cols = [];
        if (preg_match('/<cols>(.*?)<\/cols>/s', $sheetXml, $colMatches)) {
            preg_match_all('/<col\s+([^>]+)\/>/', $colMatches[1], $colItems);
            foreach ($colItems[1] as $attrs) {
                $min = 1; $max = 1; $w = 10;
                if (preg_match('/min="(\d+)"/', $attrs, $m)) $min = (int)$m[1];
                if (preg_match('/max="(\d+)"/', $attrs, $m)) $max = (int)$m[1];
                if (preg_match('/width="([0-9.]+)"/', $attrs, $m)) $w = (float)$m[1];
                $hidden = (bool)preg_match('/hidden="1"|hidden="true"/i', $attrs);
                for ($c = $min; $c <= min($max, $maxCols); $c++) {
                    $cols[(string)$c] = [
                        'w' => $w,
                        'hidden' => $hidden,
                    ];
                }
            }
        }

        $merges = [];
        if (preg_match('/<mergeCells[^>]*>(.*?)<\/mergeCells>/s', $sheetXml, $mMatches)) {
            preg_match_all('/<mergeCell\s+ref="([A-Z0-9:]+)"/', $mMatches[1], $mItems);
            $merges = $mItems[1];
        }

        // Freeze Panes
        $freeze = null;
        if (preg_match('/<pane\s+([^>]+)\/>/', $sheetXml, $pMatches)) {
            $pAttrs = $pMatches[1];
            $state = preg_match('/state="([^"]+)"/', $pAttrs, $m) ? $m[1] : '';
            if ($state === 'frozen' || $state === 'frozenSplit' || preg_match('/xSplit|ySplit/', $pAttrs)) {
                $xSplit = preg_match('/xSplit="(\d+)"/', $pAttrs, $m) ? (int)$m[1] : 0;
                $ySplit = preg_match('/ySplit="(\d+)"/', $pAttrs, $m) ? (int)$m[1] : 0;
                $topLeftCell = preg_match('/topLeftCell="([A-Z0-9]+)"/', $pAttrs, $m) ? $m[1] : null;

                // Batas pemisah area beku di Univer WAJIB sama dengan ySplit dan xSplit
                // agar baris & kolom header yang dibekukan selalu terkunci di posisi (0,0) tanpa terpotong / hilang.
                $scrollColumn = $xSplit;
                $scrollRow = $ySplit;
                if ($topLeftCell && preg_match('/^([A-Z]+)(\d+)$/', $topLeftCell, $tlm)) {
                    $scrollColumn = $this->columnLetterToIndex($tlm[1]) - 1;
                    $scrollRow = (int)$tlm[2] - 1;
                }

                if ($xSplit > 0 || $ySplit > 0) {
                    $freeze = [
                        'xSplit' => $xSplit,
                        'ySplit' => $ySplit,
                        'startRow' => $ySplit,
                        'startColumn' => $xSplit,
                        'scrollRow' => $scrollRow,
                        'scrollColumn' => $scrollColumn,
                    ];
                }
            }
        }

        // Active Cell & Selection
        $activeCell = null;
        $activeRow = null;
        $activeCol = null;
        if (preg_match('/<selection\s+[^>]*activeCell="([A-Z0-9]+)"/i', $sheetXml, $acm)) {
            $activeCell = $acm[1];
            if (preg_match('/^([A-Z]+)(\d+)$/', $activeCell, $m)) {
                $activeCol = $this->columnLetterToIndex($m[1]) - 1;
                $activeRow = (int)$m[2] - 1;
            }
        }

        // Tab Color
        $tabColor = null;
        if (preg_match('/<tabColor\s+[^>]*rgb="([A-Fa-f0-9]{6,8})"/i', $sheetXml, $tcMatches)) {
            $tabColor = '#' . substr($tcMatches[1], -6);
        }

        // Gridlines
        $showGridlines = 1;
        if (preg_match('/<sheetView[^>]*showGridLines="0"/i', $sheetXml)) {
            $showGridlines = 0;
        }

        // Zoom Scale
        $zoomRatio = 1.0;
        if (preg_match('/<sheetView[^>]*zoomScale="(\d+)"/i', $sheetXml, $zm)) {
            $zoomRatio = ((float)$zm[1]) / 100;
        }

        // AutoFilter
        $autoFilter = null;
        if (preg_match('/<autoFilter\s+ref="([^"]+)"/i', $sheetXml, $afm)) {
            $autoFilter = $afm[1];
        }

        // Data Validations
        $dataValidations = [];
        if (preg_match('/<dataValidations[^>]*>(.*?)<\/dataValidations>/s', $sheetXml, $dvSection)) {
            if (preg_match_all('/<dataValidation\s+([^>]+?)(?:\/>|>(.*?)<\/dataValidation>)/s', $dvSection[1], $dvm, PREG_SET_ORDER)) {
                foreach ($dvm as $dv) {
                    $attrs = $dv[1];
                    $inner = $dv[2] ?? '';
                    $type = preg_match('/type="([^"]+)"/i', $attrs, $m) ? $m[1] : 'list';
                    $sqref = preg_match('/sqref="([^"]+)"/i', $attrs, $m) ? $m[1] : '';
                    $formula1 = preg_match('/<formula1>(.*?)<\/formula1>/s', $inner, $m) ? $m[1] : '';
                    $dataValidations[] = [
                        'type' => $type,
                        'sqref' => $sqref,
                        'formula1' => $formula1,
                    ];
                }
            }
        }

        return [
            'cols' => $cols,
            'merges' => $merges,
            'freeze' => $freeze,
            'activeCell' => $activeCell,
            'activeRow' => $activeRow,
            'activeCol' => $activeCol,
            'tabColor' => $tabColor,
            'showGridlines' => $showGridlines,
            'zoomRatio' => $zoomRatio,
            'autoFilter' => $autoFilter,
            'dataValidations' => $dataValidations,
        ];
    }

    /**
     * Mengonversi format style OpenXML ke objek style standar Univer.
     */
    protected function resolveCellStyle(?int $sIdx, array $styles): ?array
    {
        if ($sIdx === null || !isset($styles['cellXfs'][$sIdx])) {
            return null;
        }

        $xf = $styles['cellXfs'][$sIdx];
        $font = isset($xf['fontId']) && isset($styles['fonts'][$xf['fontId']]) ? $styles['fonts'][$xf['fontId']] : null;
        $fill = isset($xf['fillId']) && isset($styles['fills'][$xf['fillId']]) ? $styles['fills'][$xf['fillId']] : null;
        $border = isset($xf['borderId']) && isset($styles['borders'][$xf['borderId']]) ? $styles['borders'][$xf['borderId']] : null;
        $numFmtId = $xf['numFmtId'] ?? null;
        $numFmtPattern = ($numFmtId !== null && isset($styles['numFmts'][$numFmtId])) ? $styles['numFmts'][$numFmtId] : null;
        $align = $xf['align'] ?? null;

        $sData = [];

        // Font attributes
        if ($font) {
            if (!empty($font['name'])) {
                $sData['ff'] = $font['name'];
            }
            if (!empty($font['size'])) {
                $sData['fs'] = $font['size'];
            }
            if (!empty($font['bold'])) {
                $sData['bl'] = 1;
            }
            if (!empty($font['italic'])) {
                $sData['it'] = 1;
            }
            if (!empty($font['underline'])) {
                $sData['ul'] = ['s' => 1];
            }
            if (!empty($font['strike'])) {
                $sData['st'] = ['s' => 1];
            }
            if (!empty($font['color'])) {
                $sData['cl'] = ['rgb' => $font['color']];
            }
        }

        // Fill background
        if (!empty($fill['rgb'])) {
            $sData['bg'] = ['rgb' => $fill['rgb']];
        }

        // Table Borders (Top, Bottom, Left, Right)
        if (!empty($border)) {
            $sData['bd'] = $border;
        }

        // Text Alignments
        if (!empty($align['h'])) {
            $hMap = ['left' => 1, 'center' => 2, 'right' => 3, 'justify' => 4];
            if (isset($hMap[$align['h']])) {
                $sData['ht'] = $hMap[$align['h']];
            }
        }
        if (!empty($align['v'])) {
            $vMap = ['top' => 1, 'center' => 2, 'middle' => 2, 'bottom' => 3];
            if (isset($vMap[$align['v']])) {
                $sData['vt'] = $vMap[$align['v']];
            }
        }
        if (!empty($align['wrap'])) {
            $sData['tb'] = 3;
        }

        // Number Format Pattern (Accounting '-', Currency, Percent, Date)
        if ($numFmtPattern && $numFmtPattern !== 'General') {
            $sData['n'] = ['pattern' => $numFmtPattern];
        }

        return !empty($sData) ? $sData : null;
    }

    private function columnLetterToIndex(string $letter): int
    {
        $letter = strtoupper($letter);
        $len = strlen($letter);
        $index = 0;
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($letter[$i]) - ord('A') + 1);
        }
        return $index;
    }

    private function indexToColumnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(ord('A') + $mod) . $letter;
            $index = (int)(($index - $mod) / 26);
        }
        return $letter;
    }
}
