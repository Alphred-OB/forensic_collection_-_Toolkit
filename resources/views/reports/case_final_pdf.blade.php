<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Official Forensic Case Report - {{ $case->case_number }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #1e293b; line-height: 1.5; margin: 30px; }
        .header { border-bottom: 3px solid #0f172a; padding-bottom: 15px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: flex-end; }
        .logo { font-size: 20px; font-weight: bold; color: #0f172a; letter-spacing: 1px; }
        .sublogo { font-size: 11px; color: #2563eb; font-weight: 600; text-transform: uppercase; }
        .badge { background-color: #0f172a; color: #ffffff; padding: 4px 8px; font-size: 10px; font-weight: bold; border-radius: 4px; text-transform: uppercase; }
        .section-title { font-size: 14px; font-weight: bold; color: #0f172a; text-transform: uppercase; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px; margin-top: 25px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 11px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 10px; text-align: left; }
        th { background-color: #f8fafc; font-weight: bold; color: #475569; text-transform: uppercase; }
        .mono { font-family: monospace; }
        .footer { margin-top: 40px; border-top: 1px solid #cbd5e1; pt-10; font-size: 10px; color: #64748b; display: flex; justify-content: space-between; }
        .signature-box { margin-top: 30px; display: flex; justify-content: space-between; }
        .sig-line { width: 45%; border-top: 1px solid #0f172a; pt-5; font-size: 11px; font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <div>
            <div class="logo">FORENSIC TOOLKIT - OFFICIAL CASE REPORT</div>
            <div class="sublogo">Digital Evidence & Forensic Case Management System</div>
        </div>
        <div>
            <span class="badge">CLASSIFIED / COURT READY</span>
        </div>
    </div>

    <table style="border: none; margin-bottom: 15px;">
        <tr style="border: none;">
            <td style="border: none; width: 50%; vertical-align: top;">
                <strong>Case Number:</strong> {{ $case->case_number }}<br>
                <strong>Title:</strong> {{ $case->title }}<br>
                <strong>Priority Level:</strong> {{ strtoupper($case->priority) }}<br>
                <strong>Classification Tags:</strong> {{ $case->tags ?: 'None' }}
            </td>
            <td style="border: none; width: 50%; vertical-align: top;">
                <strong>Lifecycle Status:</strong> {{ strtoupper($case->status) }}<br>
                <strong>Lead Investigator / Creator:</strong> {{ $case->creator->name }}<br>
                <strong>Created Date:</strong> {{ $case->created_at->format('Y-m-d H:i:s T') }}<br>
                <strong>Report Generated:</strong> {{ $timestamp }}
            </td>
        </tr>
    </table>

    <div class="section-title">I. Executive Summary & Overview</div>
    <p style="font-size: 12px;">{{ $case->description ?: 'No detailed background provided.' }}</p>

    <div class="section-title">II. Assigned Investigation Team</div>
    <table>
        <thead>
            <tr>
                <th>Personnel Name</th>
                <th>Role / Designation</th>
                <th>Email Address</th>
            </tr>
        </thead>
        <tbody>
            @foreach($case->assignedUsers as $member)
                <tr>
                    <td>{{ $member->name }}</td>
                    <td>{{ $member->role }}</td>
                    <td>{{ $member->email }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">III. Ingested Evidence Vault Items</div>
    <table>
        <thead>
            <tr>
                <th>Evidence ID</th>
                <th>Source Device</th>
                <th>Classification</th>
                <th>SHA-256 Fingerprint</th>
                <th>Current Custodian</th>
            </tr>
        </thead>
        <tbody>
            @foreach($case->evidenceItems as $item)
                <tr>
                    <td class="mono"><strong>{{ $item->evidence_number }}</strong></td>
                    <td>{{ $item->source_device }}</td>
                    <td>{{ str_replace('_', ' ', $item->classification) }}</td>
                    <td class="mono" style="font-size: 9px; select-all">{{ $item->file_hash_sha256 }}</td>
                    <td>{{ $item->currentCustodian->name }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">IV. Complete Case Activity & Audit History</div>
    <table>
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>User / Actor</th>
                <th>Action Type</th>
                <th>Target Details</th>
            </tr>
        </thead>
        <tbody>
            @foreach($activityLogs as $log)
                <tr>
                    <td class="mono">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                    <td>{{ $log->user ? $log->user->name : 'System' }}</td>
                    <td class="mono"><strong>{{ $log->action_type }}</strong></td>
                    <td class="mono" style="font-size: 9px;">{{ json_encode($log->details) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">V. Cryptographic Report Manifest & Integrity Seal</div>
    <div style="background-color: #f1f5f9; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 10px; margin-top: 10px;">
        <strong>SHA-256 MANIFEST SEAL:</strong> {{ $manifestHash }}<br>
        <span style="color: #64748b; font-size: 9px;">This report has been digitally sealed by the Forensic Toolkit system. Any modification invalidates the manifest checksum.</span>
    </div>

    <div class="signature-box">
        <div class="sig-line">
            Lead Investigator Signature<br>
            Date: ________________________
        </div>
        <div class="sig-line">
            Digital Forensics Auditor Signature<br>
            Date: ________________________
        </div>
    </div>

</body>
</html>
