<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ForensicCase;
use App\Models\CaseAssignment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ForensicPlatformSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Admin, Investigators, and Reviewer
        $admin = User::create([
            'name' => 'System Admin',
            'email' => 'admin@forensics.org',
            'password' => Hash::make('password123'),
            'role' => 'Administrator',
        ]);

        $investigator1 = User::create([
            'name' => 'Det. Sarah Jenkins',
            'email' => 'sjenkins@forensics.org',
            'password' => Hash::make('password123'),
            'role' => 'Investigator',
        ]);

        $investigator2 = User::create([
            'name' => 'Analyst Michael Chen',
            'email' => 'mchen@forensics.org',
            'password' => Hash::make('password123'),
            'role' => 'Investigator',
        ]);

        $reviewer = User::create([
            'name' => 'Auditor Robert Vance',
            'email' => 'rvance@forensics.org',
            'password' => Hash::make('password123'),
            'role' => 'Reviewer',
        ]);

        // 2. Create Sample Forensic Case
        $case = ForensicCase::create([
            'case_number' => 'CASE-202607-001',
            'title' => 'Operation Silent Citadel - Corporate Cyber Intrusion',
            'description' => 'Investigation into unauthorized exfiltration of proprietary R&D source code files.',
            'status' => 'open',
            'created_by' => $investigator1->id,
        ]);

        // 3. Assign Investigators
        CaseAssignment::create([
            'case_id' => $case->id,
            'user_id' => $investigator1->id,
            'role_in_case' => 'Investigator',
        ]);

        CaseAssignment::create([
            'case_id' => $case->id,
            'user_id' => $investigator2->id,
            'role_in_case' => 'Investigator',
        ]);
    }
}
