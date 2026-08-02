<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\EvidenceItem;
use App\Models\ForensicCase;
use App\Services\AuditLoggerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $action = $request->input('action');
        $userId = $request->input('user_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = AuditLog::with('user');

        $user = Auth::user();
        if (!in_array($user->role, ['Administrator', 'Reviewer'])) {
            $assignedCaseIds = $user->assignedCases()->pluck('cases.id');
            $assignedEvidenceIds = EvidenceItem::whereIn('case_id', $assignedCaseIds)->pluck('id');

            $query->where(function ($q) use ($user, $assignedCaseIds, $assignedEvidenceIds) {
                $q->where('user_id', $user->id)
                  ->orWhere(function ($q2) use ($assignedCaseIds) {
                      $q2->where('target_type', ForensicCase::class)->whereIn('target_id', $assignedCaseIds);
                  })
                  ->orWhere(function ($q2) use ($assignedEvidenceIds) {
                      $q2->where('target_type', EvidenceItem::class)->whereIn('target_id', $assignedEvidenceIds);
                  });
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('action_type', 'like', "%{$search}%")
                  ->orWhere('entry_hash', 'like', "%{$search}%")
                  ->orWhere('details', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($action) {
            if ($action === 'view') {
                $query->where('action_type', 'like', 'view%');
            } elseif ($action === 'create') {
                $query->where(function ($q) {
                    $q->where('action_type', 'like', 'create%')->orWhere('action_type', 'like', 'upload%');
                });
            } elseif ($action === 'edit') {
                $query->where(function ($q) {
                    $q->where('action_type', 'like', 'update%')->orWhere('action_type', 'like', 'edit%');
                });
            } elseif ($action === 'delete') {
                $query->where('action_type', 'like', '%delete%');
            } else {
                $query->where('action_type', $action);
            }
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $logs = $query->latest('id')->paginate(50)->withQueryString();
        $users = \App\Models\User::all();
        $verificationResult = AuditLoggerService::verifyChainIntegrity();

        return view('audit.index', compact('logs', 'users', 'verificationResult', 'search', 'action', 'userId', 'dateFrom', 'dateTo'));
    }

    public function runSystemScan()
    {
        // 1. Audit Chain Verification
        $chainResult = AuditLoggerService::verifyChainIntegrity();

        // 2. Physical Evidence Files Scan
        $evidenceItems = EvidenceItem::all();
        $corruptedEvidence = [];
        $verifiedCount = 0;

        foreach ($evidenceItems as $item) {
            if (!$item->verifyIntegrity()) {
                $corruptedEvidence[] = [
                    'id' => $item->id,
                    'number' => $item->evidence_number,
                    'file' => $item->file_name,
                ];
            } else {
                $verifiedCount++;
            }
        }

        $scanResults = [
            'chain_valid' => $chainResult['is_valid'],
            'total_audit_records' => $chainResult['total_entries'],
            'total_evidence_files' => $evidenceItems->count(),
            'verified_evidence_files' => $verifiedCount,
            'corrupted_evidence_files' => count($corruptedEvidence),
            'corrupted_items' => $corruptedEvidence,
            'scanned_at' => now()->format('Y-m-d H:i:s'),
        ];

        AuditLoggerService::log('admin_system_integrity_scan', null, null, [
            'chain_valid' => $chainResult['is_valid'],
            'corrupted_files_count' => count($corruptedEvidence),
        ]);

        return redirect()->back()->with('scan_results', $scanResults);
    }

    public function exportCsv(): StreamedResponse
    {
        $fileName = 'forensic_audit_trail_' . now()->format('Ymd_His') . '.csv';
        $logs = AuditLog::with('user')->latest('id')->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename={$fileName}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Log ID', 'User Name', 'User Email', 'Action Type', 'Target Entity', 'Entry Hash (SHA-256)', 'Previous Entry Hash', 'Timestamp'];

        $callback = function() use($logs, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($logs as $log) {
                $row = [
                    $log->id,
                    $log->user ? $log->user->name : 'System',
                    $log->user ? $log->user->email : 'N/A',
                    $log->action_type,
                    $log->target_type ? class_basename($log->target_type) . ' #' . $log->target_id : 'N/A',
                    $log->entry_hash,
                    $log->previous_entry_hash,
                    $log->created_at->format('Y-m-d H:i:s'),
                ];

                fputcsv($file, $row);
            }

            fclose($file);
        };

        AuditLoggerService::log('admin_export_audit_csv', null, null, [
            'exported_records_count' => $logs->count(),
        ]);

        return response()->stream($callback, 200, $headers);
    }
}
