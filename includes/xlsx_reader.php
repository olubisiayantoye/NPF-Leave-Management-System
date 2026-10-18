<?php
/**
 * Minimal .xlsx reader — no external dependencies (just the bundled
 * php-zip and php-xml extensions). Reads the first worksheet of a
 * workbook into a plain array of rows, each row a zero-indexed array
 * of string cell values (Excel formatting/formulas are not evaluated).
 *
 * This intentionally does not attempt to be a general-purpose XLSX
 * library — it covers what NRMS's bulk upload needs: shared strings,
 * inline strings, and raw numeric/date-serial cells.
 */
class XlsxReader
{
    private ZipArchive $zip;
    private array $sharedStrings = [];
    private string $sheetPath;

    public function __construct(string $filePath)
    {
        $this->zip = new ZipArchive();
        if ($this->zip->open($filePath) !== true) {
            throw new RuntimeException('Could not open the uploaded file as an Excel (.xlsx) workbook.');
        }
        $this->loadSharedStrings();
        $this->sheetPath = $this->findFirstSheetPath();
    }

    private function loadSharedStrings(): void
    {
        $data = $this->zip->getFromName('xl/sharedStrings.xml');
        if ($data === false) return; // workbook has no shared strings table — fine, cells may use inline strings
        $xml = @simplexml_load_string($data);
        if (!$xml) return;
        foreach ($xml->si as $si) {
            if (isset($si->t)) {
                $this->sharedStrings[] = (string)$si->t;
            } else {
                // Rich text split across multiple <r><t>...</t></r> runs
                $text = '';
                foreach ($si->r as $run) {
                    $text .= (string)$run->t;
                }
                $this->sharedStrings[] = $text;
            }
        }
    }

    private function findFirstSheetPath(): string
    {
        if ($this->zip->locateName('xl/worksheets/sheet1.xml') !== false) {
            return 'xl/worksheets/sheet1.xml';
        }
        for ($i = 0; $i < $this->zip->numFiles; $i++) {
            $name = $this->zip->getNameIndex($i);
            if ($name !== false && preg_match('#^xl/worksheets/sheet\d+\.xml$#', $name)) {
                return $name;
            }
        }
        throw new RuntimeException('This .xlsx file does not contain a readable worksheet.');
    }

    private function columnLetterToIndex(string $cellRef): int
    {
        preg_match('/^([A-Z]+)/', $cellRef, $m);
        $letters = $m[1] ?? 'A';
        $index = 0;
        foreach (str_split($letters) as $ch) {
            $index = $index * 26 + (ord($ch) - 64);
        }
        return $index - 1; // zero-based
    }

    /**
     * Returns all rows as zero-indexed arrays of string values.
     * Empty trailing cells within a row are padded with ''.
     */
    public function rows(): array
    {
        $data = $this->zip->getFromName($this->sheetPath);
        if ($data === false) {
            throw new RuntimeException('Could not read the worksheet contents.');
        }
        $xml = @simplexml_load_string($data);
        if (!$xml || !isset($xml->sheetData)) {
            throw new RuntimeException('The worksheet appears to be empty or malformed.');
        }

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $rowData = [];
            foreach ($row->c as $c) {
                $ref = (string)$c['r'];
                $colIndex = $ref !== '' ? $this->columnLetterToIndex($ref) : count($rowData);
                $type = (string)$c['t'];

                if ($type === 's') {
                    $value = $this->sharedStrings[(int)($c->v ?? -1)] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = (string)($c->is->t ?? '');
                } else {
                    $value = isset($c->v) ? (string)$c->v : '';
                }
                $rowData[$colIndex] = $value;
            }

            if ($rowData) {
                $max = max(array_keys($rowData));
                $ordered = [];
                for ($i = 0; $i <= $max; $i++) $ordered[] = $rowData[$i] ?? '';
                $rows[] = $ordered;
            } else {
                $rows[] = [];
            }
        }
        return $rows;
    }

    public function close(): void
    {
        $this->zip->close();
    }
}
