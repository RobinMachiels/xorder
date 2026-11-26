<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\I18n;
use App\Models\Order;
use App\Models\User;
use RuntimeException;
use ZipArchive;

/**
 * Zero-dependency OOXML (.xlsx) workbook generator producing styled multi-sheet local backups.
 */
final class XlsxExporter
{
    private I18n $i18n;

    public function __construct(I18n $i18n)
    {
        $this->i18n = $i18n;
    }

    /**
     * @param array<int, Order> $orders
     * @param array<int, array{pickup_date: string, product_name: string, unit: string, total_qty: float, order_count: int}> $kitchenRows
     */
    public function generate(User $user, array $orders, array $kitchenRows): string
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP ZipArchive-extensie is vereist voor Excel-export.');
        }

        $sheets = [
            [
                'name' => $this->sanitizeSheetName($this->i18n->t('xlsx.sheet_orders')),
                'widths' => [13, 13, 15, 24, 16, 24, 18, 18, 13, 13, 15, 42, 32],
                'freeze' => true,
                'rows' => $this->buildOrdersRows($orders),
            ],
            [
                'name' => $this->sanitizeSheetName($this->i18n->t('xlsx.sheet_kitchen')),
                'widths' => [14, 42, 12, 16, 18],
                'freeze' => true,
                'rows' => $this->buildKitchenRows($kitchenRows),
            ],
            [
                'name' => $this->sanitizeSheetName($this->i18n->t('xlsx.sheet_lines')),
                'widths' => [13, 13, 24, 38, 12, 12, 14, 14],
                'freeze' => true,
                'rows' => $this->buildDetailRows($orders),
            ],
            [
                'name' => $this->sanitizeSheetName($this->i18n->t('xlsx.sheet_info')),
                'widths' => [28, 46],
                'freeze' => false,
                'rows' => $this->buildInfoRows($user, count($orders)),
            ],
        ];

        $tmpFile = tempnam(sys_get_temp_dir(), 'xorder_xlsx_');
        if ($tmpFile === false) {
            throw new RuntimeException('Kan tijdelijk bestand niet aanmaken voor Excel-backup.');
        }

        $zip = new ZipArchive();
        if ($zip->open($tmpFile, ZipArchive::OVERWRITE | ZipArchive::CREATE) !== true) {
            @unlink($tmpFile);
            throw new RuntimeException('Kan Excel-archief niet openen.');
        }

        $zip->addFromString('[Content_Types].xml', $this->buildContentTypesXml(count($sheets)));
        $zip->addFromString('_rels/.rels', $this->buildRootRelsXml());
        $zip->addFromString('xl/workbook.xml', $this->buildWorkbookXml($sheets));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->buildWorkbookRelsXml(count($sheets)));
        $zip->addFromString('xl/styles.xml', $this->buildStylesXml());

        foreach ($sheets as $index => $sheet) {
            $sheetNumber = $index + 1;
            $zip->addFromString(
                'xl/worksheets/sheet' . $sheetNumber . '.xml',
                $this->buildWorksheetXml($sheet['rows'], $sheet['widths'], $sheet['freeze'])
            );
        }

        $zip->close();

        $binary = file_get_contents($tmpFile);
        @unlink($tmpFile);

        if ($binary === false) {
            throw new RuntimeException('Kan gegenereerd Excel-bestand niet lezen.');
        }

        return $binary;
    }

    /**
     * @param array<int, Order> $orders
     * @return array<int, array<int, array{type: string, value: string|float|int, style: int}>>
     */
    private function buildOrdersRows(array $orders): array
    {
        $headers = [
            $this->i18n->t('xlsx.col_order_no'),
            $this->i18n->t('xlsx.col_pickup_date'),
            $this->i18n->t('xlsx.col_pickup_slot'),
            $this->i18n->t('xlsx.col_customer'),
            $this->i18n->t('xlsx.col_phone'),
            $this->i18n->t('xlsx.col_email'),
            $this->i18n->t('xlsx.col_status'),
            $this->i18n->t('xlsx.col_payment'),
            $this->i18n->t('xlsx.col_deposit'),
            $this->i18n->t('xlsx.col_total'),
            $this->i18n->t('xlsx.col_balance'),
            $this->i18n->t('xlsx.col_items_summary'),
            $this->i18n->t('xlsx.col_notes'),
        ];

        $rows = [$this->makeHeaderRow($headers)];

        foreach ($orders as $order) {
            $rows[] = [
                $this->strCell($order->orderNumber),
                $this->strCell($order->pickupDate),
                $this->strCell($order->pickupSlot),
                $this->strCell($order->customerName),
                $this->strCell($order->customerPhone),
                $this->strCell($order->customerEmail),
                $this->strCell($this->i18n->t('status.' . $order->status)),
                $this->strCell($this->i18n->t('payment.' . $order->paymentStatus)),
                $this->numCell($order->depositAmount, 2),
                $this->numCell($order->totalAmount, 2),
                $this->numCell($order->remainingBalance(), 2),
                $this->strCell($order->itemsSummary()),
                $this->strCell($order->notes),
            ];
        }

        return $rows;
    }

    /**
     * @param array<int, array{pickup_date: string, product_name: string, unit: string, total_qty: float, order_count: int}> $kitchenRows
     * @return array<int, array<int, array{type: string, value: string|float|int, style: int}>>
     */
    private function buildKitchenRows(array $kitchenRows): array
    {
        $headers = [
            $this->i18n->t('xlsx.col_pickup_date'),
            $this->i18n->t('xlsx.col_product'),
            $this->i18n->t('xlsx.col_unit'),
            $this->i18n->t('xlsx.col_qty'),
            $this->i18n->t('xlsx.col_orders_cnt'),
        ];

        $rows = [$this->makeHeaderRow($headers)];

        foreach ($kitchenRows as $item) {
            $rows[] = [
                $this->strCell($item['pickup_date']),
                $this->strCell($item['product_name']),
                $this->strCell($item['unit']),
                $this->numCell($item['total_qty'], 0),
                $this->numCell($item['order_count'], 0),
            ];
        }

        return $rows;
    }

    /**
     * @param array<int, Order> $orders
     * @return array<int, array<int, array{type: string, value: string|float|int, style: int}>>
     */
    private function buildDetailRows(array $orders): array
    {
        $headers = [
            $this->i18n->t('xlsx.col_order_no'),
            $this->i18n->t('xlsx.col_pickup_date'),
            $this->i18n->t('xlsx.col_customer'),
            $this->i18n->t('xlsx.col_product'),
            $this->i18n->t('xlsx.col_unit'),
            $this->i18n->t('xlsx.col_qty'),
            $this->i18n->t('xlsx.col_unit_price'),
            $this->i18n->t('xlsx.col_line_total'),
        ];

        $rows = [$this->makeHeaderRow($headers)];

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $rows[] = [
                    $this->strCell($order->orderNumber),
                    $this->strCell($order->pickupDate),
                    $this->strCell($order->customerName),
                    $this->strCell($item->productName),
                    $this->strCell($item->unit),
                    $this->numCell($item->quantity, 0),
                    $this->numCell($item->unitPrice, 2),
                    $this->numCell($item->lineTotal, 2),
                ];
            }
        }

        return $rows;
    }

    /**
     * @return array<int, array<int, array{type: string, value: string|float|int, style: int}>>
     */
    private function buildInfoRows(User $user, int $orderCount): array
    {
        return [
            [
                $this->strCell($this->i18n->t('xlsx.info_business'), 1),
                $this->strCell($user->businessName, 1),
            ],
            [
                $this->strCell($this->i18n->t('xlsx.info_sector'), 3),
                $this->strCell($this->i18n->t('auth.sector_' . $user->sector)),
            ],
            [
                $this->strCell($this->i18n->t('xlsx.info_exported_at'), 3),
                $this->strCell(date('Y-m-d H:i:s')),
            ],
            [
                $this->strCell($this->i18n->t('xlsx.info_total_orders'), 3),
                $this->numCell($orderCount, 0),
            ],
            [
                $this->strCell($this->i18n->t('xlsx.info_source'), 3),
                $this->strCell('XOrder by AssurIO (Feestseizoen 2026)'),
            ],
        ];
    }

    /**
     * @param array<int, string> $headers
     * @return array<int, array{type: string, value: string|float|int, style: int}>
     */
    private function makeHeaderRow(array $headers): array
    {
        $row = [];
        foreach ($headers as $header) {
            $row[] = $this->strCell($header, 1);
        }

        return $row;
    }

    /**
     * @return array{type: string, value: string, style: int}
     */
    private function strCell(string $value, int $style = 0): array
    {
        return ['type' => 'str', 'value' => $value, 'style' => $style];
    }

    /**
     * @return array{type: string, value: float|int, style: int}
     */
    private function numCell(float|int $value, int $style = 0): array
    {
        return ['type' => 'num', 'value' => $value, 'style' => $style];
    }

    private function buildContentTypesXml(int $sheetCount): string
    {
        $overrides = '';
        for ($i = 1; $i <= $sheetCount; $i++) {
            $overrides .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . $overrides
            . '</Types>';
    }

    private function buildRootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    /**
     * @param array<int, array{name: string}> $sheets
     */
    private function buildWorkbookXml(array $sheets): string
    {
        $sheetNodes = '';
        foreach ($sheets as $index => $sheet) {
            $id = $index + 1;
            $sheetNodes .= '<sheet name="' . $this->escapeXml($sheet['name']) . '" sheetId="' . $id . '" r:id="rId' . $id . '"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $sheetNodes . '</sheets>'
            . '</workbook>';
    }

    private function buildWorkbookRelsXml(int $sheetCount): string
    {
        $rels = '';
        for ($i = 1; $i <= $sheetCount; $i++) {
            $rels .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
        }
        $styleRelId = $sheetCount + 1;
        $rels .= '<Relationship Id="rId' . $styleRelId . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $rels
            . '</Relationships>';
    }

    private function buildStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1">'
            . '<numFmt numFmtId="164" formatCode="&quot;€ &quot;#,##0.00"/>'
            . '</numFmts>'
            . '<fonts count="3">'
            . '<font><sz val="11"/><color rgb="FF1F2933"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FF14332B"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF14332B"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '</borders>'
            . '<cellStyleXfs count="1">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>'
            . '</cellStyleXfs>'
            . '<cellXfs count="4">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '</cellXfs>'
            . '</styleSheet>';
    }

    /**
     * @param array<int, array<int, array{type: string, value: string|float|int, style: int}>> $rows
     * @param array<int, int|float> $widths
     */
    private function buildWorksheetXml(array $rows, array $widths, bool $freezeTopRow): string
    {
        $colsXml = '<cols>';
        foreach ($widths as $index => $width) {
            $colNum = $index + 1;
            $colsXml .= '<col min="' . $colNum . '" max="' . $colNum . '" width="' . $width . '" customWidth="1"/>';
        }
        $colsXml .= '</cols>';

        $sheetViewsXml = '<sheetViews><sheetView workbookViewId="0">';
        if ($freezeTopRow) {
            $sheetViewsXml .= '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>';
        }
        $sheetViewsXml .= '</sheetView></sheetViews>';

        $sheetDataXml = '<sheetData>';
        $maxCols = 1;
        foreach ($rows as $rowIndex => $cells) {
            $rowNumber = $rowIndex + 1;
            $maxCols = max($maxCols, count($cells));
            $heightAttr = $rowNumber === 1 ? ' ht="24" customHeight="1"' : '';
            $sheetDataXml .= '<row r="' . $rowNumber . '"' . $heightAttr . '>';

            foreach ($cells as $colIndex => $cell) {
                $ref = $this->columnLetter($colIndex) . $rowNumber;
                $styleAttr = $cell['style'] > 0 ? ' s="' . $cell['style'] . '"' : '';

                if ($cell['type'] === 'num') {
                    $sheetDataXml .= '<c r="' . $ref . '"' . $styleAttr . '><v>' . $cell['value'] . '</v></c>';
                } else {
                    $text = $this->escapeXml((string) $cell['value']);
                    $sheetDataXml .= '<c r="' . $ref . '" t="inlineStr"' . $styleAttr . '><is><t xml:space="preserve">' . $text . '</t></is></c>';
                }
            }

            $sheetDataXml .= '</row>';
        }
        $sheetDataXml .= '</sheetData>';

        $autoFilterXml = '';
        if ($freezeTopRow && count($rows) > 0) {
            $lastCol = $this->columnLetter($maxCols - 1);
            $lastRow = count($rows);
            $autoFilterXml = '<autoFilter ref="A1:' . $lastCol . $lastRow . '"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . $sheetViewsXml
            . '<sheetFormatPr defaultRowHeight="19"/>'
            . $colsXml
            . $sheetDataXml
            . $autoFilterXml
            . '</worksheet>';
    }

    private function columnLetter(int $index): string
    {
        $letter = '';
        $num = $index + 1;
        while ($num > 0) {
            $mod = ($num - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $num = intdiv($num - 1, 26);
        }

        return $letter;
    }

    private function sanitizeSheetName(string $name): string
    {
        $cleaned = preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', '-', $name) ?: 'Sheet';
        if (function_exists('mb_substr')) {
            return mb_substr($cleaned, 0, 31, 'UTF-8');
        }

        return substr($cleaned, 0, 31);
    }

    private function escapeXml(string $value): string
    {
        // Strip invalid XML 1.0 control characters (keep tab, LF, CR)
        $clean = preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $value);
        if ($clean === null) {
            $clean = '';
        }

        return htmlspecialchars($clean, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
