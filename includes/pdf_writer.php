<?php
/**
 * Minimal PDF writer — no Composer, no FPDF/TCPDF, just hand-built PDF 1.4
 * objects. Supports left-aligned text at absolute positions, straight
 * lines, and multi-page documents using the 14 standard PDF fonts
 * (Helvetica / Helvetica-Bold here), which need no embedding.
 *
 * This is intentionally simple — plain text reports and a single-record
 * signal layout, not general-purpose page design — but every byte of the
 * output is real, spec-compliant PDF.
 */

function pdf_escape(string $text): string
{
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
}

class SimplePdf
{
    private array $objects = [];
    private int $nextNum = 1;
    private int $fontRegular;
    private int $fontBold;
    private int $pagesNum;
    private array $pageNums = [];

    public function __construct()
    {
        $this->fontRegular = $this->alloc();
        $this->objects[$this->fontRegular] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        $this->fontBold = $this->alloc();
        $this->objects[$this->fontBold] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';

        $this->pagesNum = $this->alloc(); // body filled in output()
    }

    private function alloc(): int
    {
        return $this->nextNum++;
    }

    /** $ops is a raw PDF content stream (BT/ET, Tj, m/l/S, etc — see helpers below). */
    public function addPage(string $ops, int $width = 595, int $height = 842): void
    {
        $contentNum = $this->alloc();
        $len = strlen($ops);
        $this->objects[$contentNum] = "<< /Length {$len} >>\nstream\n{$ops}\nendstream";

        $pageNum = $this->alloc();
        $this->objects[$pageNum] =
            "<< /Type /Page /Parent {$this->pagesNum} 0 R /MediaBox [0 0 {$width} {$height}] " .
            "/Resources << /Font << /F1 {$this->fontRegular} 0 R /F2 {$this->fontBold} 0 R >> >> " .
            "/Contents {$contentNum} 0 R >>";
        $this->pageNums[] = $pageNum;
    }

    /** Absolute-positioned text (PDF origin is bottom-left). */
    public static function text(float $x, float $y, string $text, float $size = 10, string $font = 'F1'): string
    {
        $escaped = pdf_escape($text);
        return "BT /{$font} {$size} Tf 1 0 0 1 {$x} {$y} Tm ({$escaped}) Tj ET\n";
    }

    public static function line(float $x1, float $y1, float $x2, float $y2, float $width = 0.5): string
    {
        return "{$width} w {$x1} {$y1} m {$x2} {$y2} l S\n";
    }

    public function output(): string
    {
        $catalogNum = $this->alloc();
        $this->objects[$catalogNum] = "<< /Type /Catalog /Pages {$this->pagesNum} 0 R >>";

        $kids = implode(' ', array_map(fn($n) => "{$n} 0 R", $this->pageNums));
        $this->objects[$this->pagesNum] = "<< /Type /Pages /Kids [{$kids}] /Count " . count($this->pageNums) . ' >>';

        ksort($this->objects);
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($this->objects as $num => $body) {
            $offsets[$num] = strlen($pdf);
            $pdf .= "{$num} 0 obj\n{$body}\nendobj\n";
        }
        $xrefStart = strlen($pdf);
        $count = count($this->objects) + 1;
        $pdf .= "xref\n0 {$count}\n0000000000 65535 f \n";
        for ($i = 1; $i < $count; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }
        $pdf .= "trailer\n<< /Size {$count} /Root {$catalogNum} 0 R >>\nstartxref\n{$xrefStart}\n%%EOF";
        return $pdf;
    }

    public function stream(string $filename): never
    {
        $body = $this->output();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($body));
        echo $body;
        exit;
    }
}

/**
 * Renders $rows as a paginated table with an NPF letterhead, and streams
 * it as a PDF download. $columns maps header label => row array key.
 */
function export_pdf_table(string $filename, string $title, array $columns, array $rows): never
{
    $pdf = new SimplePdf();
    $pageWidth = 842; $pageHeight = 595; // landscape — most of these reports are wide
    $marginX = 40;
    $rowsPerPage = 28;
    $colCount = count($columns);
    $colWidth = ($pageWidth - 2 * $marginX) / max(1, $colCount);

    $chunks = array_chunk($rows, $rowsPerPage) ?: [[]];
    $totalPages = count($chunks);

    foreach ($chunks as $pageIndex => $chunk) {
        $ops = '';
        $y = $pageHeight - 50;

        $ops .= SimplePdf::text($marginX, $y, 'NIGERIA POLICE FORCE', 13, 'F2');
        $ops .= SimplePdf::text($marginX, $y - 16, 'Force Headquarters, Abuja', 8);
        $ops .= SimplePdf::text($marginX, $y - 34, $title, 12, 'F2');
        $ops .= SimplePdf::text($pageWidth - $marginX - 140, $y, 'Generated: ' . date('Y-m-d H:i'), 8);
        $ops .= SimplePdf::text($pageWidth - $marginX - 140, $y - 12, 'Page ' . ($pageIndex + 1) . ' of ' . $totalPages, 8);

        $y -= 55;
        $ops .= SimplePdf::line($marginX, $y, $pageWidth - $marginX, $y);
        $y -= 14;

        $x = $marginX;
        foreach (array_keys($columns) as $label) {
            $ops .= SimplePdf::text($x, $y, mb_substr($label, 0, 22), 8, 'F2');
            $x += $colWidth;
        }
        $y -= 6;
        $ops .= SimplePdf::line($marginX, $y, $pageWidth - $marginX, $y);
        $y -= 14;

        foreach ($chunk as $row) {
            $x = $marginX;
            foreach ($columns as $key) {
                $val = (string) ($row[$key] ?? '');
                $ops .= SimplePdf::text($x, $y, mb_substr($val, 0, 26), 8);
                $x += $colWidth;
            }
            $y -= 16;
            if ($y < 40) break; // safety net — rowsPerPage should already prevent this
        }

        $pdf->addPage($ops, $pageWidth, $pageHeight);
    }

    $pdf->stream($filename);
}
