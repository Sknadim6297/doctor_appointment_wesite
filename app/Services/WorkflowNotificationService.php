<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\User;
use App\Models\WorkflowNotification;
use Illuminate\Support\Collection;

class WorkflowNotificationService
{
    public function notifyUser(
        User $recipient,
        string $type,
        string $title,
        ?string $body = null,
        ?Enrollment $enrollment = null,
        ?User $actor = null,
        ?string $actionUrl = null,
        array $metadata = [],
    ): WorkflowNotification {
        return WorkflowNotification::create([
            'user_id' => $recipient->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'action_url' => $actionUrl,
            'enrollment_id' => $enrollment?->id,
            'actor_user_id' => $actor?->id,
            'metadata' => $metadata,
        ]);
    }

    public function notifyAdmins(
        string $type,
        string $title,
        ?string $body = null,
        ?Enrollment $enrollment = null,
        ?User $actor = null,
        ?string $actionUrl = null,
        array $metadata = [],
    ): void {
        foreach ($this->adminRecipients() as $admin) {
            if ($actor && (int) $admin->id === (int) $actor->id) {
                continue;
            }

            $this->notifyUser($admin, $type, $title, $body, $enrollment, $actor, $actionUrl, $metadata);
        }
    }

    public function notifyEnrollmentOwner(
        Enrollment $enrollment,
        string $type,
        string $title,
        ?string $body = null,
        ?User $actor = null,
        ?string $actionUrl = null,
        array $metadata = [],
    ): void {
        $ownerId = (int) ($enrollment->created_by ?? $enrollment->agent_id ?? 0);
        if ($ownerId <= 0) {
            return;
        }

        $owner = User::query()->find($ownerId);
        if (!$owner) {
            return;
        }

        $this->notifyUser($owner, $type, $title, $body, $enrollment, $actor, $actionUrl, $metadata);
    }

    /**
     * @return Collection<int, User>
     */
    public function adminRecipients(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->where(function ($q): void {
                $q->whereIn('role', ['admin', 'super_admin']);
            })
            ->get();
    }

    public function unreadCount(?User $user): int
    {
        if (!$user) {
            return 0;
        }

        return WorkflowNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, WorkflowNotification>
     */
    public function recentForUser(User $user, int $limit = 20)
    {
        return WorkflowNotification::query()
            ->with(['actor', 'enrollment'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function markRead(int $notificationId, User $user): bool
    {
        $notification = WorkflowNotification::query()
            ->where('user_id', $user->id)
            ->whereKey($notificationId)
            ->first();

        if (!$notification || $notification->read_at) {
            return false;
        }

        $notification->update(['read_at' => now()]);

        return true;
    }

    public function markAllRead(User $user): int
    {
        return WorkflowNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
