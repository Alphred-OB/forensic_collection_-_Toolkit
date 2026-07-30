<?php

namespace App\Http\Controllers;

use App\Models\CustodyTransfer;
use App\Models\EvidenceItem;
use App\Models\User;
use App\Services\AuditLoggerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustodyController extends Controller
{
    public function create($evidenceId)
    {
        $evidence = EvidenceItem::with('case')->findOrFail($evidenceId);
        
        if ($evidence->current_custodian_id !== Auth::id() && Auth::user()->role !== 'Administrator') {
            return redirect()->back()->with('error', 'Only the current custodian can transfer evidence.');
        }

        $users = User::where('id', '!=', Auth::id())->get();
        return view('custody.create', compact('evidence', 'users'));
    }

    public function store(Request $request, $evidenceId)
    {
        $evidence = EvidenceItem::findOrFail($evidenceId);

        $request->validate([
            'to_user_id' => 'required|exists:users,id',
            'reason' => 'required|string',
        ]);

        $transfer = CustodyTransfer::create([
            'evidence_item_id' => $evidence->id,
            'from_user_id' => Auth::id(),
            'to_user_id' => $request->to_user_id,
            'reason' => $request->reason,
            'status' => 'pending',
            'transferred_at' => now(),
        ]);

        AuditLoggerService::log('initiate_custody_transfer', CustodyTransfer::class, $transfer->id, [
            'evidence_number' => $evidence->evidence_number,
            'to_user_id' => $request->to_user_id,
            'reason' => $request->reason,
        ]);

        return redirect()->route('evidence.show', $evidence->id)->with('success', 'Custody transfer initiated. Awaiting acceptance from recipient.');
    }

    public function accept($transferId)
    {
        $transfer = CustodyTransfer::findOrFail($transferId);

        if ($transfer->to_user_id !== Auth::id()) {
            return redirect()->back()->with('error', 'Unauthorized to accept this transfer.');
        }

        $transfer->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        // Update evidence item current custodian
        $evidence = $transfer->evidenceItem;
        $evidence->update([
            'current_custodian_id' => Auth::id(),
        ]);

        AuditLoggerService::log('accept_custody_transfer', CustodyTransfer::class, $transfer->id, [
            'evidence_number' => $evidence->evidence_number,
            'new_custodian_id' => Auth::id(),
        ]);

        return redirect()->route('evidence.show', $evidence->id)->with('success', 'Custody transfer accepted. You are now the official custodian.');
    }

    public function reject($transferId)
    {
        $transfer = CustodyTransfer::findOrFail($transferId);

        if ($transfer->to_user_id !== Auth::id()) {
            return redirect()->back()->with('error', 'Unauthorized to reject this transfer.');
        }

        $transfer->update([
            'status' => 'rejected',
        ]);

        AuditLoggerService::log('reject_custody_transfer', CustodyTransfer::class, $transfer->id, [
            'evidence_number' => $transfer->evidenceItem->evidence_number,
        ]);

        return redirect()->back()->with('success', 'Custody transfer rejected.');
    }
}
