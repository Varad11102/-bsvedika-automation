/**
 * Test-only scaffold.
 *
 * This script intentionally does not transmit form responses or write to MySQL.
 * Add the production endpoint and secret only after the Hostinger test API,
 * review workflow, and test table have been approved.
 */

const REVIEW_STATUS_HEADER = 'Review Status';
const ADMIN_NOTES_HEADER = 'Admin Notes';
const MEMBER_ID_HEADER = 'Member ID';
const PROCESSED_AT_HEADER = 'Processed At';

function prepareReviewColumns() {
  const sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
  const lastColumn = Math.max(sheet.getLastColumn(), 1);
  const headers = sheet.getRange(1, 1, 1, lastColumn).getValues()[0];

  [REVIEW_STATUS_HEADER, ADMIN_NOTES_HEADER, MEMBER_ID_HEADER, PROCESSED_AT_HEADER]
    .forEach((header) => {
      if (!headers.includes(header)) {
        sheet.getRange(1, sheet.getLastColumn() + 1).setValue(header);
        headers.push(header);
      }
    });
}

/**
 * Installable form-submit trigger target.
 * New submissions remain pending until an administrator reviews them.
 */
function onFormSubmitForReview(event) {
  if (!event || !event.range) {
    throw new Error('Run this function only from an installable form-submit trigger.');
  }

  const sheet = event.range.getSheet();
  const headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
  const statusColumn = headers.indexOf(REVIEW_STATUS_HEADER) + 1;

  if (statusColumn === 0) {
    throw new Error('Run prepareReviewColumns() before enabling the trigger.');
  }

  sheet.getRange(event.range.getRow(), statusColumn).setValue('PENDING');
}
