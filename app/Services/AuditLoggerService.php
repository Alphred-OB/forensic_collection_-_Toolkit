<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditLoggerService
{
    /**
     * Log an action in a cryptographic tamper-evident hash chain.
     */
    public static function log(string $actionType, ?string $targetType = null, ?int $targetId = null, array $details = []): AuditLog
    {
        $userId = Auth::id();

        // Get the latest audit log entry to form the hash chain
        $previousEntry = AuditLog::orderBy('id', 'desc')->first();
        $previousHash = $previousEntry ? $previousEntry->entry_hash : '0000000000000000000000000000000000000000000000000000000000000000';

        $timestamp = now()->toIso8601String();
        $jsonDetails = json_encode($details);

        // Compute hash of the current payload + previous hash
        $payloadToHash = sprintf(
            '%s|%s|%s|%s|%s|%s|%s',
            $userId ?? 'system',
            $actionType,
            $targetType ?? '',
            $targetId ?? '',
            $jsonDetails,
            $timestamp,
            $previousHash
        );

        $entryHash = hash('sha256', $payloadToHash);

        return AuditLog::create([
            'user_id' => $userId,
            'action_type' => $actionType,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'details_json' => $details,
            'entry_hash' => $entryHash,
            'previous_entry_hash' => $previousHash,
            'created_at' => $timestamp,
        ]);
    }

    /**
     * Verify the entire audit log chain integrity.
     */
    public static function verifyChainIntegrity(): array
    {
        $logs = AuditLog::orderBy('id', 'asc')->get();
        $tamperedLogs = [];
        $expectedPreviousHash = '0000000000000000000000000000000000000000000000000000000000000000';

        foreach ($logs as $log) {
            if ($log->previous_entry_hash !== $expectedPreviousHash) {
                $tamperedLogs[] = [
                    'id' => $log->id,
                    'reason' => 'Previous entry hash mismatch',
                ];
            }

            $payloadToHash = sprintf(
                '%s|%s|%s|%s|%s|%s|%s',
                $log->user_id ?? 'system',
                $log->action_type,
                $log->target_type ?? '',
                $log->target_id ?? '',
                json_encode($log->details_json),
                $log->created_at->toIso8601String(),
                $log->previous_entry_hash
            );

            $recalculatedHash = hash('sha256', $payloadToHash);

            if ($log->entry_hash !== $recalculatedHash) {
                $tamperedLogs[] = [
                    'id' => $log->id,
                    'reason' => 'Calculated payload hash mismatch',
                ];
            }

            $expectedPreviousHash = $log->entry_hash;
        }

        return [
            'is_valid' => count($tamperedLogs) === 0,
            'total_entries' => count($logs),
            'tampered_entries' => $tamperedLogs,
        ];
    }
}
