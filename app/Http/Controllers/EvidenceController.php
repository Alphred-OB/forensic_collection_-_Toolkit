<?php

namespace App\Http\Controllers;

use App\Models\EvidenceItem;
use App\Models\ForensicCase;
use App\Services\AuditLoggerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EvidenceController extends Controller
{
    public function create($caseId)
    {
        $case = ForensicCase::findOrFail($caseId);
        if (!$case->isEditable()) {
            abort(403, 'Action prohibited: Cannot upload evidence to a closed or archived case.');
        }
        $parentItems = EvidenceItem::where('case_id', $case->id)->get();
        return view('evidence.create', compact('case', 'parentItems'));
    }

    public function store(Request $request, $caseId)
    {
        $case = ForensicCase::findOrFail($caseId);
        if (!$case->isEditable()) {
            abort(403, 'Action prohibited: Cannot upload evidence to a closed or archived case.');
        }

        $request->validate([
            'evidence_number' => 'required|string|unique:evidence_items,evidence_number',
            'parent_id' => 'nullable|exists:evidence_items,id',
            'description' => 'required|string',
            'source_device' => 'required|string',
            'classification' => 'required|in:original,forensic_copy,export,screenshot,reconstructed,custom',
            'custom_classification' => 'nullable|required_if:classification,custom|string|max:100',
            'evidence_file' => 'required|file|max:1048576', // up to 1GB
            'collected_at' => 'required|date',
            'collected_location' => 'required|string',
        ]);

        $file = $request->file('evidence_file');
        $filePath = $file->store('evidence_vault/' . $case->id, 'local');
        $fullPath = Storage::disk('local')->path($filePath);

        // Compute mandatory SHA-256 hash immediately upon upload
        $sha256Hash = hash_file('sha256', $fullPath);

        $evidence = EvidenceItem::create([
            'case_id' => $case->id,
            'parent_id' => $request->parent_id ?: null,
            'evidence_number' => $request->evidence_number,
            'description' => $request->description,
            'source_device' => $request->source_device,
            'classification' => $request->classification,
            'custom_classification' => $request->classification === 'custom' ? $request->custom_classification : null,
            'file_path' => $filePath,
            'file_name' => $file->getClientOriginalName(),
            'file_hash_sha256' => $sha256Hash,
            'file_size' => $file->getSize(),
            'uploaded_by' => Auth::id(),
            'collected_at' => $request->collected_at,
            'collected_location' => $request->collected_location,
            'current_custodian_id' => Auth::id(),
        ]);

        AuditLoggerService::log('upload_evidence', EvidenceItem::class, $evidence->id, [
            'evidence_number' => $evidence->evidence_number,
            'file_name' => $evidence->file_name,
            'hash_sha256' => $sha256Hash,
            'file_size' => $evidence->file_size,
        ]);

        return redirect()->route('cases.show', $case->id)->with('success', 'Evidence uploaded and cryptographic SHA-256 hash generated.');
    }

    public function show($id)
    {
        $evidence = EvidenceItem::with(['case', 'uploader', 'currentCustodian', 'transfers.fromUser', 'transfers.toUser'])->findOrFail($id);
        
        $isIntegrityValid = $evidence->verifyIntegrity();

        $fileMetadata = [
            'mime_type' => 'Unknown',
            'extension' => strtoupper(pathinfo($evidence->file_name, PATHINFO_EXTENSION) ?: 'BIN'),
            'last_modified' => 'N/A',
            'exists_on_disk' => false,
        ];

        $hexData = [];
        $exifData = [];

        if (Storage::disk('local')->exists($evidence->file_path)) {
            $physicalPath = Storage::disk('local')->path($evidence->file_path);
            $fileMetadata['exists_on_disk'] = true;
            $fileMetadata['mime_type'] = Storage::disk('local')->mimeType($evidence->file_path) ?: 'application/octet-stream';
            $fileMetadata['last_modified'] = date('Y-m-d H:i:s', Storage::disk('local')->lastModified($evidence->file_path));

            // Generate Hex Dump (First 512 bytes)
            $handle = fopen($physicalPath, 'rb');
            if ($handle) {
                $rawChunk = fread($handle, 512);
                fclose($handle);

                for ($offset = 0; $offset < strlen($rawChunk); $offset += 16) {
                    $slice = substr($rawChunk, $offset, 16);
                    $hexBytes = [];
                    $asciiBytes = '';

                    for ($i = 0; $i < strlen($slice); $i++) {
                        $byte = ord($slice[$i]);
                        $hexBytes[] = sprintf('%02X', $byte);
                        $asciiBytes .= ($byte >= 32 && $byte <= 126) ? chr($byte) : '.';
                    }

                    $hexData[] = [
                        'offset' => sprintf('%08X', $offset),
                        'hex' => implode(' ', $hexBytes),
                        'ascii' => $asciiBytes,
                    ];
                }
            }

            // Extract Image Dimensions / EXIF if image
            if (str_starts_with($fileMetadata['mime_type'], 'image/')) {
                $sizeInfo = @getimagesize($physicalPath);
                if ($sizeInfo) {
                    $exifData['Image Dimensions'] = $sizeInfo[0] . ' x ' . $sizeInfo[1] . ' pixels';
                    $exifData['Bits / Color Channel'] = ($sizeInfo['bits'] ?? 'N/A') . ' bits';
                }
            }
        }

        AuditLoggerService::log('view_evidence', EvidenceItem::class, $evidence->id, [
            'evidence_number' => $evidence->evidence_number,
            'integrity_valid' => $isIntegrityValid,
        ]);

        return view('evidence.show', compact('evidence', 'isIntegrityValid', 'fileMetadata', 'hexData', 'exifData'));
    }

    public function download($id)
    {
        $evidence = EvidenceItem::findOrFail($id);

        if (!$evidence->verifyIntegrity()) {
            AuditLoggerService::log('download_evidence_tamper_alert', EvidenceItem::class, $evidence->id, [
                'evidence_number' => $evidence->evidence_number,
                'status' => 'TAMPER_DETECTED',
            ]);
            return redirect()->back()->with('error', 'CRITICAL ALERT: File integrity verification failed! Download blocked.');
        }

        AuditLoggerService::log('download_evidence', EvidenceItem::class, $evidence->id, [
            'evidence_number' => $evidence->evidence_number,
            'hash_sha256' => $evidence->file_hash_sha256,
        ]);

        return Storage::disk('local')->download($evidence->file_path, $evidence->file_name);
    }

    public function exportBatchZip($caseId)
    {
        $case = ForensicCase::with('evidenceItems')->findOrFail($caseId);

        if ($case->evidenceItems->isEmpty()) {
            return redirect()->back()->with('error', 'No evidence items recorded in this case to export.');
        }

        $zipFileName = 'Evidence_Vault_Batch_' . $case->case_number . '_' . time() . '.zip';
        $tempZipPath = storage_path('app/temp_' . $zipFileName);

        $zip = new \ZipArchive();
        if ($zip->open($tempZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return redirect()->back()->with('error', 'Failed to initialize ZIP archive.');
        }

        $manifestLines = [];
        $manifestLines[] = "=================================================================";
        $manifestLines[] = "FORENSIC TOOLKIT -- CRYPTOGRAPHIC BATCH MANIFEST CHECKSUM";
        $manifestLines[] = "CASE ID: {$case->case_number} | TITLE: {$case->title}";
        $manifestLines[] = "EXPORT TIMESTAMP: " . now()->toIso8601String();
        $manifestLines[] = "EXPORTED BY: " . Auth::user()->name . " (" . Auth::user()->email . ")";
        $manifestLines[] = "=================================================================\n";

        foreach ($case->evidenceItems as $item) {
            if (Storage::disk('local')->exists($item->file_path)) {
                $physicalPath = Storage::disk('local')->path($item->file_path);
                $entryZipPath = $item->evidence_number . '_' . $item->file_name;
                $zip->addFile($physicalPath, $entryZipPath);

                $liveHash = hash_file('sha256', $physicalPath);
                $status = hash_equals($item->file_hash_sha256, $liveHash) ? "VERIFIED_OK" : "CORRUPTED_TAMPERED";

                $manifestLines[] = sprintf(
                    "ITEM: %s | FILE: %s | SHA256: %s | STATUS: %s",
                    $item->evidence_number,
                    $entryZipPath,
                    $liveHash,
                    $status
                );
            }
        }

        $manifestContent = implode("\n", $manifestLines);
        $zip->addFromString('manifest.sha256', $manifestContent);
        $zip->close();

        AuditLoggerService::log('batch_export_evidence', ForensicCase::class, $case->id, [
            'case_number' => $case->case_number,
            'evidence_count' => $case->evidenceItems->count(),
            'zip_file' => $zipFileName,
        ]);

        return response()->download($tempZipPath, $zipFileName)->deleteFileAfterSend(true);
    }
}
