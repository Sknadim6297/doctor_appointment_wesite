<?php

namespace App\Services;

use App\Models\AdminActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    public function __construct(private readonly ClientContextService $clientContextService)
    {
    }

    public function log(
        Request $request,
        string $moduleKey,
        string $action,
        ?Model $subject = null,
        ?User $owner = null,
        ?string $description = null,
        array $metadata = []
    ): AdminActivityLog {
        $context = $this->clientContextService->fromRequest($request);
        $actor = Auth::user();

        return AdminActivityLog::query()->create([
            'actor_user_id' => $actor?->id,
            'owner_user_id' => $owner?->id,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'module_key' => $moduleKey,
            'action' => $action,
            'description' => $description,
            'device_type' => $context['device_type'],
            'device_name' => $context['device_name'],
            'browser_name' => trim(($context['browser_name'] ?? 'Unknown') . ' ' . ($context['browser_version'] ?? '')),
            'ip_address' => $context['ip_address'],
            'user_agent' => $context['user_agent'],
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);
    }
}