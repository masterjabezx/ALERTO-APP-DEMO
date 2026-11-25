# ALERTO APP — Contact Number Verification System

## 1. Overview
Every emergency incident submitted via ALERTO APP requires a valid Philippine contact telephone number (`caller_phone`). However, syntactic validity alone (`09XXXXXXXXX`) does not confirm that the number belongs to the caller, is currently in service, or is not a malicious prank.

The Contact Verification system allows intake operators and administrators to verify and document caller legitimacy.

---

## 2. Verification Statuses

| Status | Label | Meaning |
|---|---|---|
| `unverified` | **Unverified** | Initial state upon submission. No verification attempt has taken place. |
| `verification_pending` | **Verification Pending** | Callback initiated by operator; line ringing, no answer, or busy. |
| `verified` | **Verified** | Operator spoke with caller and confirmed the emergency scene details. |
| `failed_verification` | **Failed Verification** | Number unreachable, out of service, incorrect owner, or caller denied reporting. |

---

## 3. Supported Verification Methods

### 1. Callback Verification (Primary Operational Method)
Intake triage operators place an immediate telephone callback to the reported caller phone number. The operator verifies:
- Caller identity and relationship to the scene.
- Nature of the emergency and immediate casualties.
- Landmark clarification.

The operator records the verification result, remarks, and timestamp via the Incident Detail management drawer.

### 2. OTP Verification (SMS Gateway Prepared)
When an SMS provider gateway is formally connected, citizens can verify via dynamic One-Time Password tokens. In compliance with architectural rules, OTP delivery is marked as requiring SMS provider setup and is not fabricated in the absence of a live gateway.

---

## 4. Database Architecture
Verification events are permanently audited in `incident_contact_verifications`:
- `incident_id`: Foreign key to `incidents`
- `contact_number`: Phone number verified
- `verification_method`: `'callback'` or `'otp_sms'`
- `status`: `'verified'`, `'failed_verification'`, `'verification_pending'`, `'unverified'`
- `verified_by`: User ID of reviewing officer
- `remarks`: Operator notes
- `verified_at`: Timestamp

