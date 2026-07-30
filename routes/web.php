<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\CaseController;
use App\Http\Controllers\CustodyController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Two-Factor Authentication Secondary Challenge (Guest accessible via session)
Route::get('/two-factor-challenge', [\App\Http\Controllers\TwoFactorController::class, 'showChallenge'])->name('two-factor.challenge');
Route::post('/two-factor-challenge', [\App\Http\Controllers\TwoFactorController::class, 'verifyChallenge'])->name('two-factor.verify');

Route::middleware(['auth', 'verified'])->group(function () {
    // 2FA Setup & Profile Management
    Route::get('/user/two-factor-setup', [\App\Http\Controllers\TwoFactorController::class, 'showSetup'])->name('two-factor.setup');
    Route::post('/user/two-factor-enable', [\App\Http\Controllers\TwoFactorController::class, 'enable'])->name('two-factor.enable');
    Route::post('/user/two-factor-disable', [\App\Http\Controllers\TwoFactorController::class, 'disable'])->name('two-factor.disable');
    Route::get('/dashboard', [CaseController::class, 'index'])->name('dashboard');

    // Case Routes
    Route::get('/cases', [CaseController::class, 'index'])->name('cases.index');
    Route::get('/cases/create', [CaseController::class, 'create'])->name('cases.create');
    Route::post('/cases', [CaseController::class, 'store'])->name('cases.store');
    Route::get('/cases/{id}', [CaseController::class, 'show'])->name('cases.show');
    Route::get('/cases/{id}/edit', [CaseController::class, 'edit'])->name('cases.edit');
    Route::put('/cases/{id}', [CaseController::class, 'update'])->name('cases.update');
    Route::delete('/cases/{id}', [CaseController::class, 'destroy'])->name('cases.destroy');
    Route::post('/cases/{id}/restore', [CaseController::class, 'restore'])->name('cases.restore');
    Route::post('/cases/{id}/team', [CaseController::class, 'updateTeam'])->name('cases.update-team');
    Route::post('/cases/{id}/status', [CaseController::class, 'updateStatus'])->name('cases.update-status');
    Route::post('/cases/{id}/close', [CaseController::class, 'close'])->name('cases.close');

    // Evidence Routes
    Route::get('/cases/{caseId}/evidence/create', [EvidenceController::class, 'create'])->name('evidence.create');
    Route::post('/cases/{caseId}/evidence', [EvidenceController::class, 'store'])->name('evidence.store');
    Route::get('/evidence/{id}', [EvidenceController::class, 'show'])->name('evidence.show');
    Route::get('/evidence/{id}/download', [EvidenceController::class, 'download'])->name('evidence.download');

    // Custody Transfer Routes
    Route::get('/evidence/{evidenceId}/transfer', [CustodyController::class, 'create'])->name('custody.create');
    Route::post('/evidence/{evidenceId}/transfer', [CustodyController::class, 'store'])->name('custody.store');
    Route::post('/custody/{transferId}/accept', [CustodyController::class, 'accept'])->name('custody.accept');
    Route::post('/custody/{transferId}/reject', [CustodyController::class, 'reject'])->name('custody.reject');

    // Audit Trail Routes
    Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');

    // Admin Console Routes (Restricted to Administrator role)
    Route::middleware(\App\Http\Middleware\EnsureIsAdmin::class)->group(function () {
        Route::get('/admin', [AdminController::class, 'dashboard'])->name('admin.dashboard');
        Route::get('/admin/users', [AdminController::class, 'users'])->name('admin.users.index');
        Route::post('/admin/users', [AdminController::class, 'storeUser'])->name('admin.users.store');
        Route::put('/admin/users/{id}', [AdminController::class, 'updateUser'])->name('admin.users.update');
        Route::delete('/admin/users/{id}', [AdminController::class, 'deleteUser'])->name('admin.users.delete');

        // Global Evidence Vault & Storage Inspector
        Route::get('/admin/evidence', [AdminController::class, 'globalEvidenceVault'])->name('admin.evidence.index');

        // Security Policies & Vault Settings
        Route::get('/admin/settings', [AdminController::class, 'settings'])->name('admin.settings.index');
        Route::post('/admin/settings', [AdminController::class, 'updateSettings'])->name('admin.settings.update');

        // Audit Trail Scanner & Export
        Route::post('/admin/audit/scan', [AuditController::class, 'runSystemScan'])->name('admin.audit.scan');
        Route::get('/admin/audit/export-csv', [AuditController::class, 'exportCsv'])->name('admin.audit.export-csv');
    });

    // Chain of Custody & Final Case Reports
    Route::get('/reports/coc/{evidenceId}', [ReportController::class, 'generateCoCReport'])->name('reports.coc');
    Route::get('/reports/case/{caseId}', [ReportController::class, 'generateCaseFinalReport'])->name('reports.case');

    // Batch Evidence Vault Export
    Route::get('/cases/{caseId}/export-batch', [EvidenceController::class, 'exportBatchZip'])->name('evidence.batch-export');

    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
