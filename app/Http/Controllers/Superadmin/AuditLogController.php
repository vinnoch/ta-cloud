<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\AuditLogMaintenance;
use App\Services\AuditLogPresenter;
use App\Services\RoleNavigationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function settings(AuditLogMaintenance $maintenance): View
    {
        $navigation = app(RoleNavigationService::class);

        return view('superadmin.audit-settings', [
            'title' => 'Setting Log Sistem',
            'heading' => 'Setting Log Sistem',
            'crumbs' => 'SUPERADMIN • SETTING • LOG SISTEM',
            'navItems' => $navigation->superadminNavItems(),
            'navFooterItems' => $navigation->footerItems(),
            'navRole' => 'superadmin',
            'primaryCta' => null,
            'retentionDays' => $maintenance->retentionDays(),
        ]);
    }

    public function index(Request $request, AuditLogPresenter $presenter): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'topic' => ['nullable', 'string', Rule::in(array_keys(AuditLogPresenter::TOPICS))],
            'actor' => ['nullable', 'string', 'max:100'],
            'days' => ['nullable', 'integer', Rule::in([30, 60, 90])],
        ]);
        $navigation = app(RoleNavigationService::class);
        $logs = AuditLog::query()
            ->with(['actor', 'target'])
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('action', 'like', "%{$search}%")
                        ->orWhere('actor_email', 'like', "%{$search}%")
                        ->orWhereHas('actor', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['topic'] ?? null, fn ($query, string $topic) => $presenter->constrainToTopic($query, $topic))
            ->when($filters['actor'] ?? null, function ($query, string $actor): void {
                $query->where(function ($query) use ($actor): void {
                    $query->where('actor_email', 'like', "%{$actor}%")
                        ->orWhereHas('actor', function ($query) use ($actor): void {
                            $query->where('name', 'like', "%{$actor}%")
                                ->orWhere('email', 'like', "%{$actor}%");
                        });
                });
            })
            ->when($filters['days'] ?? null, fn ($query, int $days) => $query->where('created_at', '>=', now()->subDays($days)))
            ->latest()
            ->paginate(50)
            ->withQueryString();

        $logs->through(function (AuditLog $log) use ($presenter): AuditLog {
            $log->setAttribute('display', $presenter->present($log));

            return $log;
        });

        return view('superadmin.audit', [
            'title' => 'Log Sistem',
            'heading' => 'Log Sistem',
            'crumbs' => 'SUPERADMIN • LOG SISTEM',
            'navItems' => $navigation->superadminNavItems(),
            'navFooterItems' => $navigation->footerItems(),
            'navRole' => 'superadmin',
            'primaryCta' => null,
            'logs' => $logs,
            'auditTopics' => array_keys(AuditLogPresenter::TOPICS),
            'filters' => $filters,
        ]);
    }

    public function updateRetention(Request $request, AuditLogMaintenance $maintenance): RedirectResponse
    {
        $data = $request->validate([
            'log_retention_days' => ['required', 'integer', 'min:1', 'max:65535'],
        ]);
        $deleted = $maintenance->updateRetention($data['log_retention_days'], $request);

        return redirect()->route('superadmin.audit.settings')
            ->with('status', "Penyimpanan log diperbarui. {$deleted} log kedaluwarsa dihapus.");
    }

    public function destroy(Request $request, AuditLogMaintenance $maintenance): RedirectResponse
    {
        $deleted = $maintenance->purgeAll($request);

        return redirect()->route('superadmin.audit.settings')
            ->with('status', "{$deleted} log sistem dihapus. Catatan penghapusan disimpan.");
    }
}
