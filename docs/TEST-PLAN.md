# WashFlow Manual Test Plan

Use this plan against a local or staging database containing disposable test data. Do not run destructive tests against a live shop database. Record the date, tester, browser, and result for each case. Never write real passwords or database credentials in this document.

## Test record

| Run date | Tester | Browser/device | Database/seed | Result |
|---|---|---|---|---|
| | | | | |

Use **PASS**, **FAIL**, or **BLOCKED** in the result column and include a short note for failures or blocked tests.

## 1. Authentication, accounts, and access control

| ID | Steps | Expected result | Result |
|---|---|---|---|
| AUTH-01 | Open the sign-in page while logged out. Submit valid staff credentials, then sign out and repeat with valid admin credentials. | Each role can sign in and lands on its own home page: staff on Orders and admin on Dashboard. The session ID changes at login. | |
| AUTH-02 | Submit an incorrect username, an incorrect password, and credentials for a locked or deactivated account. | Each attempt displays the same generic sign-in error. The account is not disclosed by the response. | |
| AUTH-03 | Using a disposable account, submit five incorrect passwords and then try to sign in again before 15 minutes have elapsed. Wait until the lock expires and try again. | The account is locked for 15 minutes after five failures. The lock is stored in the database. After expiry, a correct password works and the failure counter is reset. | |
| AUTH-04 | Sign in, leave the session idle for more than 30 minutes, and make a request to a protected page. | The expired session is rejected and the user must sign in again. | |
| AUTH-05 | Sign in as a newly created or reset account with a temporary password. Try to open a different protected page before changing it, then change to a password meeting the minimum length. | The account is required to change the temporary password. After changing it, normal access resumes. Passwords are never displayed or logged. | |
| AUTH-06 | As admin, deactivate a disposable staff account. In that account's existing browser session, request another protected page; also attempt a new sign-in. | The existing session is rejected on its next request and a deactivated account cannot sign in. | |
| AUTH-07 | While logged out, open an admin page and a staff-protected page directly. While signed in as staff, open each admin-only page directly: Dashboard, Daily Sales, Pending Pickup, Outstanding Balances, Users, and Services administration. | Logged-out users are sent to sign-in. Staff receive the friendly 403 page on every admin-only page. Staff do not see revenue totals. Admin pages and their POST endpoints enforce the role server-side. | |
| AUTH-08 | Sign in as each role and use the application's home link or root route. | Admin is sent to Dashboard; staff is sent to Orders. | |
| AUTH-09 | Inspect the session cookie after sign-in over HTTPS and HTTP. | Cookie is HttpOnly and SameSite=Lax; Secure is enabled for HTTPS. Session strict mode is enabled before a session starts. | |

## 2. CSRF and request-method checks

Run each case with a disposable record where a valid submission could change data. For every POST handler below, first submit with the token field removed, then repeat with a deliberately incorrect token. Verify that the response is HTTP 403 and that no record or business data changed. Do not reuse another user's token.

| ID | POST handler or form | Result |
|---|---|---|
| CSRF-01 | `auth/login.php` sign-in | |
| CSRF-02 | `auth/change_password.php` change password | |
| CSRF-03 | `auth/logout.php` sign out | |
| CSRF-04 | `users/create.php`, `users/edit.php`, `users/reset_password.php`, and the account activation action in `users/index.php` | |
| CSRF-05 | `services/create.php`, `services/edit.php`, and `services/toggle.php` | |
| CSRF-06 | `customers/create.php`, `customers/edit.php`, and `customers/delete.php` | |
| CSRF-07 | `orders/create.php`, `orders/update_status.php`, and `orders/void.php` | |
| CSRF-08 | `payments/create.php` and `payments/void.php` | |
| CSRF-09 | `track.php` tracking form | |
| CSRF-10 | Send GET requests to logout, order-status, order-void, payment-void, customer-delete, and other state-changing endpoints. | The request does not change data. State-changing actions accept POST only; a GET receives a method error or safe page response. | |

## 3. Customers and services

| ID | Steps | Expected result | Result |
|---|---|---|---|
| DATA-01 | Create a customer with a valid name and phone number containing spaces, punctuation, and a country code. View and edit the customer. | The customer is saved once; the stored phone value contains digits only. Values render as text, not executable markup. | |
| DATA-02 | Submit missing required customer fields and malformed values. Try a duplicate or deletion case that is disallowed by the application. | Server-side validation shows useful errors; invalid data is not saved. A customer attached to orders cannot be deleted if the application prevents it. | |
| DATA-03 | Create, edit, deactivate, and reactivate a service using disposable values. | Valid changes persist. Inactive services are not offered for new orders. Existing order price snapshots remain unchanged after a service price edit. | |
| DATA-04 | Submit missing, negative, non-numeric, and out-of-range service prices. | Invalid prices are rejected server-side; valid prices use the required two-decimal currency precision. | |

## 4. Orders and status workflow

| ID | Steps | Expected result | Result |
|---|---|---|---|
| ORDER-01 | Create an order for a customer with one or more active services and no initial payment. | A unique ticket number is generated; line totals and order total are computed on the server; service prices are snapshotted. The order begins in the initial status. | |
| ORDER-02 | Create an order with a partial initial payment. Attempt to tamper with the browser-submitted total or service price. | The saved order total is recomputed from active service prices and quantities. Any initial payment is validated against that total and recorded consistently. | |
| ORDER-03 | Try to submit an order with no service lines, an invalid customer or service ID, zero/negative quantity, an invalid quantity, and an initial payment greater than the total. | Each invalid order is rejected without partial order, item, or payment records. | |
| ORDER-04 | Print the order ticket and inspect the printed page. | Ticket and customer/order information are correct. Navigation and screen-only controls are hidden in print. | |
| ORDER-05 | Advance a test order through each allowed status in sequence. Try to skip a status, repeat a status, and claim an order with a balance due. | Only the next legal transition succeeds. Invalid transitions are rejected. Claiming is blocked until balance due is zero. | |
| ORDER-06 | Open an order detail page with payments. Compare amount paid, payment history, and balance due with the non-voided payment rows. | Displayed values agree; voided payments are excluded. A voided order is not presented as an active order. | |
| ORDER-07 | Request an order ID that does not exist. | The friendly 404 response is displayed without exposing an internal error or record data. | |

## 5. Payments and void corrections

| ID | Steps | Expected result | Result |
|---|---|---|---|
| PAY-01 | Record a payment using cash, GCash, and other on separate disposable orders. Test the optional reference field. | Each valid payment is appended, records the signed-in receiver, updates payment status, and opens its receipt. | |
| PAY-02 | Submit payment amounts of zero, a negative number, more than the current balance, malformed text, and a valid amount. | Invalid amounts are rejected server-side. A valid payment changes the balance by exactly the accepted amount. | |
| PAY-03 | Open a fully paid order and attempt another payment. | The application refuses a payment when no balance remains. | |
| PAY-04 | Submit two rapid payment requests for the same remaining balance from separate tabs or clients. | The order lock and server-side balance recomputation prevent the combined payments from exceeding the balance; only an admissible payment is recorded. | |
| PAY-05 | Alter the order ID and amount in a crafted payment POST. | The server validates the target order and recomputes its balance; it never trusts a browser-supplied total or balance. No payment can exceed the selected order's current balance. | |
| PAY-06 | Print a valid receipt and request a receipt ID that does not exist. | A valid receipt shows its number, time, ticket, customer, amount, method, reference, post-payment balance, and receiver. Print controls are hidden. A missing payment shows the friendly 404 response. | |
| PAY-07 | As staff, attempt payment-void and order-void POST requests. As admin, try a missing reason, a reason shorter than five characters, a reason over 255 characters, and a valid reason. | Staff are forbidden. Admin requests require a valid reason. No invalid request changes data. | |
| PAY-08 | Try to void a Claimed order and a payment belonging to a Claimed order. | Both operations are refused. | |
| PAY-09 | As admin, void an eligible payment, then try to void it again. Recheck order payment status, balances, dashboard totals, and reports. | The payment is marked void once and excluded from all paid/balance/sales totals; the second void is refused. Payment and order rows remain present. | |
| PAY-10 | As admin, void an eligible order and then try to void it again. Recheck its payments and report totals. | In one transaction the order and its payments are voided with the recorded reason and actor. The second void is refused. All voided rows are excluded from active totals. | |

## 6. Reports, filters, and pagination

| ID | Steps | Expected result | Result |
|---|---|---|---|
| REPORT-01 | As admin, compare Dashboard sales, outstanding balance, counts by status, and pending-pickup count with the corresponding non-voided seed/test rows. | Each number agrees with the underlying data. Staff cannot access revenue reports. | |
| REPORT-02 | Open Daily Sales for today, a date with payments, a date with new orders but no payments, and an empty date. Try an invalid date format. | Date filtering uses the selected valid date (or defaults to today); invalid dates are rejected or safely defaulted. Sales rows exclude voided orders and payments. Empty results show “No records.” | |
| REPORT-03 | Compare Pending Pickup with active Ready orders. | Ready orders appear oldest first with correct waiting days; voided orders do not appear. | |
| REPORT-04 | Compare Outstanding Balances and its summary total with active unpaid/partial orders and non-voided payments. | Only positive balances appear, oldest first; displayed per-order values and summary total agree. Voided orders/payments are excluded. | |
| REPORT-05 | On every list, open page 1, a later page, `?page=0`, `?page=-1`, `?page=abc`, an unusually large page, and an overflowing integer. | Lists use 15 rows per page. Invalid values safely resolve to page 1; large valid pages show an empty page or are safely bounded, never a SQL error. | |
| REPORT-06 | On each report page, use the Reports sub-navigation to open Dashboard, Daily Sales, Pending Pickup, and Outstanding Balances. | All four destinations are reachable. The active report item alone is highlighted and is identified accessibly as current. | |
| REPORT-07 | As admin, enable “Show voided” on each list that provides the filter; turn it off again. | Voided rows are hidden by default and shown only when requested. They are clearly marked as void and amounts are visually struck through as text remains readable. | |

## 7. Public order tracking

| ID | Steps | Expected result | Result |
|---|---|---|---|
| TRACK-01 | While logged out, track a valid ticket with the matching last four phone digits. | Only the ticket, customer's first name, order date/status, friendly status text, service names, total, amount paid, and balance due are shown. No full name, phone, address, staff name, other order, or internal ID is exposed. | |
| TRACK-02 | Try an unknown ticket, wrong digits, no customer phone, malformed ticket, and malformed digits. | Every failure shows the same generic “No matching order found.” response; no lookup-specific detail is disclosed. | |
| TRACK-03 | Submit five failed lookups from one IP and then try another ticket. Separately submit five failures for one ticket from different browser sessions/IPs if a test network is available. | Further lookups are refused with the same generic response for 15 minutes. The throttle persists in the database and is unaffected by clearing cookies or opening a private window. | |
| TRACK-04 | Attempt a valid lookup while throttled, then repeat after the throttle window has expired. | Throttling blocks the request until expiry; lookup becomes available afterward. | |
| TRACK-05 | Inspect the tracking page before and after a lookup while logged out. | Tracking is the public read-only order interface; its only application write is an attempt-log row. All displayed values are escaped. | |

## 8. Errors, output safety, and deployment configuration

| ID | Steps | Expected result | Result |
|---|---|---|---|
| QA-01 | Request the friendly 403 and 404 pages and exercise an application error in a disposable local environment to check the 500 page. | Each error page is friendly, has no stack trace or database details, and is usable on a phone. | |
| QA-02 | In a disposable customer/service text field, try harmless text containing HTML metacharacters (for example `<b>test</b>`). View it on detail, list, receipt, and report pages. | The value is displayed as text and does not create markup or run script. | |
| QA-03 | Review local and sample configuration, ignore rules, and repository history before deployment. | Real credentials exist only in local ignored configuration; the sample contains placeholders. No credentials or secrets are committed. If repository history cannot be inspected, mark this case BLOCKED rather than assuming it passes. | |
| QA-04 | Inspect the rendered application and dependency files. | Production application uses only the approved course stack and Tailwind loading method; no unapproved frameworks, libraries, icon fonts, or third-party assets are loaded. Prototype reference files are not included in application pages. | |
| QA-05 | Cause a controlled application exception locally with DEBUG disabled, then repeat with DEBUG enabled only in the local environment. | Production-style mode hides details and logs the error to the configured log; debug details are available only when DEBUG is enabled. | |

## 9. Responsive checks for every rendered page

Perform each route below at **360px**, **768px**, and **1280px** viewport widths. At each width: check that the document itself has no horizontal overflow; every visible field has a label, readable text, and a usable phone keyboard type where applicable; buttons and links have at least a 44px tap target; focus is visible when tabbing; content is not hidden by sticky UI; and forms can be completed without desktop-only interactions. Use the menu button at 360px and 768px and verify it opens/closes; at 1280px verify the desktop sidebar/navigation. At list/report pages, verify mobile cards below the table breakpoint and the table at wider sizes. Check print pages with the print preview as noted above.

| Route / rendered page | Page-specific responsive checks | 360px | 768px | 1280px |
|---|---|---|---|---|
| `auth/login.php` | Credentials fit; password visibility control is tappable; sign-in button is usable. | | | |
| `auth/change_password.php` | Password fields, helper/error text, and save action fit without clipping. | | | |
| `users/index.php` | Mobile user cards and desktop table; admin actions remain reachable. | | | |
| `users/create.php` | Account fields stack; labels/errors and save action remain visible. | | | |
| `users/edit.php` | Edit form and active/deactivated state controls remain usable. | | | |
| `users/reset_password.php` | Reset form fits and temporary password is not exposed after submission. | | | |
| `services/index.php` | Mobile service cards and desktop table; active filter and actions fit. | | | |
| `services/create.php` | Service fields and price input use phone-friendly layout and keyboard. | | | |
| `services/edit.php` | Service form and save/toggle actions remain reachable. | | | |
| `customers/index.php` | Mobile customer cards and desktop table; phone/address content wraps. | | | |
| `customers/create.php` | Single-column fields, phone keyboard, validation, and save action. | | | |
| `customers/edit.php` | Edit and remove actions fit; confirmation is understandable and tappable. | | | |
| `orders/index.php` | Mobile order cards and desktop table; search, filters, pagination, and void filter fit. | | | |
| `orders/create.php` | Customer/service selection, quantity, payment, and dynamic line controls work on mobile. | | | |
| `orders/view.php` | Order summary, status controls, payment history, and void actions fit; payment list cards/table switch correctly. | | | |
| `orders/ticket.php` | Ticket content is readable and print layout fits; no horizontal overflow. | | | |
| `payments/create.php` | Balance card is prominent; amount uses decimal keypad; full-width mobile submit works. | | | |
| `payments/receipt.php` | Receipt fills screen width, wraps long values, and prints without navigation/buttons. | | | |
| `reports/dashboard.php` | Summary cards stack/flow correctly; recent orders use cards/table appropriately; quick actions remain tappable. | | | |
| `reports/daily_sales.php` | Date picker is full-width on mobile; summary cards and payment cards/table remain readable. | | | |
| `reports/pending_pickup.php` | Mobile cards include tap-to-call phone and large View action; desktop table is readable. | | | |
| `reports/outstanding_balances.php` | Mobile cards include tap-to-call and Record Payment action; amounts wrap/read correctly. | | | |
| `track.php` | Friendly heading, two fields, helper text, stepper, result/error, and shop contact fit; phone keyboard and disabled-after-submit behavior work. | | | |
| `errors/403.php` | Message and return action fit and remain readable. | | | |
| `errors/404.php` | Message and return action fit and remain readable. | | | |
| `errors/500.php` | Message and recovery action fit and remain readable. | | | |

## Completion

Do not apply a code fix based only on a failed manual case until the failure is recorded with the route, viewport/browser, steps, expected result, and actual result. Retest affected cases after an approved fix.
