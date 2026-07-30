<?php

namespace App\Http\Controllers;

use App\Models\EvidenceItem;
use App\Models\ForensicCase;
use App\Models\Report;
use App\Services\AuditLoggerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    public function generateCoCReport($evidenceId)
    {
        $evidence = EvidenceItem::with(['case', 'uploader', 'currentCustodian', 'transfers.fromUser', 'transfers.toUser'])->findOrFail($evidenceId);
        
        $timestamp = now()->toIso8601String();
        $manifestData = sprintf(
            'EVIDENCE:%s|HASH:%s|CUSTODIAN:%s|GEN_BY:%s|TIME:%s',
            $evidence->evidence_number,
            $evidence->file_hash_sha256,
            $evidence->currentCustodian->name,
            Auth::user()->name,
            $timestamp
        );
        
        $manifestHash = hash('sha256', $manifestData);

        $reportContent = view('reports.coc_pdf', compact('evidence', 'manifestHash', 'timestamp'))->render();
        $fileName = 'CoC_Report_' . $evidence->evidence_number . '_' . time() . '.html';
        $filePath = 'reports/' . $fileName;

        Storage::disk('local')->put($filePath, $reportContent);

        $report = Report::create([
            'case_id' => $evidence->case_id,
            'evidence_item_id' => $evidence->id,
            'type' => 'coc_form',
            'file_path' => $filePath,
            'generated_by' => Auth::id(),
            'generated_at' => $timestamp,
            'manifest_hash' => $manifestHash,
        ]);

        AuditLoggerService::log('generate_report', Report::class, $report->id, [
            'type' => 'coc_form',
            'manifest_hash' => $manifestHash,
        ]);

        return response()->streamDownload(function () use ($reportContent) {
            echo $reportContent;
        }, $fileName);
    }

    public function generateCaseFinalReport($caseId)
    {
        $case = ForensicCase::with([
            'creator',
            'assignedUsers',
            'evidenceItems.uploader',
            'evidenceItems.currentCustodian',
            'evidenceItems.transfers.fromUser',
            'evidenceItems.transfers.toUser'
        ])->findOrFail($caseId);

        $activityLogs = \App\Models\AuditLog::with('user')
            ->where(function ($q) use ($case) {
                $q->where('target_type', ForensicCase::class)->where('target_id', $case->id);
            })
            ->orWhereIn('target_id', $case->evidenceItems->pluck('id'))
            ->latest('id')
            ->get();

        $timestamp = now()->toIso8601String();
        $manifestData = sprintf(
            'CASE:%s|EVD_COUNT:%d|GEN_BY:%s|TIME:%s',
            $case->case_number,
            $case->evidenceItems->count(),
            Auth::user()->name,
            $timestamp
        );
        $manifestHash = hash('sha256', $manifestData);

        $reportContent = view('reports.case_final_pdf', compact('case', 'activityLogs', 'manifestHash', 'timestamp'))->render();
        $fileName = 'Final_Case_Report_' . $case->case_number . '_' . time() . '.html';
        $filePath = 'reports/' . $fileName;

        Storage::disk('local')->put($filePath, $reportContent);

        $report = Report::create([
            'case_id' => $case->id,
            'evidence_item_id' => null,
            'type' => 'final_report',
            'file_path' => $filePath,
            'generated_by' => Auth::id(),
            'generated_at' => $timestamp,
            'manifest_hash' => $manifestHash,
        ]);

        AuditLoggerService::log('generate_case_final_report', Report::class, $report->id, [
            'case_number' => $case->case_number,
            'manifest_hash' => $manifestHash,
        ]);

        return response($reportContent, 200, [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }
}
