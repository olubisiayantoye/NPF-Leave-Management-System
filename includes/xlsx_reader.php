<?php
/**
 * Minimal XLSX reader — no Composer, no PhpSpreadsheet. Reads the first
 * worksheet of a .xlsx file into a plain array of rows (each row itself
 * an array of cell strings, in column order). Handles shared strings,
 * inline strings, and numeric cells — the three cell types Excel/LibreOffice
 * actually produce for simple data exports.
 *
 * Usage: $rows = read_xlsx('/path/to/file.xlsx');
 * Returns null if the file isn't a readable XLSX.
 */
function read_xlsx(string $path): ?array
{
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        return null;
    }

    // Shared strings table (may not exist if the writer used inline strings)
    $shared = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        $doc = new DOMDocument();
        $doc->loadXML($sharedXml, LIBXML_NOWARNING | LIBXML_NOERROR);
        foreach ($doc->getElementsByTagName('si') as $si) {
            // A shared string item can contain multiple <t> runs (rich text) — concatenate them.
            $text = '';
            foreach ($si->getElementsByTagName('t') as $t) {
                $text .= $t->nodeValue;
            }
            $shared[] = $text;
        }
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    if ($sheetXml === false) {
        $zip->close();
        return null;
    }
    $zip->close();

    $doc = new DOMDocument();
    $doc->loadXML($sheetXml, LIBXML_NOWARNING | LIBXML_NOERROR);

    $rows = [];
    foreach ($doc->getElementsByTagName('row') as $rowEl) {
        $row = [];
        $colCursor = 0;
        foreach ($rowEl->getElementsByTagName('c') as $cellEl) {
            $ref = $cellEl->getAttribute('r'); // e.g. "C4"
            $colIndex = xlsx_col_index_from_ref($ref) ?? $colCursor;

            $type = $cellEl->getAttribute('t');
            $value = '';

            if ($type === 's') { // shared string
                $vNode = $cellEl->getElementsByTagName('v')->item(0);
                $idx = $vNode ? (int) $vNode->nodeValue : -1;
                $value = $shared[$idx] ?? '';
            } elseif ($type === 'inlineStr') {
                $isNode = $cellEl->getElementsByTagName('is')->item(0);
                if ($isNode) {
                    foreach ($isNode->getElementsByTagName('t') as $t) {
                        $value .= $t->nodeValue;
                    }
                }
            } else { // numeric or plain
                $vNode = $cellEl->getElementsByTagName('v')->item(0);
                $value = $vNode ? $vNode->nodeValue : '';
            }

            // Pad any skipped columns so positions line up with the header row.
            while ($colCursor < $colIndex) {
                $row[$colCursor] = '';
                $colCursor++;
            }
            $row[$colIndex] = $value;
            $colCursor = $colIndex + 1;
        }
        $rows[] = $row;
    }

    return $rows;
}

function xlsx_col_index_from_ref(string $ref): ?int
{
    if (!preg_match('/^([A-Z]+)\d+$/', $ref, $m)) {
        return null;
    }
    $letters = $m[1];
    $index = 0;
    foreach (str_split($letters) as $ch) {
        $index = $index * 26 + (ord($ch) - 64);
    }
    return $index - 1;
}
