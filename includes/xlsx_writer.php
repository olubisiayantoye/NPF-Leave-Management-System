<?php
/**
 * Minimal XLSX writer — no Composer, no PhpSpreadsheet, just ZipArchive
 * and hand-built OOXML. Single sheet, header row + data rows, all cells
 * written as inline strings/numbers (no sharedStrings.xml needed, which
 * keeps this simple and keeps every value human-readable in the raw XML).
 *
 * Usage:
 *   write_xlsx('/tmp/out.xlsx', ['F-AP No', 'Name'], [['315443','A. Bello'], ...]);
 */

function xlsx_col_letter(int $index): string
{
    $letter = '';
    $index++;
    while ($index > 0) {
        $mod = ($index - 1) % 26;
        $letter = chr(65 + $mod) . $letter;
        $index = intdiv($index - $mod, 26);
    }
    return $letter;
}

function xlsx_cell_xml(int $colIndex, int $rowIndex, $value): string
{
    $ref = xlsx_col_letter($colIndex) . $rowIndex;

    if ($value === null || $value === '') {
        return "<c r=\"{$ref}\"/>";
    }
    if (is_numeric($value) && !preg_match('/^0\d/', (string) $value)) {
        // Numeric — but keep things like "07/10" or "0123" as text via the leading-zero guard above.
        $v = htmlspecialchars((string) $value, ENT_XML1);
        return "<c r=\"{$ref}\"><v>{$v}</v></c>";
    }

    $v = htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    return "<c r=\"{$ref}\" t=\"inlineStr\"><is><t xml:space=\"preserve\">{$v}</t></is></c>";
}

function write_xlsx(string $path, array $headers, array $rows): bool
{
    $zip = new ZipArchive();
    if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return false;
    }

    $zip->addFromString('[Content_Types].xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
        '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
        '<Default Extension="xml" ContentType="application/xml"/>' .
        '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
        '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
        '</Types>'
    );

    $zip->addFromString('_rels/.rels',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
        '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' .
        '</Relationships>'
    );

    $zip->addFromString('xl/_rels/workbook.xml.rels',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
        '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' .
        '</Relationships>'
    );

    $zip->addFromString('xl/workbook.xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" ' .
        'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
        '<sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets>' .
        '</workbook>'
    );

    $rowsXml = '';

    // Header row (bold not implemented — kept dependency-free; still perfectly readable data)
    $cells = '';
    foreach ($headers as $i => $h) {
        $cells .= xlsx_cell_xml($i, 1, $h);
    }
    $rowsXml .= "<row r=\"1\">{$cells}</row>";

    foreach ($rows as $rIdx => $row) {
        $rowNum = $rIdx + 2;
        $cells = '';
        foreach (array_values($row) as $cIdx => $val) {
            $cells .= xlsx_cell_xml($cIdx, $rowNum, $val);
        }
        $rowsXml .= "<row r=\"{$rowNum}\">{$cells}</row>";
    }

    $zip->addFromString('xl/worksheets/sheet1.xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
        "<sheetData>{$rowsXml}</sheetData>" .
        '</worksheet>'
    );

    $zip->close();
    return true;
}

/** Streams an XLSX file as a download built from $columns (label=>rowKey) and $rows, then exits. */
function export_xlsx(string $filename, array $columns, array $rows): never
{
    $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
    $headers = array_keys($columns);
    $dataRows = [];
    foreach ($rows as $row) {
        $line = [];
        foreach ($columns as $key) {
            $line[] = $row[$key] ?? '';
        }
        $dataRows[] = $line;
    }
    write_xlsx($tmp, $headers, $dataRows);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($tmp));
    readfile($tmp);
    unlink($tmp);
    exit;
}
