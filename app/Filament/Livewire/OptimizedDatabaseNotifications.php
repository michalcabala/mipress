<?php

declare(strict_types=1);

namespace App\Filament\Livewire;

use Filament\Livewire\DatabaseNotifications as BaseDatabaseNotifications;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;

class OptimizedDatabaseNotifications extends BaseDatabaseNotifications
{
    private const UNREAD_COUNT_ALIAS = 'mipress_unread_notifications_count';

    private DatabaseNotificationCollection|Paginator|null $resolvedNotifications = null;

    private ?int $resolvedUnreadNotificationsCount = null;

    public function getNotifications(): DatabaseNotificationCollection|Paginator
    {
        if ($this->resolvedNotifications !== null) {
            return $this->resolvedNotifications;
        }

        return $this->resolvedNotifications = parent::getNotifications();
    }

    public function getNotificationsQuery(): Builder|Relation
    {
        $query = parent::getNotificationsQuery();

        $query->addSelect([
            self::UNREAD_COUNT_ALIAS => $this->getUnreadNotificationsCountSubquery(),
        ]);

        return $query;
    }

    public function getUnreadNotificationsCount(): int
    {
        if ($this->resolvedUnreadNotificationsCount !== null) {
            return $this->resolvedUnreadNotificationsCount;
        }

        if ($this->resolvedNotifications !== null) {
            return $this->resolvedUnreadNotificationsCount = $this->extractUnreadNotificationsCount($this->resolvedNotifications);
        }

        /** @phpstan-ignore-next-line */
        return $this->resolvedUnreadNotificationsCount = parent::getNotificationsQuery()->unread()->count();
    }

    private function getUnreadNotificationsCountSubquery(): Builder
    {
        $user = $this->getUser();

        if (! $user) {
            abort(401);
        }

        return DatabaseNotification::query()
            ->selectRaw('count(*)')
            ->where('notifiable_type', $user::class)
            ->where('notifiable_id', $user->getKey())
            ->where('data->format', 'filament')
            ->whereNull('read_at');
    }

    private function extractUnreadNotificationsCount(DatabaseNotificationCollection|Paginator $notifications): int
    {
        $firstNotification = $notifications instanceof Paginator
            ? collect($notifications->items())->first()
            : $notifications->first();

        if (! $firstNotification instanceof DatabaseNotification) {
            return 0;
        }

        return (int) ($firstNotification->getAttribute(self::UNREAD_COUNT_ALIAS) ?? 0);
    }
}
