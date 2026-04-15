<?php

namespace App\Services\DispatchRequest;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Điền mẫu HTML xuất từ Google Sheet (ritz/waffle) cho BM.02 P2P — cùng nguồn với preview/PDF.
 */
class Bm02P2pGoogleSheetHtmlRenderer
{
    private const TEMPLATE_RELATIVE = 'dispatch/bm02-p2p-google-template.html';

    /** Logo tùy chọn (copy từ export Google). */
    private const LOGO_FILENAME = 'bm02-google-logo.jpg';

    /**
     * @param  array<string, mixed>  $vm  View model từ Bm02P2pFormGenerator::buildViewModel (+ issued_date, google_checkbox_states, google_signatory_line2)
     */
    public function render(array $vm): ?string
    {
        $path = resource_path(self::TEMPLATE_RELATIVE);
        if (! is_readable($path)) {
            return null;
        }

        $html = file_get_contents($path);
        $html = $this->stripSheetCssLink($html);
        $html = $this->rewriteLogoImage($html);
        $html = $this->replaceSignaturePlaceholders($html, $vm);

        libxml_use_internal_errors(true);
        $dom = new DOMDocument;
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        $xpath = new DOMXPath($dom);

        $this->fillIssuedDateCell($xpath, $vm);
        $this->fillMetaRows($xpath, $vm);
        $this->mergePurposeRow($xpath, $vm);
        $this->fillBasisSpan($xpath, $vm);
        $this->fillDateRows($xpath, $vm);
        $this->fillUrgentRow($xpath, $vm);
        $this->fillCheckboxUses($xpath, $vm);
        $this->fillTripRows($xpath, $vm);
        $this->fillTotalRow($xpath, $vm);

        $this->stripSpreadsheetChrome($xpath);
        $this->enableSheetLikeGridOnTable($xpath);

        $html = $this->serializeBodyInnerHtml($dom);

        return $this->injectPrintGridFallback($html);
    }

    /**
     * Bảng export Google dùng class `no-grid` + file sheet.css (đã bỏ) nên trong trình duyệt mất lưới ô.
     */
    private function enableSheetLikeGridOnTable(DOMXPath $xpath): void
    {
        foreach ($xpath->query('//table[contains(@class, "waffle")]') as $table) {
            if (! $table instanceof DOMElement) {
                continue;
            }
            $cls = $table->getAttribute('class');
            $table->setAttribute('class', trim((string) preg_replace('/\bno-grid\b/', '', $cls)));
        }
    }

    /**
     * Bỏ các phần "chrome" của Google Sheets (thead cột chữ cái, cột số dòng).
     */
    private function stripSpreadsheetChrome(DOMXPath $xpath): void
    {
        foreach ($xpath->query('//table[contains(@class, "waffle")]//thead') as $thead) {
            if ($thead->parentNode) {
                $thead->parentNode->removeChild($thead);
            }
        }
        foreach ($xpath->query('//table[contains(@class, "waffle")]//th[contains(@class, "row-headers-background")]') as $th) {
            if ($th->parentNode) {
                $th->parentNode->removeChild($th);
            }
        }
    }

    /**
     * Lưới ô mặc định giống Excel (sheet.css gốc không tải được khi mở blob / in).
     */
    private function injectPrintGridFallback(string $html): string
    {
        $fallback = '<style type="text/css" id="bm02-print-grid-fallback">'
            .'@page{margin:8mm 8mm 8mm 8mm;}'
            .'body{font-family:"Times New Roman",serif;font-size:11pt;}'
            .'.ritz.grid-container{width:100%;max-width:100%;}'
            .'.ritz.grid-container .waffle{width:100%;border-collapse:collapse;table-layout:fixed;}'
            .'.ritz.grid-container .waffle td,.ritz.grid-container .waffle th{'
            .'border:1px solid #bfbfbf!important;'
            .'}'
            .'.ritz.grid-container .column-headers-background,.ritz.grid-container .row-headers-background,.ritz.grid-container .row-header{display:none!important;}'
            .'.ritz.grid-container .waffle thead th{background:#f3f3f3;font-weight:600;}'
            .'</style>';

        $prefix = '<meta charset="utf-8">';
        if (str_starts_with($html, $prefix)) {
            return $prefix.$fallback.substr($html, strlen($prefix));
        }

        return $fallback.$html;
    }

    /**
     * @param  array<string, mixed>  $vm
     */
    private function replaceSignaturePlaceholders(string $html, array $vm): string
    {
        $n = htmlspecialchars((string) ($vm['requester_name'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $l2 = htmlspecialchars((string) ($vm['google_signatory_line2'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $html = str_replace('Phạm Thanh Hùng', $n, $html);

        return (string) preg_replace('/Bùi Quang Minh/u', $l2, $html, 1);
    }

    private function stripSheetCssLink(string $html): string
    {
        return (string) preg_replace('#<link[^>]*sheet\.css[^>]*/?>#i', '', $html);
    }

    private function rewriteLogoImage(string $html): string
    {
        $logo = resource_path('dispatch/'.self::LOGO_FILENAME);
        if (is_readable($logo)) {
            $url = 'file://'.str_replace('\\', '/', $logo);

            return (string) preg_replace(
                '#(<img\s[^>]*src=")resources/cellImage[^"]+(")#',
                '$1'.$url.'$2',
                $html,
                1,
            );
        }

        return (string) preg_replace('#<img[^>]*cellImage[^>]*/>#', '', $html, 1);
    }

    /**
     * @param  array<string, mixed>  $vm
     */
    private function fillIssuedDateCell(DOMXPath $xpath, array $vm): void
    {
        $issued = (string) ($vm['issued_date'] ?? '');
        foreach ($xpath->query('//td[contains(@class, "s14")]') as $td) {
            if (trim($td->textContent) !== '') {
                $this->setTextPreserveNewlines($td, $issued);

                return;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $vm
     */
    private function fillMetaRows(DOMXPath $xpath, array $vm): void
    {
        $this->setRowValueCell($xpath, '1313432914R6', 2, (string) ($vm['requester_name'] ?? ''));
        $this->setRowValueCell($xpath, '1313432914R7', 2, (string) ($vm['requester_email'] ?? ''));
        $this->setRowValueCell($xpath, '1313432914R8', 2, (string) ($vm['requester_phone'] ?? ''));
        $this->setRowValueCell($xpath, '1313432914R9', 2, (string) ($vm['requester_unit'] ?? ''));
    }

    private function setRowValueCell(DOMXPath $xpath, string $rowThId, int $tdIndex, string $value): void
    {
        $tr = $xpath->query('//tr[.//th[@id="'.$rowThId.'"]]')->item(0);
        if (! $tr) {
            return;
        }
        $tds = $xpath->query('./td', $tr);
        $td = $tds->item($tdIndex);
        if ($td) {
            $this->setTextPreserveNewlines($td, $value);
        }
    }

    /**
     * @param  array<string, mixed>  $vm
     */
    private function mergePurposeRow(DOMXPath $xpath, array $vm): void
    {
        $purpose = (string) ($vm['purpose'] ?? '');
        $tr = $xpath->query('//tr[.//th[@id="1313432914R12"]]')->item(0);
        if (! $tr) {
            return;
        }
        $tds = $xpath->query('./td', $tr);
        if ($tds->length < 16) {
            return;
        }
        /** @var DOMElement|null $first */
        $first = $tds->item(2);
        if (! $first instanceof DOMElement) {
            return;
        }
        $first->setAttribute('colspan', '14');
        $style = $first->getAttribute('style');
        $first->setAttribute('style', trim($style.';white-space:pre-wrap;font-size:11pt;'));
        $this->setTextPreserveNewlines($first, $purpose);
        $toRemove = [];
        for ($i = 3; $i <= 15; $i++) {
            $n = $tds->item($i);
            if ($n) {
                $toRemove[] = $n;
            }
        }
        foreach ($toRemove as $node) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $vm
     */
    private function fillBasisSpan(DOMXPath $xpath, array $vm): void
    {
        $basis = trim((string) ($vm['basis_note'] ?? ''));
        $tr = $xpath->query('//tr[.//th[@id="1313432914R13"]]')->item(0);
        if (! $tr || $basis === '') {
            return;
        }
        $spans = $xpath->query('.//div[contains(@class, "softmerge-inner")]//span', $tr);
        if ($spans->length < 2) {
            return;
        }
        $second = $spans->item(1);
        if ($second) {
            $this->setTextPreserveNewlines($second, $basis);
        }
    }

    /**
     * @param  array<string, mixed>  $vm
     */
    private function fillDateRows(DOMXPath $xpath, array $vm): void
    {
        $this->setRowValueCell($xpath, '1313432914R16', 2, (string) ($vm['proposed_date'] ?? ''));
        $this->setRowValueCell($xpath, '1313432914R19', 2, (string) ($vm['date_needed'] ?? ''));
    }

    /**
     * @param  array<string, mixed>  $vm
     */
    private function fillUrgentRow(DOMXPath $xpath, array $vm): void
    {
        $tr = $xpath->query('//tr[.//th[@id="1313432914R20"]]')->item(0);
        if (! $tr) {
            return;
        }
        $reason = (string) ($vm['urgent_reason'] ?? '');
        if (! empty($vm['is_urgent']) && $reason !== '') {
            $td = $xpath->query('./td', $tr)->item(4);
            if ($td) {
                $this->setTextPreserveNewlines($td, $reason);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $vm
     */
    private function fillCheckboxUses(DOMXPath $xpath, array $vm): void
    {
        /** @var list<bool> $states */
        $states = $vm['google_checkbox_states'] ?? [];
        $uses = $xpath->query('//*[local-name()="use" and contains(@href, "checkbox-id")]');
        $n = min($uses->length, count($states));
        for ($i = 0; $i < $n; $i++) {
            /** @var DOMElement|null $el */
            $el = $uses->item($i);
            if (! $el instanceof DOMElement) {
                continue;
            }
            $checked = $states[$i];
            $el->setAttribute('href', $checked ? '#checked-checkbox-id' : '#unchecked-checkbox-id');
        }
    }

    /**
     * @param  array<string, mixed>  $vm
     */
    private function fillTripRows(DOMXPath $xpath, array $vm): void
    {
        /** @var array<int, array<string, mixed>> $lines */
        $lines = $vm['trip_lines'] ?? [];
        $rowIds = ['1313432914R39', '1313432914R40', '1313432914R41'];
        $overflow = trim((string) ($vm['overflow_note'] ?? ''));

        foreach ($rowIds as $idx => $rid) {
            $line = $lines[$idx] ?? [];
            $notes = (string) ($line['notes'] ?? '');
            if ($idx === 2 && $overflow !== '') {
                $notes = trim($notes === '' ? $overflow : $notes."\n".$overflow);
            }
            $this->fillOneTripRow($xpath, $rid, array_merge($line, ['notes' => $notes]));
        }
    }

    /**
     * @param  array<string, mixed>  $line
     */
    private function fillOneTripRow(DOMXPath $xpath, string $rowThId, array $line): void
    {
        $tr = $xpath->query('//tr[.//th[@id="'.$rowThId.'"]]')->item(0);
        if (! $tr) {
            return;
        }
        $tds = $xpath->query('./td', $tr);
        if ($tds->length < 14) {
            return;
        }
        $lt = $line['line_total'] ?? '';
        $lt = $lt === '' || $lt === null ? '' : (string) $lt;

        $this->setTdText($tds->item(2), (string) ($line['depart_at'] ?? ''));
        $this->setTdText($tds->item(3), (string) ($line['pickup'] ?? ''));
        $this->setTdText($tds->item(4), (string) ($line['return_at'] ?? ''));
        $this->setTdText($tds->item(5), (string) ($line['dropoff'] ?? ''));
        $this->setTdText($tds->item(6), (string) ($line['guests'] ?? ''));
        $this->setTdText($tds->item(7), '');
        $this->setTdText($tds->item(8), '');
        $this->setTdText($tds->item(9), (string) ($line['person_in_charge'] ?? ''));
        $this->setTdText($tds->item(10), (string) ($line['unit_price'] ?? ''));
        $this->setTdText($tds->item(11), (string) ($line['extra_fee'] ?? ''));
        $this->setTdText($tds->item(12), $lt);
        $this->setTdText($tds->item(13), (string) ($line['notes'] ?? ''));
    }

    private function setTdText(?\DOMNode $td, string $text): void
    {
        if ($td) {
            $this->setTextPreserveNewlines($td, $text);
        }
    }

    /**
     * @param  array<string, mixed>  $vm
     */
    private function fillTotalRow(DOMXPath $xpath, array $vm): void
    {
        $total = (string) ($vm['passenger_total'] ?? '');
        if ($total === '') {
            return;
        }
        $tr = $xpath->query('//tr[.//th[@id="1313432914R44"]]')->item(0);
        if (! $tr) {
            return;
        }
        $tds = $xpath->query('./td', $tr);
        $td = $tds->item(6);
        if ($td) {
            $this->setTextPreserveNewlines($td, $total);
        }
    }

    private function setTextPreserveNewlines(\DOMNode $node, string $text): void
    {
        $doc = $node->ownerDocument;
        while ($node->firstChild) {
            $node->removeChild($node->firstChild);
        }
        $parts = explode("\n", $text);
        foreach ($parts as $i => $part) {
            if ($i > 0 && $doc) {
                $node->appendChild($doc->createElement('br'));
            }
            $node->appendChild($doc ? $doc->createTextNode($part) : new \DOMText($part));
        }
    }

    private function serializeBodyInnerHtml(DOMDocument $dom): string
    {
        $body = $dom->getElementsByTagName('body')->item(0);
        if (! $body) {
            return $dom->saveHTML();
        }
        $out = '';
        foreach ($body->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return '<meta charset="utf-8">'.$out;
    }
}
