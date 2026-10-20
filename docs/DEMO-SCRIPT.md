# WashFlow Demo Script

**Target duration:** about 6 minutes (within the requested 5–7 minute window).  
**Setup:** Start WAMP and the application; import the disposable seed data; have one test customer, one active service, a staff account, an admin account, and a Ready or in-progress test order available. Use only test credentials and test data. Do not demonstrate with real customer information.

| Time | Speaker / module | Demo flow and narration |
|---|---|---|
| 0:00–0:45 | **Navarro — Auth, Accounts & Integration** | Start on the sign-in page and sign in with the prepared staff account. Explain that WashFlow uses PHP sessions and role-based access, and that login uses password hashes and a database-backed failed-login counter. Point out that staff land on Orders while admins land on the Dashboard. Show the shared navigation and where My Account and Logout are available. Do not disclose the test password. |
| 0:45–2:00 | **Tejada — Order Intake / Customers** | Use the prepared customer or create a clearly marked test customer. Create an order with an active service and quantity, optionally entering a partial initial payment. Explain that the server reloads service pricing and computes the order total; the order and its item rows are saved together, with a unit-price snapshot. Show the generated ticket number and print the claim ticket. |
| 2:00–3:10 | **De Asis — Order Status Tracking & Payments** | Open the test order from its ticket number. Show the status progress and payment summary. Record a partial payment, point out the receipt, then advance the order one permitted status step. Explain that the server locks the order, recomputes balance before payment, and blocks claiming until paid. |
| 3:10–4:20 | **Amate — Service / Pricing Management** | Sign in as the prepared admin if needed. Open Services, add a temporary service with a test name, price, and pricing type, then show it in the list. Explain that existing order-item price snapshots do not change when a service price is edited, and services are deactivated rather than deleted. Restore or deactivate the temporary test service afterward. |
| 4:20–5:35 | **Parreño — Reports / Dashboard / Public Tracking** | Show Dashboard sales, outstanding balance, status counts, pickup count, and Needs attention. Navigate to Daily Sales, Pending Pickup, and Outstanding Balances. Then open public `track.php` in a logged-out/private browser and track a prepared test order using the claim ticket and test phone suffix. Explain that only limited information for the matching order is shown and tracking attempts are logged for throttling. |
| 5:35–6:10 | **Navarro — Wrap-up / Integration** | Summarize the connected flow: customer and service data feed order intake; order items preserve the original price; payments and status changes update the order lifecycle; reports use non-voided data; public tracking is limited and does not require login. Invite questions. |

## Before the demo

- Confirm the database and app are available and the accounts can sign in.
- Use test records only; avoid voiding or changing seed records that the next presenter needs.
- Ensure the test order has the desired balance and status for the payment and claim demonstration.
- Keep a prepared valid tracking ticket and its test phone suffix available for the public tracking segment.
- If a page or database action fails, explain the observed behavior honestly; do not claim an unshown feature works.
