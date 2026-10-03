const REVIEW_STATUS_HEADER = 'Review Status';
const ADMIN_NOTES_HEADER = 'Admin Notes';
const MEMBER_ID_HEADER = 'Member ID';
const PROCESSED_AT_HEADER = 'Processed At';

const SOURCE_HEADERS = {
  name: 'Full Name',
  age: 'Age',
  sect: 'Sect',
  subsect: 'Sub-sect',
  gothram: 'Gothram',
  fname: 'Father or Spouse Name',
  mobile: 'Mobile Number',
  email: 'Email Address',
  address: 'Full Address',
  aadhar_last4: 'Aadhaar Last 4 Digits',
  photo_url: 'Passport-size Photo',
  consent: 'Consent',
  timestamp: 'Timestamp',
};

function onOpen() {
  SpreadsheetApp.getUi()
    .createMenu('BSVedika')
    .addItem('Prepare review columns', 'prepareReviewColumns')
    .addItem('Send approved active row to test API', 'sendApprovedActiveRow')
    .addToUi();
}

function prepareReviewColumns() {
  const sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
  const headers = sheet.getRange(1, 1, 1, Math.max(sheet.getLastColumn(), 1)).getValues()[0];

  [REVIEW_STATUS_HEADER, ADMIN_NOTES_HEADER, MEMBER_ID_HEADER, PROCESSED_AT_HEADER]
    .forEach((header) => {
      if (!headers.includes(header)) {
        sheet.getRange(1, sheet.getLastColumn() + 1).setValue(header);
        headers.push(header);
      }
    });
}

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

function sendApprovedActiveRow() {
  const ui = SpreadsheetApp.getUi();
  const sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
  const row = sheet.getActiveRange().getRow();

  if (row < 2) {
    throw new Error('Select a submission row first.');
  }

  const headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getDisplayValues()[0];
  const values = sheet.getRange(row, 1, 1, sheet.getLastColumn()).getDisplayValues()[0];
  const record = Object.fromEntries(headers.map((header, index) => [header, values[index]]));

  if (record[REVIEW_STATUS_HEADER] !== 'APPROVED') {
    throw new Error('Only rows marked APPROVED can be sent.');
  }

  if (record[MEMBER_ID_HEADER]) {
    throw new Error('This row already has a Member ID.');
  }

  const properties = PropertiesService.getScriptProperties();
  const endpoint = properties.getProperty('BSV_TEST_ENDPOINT');
  const secret = properties.getProperty('BSV_SHARED_SECRET');

  if (!endpoint || !secret) {
    throw new Error('Set BSV_TEST_ENDPOINT and BSV_SHARED_SECRET in Script Properties.');
  }

  const consentText = String(record[SOURCE_HEADERS.consent] || '').trim();
  const payload = {
    name: record[SOURCE_HEADERS.name],
    age: record[SOURCE_HEADERS.age],
    sect: record[SOURCE_HEADERS.sect],
    subsect: record[SOURCE_HEADERS.subsect],
    gothram: record[SOURCE_HEADERS.gothram],
    fname: record[SOURCE_HEADERS.fname],
    mobile: record[SOURCE_HEADERS.mobile],
    email: record[SOURCE_HEADERS.email],
    address: record[SOURCE_HEADERS.address],
    aadhar_last4: record[SOURCE_HEADERS.aadhar_last4],
    photo_url: record[SOURCE_HEADERS.photo_url],
    userid: 'gform:' + SpreadsheetApp.getActiveSpreadsheet().getId() + ':' + sheet.getSheetId() + ':' + row,
    consent: consentText.length > 0,
  };

  const body = JSON.stringify(payload);
  const timestamp = String(Math.floor(Date.now() / 1000));
  const signatureBytes = Utilities.computeHmacSha256Signature(timestamp + '.' + body, secret);
  const signature = signatureBytes
    .map((byte) => ('0' + ((byte < 0 ? byte + 256 : byte).toString(16))).slice(-2))
    .join('');

  const response = UrlFetchApp.fetch(endpoint, {
    method: 'post',
    contentType: 'application/json',
    payload: body,
    headers: {
      'X-BSV-Timestamp': timestamp,
      'X-BSV-Signature': signature,
    },
    muteHttpExceptions: true,
  });

  const responseCode = response.getResponseCode();
  const result = JSON.parse(response.getContentText());

  if (responseCode !== 200 && responseCode !== 201) {
    throw new Error('Test API rejected the row: ' + (result.error || responseCode));
  }

  const memberIdColumn = headers.indexOf(MEMBER_ID_HEADER) + 1;
  const processedAtColumn = headers.indexOf(PROCESSED_AT_HEADER) + 1;
  sheet.getRange(row, memberIdColumn).setValue(result.member_id);
  sheet.getRange(row, processedAtColumn).setValue(new Date());
  ui.alert('Test row created with member ID ' + result.member_id + '.');
}
