<?php

namespace App\Support;

use App\Models\ExpenseDocument;
use App\Models\ExpenseDocumentLine;
use App\Models\Trip;
use App\Models\TripCost;

final class FinancialDataLock
{
    public static function tripIsPaid(Trip $trip): bool
    {
        return ($trip->payment_status ?? 'unpaid') === 'paid';
    }

    public static function assertTripNotPaid(Trip $trip): void
    {
        if (self::tripIsPaid($trip)) {
            abort(409, Messages::TRIP_FINANCIALLY_LOCKED);
        }
    }

    /** Chuyến completed / cancelled: không chỉnh danh sách hành khách hoặc chi phí trong luồng chuyến đang chạy. */
    public static function assertTripAllowsPassengerAndCostEdits(Trip $trip): void
    {
        $s = (string) ($trip->status ?? '');

        if (in_array($s, ['completed', 'cancelled'], true)) {
            abort(409, Messages::TRIP_TERMINAL_NO_LIST_OR_COST_EDITS);
        }
    }

    /** Tài xế ghi chi phí phát sinh gắn chuyến (kể cả chuyến đã hoàn thành, chưa thanh toán). */
    public static function assertTripAllowsNewDriverCost(Trip $trip): void
    {
        self::assertTripNotPaid($trip);

        if ((string) ($trip->status ?? '') === 'cancelled') {
            abort(409, Messages::TRIP_TERMINAL_NO_LIST_OR_COST_EDITS);
        }
    }

    /**
     * Block new receipts on a cost row once accountant confirmed (BRD: lock after confirm).
     */
    /** Chi phí đã nằm trong hồ sơ chi phí đã chốt (đã nộp / đã ký / đã thanh toán) → không sửa, không xóa. */
    public static function tripCostLockedByExpenseDocument(TripCost $cost): bool
    {
        return ExpenseDocumentLine::query()
            ->where('trip_cost_id', $cost->id)
            ->whereHas('document', fn ($q) => $q->whereIn('status', ExpenseDocument::LOCKING_STATUSES))
            ->exists();
    }

    public static function assertTripCostNotLockedByExpense(TripCost $cost): void
    {
        if (self::tripCostLockedByExpenseDocument($cost)) {
            abort(409, 'Chi phí đã nằm trong hồ sơ đề nghị thanh toán đã nộp — không thể sửa/xóa.');
        }
    }

    public static function assertExpenseDocumentEditable(ExpenseDocument $doc): void
    {
        if (! $doc->isEditable()) {
            abort(409, $doc->status === ExpenseDocument::STATUS_PAID
                ? 'Hồ sơ đã thanh toán — dữ liệu đã khóa.'
                : 'Hồ sơ đã nộp — không thể chỉnh sửa. Liên hệ kế toán để được trả lại nếu cần sửa.');
        }
    }

    public static function tripCostAllowsDriverEdits(TripCost $cost): bool
    {
        if (! in_array($cost->status, ['draft', 'submitted'], true)) {
            return false;
        }

        if (self::tripCostLockedByExpenseDocument($cost)) {
            return false;
        }

        $cost->loadMissing('trip');

        if ($cost->trip === null) {
            return true;
        }

        if (self::tripIsPaid($cost->trip)) {
            return false;
        }

        $s = (string) ($cost->trip->status ?? '');

        return $s !== 'cancelled';
    }

    public static function assertTripCostAllowsDriverEdits(TripCost $cost): void
    {
        if (! self::tripCostAllowsDriverEdits($cost)) {
            if (self::tripCostLockedByExpenseDocument($cost)) {
                abort(409, 'Chi phí đã nằm trong hồ sơ đề nghị thanh toán đã nộp — không thể sửa/xóa.');
            }
            if ($cost->trip !== null && self::tripIsPaid($cost->trip)) {
                abort(409, Messages::TRIP_FINANCIALLY_LOCKED);
            }
            abort(409, Messages::TRIP_TERMINAL_NO_LIST_OR_COST_EDITS);
        }
    }

    public static function assertTripCostAllowsNewAttachment(TripCost $cost): void
    {
        $cost->loadMissing('trip');

        if ($cost->trip !== null) {
            self::assertTripNotPaid($cost->trip);

            if ((string) ($cost->trip->status ?? '') === 'cancelled') {
                abort(409, Messages::TRIP_TERMINAL_NO_LIST_OR_COST_EDITS);
            }
        }

        if ($cost->status === 'confirmed') {
            abort(409, Messages::COST_CONFIRMED_NO_ATTACHMENT);
        }

        if (! in_array($cost->status, ['draft', 'submitted'], true)) {
            abort(409, Messages::COST_NOT_ACTIONABLE);
        }
    }
}
