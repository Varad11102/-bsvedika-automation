# BSVedika ID Card Automation

Safe, test-first automation for Google Form submissions and BSVedika ID-card registration.

## Current status

This repository contains a non-production scaffold. It does **not** connect to the live `plus_signup` table and contains no credentials or applicant data.

## Planned flow

Google Form → linked Google Sheet → admin approval → Apps Script → authenticated PHP API → test table → production only after explicit approval.

## Safety rules

- Never commit database credentials, API keys, applicant records, photos, Aadhaar data, or exported form responses.
- Collect only the last four Aadhaar digits.
- Use a separate test database/table until production approval.
- Use parameterized SQL and an insert-only restricted database account.
- Back up production before enabling live writes.
- Do not log request bodies or personal information.

## Repository layout

- `public/index.php` — health endpoint and future authenticated submission endpoint.
- `src/Validation.php` — server-side registration validation.
- `config/.env.example` — names of required environment variables only.
- `google-apps-script/Code.gs` — test-only Google Sheet approval trigger scaffold.
- `.github/workflows/ci.yml` — PHP syntax checks.

## Next steps

1. Link the Google Form to a response Sheet.
2. Add review columns: `Review Status`, `Admin Notes`, `Member ID`, and `Processed At`.
3. Create a separate test table.
4. Configure test credentials only in Hostinger environment settings.
5. Test one synthetic record.
6. Review and approve any production deployment separately.
