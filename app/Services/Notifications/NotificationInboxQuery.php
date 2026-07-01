<?php

namespace App\Services\Notifications;

use App\Models\DispatchRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

final class NotificationInboxQuery
{
    /**
     * Ẩn thông báo gắn phiếu đã soft-delete hoặc đã hủy (cancelled).
     *
     * @param  Builder<\Illuminate\Notifications\DatabaseNotification>|Relation<\Illuminate\Notifications\DatabaseNotification, mixed, mixed>  $query
     * @return Builder<\Illuminate\Notifications\DatabaseNotification>|Relation<\Illuminate\Notifications\DatabaseNotification, mixed, mixed>
     */
    public static function excludeRemovedDispatchRequests(Builder|Relation $query): Builder|Relation
    {
        $visibleDispatchRequestIds = DispatchRequest::query()
            ->select('id')
            ->visibleOnPortalRequestIndex();

        return $query->where(function (Builder $outer) use ($visibleDispatchRequestIds) {
            $outer->whereNull('data->dispatch_request_id')
                ->orWhereIn('data->dispatch_request_id', $visibleDispatchRequestIds);
        });
    }
}
