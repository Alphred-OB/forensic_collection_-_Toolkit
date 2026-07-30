# Product Requirements Document
## Forensic Evidence Collection & Case Management Platform (Web Platform)

**Prepared by:** Alphred | School Project
**Document Version:** 0.1 (Draft)
**Date:** July 19, 2026

---

## 0. Glossary of Terms

This section explains every forensic/technical term used in this document in plain language, before it's used. Refer back here anytime a term is unclear.

| Term | Plain-Language Meaning |
|---|---|
| **Chain of Custody (CoC)** | A chronological written record showing every person who collected, handled, transferred, analyzed, or stored a piece of evidence, and when. If there's a gap in this record, a court (or an internal reviewer) can argue the evidence may have been tampered with, which can get it thrown out. |
| **Hashing / Hash Value** | A mathematical fingerprint of a file. Running a file through a hashing algorithm (e.g., SHA-256) produces a fixed-length string of characters. If even one bit of the file changes, the hash changes completely. This is how the app proves a piece of evidence hasn't been altered since it was collected. |
| **SHA-256** | A specific, widely trusted hashing algorithm. It is the current standard for forensic integrity verification (older algorithms like MD5 and SHA-1 are considered too easy to fake and are only kept around for compatibility with older tools). |
| **Forensic Image** | An exact, bit-for-bit copy of a digital storage device (a hard drive, phone, USB stick, etc.), including deleted files and empty space — not just a copy of the visible files. Created using specialized external tools (e.g., FTK Imager, Cellebrite), not something a web browser can do. |
| **Write-Blocker** | A hardware or software tool used during acquisition that physically or logically prevents any data from being written to the original evidence device, so the act of copying it can't accidentally change it. Relevant to the external acquisition tools, not to this web app. |
| **Evidence Item** | Any single unit of evidence being tracked — a device, a file, a forensic image, a screenshot, a log export, etc. Each one gets its own ID and its own custody trail. |
| **Case** | A container that groups all evidence items, users, and activity related to one investigation or incident. |
| **Custodian** | The person currently responsible for a piece of evidence at any given point in time. Custody "transfers" from one custodian to another, and each transfer must be logged. |
| **Audit Log / Audit Trail** | A running, tamper-evident record of every action taken in the system (who logged in, who viewed what, who uploaded or exported evidence, etc.). Distinct from the Chain of Custody log, which is specifically about evidence handling. |
| **Hash Chain** | A technique where each new audit log entry includes the hash of the previous entry. This links every entry into an unbreakable sequence — if anyone tries to alter or delete a past entry, every subsequent hash stops matching, making tampering immediately detectable. |
| **Role-Based Access Control (RBAC)** | A permissions system where what a user can see or do depends on their assigned role (e.g., Investigator, Reviewer, Administrator) rather than being set individually per user. |
| **Non-Repudiation** | A guarantee that a person who performed an action (e.g., uploading evidence, transferring custody) cannot later credibly deny having done it. Usually achieved through authenticated actions, timestamps, and digital signatures. |
| **Metadata** | Data about the evidence file itself — file size, creation date, device it came from, examiner who uploaded it — as opposed to the contents of the file. |
| **Admissibility** | Whether evidence is allowed to be used in a legal proceeding. Depends heavily on whether the chain of custody and integrity verification can be proven to a court's satisfaction. |
| **Tamper-Evident** | Designed so that if someone tries to alter or interfere with something (a record, a file, a package), the interference leaves visible or detectable traces — it doesn't have to be tamper-proof, just tamper-evident. |
| **Acquisition** | The act of collecting/copying digital evidence from its original source. In this project's web-based scope, acquisition happens outside the app (via external forensic tools); the app's job starts when that acquired evidence is uploaded. |
| **Disposition** | The final status/outcome of a piece of evidence at the end of a case — e.g., returned to owner, destroyed, archived, transferred to another agency. |

---

## 1. Purpose & Overview

This project is a school assignment to design a platform that lets investigators collect, document, and manage digital evidence in a way that is legally defensible — meaning that if the evidence is ever questioned in court or during an internal review, there is an unbroken, provable record of exactly what happened to it from the moment it was collected.

This document (the PRD) defines **WHAT** the product needs to do and **WHY**, from the perspective of the people who will use it. A companion document, the Technical Requirements Document (TRD), defines **HOW** it will be built.

## 2. Problem Statement

Digital evidence (files, device images, screenshots, logs, exports) is fragile — it's easy to copy, easy to alter, and hard to prove was handled properly after the fact. Investigators and organizations currently rely on a mix of spreadsheets, paper forms, and generic file storage to track evidence, which creates real risk:

- No reliable way to prove a file hasn't been altered since it was collected.
- No single, trustworthy record of who has handled a piece of evidence and when.
- Manual paper/spreadsheet-based Chain of Custody forms are easy to lose, forge, or fill in inconsistently.
- No access control — anyone with folder access can view or copy sensitive evidence.
- No easy way to generate court-ready or audit-ready documentation on demand.

## 3. Goals & Objectives

- Give investigators a single system to log evidence, track its custody, and generate documentation — replacing paper/spreadsheet processes.
- Make every piece of evidence's integrity provable at any time using cryptographic hashing.
- Make every action taken on evidence (view, upload, transfer, export) automatically and tamper-evidently logged — no manual log-keeping required.
- Enforce who can see and do what, based on their role in a case.
- Produce ready-to-export Chain of Custody reports and case summaries.
- Be usable by teams with limited digital forensics background — the workflow should guide correct evidence handling, not assume expert knowledge.

## 4. Scope

### 4.1 In Scope (Phase 1 — Web Platform)

- Web-based application only (desktop/mobile-responsive browser use). No native desktop or mobile app in this phase.
- Case creation and management.
- Evidence intake: uploading evidence files/exports that were acquired using external forensic tools (see Section 4.3).
- Automatic hashing (SHA-256) and integrity verification of every uploaded evidence item.
- Chain of Custody logging: every transfer of responsibility for evidence is recorded with who, when, and why.
- Tamper-evident audit trail of all system activity.
- Role-based access control (e.g., Administrator, Investigator, Reviewer/Auditor).
- Evidence and case metadata management (descriptions, device info, tags, timestamps).
- Exportable reports: Chain of Custody forms, case summaries, evidence inventories (PDF).
- Secure storage of evidence files with encryption at rest.

### 4.2 Out of Scope (Phase 1)

- Native acquisition/imaging of devices (disk imaging, mobile extraction) — a web browser cannot access raw storage devices at that level. This must be done using existing external forensic tools.
- Native desktop or mobile applications (may be a later phase).
- Automated forensic analysis (e.g., malware analysis, artifact parsing, timeline reconstruction) — the platform manages and documents evidence, it does not analyze it.
- Direct courtroom e-filing/integration with specific court systems.

### 4.3 A Note on "Acquisition" in a Web Platform

Actually copying data off a physical device (a phone, a hard drive) in a forensically sound way requires direct, low-level hardware access and specialized write-blocking hardware/software. A web browser cannot do this — it's a fundamental limitation of the browser sandbox, not a shortcut. Because of that, the realistic and industry-normal design is:

- Investigators use existing, court-recognized external tools (e.g., FTK Imager, Cellebrite, Magnet AXIOM, Autopsy) to acquire the evidence and produce a forensic image or export.
- The resulting file is then uploaded to this platform, which immediately hashes it, timestamps it, records who uploaded it, and begins the Chain of Custody from that point forward.

This is standard practice — most professional evidence-management platforms (not just web ones) work this way; the acquisition tool and the case-management/CoC platform are separate, specialized pieces.

## 5. Target Users (Personas)

The intended user base has not been finalized. The platform should be designed to work for any of the following, since the underlying workflow is nearly identical across all three — the difference is mainly in branding, terminology, and which compliance references are surfaced:

### 5.1 Investigator / Examiner
Uploads and documents evidence, performs custody transfers, adds case notes. Needs a guided, hard-to-mess-up workflow.

### 5.2 Case Administrator / Supervisor
Creates cases, assigns investigators, reviews evidence logs, approves custody transfers, generates reports.

### 5.3 Reviewer / Auditor
Read-only access to review evidence handling, audit logs, and generate compliance reports. Cannot modify evidence.

### 5.4 System Administrator
Manages user accounts, roles, and system configuration. Does not access case content by default.

## 6. Functional Requirements

Each requirement below is written as a user story: "As a [role], I want to [action], so that [benefit]." Priority: Must-Have (M), Should-Have (S), Could-Have (C) — based on the MoSCoW prioritization method (a simple way of ranking what's essential vs. nice-to-have).

### 6.1 Case Management

| User Story | Priority | ID |
|---|---|---|
| As a Case Administrator, I want to create a new case with a unique case ID, so that all related evidence and activity is grouped and traceable. | M | FR-CM-01 |
| As a Case Administrator, I want to assign investigators to a case with specific roles, so that access is controlled. | M | FR-CM-02 |
| As a Case Administrator, I want to close/archive a case, so that it becomes read-only once the investigation is complete. | M | FR-CM-03 |
| As any user, I want to see a dashboard of cases I'm assigned to, so that I can quickly access relevant work. | S | FR-CM-04 |

### 6.2 Evidence Intake

| User Story | Priority | ID |
|---|---|---|
| As an Investigator, I want to upload an evidence file and fill in intake details (description, source device, collection date/time/location), so that it's properly documented from the start. | M | FR-EI-01 |
| As the system, I want to automatically generate a SHA-256 hash of every uploaded file immediately on upload, so that its integrity can be verified later. | M | FR-EI-02 |
| As an Investigator, I want to classify each evidence item as Original, Forensic Copy, Export, Screenshot, or Reconstructed, so that its evidentiary weight is clear. | M | FR-EI-03 |
| As an Investigator, I want to attach photos of the physical device/scene alongside digital evidence, so that physical context is preserved. | S | FR-EI-04 |
| As the system, I want to reject an upload if the file appears corrupted or the hash can't be generated, so that broken evidence isn't silently accepted. | M | FR-EI-05 |

### 6.3 Chain of Custody

| User Story | Priority | ID |
|---|---|---|
| As an Investigator, I want to transfer custody of an evidence item to another user, so that responsibility is clearly and formally recorded. | M | FR-COC-01 |
| As the system, I want to require the receiving party to confirm/accept a custody transfer, so that transfers can't be logged unilaterally. | M | FR-COC-02 |
| As any authorized user, I want to view the complete custody history of an evidence item, so that I can verify its handling at any time. | M | FR-COC-03 |
| As the system, I want to re-verify a file's hash whenever it is downloaded/exported, so that any change is caught immediately. | M | FR-COC-04 |

### 6.4 Access Control & Audit

| User Story | Priority | ID |
|---|---|---|
| As a System Administrator, I want to assign roles to users, so that access to case and evidence data is restricted appropriately. | M | FR-AC-01 |
| As the system, I want to log every view, upload, download, and transfer action automatically, so that a full audit trail exists without manual effort. | M | FR-AC-02 |
| As the system, I want every audit log entry to include the hash of the previous entry, so that the log itself is tamper-evident. | M | FR-AC-03 |
| As a Reviewer/Auditor, I want read-only access to cases assigned to me, so that I can review without risk of altering evidence. | M | FR-AC-04 |

### 6.5 Reporting & Export

| User Story | Priority | ID |
|---|---|---|
| As a Case Administrator, I want to generate a Chain of Custody report (PDF) for any evidence item, so that it can be shared with legal counsel or a court. | M | FR-RP-01 |
| As a Case Administrator, I want to export a full case summary (evidence inventory, timeline, custody history), so that the case can be reviewed externally. | M | FR-RP-02 |
| As the system, I want every exported report to include a signed hash manifest, so that the export itself is verifiable as unaltered. | S | FR-RP-03 |

## 7. Non-Functional Requirements

- **Security:** All evidence files encrypted at rest; all traffic over HTTPS/TLS.
- **Integrity:** Every evidence file's hash must be re-verifiable at any time; any mismatch must trigger a visible alert, not fail silently.
- **Availability:** Target 99.5% uptime for a production deployment (adjust once hosting tier is finalized).
- **Auditability:** No action on evidence should be possible without leaving a logged, attributable trace.
- **Usability:** A first-time investigator should be able to complete evidence intake correctly without external training, guided by the interface.
- **Scalability:** Must support multiple concurrent cases and investigators without evidence from one case being visible to users not assigned to it.
- **Data residency:** If this platform is deployed for a real organization later, confirm whether evidence data must remain on Ghana/Africa-region infrastructure depending on jurisdiction.

## 8. Assumptions & Constraints

- Evidence acquisition (imaging/extraction) happens outside this platform, using existing third-party forensic tools.
- Users have access to a modern web browser; no legacy browser support required.
- Initial deployment is single-organization (multi-tenancy for multiple client organizations is a future consideration, not Phase 1).
- Legal admissibility ultimately depends on the intended jurisdiction's rules of evidence — the platform is designed to meet the strictest common bar, but should be reviewed by qualified legal counsel before use in actual legal proceedings.

## 9. Success Metrics

- 100% of uploaded evidence items have a verified hash on record.
- Zero undocumented custody transfers (every transfer has a logged sender and receiver).
- Chain of Custody report generation takes under 30 seconds per evidence item.
- Reduction in time spent on manual evidence documentation compared to prior paper/spreadsheet process.

## 10. Future Phases (Not in Current Scope)

- Native mobile app for field intake with camera/GPS integration.
- Direct integration with acquisition tools (e.g., automatic ingest from Cellebrite/Autopsy exports).
- Multi-organization / multi-tenant support.
- Built-in analysis features (timeline reconstruction, artifact parsing).

## 11. Compliance & Reference Standards

These are not requirements to implement code against directly, but reference frameworks the design should align with:

- NIST SP 800-86 — Guide to Integrating Forensic Techniques into Incident Response.
- ISO/IEC 27037 — Guidelines for identification, collection, acquisition, and preservation of digital evidence.
- SWGDE Best Practices for Digital Evidence Collection.
- Applicable local law (e.g., Ghana's Electronic Transactions Act, Evidence Act) — to be reviewed with legal counsel once the target user base is confirmed.
