<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Chain of Custody Report - {{ $evidence->evidence_number }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #1a202c; padding: 20px; line-height: 1.5; }
        .header { border-bottom: 2px solid #2d3748; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { font-size: 20px; text-transform: uppercase; margin: 0; }
        .header p { color: #718096; font-size: 12px; margin: 0; }
        .section { margin-bottom: 20px; }
        .section-title { font-size: 14px; font-weight: bold; background: #edf2f7; padding: 6px; border-left: 4px solid #3182ce; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 12px; }
        th, td { border: 1px solid #cbd5e0; padding: 8px; text-align: left; }
        th { background: #f7fafc; font-weight: bold; }
        .hash-box { font-family: monospace; background: #1a202c; color: #48bb78; padding: 8px; font-size: 11px; word-break: break-all; border-radius: 4px; }
        .footer { margin-top: 40px; border-top: 1px solid #e2e8f0; padding-top: 10px; font-size: 10px; color: #a0aec0; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Official Chain of Custody Report</h1>
        <p>Forensic Evidence Platform | Generated: {{ $timestamp }} UTC</p>
    </div>

    <div class="section">
        <div class="section-title">1. Evidence Item Identification</div>
        <table>
            <tr><th>Evidence Tag ID</th><td>{{ $evidence->evidence_number }}</td><th>Case Number</th><td>{{ $evidence->case->case_number }}</td></tr>
            <tr><th>Classification</th><td>{{ strtoupper($evidence->classification) }}</td><th>Source Device</th><td>{{ $evidence->source_device }}</td></tr>
            <tr><th>Collection Date</th><td>{{ $evidence->collected_at }}</td><th>Location Collected</th><td>{{ $evidence->collected_location }}</td></tr>
            <tr><th>Original File Name</th><td>{{ $evidence->file_name }}</td><th>File Size</th><td>{{ $evidence->file_size }} bytes</td></tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">2. Cryptographic Integrity Verification</div>
        <p style="font-size: 11px; margin-bottom: 5px;"><strong>SHA-256 Digest (Generated at Upload Intake):</strong></p>
        <div class="hash-box">{{ $evidence->file_hash_sha256 }}</div>
    </div>

    <div class="section">
        <div class="section-title">3. Sequential Custody Trail</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Released By (Transferor)</th>
                    <th>Received By (Transferee)</th>
                    <th>Transfer Reason</th>
                    <th>Status</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>SYSTEM INGESTION</td>
                    <td>{{ $evidence->uploader->name }}</td>
                    <td>Initial Evidence Upload & Intake Hashing</td>
                    <td>ACCEPTED</td>
                    <td>{{ $evidence->created_at }}</td>
                </tr>
                @foreach($evidence->transfers as $index => $transfer)
                    <tr>
                        <td>{{ $index + 2 }}</td>
                        <td>{{ $transfer->fromUser->name }}</td>
                        <td>{{ $transfer->toUser->name }}</td>
                        <td>{{ $transfer->reason }}</td>
                        <td>{{ strtoupper($transfer->status) }}</td>
                        <td>{{ $transfer->transferred_at }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">4. Document Cryptographic Manifest Sign-Off</div>
        <table>
            <tr><th>Current Official Custodian</th><td>{{ $evidence->currentCustodian->name }}</td></tr>
            <tr><th>Report Export Manifest SHA-256</th><td class="hash-box" style="background:#2d3748;">{{ $manifestHash }}</td></tr>
        </table>
    </div>

    <div class="footer">
        CONFIDENTIAL FORENSIC DOCUMENT — LEGAL ADMISSIBILITY AUDIT TRAIL PRESERVED
    </div>
</body>
</html>
