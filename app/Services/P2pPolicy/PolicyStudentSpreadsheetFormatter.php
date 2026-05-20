<?php

namespace App\Services\P2pPolicy;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;

final class PolicyStudentSpreadsheetFormatter
{
    private const SHEET_DATA = 0;

    private const SHEET_GUIDE = 1;

    private const SHEET_REFERENCE = 2;

    public static function createWriter(): Writer
    {
        return new Writer(new Options);
    }

    public static function beginDataSheet(Writer $writer, bool $withBanner): void
    {
        $writer->getCurrentSheet()->setName('Danh_sach');
        self::applyColumnWidths($writer);

        $pad = static fn (array $values): array => self::padRow($values);

        if ($withBanner) {
            $writer->addRow(Row::fromValuesWithStyles(
                $pad(['VA Schools — Import học sinh P2P Policy']),
                self::styleBanner(),
            ));
            $writer->getOptions()->mergeCells(1, 1, PolicyStudentSpreadsheetSpec::COLUMN_COUNT, 1, self::SHEET_DATA);

            $writer->addRow(Row::fromValuesWithStyles(
                $pad(['Điền dữ liệu từ dòng 4. Dòng 3 là khóa cột (route_name…) — không sửa. Xem sheet Hướng dẫn & Tham chiếu.']),
                self::styleSubtitle(),
            ));
            $writer->getOptions()->mergeCells(1, 2, PolicyStudentSpreadsheetSpec::COLUMN_COUNT, 2, self::SHEET_DATA);
        }

        $writer->addRow(Row::fromValuesWithStyles(
            $pad(PolicyStudentSpreadsheetSpec::LABELS_VI),
            self::styleLabelHeader(),
        ));

        $writer->addRow(Row::fromValuesWithStyles(
            $pad(PolicyStudentSpreadsheetSpec::KEYS),
            self::styleKeyHeader(),
        ));
    }

    /**
     * @param  list<string|int|float|null>  $values
     */
    public static function dataRow(Writer $writer, array $values, int $index): void
    {
        $style = $index % 2 === 0 ? self::styleDataRowEven() : self::styleDataRowOdd();
        $writer->addRow(Row::fromValuesWithStyles(self::padRow($values), $style));
    }

    public static function writeGuideSheet(Writer $writer): void
    {
        $writer->addNewSheetAndMakeItCurrent();
        $writer->getCurrentSheet()->setName('Huong_dan');
        self::applyColumnWidthsGuide($writer);

        $writer->addRow(Row::fromValuesWithStyles(
            self::padRow(['Hướng dẫn import roster P2P Policy'], 3),
            self::styleGuideTitle(),
        ));
        $writer->getOptions()->mergeCells(1, 1, 3, 1, self::SHEET_GUIDE);

        $writer->addRow(Row::fromValuesWithStyles(
            ['Cột (key)', 'Nhãn tiếng Việt', 'Mô tả / quy tắc'],
            self::styleGuideHeader(),
        ));

        $i = 0;
        foreach (PolicyStudentSpreadsheetSpec::guideRows() as $g) {
            $writer->addRow(Row::fromValuesWithStyles(
                [$g['key'], $g['label_vi'], $g['hint']],
                $i % 2 === 0 ? self::styleGuideRowEven() : self::styleGuideRowOdd(),
            ));
            $i++;
        }
    }

    public static function writeReferenceSheet(Writer $writer): void
    {
        $writer->addNewSheetAndMakeItCurrent();
        $writer->getCurrentSheet()->setName('Tham_chieu');
        self::applyColumnWidthsReference($writer);

        $writer->addRow(Row::fromValuesWithStyles(
            self::padRow(['Giá trị cho phép'], 2),
            self::styleGuideTitle(),
        ));
        $writer->getOptions()->mergeCells(1, 1, 2, 1, self::SHEET_REFERENCE);

        $writer->addRow(Row::fromValuesWithStyles(['Trường', 'Giá trị'], self::styleGuideHeader()));

        $refs = [
            ['direction', 'one_way — một chiều (thường chỉ chuyến sáng)'],
            ['direction', 'two_way — hai chiều sáng & chiều (mặc định)'],
            ['policy_type', 'default — mặc định (hoặc giá trị nội bộ khác nếu có)'],
            ['is_active', '1 — đang active'],
            ['is_active', '0 — ngừng'],
            ['effective_from / effective_to', 'Định dạng YYYY-MM-DD'],
        ];
        foreach ($refs as $i => [$field, $value]) {
            $writer->addRow(Row::fromValuesWithStyles(
                [$field, $value],
                $i % 2 === 0 ? self::styleGuideRowEven() : self::styleGuideRowOdd(),
            ));
        }
    }

    private static function applyColumnWidths(Writer $writer): void
    {
        $options = $writer->getOptions();
        foreach (PolicyStudentSpreadsheetSpec::COLUMN_WIDTHS as $i => $width) {
            $options->setColumnWidth($width, $i + 1);
        }
    }

    private static function applyColumnWidthsGuide(Writer $writer): void
    {
        $options = $writer->getOptions();
        $options->setColumnWidth(22, 1);
        $options->setColumnWidth(20, 2);
        $options->setColumnWidth(56, 3);
    }

    private static function applyColumnWidthsReference(Writer $writer): void
    {
        $options = $writer->getOptions();
        $options->setColumnWidth(28, 1);
        $options->setColumnWidth(48, 2);
    }

    /**
     * @param  list<string|int|float|null>  $values
     * @return list<string|int|float|null>
     */
    private static function padRow(array $values, ?int $count = null): array
    {
        $count ??= PolicyStudentSpreadsheetSpec::COLUMN_COUNT;
        $values = array_values($values);
        while (count($values) < $count) {
            $values[] = '';
        }

        return array_slice($values, 0, $count);
    }

    private static function styleBanner(): Style
    {
        return (new Style)
            ->setFontBold()
            ->setFontSize(14)
            ->setFontName('Calibri')
            ->setFontColor(Color::rgb(255, 255, 255))
            ->setBackgroundColor(Color::rgb(15, 118, 110))
            ->setCellAlignment(CellAlignment::CENTER)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);
    }

    private static function styleSubtitle(): Style
    {
        return (new Style)
            ->setFontSize(10)
            ->setFontName('Calibri')
            ->setFontColor(Color::rgb(15, 118, 110))
            ->setBackgroundColor(Color::rgb(204, 251, 241))
            ->setShouldWrapText()
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);
    }

    private static function styleLabelHeader(): Style
    {
        return (new Style)
            ->setFontBold()
            ->setFontSize(11)
            ->setFontName('Calibri')
            ->setFontColor(Color::rgb(255, 255, 255))
            ->setBackgroundColor(Color::rgb(13, 148, 136))
            ->setCellAlignment(CellAlignment::CENTER)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER)
            ->setShouldWrapText();
    }

    private static function styleKeyHeader(): Style
    {
        return (new Style)
            ->setFontBold()
            ->setFontSize(10)
            ->setFontName('Consolas')
            ->setFontColor(Color::rgb(51, 65, 85))
            ->setBackgroundColor(Color::rgb(226, 232, 240))
            ->setCellAlignment(CellAlignment::LEFT);
    }

    private static function styleDataRowEven(): Style
    {
        return (new Style)
            ->setFontSize(10)
            ->setFontName('Calibri')
            ->setBackgroundColor(Color::rgb(255, 255, 255));
    }

    private static function styleDataRowOdd(): Style
    {
        return (new Style)
            ->setFontSize(10)
            ->setFontName('Calibri')
            ->setBackgroundColor(Color::rgb(248, 250, 252));
    }

    private static function styleGuideTitle(): Style
    {
        return (new Style)
            ->setFontBold()
            ->setFontSize(13)
            ->setFontName('Calibri')
            ->setFontColor(Color::rgb(255, 255, 255))
            ->setBackgroundColor(Color::rgb(109, 40, 217))
            ->setCellAlignment(CellAlignment::CENTER);
    }

    private static function styleGuideHeader(): Style
    {
        return (new Style)
            ->setFontBold()
            ->setFontSize(11)
            ->setFontName('Calibri')
            ->setFontColor(Color::rgb(255, 255, 255))
            ->setBackgroundColor(Color::rgb(124, 58, 237))
            ->setShouldWrapText();
    }

    private static function styleGuideRowEven(): Style
    {
        return (new Style)
            ->setFontSize(10)
            ->setFontName('Calibri')
            ->setBackgroundColor(Color::rgb(245, 243, 255))
            ->setShouldWrapText();
    }

    private static function styleGuideRowOdd(): Style
    {
        return (new Style)
            ->setFontSize(10)
            ->setFontName('Calibri')
            ->setBackgroundColor(Color::rgb(255, 255, 255))
            ->setShouldWrapText();
    }
}
