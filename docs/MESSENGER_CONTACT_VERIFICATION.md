# Facebook Messenger Contact Verification Guide

## 1. The Critical Need for Contact Verification
Because Meta Messenger accounts are identified by Page-Scoped User IDs (PSIDs) rather than public phone numbers, verifying a direct callback phone number is essential for:
- Dispatchers to conduct voice callbacks and establish situational awareness.
- First responders to coordinate when arriving at the emergency scene.
- Filtering out prank reports and malicious spam.

---

## 2. Philippine Mobile Number Normalization & Regex

The system enforces strict formatting matching Philippine telecommunication standards:

### Standard Formats Accepted:
- `09171234567` (11 digits starting with `09`)
- `+639171234567` (International notation)
- `639171234567`
- `9171234567` (10 digits)
- Formats containing spaces, dashes, or parentheses (e.g. `(0917) 123-4567`, `0917-123-4567`).

### Extraction Logic:
```php
$cleaned = preg_replace('/[^0-9]/', '', $input);

if (str_starts_with($cleaned, '639') && strlen($cleaned) === 12) {
    return '0'.substr($cleaned, 2);
}

if (str_starts_with($cleaned, '9') && strlen($cleaned) === 10) {
    return '0'.$cleaned;
}

if (str_starts_with($cleaned, '09') && strlen($cleaned) === 11) {
    return $cleaned;
}
```

---

## 3. Feedback Loop for Invalid Submissions
If a user submits text during `awaiting_phone` that cannot be resolved into a valid 11-digit Philippine mobile number, the assistant replies:
> *"⚠️ We could not verify that contact number. Please provide a valid 11-digit Philippine mobile number (e.g., 09171234567) so our emergency dispatchers can contact you."*

