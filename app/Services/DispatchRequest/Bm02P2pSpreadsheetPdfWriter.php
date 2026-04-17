<?php

namespace App\Services\DispatchRequest;

use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf as PhpSpreadsheetPdfMpdf;

/**
 * mPDF mặc định của PhpSpreadsheet chỉ truyền tempDir — thiếu cỡ chữ/DPI khiến bản in BM.02 mờ và khó đọc.
 */
class Bm02P2pSpreadsheetPdfWriter extends PhpSpreadsheetPdfMpdf
{
    protected function createExternalWriterInstance(array $config): Mpdf
    {
        $merged = array_merge($config, [
            'mode' => 'utf-8',
            'default_font' => 'dejavusans',
            'default_font_size' => 11,
            'dpi' => 110,
            'img_dpi' => 110,
            'autoScriptToLang' => true,
            'useSubstitutions' => false,
        ]);

        return new Mpdf($merged);
    }
}
