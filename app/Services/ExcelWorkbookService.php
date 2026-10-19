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
                $rId = $node->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
                $target = $relsMap[$rId] ?? null;

                $sheets[] = [
                    'name' => $name,
                    'rId' => $rId,
                    'target' => $target,
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
     * Membaca isi suatu sheet secara cepat dari .xlsx.
     */
    public function readSheetData(string $filePath, string $sheetName, int $maxRows = 150): array
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new RuntimeException("File Excel tidak dapat dibuka.");
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

            // 2. Baca sheet XML
            $sheetXml = $zip->getFromName($targetPath);
            $xmlReader = new XMLReader();
            $xmlReader->XML($sheetXml);

            $rows = [];
            $maxColIndex = 8; // Default minimal A-H
            $rowCount = 0;

            while ($xmlReader->read()) {
                if ($xmlReader->nodeType == XMLReader::ELEMENT && $xmlReader->localName === 'row') {
                    $rNum = (int)$xmlReader->getAttribute('r');
                    $rowXml = new SimpleXMLElement($xmlReader->readOuterXml());
                    $cells = [];

                    foreach ($rowXml->c as $c) {
                        $ref = (string)$c['r'];
                        $type = (string)$c['t'];
                        $formula = isset($c->f) ? (string)$c->f : null;
                        $rawVal = isset($c->v) ? (string)$c->v : '';

                        // Tentukan nilai tampilan
                        if ($type === 's' && isset($sharedStrings[(int)$rawVal])) {
                            $displayVal = $sharedStrings[(int)$rawVal];
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
                            $maxColIndex = min($colIdx, 26); // Batasi maks Z untuk tampilan optimal
                        }

                        $cells[$ref] = [
                            'ref' => $ref,
                            'col' => $colLetter,
                            'row' => $rNum,
                            'val' => $displayVal,
                            'formula' => $formula ? '=' . $formula : null,
                            'type' => $type,
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
                'columns' => $columns,
                'rows' => array_values($rows),
                'totalRows' => count($rows),
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
