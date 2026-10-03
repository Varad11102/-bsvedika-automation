# BSVedika ID Card Automation

Safe, test-first automation for Google Form submissions and BSVedika ID-card registration.

## Current status

The Google Form is linked to a review Sheet, and `plus_signup_test` exists with the production structure. This repository contains a test-only API and Sheet sender. Production writes remain disabled.

## Flow

Google Form → linked Google Sheet → admin marks `APPROVED` → Apps Script sends a signed request → Hostinger PHP API → `plus_signup_test`.

## Safety rules

- Never commit database credentials, API keys, applicant records, photos, Aadhaar data, or exported responses.
- Collect and store only the last four Aadhaar digits.
- Keep `db_table` set to `plus_signup_test` during testing.
- Use parameterized SQL and a restricted database account.
- Back up production before any production enablement.
- Do not log request bodies or personal information.

## Hostinger test deployment

1. Upload `public/submit.php`, `src/Validation.php`, and the `config` directory.
2. Copy `config/config.example.php` to `config/config.php` on Hostinger.
3. Put credentials and a long random shared secret only in Hostinger's `config.php`.
4. Confirm `db_table` is exactly `plus_signup_test`.
5. Keep `config/.htaccess` in place so the configuration cannot be downloaded.
6. Test the endpoint over HTTPS only.

## Google Apps Script

1. Paste `google-apps-script/Code.gs` into the linked Sheet's Apps Script project.
2. Set Script Properties:
   - `BSV_TEST_ENDPOINT`: HTTPS URL ending in `/submit.php`
   - `BSV_SHARED_SECRET`: the same secret stored on Hostinger
3. Install the `onFormSubmitForReview` spreadsheet trigger.
4. Use **BSVedika → Send approved active row to test API** only with synthetic data.

## Production lock

`submit.php` refuses to start if the configured table is anything other than `plus_signup_test`. Production support must be a separate reviewed change after a backup and successful synthetic test.
