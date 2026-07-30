# Technical Requirements Document
## Forensic Evidence Collection & Case Management Platform (Web Platform)

**Prepared by:** Alphred | School Project
**Document Version:** 0.1 (Draft) — companion to the PRD
**Date:** July 19, 2026

---

## 1. Purpose

This document translates the requirements in the PRD into a technical build plan: architecture, data model, security design, and infrastructure. Terms are explained inline the first time they appear — see the PRD's Glossary (Section 0) for a full reference list.

## 2. Technology Stack

| Layer | Choice |
|---|---|
| Backend Framework | Laravel 11 (PHP) — handles routing, business logic, database access, authentication |
| Frontend | Blade templates + Tailwind CSS + Alpine.js (the "TALL" stack minus Livewire) — server-rendered pages with light interactivity |
| Database | MySQL — relational database storing cases, evidence records, users, and logs |
| File Storage | Encrypted object/file storage for evidence files, separate from the database (database stores metadata + hashes, not the raw files) |
| Hosting | Hostinger |
| Edge / Security | Cloudflare (DNS, WAF — Web Application Firewall, DDoS protection, TLS termination) |
| Authentication | Laravel's built-in auth (Breeze/Fortify) + 2FA (two-factor authentication) for privileged roles |

## 3. System Architecture Overview

A standard three-layer web architecture:

- **Presentation Layer:** Blade views styled with Tailwind, Alpine.js for interactive elements (file upload progress, modals, transfer confirmations) without needing a separate JavaScript frontend framework.
- **Application Layer:** Laravel controllers, services, and jobs. This is where hashing, custody-transfer logic, permission checks, and report generation happen.
- **Data Layer:** MySQL for structured data (cases, users, evidence metadata, logs) + a separate encrypted file store for the actual evidence files.

Evidence files are never stored directly in the database — only their metadata and hash values are. The files themselves live in encrypted storage, referenced by a secure file path/ID.

### 3.1 High-Level Data Flow (Evidence Upload)

1. Investigator uploads a file (a forensic image, export, or document already acquired using an external tool) through the web interface.
2. Laravel receives the file, computes its SHA-256 hash (explained below), and stores the file in encrypted storage.
3. A new Evidence Item record is created in MySQL, storing: hash, uploader, timestamp, case ID, classification (Original/Copy/Export/etc.), and description fields.
4. An Audit Log entry and an initial Chain of Custody entry are created automatically — the investigator becomes the first recorded custodian.
5. From this point, every view, download, or transfer creates a new logged event linked to that Evidence Item.

## 4. What "Hashing" Means Technically

SHA-256 ("Secure Hash Algorithm, 256-bit") is a one-way function: you can turn a file into a hash, but you cannot turn a hash back into the file. It's deterministic — the same file always produces the same hash — and extremely sensitive to change: altering a single byte of the file produces a completely different hash.

**Implementation:** PHP's built-in `hash()` function (`hash('sha256', $fileContents)`) computes this when a file is uploaded. The resulting hash (a 64-character string) is stored alongside the evidence record. Anytime the file is downloaded or exported, the system re-computes the hash and compares it — a mismatch means the file has changed and is flagged immediately.

## 5. Database Schema (Core Tables)

This is a starting schema — refine field types/constraints during actual implementation.

### 5.1 `users`
```
id, name, email, password_hash, role_id, two_factor_secret, created_at, updated_at
```

### 5.2 `roles`
```
id, name (Administrator | Investigator | Reviewer), permissions_json
```

### 5.3 `cases`
```
id, case_number (unique), title, description, status (open|closed|archived), created_by (user_id), created_at, closed_at
```

### 5.4 `case_assignments`
```
id, case_id, user_id, role_in_case, assigned_at
```

### 5.5 `evidence_items`
```
id, case_id, evidence_number (unique), description, source_device,
classification (original|forensic_copy|export|screenshot|reconstructed),
file_path (encrypted storage reference), file_hash_sha256, file_size,
uploaded_by (user_id), collected_at, collected_location, current_custodian_id,
created_at, updated_at
```

### 5.6 `custody_transfers`
```
id, evidence_item_id, from_user_id, to_user_id, reason,
transferred_at, accepted_at (nullable until receiver confirms), status (pending|accepted)
```

### 5.7 `audit_log`
```
id, user_id, action_type (upload|view|download|transfer|export|login|...),
target_type, target_id, details_json, entry_hash, previous_entry_hash, created_at
```

The `entry_hash` / `previous_entry_hash` pair is what makes this a "hash chain" (see PRD Glossary). Each new row's `entry_hash` is calculated from its own contents plus the previous row's `entry_hash`. This means the rows are cryptographically linked in sequence — editing or deleting any past row breaks every hash after it, which is how tampering becomes detectable rather than just "logged and trusted."

### 5.8 `reports` (generated exports)
```
id, case_id, evidence_item_id (nullable), type (coc_form|case_summary), file_path, generated_by, generated_at, manifest_hash
```

## 6. Security Architecture

### 6.1 Access Control
Role-Based Access Control (RBAC): each user has a role (Administrator, Investigator, Reviewer) plus per-case assignment. A user only sees cases and evidence they are explicitly assigned to, enforced at the Laravel policy/middleware layer on every request — never just hidden in the UI.

### 6.2 Encryption
- **In transit:** HTTPS/TLS enforced everywhere, terminated at Cloudflare, re-encrypted to the origin server.
- **At rest:** Evidence files encrypted using server-side encryption (e.g., AES-256) before being written to storage.
- **Database:** sensitive fields (e.g., 2FA secrets) encrypted at the application layer using Laravel's built-in encryption.

### 6.3 Tamper-Evident Audit Trail
As described in Section 5.7, every meaningful action writes an append-only, hash-chained log entry. The application should never expose an "edit" or "delete" operation on `audit_log` or `custody_transfers` rows — these tables are insert-only by design.

### 6.4 Authentication
- Standard email/password login via Laravel Breeze or Fortify.
- Two-factor authentication (2FA) required for Administrator and Investigator roles at minimum.
- Session timeout after a period of inactivity, configurable per deployment.

## 7. Evidence Upload & Integrity Verification Flow

Since this is a web-only platform, raw device acquisition (imaging a hard drive, extracting a phone) is explicitly out of scope for the application itself — it requires direct hardware access that a browser cannot provide. The upload flow instead assumes the file being uploaded was already produced by an external forensic tool (FTK Imager, Cellebrite, Magnet AXIOM, Autopsy, etc.), and focuses on making everything after that point airtight:

- Chunked/resumable uploads for large forensic image files (these can be many gigabytes).
- Hash computed server-side immediately after upload completes — never trust a client-supplied hash.
- Optional: if the investigator's external tool already produced its own hash (most do), allow entering that hash at intake so the system can cross-check it against the one it computes.
- Virus/malware scan on upload (evidence files, especially malware samples, can be dangerous to store unscanned) — quarantine rather than reject, since the file itself may need to remain as evidence.

## 8. API Design (Internal)

Since this is a server-rendered Blade application (not a separate SPA), most "API" surface is internal Laravel routes rather than a public REST API. If a public/partner API is needed later (e.g., for integration with acquisition tools), it should follow REST conventions with token-based authentication (Laravel Sanctum), and reuse the same permission checks as the web UI — never a separate, looser set of rules.

## 9. Reporting & Export Generation

- PDF generation for Chain of Custody forms and case summaries (e.g., via a Laravel PDF package).
- Each exported PDF includes a manifest: a list of the hashes of everything included in the export, itself hashed and stored in the `reports` table, so the export can later be verified as unaltered.

## 10. Infrastructure & Deployment

- **Hosting:** Hostinger (confirm VPS tier supports required PHP/MySQL versions and storage needs for large evidence files).
- **Edge/Security:** Cloudflare in front for DNS, TLS, WAF, and DDoS protection.
- **Backups:** automated, encrypted daily backups of both the database and evidence file storage, with backup integrity checks (re-verify hashes after restore tests).
- **Environment separation:** distinct staging and production environments — never test against real evidence data.
- **Logging/monitoring:** application-level error logging plus uptime monitoring; `audit_log` is a security record, not a substitute for operational logs.

## 11. Non-Functional / Technical Requirements

| Category | Requirement |
|---|---|
| Performance | Evidence upload + hash generation should complete without blocking the UI (use a queued background job in Laravel for large files). |
| Scalability | Database schema should support many concurrent cases without cross-case data leakage (enforced via `case_assignments` + policies). |
| Reliability | Hash verification must never be skipped, even under load — treat it as a required step, not best-effort. |
| Data Integrity | `audit_log` and `custody_transfers` tables must be insert-only at the application layer. |
| Compliance | Design should align with NIST SP 800-86 and ISO/IEC 27037 principles (see PRD Section 11). |

## 12. Third-Party Integrations (Current & Future)

- Groq / Anthropic APIs — potential future use for report summarization or anomaly detection in audit logs (not core to Phase 1).
- Cloudflare — DNS, WAF, TLS.
- Future: direct ingest integrations with forensic acquisition tool exports (Cellebrite, Autopsy) to reduce manual upload steps.

## 13. Open Technical Questions

- Target user base (law enforcement / corporate / academic) is still undecided — this affects data residency requirements, retention policy defaults, and which compliance references to formally document.
- Maximum expected evidence file size (affects upload/storage architecture decisions).
- Whether multi-organization (multi-tenant) support is needed in Phase 1 or can wait.
