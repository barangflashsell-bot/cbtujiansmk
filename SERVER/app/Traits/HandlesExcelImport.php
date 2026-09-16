<?php

namespace App\Traits;

use Symfony\Component\HttpFoundation\StreamedResponse;

trait HandlesExcelImport
{
    /**
     * Parse an uploaded Excel (.xlsx, .xls) or CSV (.csv/.txt) file into an array of rows.
     */
    protected function parseUploadedSpreadsheet(string $filePath, string $extension): array
    {
        $extension = strtolower(trim($extension));
        $content = file_get_contents($filePath);

        if ($content !== false && (str_contains($content, 'urn:schemas-microsoft-com:office:spreadsheet') || str_starts_with(trim($content), '<?xml'))) {
            return $this->parseExcelXmlContent($content);
        }

        if ($extension === 'xlsx') {
            return $this->parseXlsxFile($filePath);
        }

        return $this->parseCsvFile($filePath);
    }

    /**
     * Stream an Excel Spreadsheet (.xls) template with styled headers, borders, and column widths.
     */
    /**
     * Stream an Excel Spreadsheet (.xls) template with styled headers, borders, and column widths.
     */
    protected function streamExcelTemplate(string $filename, array $headers, array $sampleRows, array $colWidths = [], array $metaRows = [], string $headerColor = '#0095FF', string $headerTextColor = '#FFFFFF'): StreamedResponse
    {
        $responseHeaders = [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ];

        $callback = function () use ($headers, $sampleRows, $colWidths, $metaRows, $headerColor, $headerTextColor) {
            echo $this->buildExcelXmlString($headers, $sampleRows, $colWidths, $metaRows, $headerColor, $headerTextColor);
        };

        return response()->stream($callback, 200, $responseHeaders);
    }

    /**
     * Stream a standard CSV file with UTF-8 BOM for Microsoft Excel Windows compatibility.
     */
    protected function streamCsvTemplate(string $filename, array $headers, array $sampleRows, array $metaRows = []): StreamedResponse
    {
        $responseHeaders = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ];

        $callback = function () use ($headers, $sampleRows, $metaRows) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM
            fputs($handle, "\xEF\xBB\xBF");

            if (!empty($metaRows)) {
                foreach ($metaRows as $mRow) {
                    fputcsv($handle, $mRow, ';');
                }
                fputcsv($handle, [], ';');
            }

            // Use semicolon for seamless Indonesian/European Excel auto-column splitting
            fputcsv($handle, $headers, ';');
            foreach ($sampleRows as $row) {
                fputcsv($handle, $row, ';');
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $responseHeaders);
    }

    /**
     * Build native Excel 2003 XML spreadsheet string.
     */
    protected function buildExcelXmlString(array $headers, array $rows, array $colWidths = [], array $metaRows = [], string $headerColor = '#0095FF', string $headerTextColor = '#FFFFFF'): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n";
        $xml .= ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n";
        $xml .= ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
        $xml .= ' <Styles>' . "\n";
        $xml .= '  <Style ss:ID="Header">' . "\n";
        $xml .= '   <Font ss:Bold="1" ss:Color="' . $headerTextColor . '" ss:FontName="Calibri" ss:Size="11"/>' . "\n";
        $xml .= '   <Interior ss:Color="' . $headerColor . '" ss:Pattern="Solid"/>' . "\n";
        $xml .= '   <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>' . "\n";
        $xml .= '   <Borders>' . "\n";
        $xml .= '    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>' . "\n";
        $xml .= '    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>' . "\n";
        $xml .= '    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>' . "\n";
        $xml .= '    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>' . "\n";
        $xml .= '   </Borders>' . "\n";
        $xml .= '  </Style>' . "\n";
        $xml .= '  <Style ss:ID="MetaLabel">' . "\n";
        $xml .= '   <Font ss:Bold="1" ss:FontName="Calibri" ss:Size="11" ss:Color="#1E293B"/>' . "\n";
        $xml .= '   <Alignment ss:Vertical="Center"/>' . "\n";
        $xml .= '  </Style>' . "\n";
        $xml .= '  <Style ss:ID="MetaValue">' . "\n";
        $xml .= '   <Font ss:Bold="1" ss:FontName="Calibri" ss:Size="11" ss:Color="#0052CC"/>' . "\n";
        $xml .= '   <Alignment ss:Vertical="Center"/>' . "\n";
        $xml .= '  </Style>' . "\n";
        $xml .= '  <Style ss:ID="TextCell">' . "\n";
        $xml .= '   <NumberFormat ss:Format="@"/>' . "\n";
        $xml .= '   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#0F172A"/>' . "\n";
        $xml .= '   <Alignment ss:Vertical="Center"/>' . "\n";
        $xml .= '   <Borders>' . "\n";
        $xml .= '    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>' . "\n";
        $xml .= '    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>' . "\n";
        $xml .= '    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>' . "\n";
        $xml .= '   </Borders>' . "\n";
        $xml .= '  </Style>' . "\n";
        $xml .= '  <Style ss:ID="CenterCell">' . "\n";
        $xml .= '   <NumberFormat ss:Format="@"/>' . "\n";
        $xml .= '   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#0F172A"/>' . "\n";
        $xml .= '   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>' . "\n";
        $xml .= '   <Borders>' . "\n";
        $xml .= '    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>' . "\n";
        $xml .= '    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>' . "\n";
        $xml .= '    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>' . "\n";
        $xml .= '   </Borders>' . "\n";
        $xml .= '  </Style>' . "\n";
        $xml .= ' </Styles>' . "\n";
        $xml .= ' <Worksheet ss:Name="Template CBT">' . "\n";
        $xml .= '  <Table>' . "\n";

        foreach ($headers as $i => $h) {
            $w = $colWidths[$i] ?? 130;
            $xml .= '   <Column ss:Width="' . $w . '"/>' . "\n";
        }

        if (!empty($metaRows)) {
            foreach ($metaRows as $mRow) {
                $xml .= '   <Row ss:Height="22">' . "\n";
                $label = $mRow[0] ?? '';
                $val = $mRow[1] ?? '';
                $xml .= '    <Cell ss:StyleID="MetaLabel"><Data ss:Type="String">' . htmlspecialchars((string)$label) . '</Data></Cell>' . "\n";
                $xml .= '    <Cell ss:StyleID="MetaValue"><Data ss:Type="String">' . htmlspecialchars((string)$val) . '</Data></Cell>' . "\n";
                $xml .= '   </Row>' . "\n";
            }
            $xml .= '   <Row ss:Height="12"></Row>' . "\n";
        }

        $xml .= '   <Row ss:Height="28">' . "\n";
        foreach ($headers as $h) {
            $xml .= '    <Cell ss:StyleID="Header"><Data ss:Type="String">' . htmlspecialchars($h) . '</Data></Cell>' . "\n";
        }
        $xml .= '   </Row>' . "\n";

        foreach ($rows as $row) {
            $xml .= '   <Row ss:Height="22">' . "\n";
            foreach ($row as $colIdx => $val) {
                $style = ($colIdx === 0 || $colIdx === 2 || $colIdx === 8) ? 'CenterCell' : 'TextCell';
                $xml .= '    <Cell ss:StyleID="' . $style . '"><Data ss:Type="String">' . htmlspecialchars((string)$val) . '</Data></Cell>' . "\n";
            }
            $xml .= '   </Row>' . "\n";
        }

        $xml .= '  </Table>' . "\n";
        $xml .= ' </Worksheet>' . "\n";
        $xml .= '</Workbook>' . "\n";

        return $xml;
    }

    /**
     * Parse XML-based Excel (.xls) file.
     */
    protected function parseExcelXmlContent(string $content): array
    {
        $xml = simplexml_load_string($content);
        $rows = [];
        if ($xml && isset($xml->Worksheet->Table->Row)) {
            foreach ($xml->Worksheet->Table->Row as $row) {
                $rowData = [];
                foreach ($row->Cell as $cell) {
                    $rowData[] = trim((string)($cell->Data ?? ''));
                }
                if (!empty(array_filter($rowData, fn($v) => $v !== ''))) {
                    $rows[] = $rowData;
                }
            }
        }
        return $rows;
    }

    /**
     * Parse CSV / TXT file content with auto delimiter detection.
     */
    protected function parseCsvFile(string $filePath): array
    {
        $content = file_get_contents($filePath);
        if ($content === false || trim($content) === '') {
            return [];
        }

        // Remove UTF-8 BOM
        $bom = pack('H*', 'EFBBBF');
        $content = preg_replace("/^$bom/", '', $content);

        // Auto-detect delimiter
        $lines = explode("\n", $content);
        $firstLine = $lines[0] ?? '';
        $delimiter = ',';
        if (substr_count($firstLine, ';') > substr_count($firstLine, ',')) {
            $delimiter = ';';
        } elseif (substr_count($firstLine, "\t") > substr_count($firstLine, ',')) {
            $delimiter = "\t";
        }

        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $content);
        rewind($handle);

        $rows = [];
        while (($data = fgetcsv($handle, 8192, $delimiter)) !== false) {
            if (! empty(array_filter($data, fn ($v) => trim((string) $v) !== ''))) {
                $rows[] = array_map(fn ($v) => trim((string) $v), $data);
            }
        }
        fclose($handle);

        return $rows;
    }

    /**
     * Parse native Excel (.xlsx) file using PHP's built-in ZipArchive and SimpleXML.
     */
    protected function parseXlsxFile(string $filePath): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new \Exception('Gagal membuka arsip file Excel (.xlsx).');
        }

        // 1. Read shared strings
        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $xml = simplexml_load_string($sharedXml);
            if ($xml !== false) {
                foreach ($xml->si as $val) {
                    if (isset($val->t)) {
                        $sharedStrings[] = (string) $val->t;
                    } elseif (isset($val->r)) {
                        $text = '';
                        foreach ($val->r as $r) {
                            $text .= (string) $r->t;
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // 2. Read first worksheet
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (str_starts_with($name, 'xl/worksheets/sheet') && str_ends_with($name, '.xml')) {
                    $sheetXml = $zip->getFromIndex($i);
                    break;
                }
            }
        }
        $zip->close();

        if ($sheetXml === false) {
            throw new \Exception('Worksheet Excel tidak ditemukan.');
        }

        $xml = simplexml_load_string($sheetXml);
        if ($xml === false || ! isset($xml->sheetData)) {
            return [];
        }

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $rowData = [];
            $lastColIdx = 0;
            foreach ($row->c as $c) {
                $cellRef = (string) $c['r'];
                $type = (string) $c['t'];

                preg_match('/^([A-Z]+)(\d+)$/', $cellRef, $matches);
                if (! empty($matches[1])) {
                    $colLetters = $matches[1];
                    $colIdx = 0;
                    for ($len = strlen($colLetters), $k = 0; $k < $len; $k++) {
                        $colIdx = $colIdx * 26 + (ord($colLetters[$k]) - ord('A') + 1);
                    }
                    $colIdx -= 1;
                } else {
                    $colIdx = $lastColIdx;
                }

                while (count($rowData) < $colIdx) {
                    $rowData[] = '';
                }

                $val = '';
                if (isset($c->v)) {
                    $rawVal = (string) $c->v;
                    if ($type === 's' && isset($sharedStrings[(int) $rawVal])) {
                        $val = $sharedStrings[(int) $rawVal];
                    } else {
                        $val = $rawVal;
                    }
                } elseif ($type === 'inlineStr' && isset($c->is->t)) {
                    $val = (string) $c->is->t;
                }

                $rowData[] = trim($val);
                $lastColIdx = count($rowData);
            }

            if (! empty(array_filter($rowData, fn ($v) => $v !== ''))) {
                $rows[] = $rowData;
            }
        }

        return $rows;
    }
}
