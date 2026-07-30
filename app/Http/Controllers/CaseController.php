<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CaseAssignment;
use App\Models\ForensicCase;
use App\Models\User;
use App\Services\AuditLoggerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CaseController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->input('search');
        $status = $request->input('status');

        $query = ForensicCase::with(['creator', 'assignedUsers'])
            ->search($search);

        if ($status === 'deleted' && $user->role === 'Administrator') {
            $query->onlyTrashed();
        } else {
            $query->status($status);
        }

        if ($user->role !== 'Administrator') {
            $query->whereHas('assignedUsers', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        $cases = $query->latest()->paginate(10)->withQueryString();

        $stats = [
            'total' => ForensicCase::count(),
            'open' => ForensicCase::where('status', 'open')->count(),
            'closed' => ForensicCase::where('status', 'closed')->count(),
            'archived' => ForensicCase::where('status', 'archived')->count(),
        ];

        return view('cases.index', compact('cases', 'stats', 'search', 'status'));
    }

    public function create()
    {
        $this->authorizeAdminOrInvestigator();
        $users = User::all();
        return view('cases.create', compact('users'));
    }

    public function store(Request $request)
    {
        $this->authorizeAdminOrInvestigator();

        $request->validate([
            'case_number' => 'required|string|unique:cases,case_number',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:Critical,High,Normal,Low',
            'tags' => 'nullable|string|max:255',
            'assigned_users' => 'nullable|array',
            'assigned_users.*' => 'exists:users,id',
        ]);

        $case = ForensicCase::create([
            'case_number' => $request->case_number,
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority,
            'tags' => $request->tags,
            'status' => 'open',
            'created_by' => Auth::id(),
        ]);

        // Auto assign creator
        CaseAssignment::create([
            'case_id' => $case->id,
            'user_id' => Auth::id(),
            'role_in_case' => Auth::user()->role,
        ]);

        // Assign selected investigators/users
        if ($request->has('assigned_users')) {
            foreach ($request->assigned_users as $userId) {
                if ((int)$userId !== Auth::id()) {
                    CaseAssignment::create([
                        'case_id' => $case->id,
                        'user_id' => $userId,
                        'role_in_case' => 'Investigator',
                    ]);
                }
            }
        }

        AuditLoggerService::log('create_case', ForensicCase::class, $case->id, [
            'case_number' => $case->case_number,
            'title' => $case->title,
        ]);

        return redirect()->route('cases.show', $case->id)->with('success', 'Case created successfully.');
    }

    public function show($id)
    {
        $case = ForensicCase::with(['creator', 'assignedUsers', 'evidenceItems.currentCustodian', 'notes.user'])->findOrFail($id);
        
        $this->authorizeCaseAccess($case);

        $allUsers = User::all();
        
        // Fetch recent case activity audit logs
        $activityLogs = AuditLog::with('user')
            ->where(function ($q) use ($case) {
                $q->where('target_type', ForensicCase::class)->where('target_id', $case->id);
            })
            ->orWhereIn('target_id', $case->evidenceItems->pluck('id'))
            ->latest('id')
            ->take(10)
            ->get();

        AuditLoggerService::log('view_case', ForensicCase::class, $case->id, [
            'case_number' => $case->case_number,
        ]);

        return view('cases.show', compact('case', 'allUsers', 'activityLogs'));
    }

    public function storeNote(Request $request, $id)
    {
        $case = ForensicCase::findOrFail($id);
        $this->authorizeCaseAccess($case);

        if (!$case->isEditable()) {
            return redirect()->back()->with('error', 'Cannot add notes to a closed or archived case.');
        }

        $request->validate([
            'note' => 'required|string|max:2000',
        ]);

        $note = \App\Models\CaseNote::create([
            'case_id' => $case->id,
            'user_id' => Auth::id(),
            'note' => $request->note,
            'is_pinned' => $request->boolean('is_pinned'),
        ]);

        AuditLoggerService::log('add_case_note', ForensicCase::class, $case->id, [
            'case_number' => $case->case_number,
            'note_id' => $note->id,
        ]);

        return redirect()->route('cases.show', $case->id)->with('success', 'Operational shift note added to case timeline.');
    }

    public function edit($id)
    {
        $case = ForensicCase::findOrFail($id);
        $this->authorizeCaseAccess($case);
        $this->authorizeAdminOrInvestigator();

        if (!$case->isEditable()) {
            return redirect()->route('cases.show', $case->id)->with('error', 'Cannot edit details of a closed or archived case.');
        }

        return view('cases.edit', compact('case'));
    }

    public function update(Request $request, $id)
    {
        $case = ForensicCase::findOrFail($id);
        $this->authorizeCaseAccess($case);
        $this->authorizeAdminOrInvestigator();

        if (!$case->isEditable()) {
            return redirect()->route('cases.show', $case->id)->with('error', 'Cannot edit details of a closed or archived case.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:Critical,High,Normal,Low',
            'tags' => 'nullable|string|max:255',
        ]);

        $case->update([
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority,
            'tags' => $request->tags,
        ]);

        AuditLoggerService::log('update_case_details', ForensicCase::class, $case->id, [
            'case_number' => $case->case_number,
            'title' => $case->title,
            'priority' => $case->priority,
            'tags' => $case->tags,
        ]);

        return redirect()->route('cases.show', $case->id)->with('success', 'Case details and classification tags updated successfully.');
    }

    public function destroy($id)
    {
        $user = Auth::user();
        if ($user->role !== 'Administrator') {
            abort(403, 'Action prohibited: Only Administrators can soft delete forensic cases.');
        }

        $case = ForensicCase::findOrFail($id);
        $caseNumber = $case->case_number;

        $case->delete();

        AuditLoggerService::log('soft_delete_case', ForensicCase::class, $id, [
            'case_number' => $caseNumber,
            'title' => $case->title,
            'deleted_by' => $user->name,
        ]);

        return redirect()->route('cases.index')->with('success', "Case {$caseNumber} soft-deleted. It can be recovered by an Admin.");
    }

    public function restore($id)
    {
        $user = Auth::user();
        if ($user->role !== 'Administrator') {
            abort(403, 'Action prohibited: Only Administrators can restore deleted forensic cases.');
        }

        $case = ForensicCase::onlyTrashed()->findOrFail($id);
        $case->restore();

        AuditLoggerService::log('restore_case', ForensicCase::class, $case->id, [
            'case_number' => $case->case_number,
            'restored_by' => $user->name,
        ]);

        return redirect()->back()->with('success', "Case {$case->case_number} restored successfully.");
    }

    public function updateTeam(Request $request, $id)
    {
        $case = ForensicCase::findOrFail($id);
        $this->authorizeCaseAccess($case);
        $this->authorizeAdminOrInvestigator();

        if (!$case->isEditable()) {
            abort(403, 'Action prohibited: Team assignments cannot be modified on a closed or archived case.');
        }

        $request->validate([
            'assigned_users' => 'required|array',
            'assigned_users.*' => 'exists:users,id',
        ]);

        // Sync team assignments
        $case->assignedUsers()->sync($request->assigned_users);

        AuditLoggerService::log('update_case_team', ForensicCase::class, $case->id, [
            'case_number' => $case->case_number,
            'team_size' => count($request->assigned_users),
        ]);

        return redirect()->back()->with('success', 'Case team assignments updated successfully.');
    }

    public function updateStatus(Request $request, $id)
    {
        $case = ForensicCase::findOrFail($id);
        $this->authorizeCaseAccess($case);
        $this->authorizeAdminOrInvestigator();

        $request->validate([
            'status' => 'required|in:open,closed,archived',
        ]);

        $oldStatus = $case->status;
        $case->status = $request->status;

        if ($request->status === 'closed' || $request->status === 'archived') {
            $case->closed_at = now();
        } else {
            $case->closed_at = null;
        }

        $case->save();

        AuditLoggerService::log('update_case_status', ForensicCase::class, $case->id, [
            'case_number' => $case->case_number,
            'old_status' => $oldStatus,
            'new_status' => $case->status,
        ]);

        return redirect()->back()->with('success', "Case status updated to " . strtoupper($case->status) . ".");
    }

    private function authorizeAdminOrInvestigator()
    {
        if (in_array(Auth::user()->role, ['Reviewer'])) {
            abort(403, 'Unauthorized action for Reviewer role.');
        }
    }

    private function authorizeCaseAccess(ForensicCase $case)
    {
        $user = Auth::user();
        if ($user->role === 'Administrator') {
            return;
        }

        if (!$case->assignedUsers->contains($user->id)) {
            abort(403, 'Access denied. You are not assigned to this case.');
        }
    }
}
