<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\EvidenceItem;
use App\Models\ForensicCase;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AuditLoggerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'admins' => User::where('role', 'Administrator')->count(),
            'investigators' => User::where('role', 'Investigator')->count(),
            'reviewers' => User::where('role', 'Reviewer')->count(),
            'total_cases' => ForensicCase::count(),
            'open_cases' => ForensicCase::where('status', 'open')->count(),
            'total_evidence' => EvidenceItem::count(),
            'total_storage_bytes' => EvidenceItem::sum('file_size'),
        ];

        $auditIntegrity = AuditLoggerService::verifyChainIntegrity();

        $recentUsers = User::latest()->take(5)->get();
        $recentAuditLogs = AuditLog::with('user')->latest('id')->take(8)->get();

        // Case Status Breakdown Chart Data
        $caseStatusChart = [
            'open' => ForensicCase::where('status', 'open')->count(),
            'closed' => ForensicCase::where('status', 'closed')->count(),
            'archived' => ForensicCase::where('status', 'archived')->count(),
        ];

        // Evidence Classification Breakdown Chart Data
        $evidenceClassificationChart = EvidenceItem::select('classification', DB::raw('count(*) as total'))
            ->groupBy('classification')
            ->pluck('total', 'classification')
            ->toArray();

        // Monthly Audit Logs Activity Trend (Last 6 Months)
        $auditTrendChart = AuditLog::select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month_year"), DB::raw('count(*) as count'))
            ->groupBy('month_year')
            ->orderBy('month_year', 'asc')
            ->take(6)
            ->pluck('count', 'month_year')
            ->toArray();

        return view('admin.dashboard', compact(
            'stats',
            'auditIntegrity',
            'recentUsers',
            'recentAuditLogs',
            'caseStatusChart',
            'evidenceClassificationChart',
            'auditTrendChart'
        ));
    }

    public function users(Request $request)
    {
        $search = $request->input('search');
        $role = $request->input('role');

        $query = User::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role) {
            $query->where('role', $role);
        }

        $users = $query->latest()->paginate(15)->withQueryString();

        return view('admin.users.index', compact('users', 'search', 'role'));
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', Rules\Password::defaults()],
            'role' => ['required', 'in:Administrator,Investigator,Reviewer'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        AuditLoggerService::log('admin_create_user', User::class, $user->id, [
            'created_user_email' => $user->email,
            'role' => $user->role,
        ]);

        return redirect()->back()->with('success', "User account for {$user->name} ({$user->role}) created successfully.");
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', 'in:Administrator,Investigator,Reviewer'],
            'password' => ['nullable', Rules\Password::defaults()],
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->role = $request->role;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        AuditLoggerService::log('admin_update_user', User::class, $user->id, [
            'user_email' => $user->email,
            'role' => $user->role,
        ]);

        return redirect()->back()->with('success', "User account for {$user->name} updated successfully.");
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return redirect()->back()->with('error', 'Action prohibited: You cannot delete your own administrator account.');
        }

        $userEmail = $user->email;
        $userName = $user->name;

        $user->delete();

        AuditLoggerService::log('admin_delete_user', User::class, $id, [
            'deleted_user_email' => $userEmail,
        ]);

        return redirect()->back()->with('success', "User account for {$userName} ({$userEmail}) deleted successfully.");
    }

    public function globalEvidenceVault(Request $request)
    {
        $search = $request->input('search');
        $classification = $request->input('classification');

        $query = EvidenceItem::with(['case', 'uploader', 'currentCustodian']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('evidence_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('file_name', 'like', "%{$search}%")
                  ->orWhere('file_hash_sha256', 'like', "%{$search}%");
            });
        }

        if ($classification) {
            $query->where('classification', $classification);
        }

        $evidenceItems = $query->latest()->paginate(20)->withQueryString();

        // Compute Footprint Analytics
        $totalStorageBytes = EvidenceItem::sum('file_size');
        $classificationBreakdown = EvidenceItem::select('classification', DB::raw('count(*) as item_count'), DB::raw('sum(file_size) as storage_bytes'))
            ->groupBy('classification')
            ->get();

        return view('admin.evidence.index', compact('evidenceItems', 'search', 'classification', 'totalStorageBytes', 'classificationBreakdown'));
    }

    public function settings()
    {
        $settings = [
            'max_upload_size_mb' => SystemSetting::getByKey('max_upload_size_mb', '1024'),
            'allowed_extensions' => SystemSetting::getByKey('allowed_extensions', 'raw,dd,img,e01,vmdk,pcap,pcapng,pdf,png,jpg,txt,zip,7z'),
            'session_timeout_minutes' => SystemSetting::getByKey('session_timeout_minutes', '30'),
            'mandatory_2fa' => SystemSetting::getByKey('mandatory_2fa', '0'),
        ];

        return view('admin.settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'max_upload_size_mb' => ['required', 'integer', 'min:10', 'max:10240'],
            'allowed_extensions' => ['required', 'string'],
            'session_timeout_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'mandatory_2fa' => ['nullable', 'boolean'],
        ]);

        SystemSetting::setKey('max_upload_size_mb', $request->max_upload_size_mb, 'Maximum single evidence file upload size limit in MB');
        SystemSetting::setKey('allowed_extensions', strtolower(trim($request->allowed_extensions)), 'Comma-separated allowed file upload extensions');
        SystemSetting::setKey('session_timeout_minutes', $request->session_timeout_minutes, 'System session inactivity timeout limit in minutes');
        SystemSetting::setKey('mandatory_2fa', $request->has('mandatory_2fa') ? '1' : '0', 'Mandatory 2FA enforcement policy');

        AuditLoggerService::log('admin_update_system_settings', null, null, [
            'max_upload_size_mb' => $request->max_upload_size_mb,
            'session_timeout_minutes' => $request->session_timeout_minutes,
            'mandatory_2fa' => $request->has('mandatory_2fa') ? '1' : '0',
        ]);

        return redirect()->back()->with('success', 'System security and vault policies updated successfully.');
    }
}
