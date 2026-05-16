<?php

namespace App\Services;

class SimplePdfService
{
    /**
     * Build a small valid PDF containing a text table.
     */
    public static function table(string $title, array $headings, array $rows): string
    {
        $lines = [$title, 'Generated: ' . now()->format('Y-m-d H:i:s'), ''];
        $lines[] = implode(' | ', $headings);
        $lines[] = str_repeat('-', min(100, max(20, strlen(end($lines)))));

        foreach ($rows as $row) {
            $lines[] = implode(' | ', array_map(
                fn ($value) => self::clean((string) $value),
                $row
            ));
        }

        return self::document($lines);
    }

    private static function document(array $lines): string
    {
        $objects = [];
        $content = "BT\n/F1 9 Tf\n50 790 Td\n14 TL\n";

        foreach ($lines as $line) {
            foreach (str_split(self::clean($line), 120) ?: [''] as $part) {
                $content .= '(' . self::escape($part) . ") Tj\nT*\n";
            }
        }

        $content .= "ET\n";

        $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
        $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>";
        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
        $objects[] = "<< /Length " . strlen($content) . " >>\nstream\n{$content}endstream";

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1) . " 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xref}\n%%EOF";

        return $pdf;
    }

    private static function clean(string $value): string
    {
        return preg_replace('/[^\x20-\x7E]/', ' ', $value) ?? '';
    }

    private static function escape(string $value): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
    }
}
