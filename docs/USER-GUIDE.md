# WashFlow User Guide

WashFlow helps laundry-shop staff record customer orders, track work status, and record payments. Admins also manage services and users and review reports. Customers can check an order using the public tracking page.

## Sign in

1. Open the WashFlow sign-in page and enter your assigned username and password.
2. A new or reset account may require you to choose a new password before using other protected pages.
3. Staff land on the Orders list. Admins land on the Dashboard.
4. Use **My Account** to change your password. Use **Logout** to end your session.

Do not share your password. Contact an administrator if your account is inactive or you cannot sign in.

## Staff: create an order and print its ticket

1. Open **Orders** and choose **New Order**.
2. Select the customer. If the customer is not listed, add them through **Customers** first.
3. Add one or more active services and enter the quantity in kilograms or pieces as requested by the service.
4. If the customer is paying now, enter the amount. The order total is computed by the server from the selected service prices and quantities.
5. Save the order. WashFlow creates a ticket number and opens the claim ticket.
6. Print the ticket for the customer. The ticket number is shown in grouped form for readability; the **Copy ticket number** button copies the exact ticket number.

The customer should keep the claim ticket. They can use the ticket number and last four digits of the phone number provided to the shop on the public tracking page.

## Find and update an order

1. Use the order list search to find an order by ticket number, customer name, or phone.
2. Open the matching order to see its services, status, payment summary, and payment history.
3. Use the status action to advance the order to its next available stage: Pending → Washing → Drying → Ready → Claimed.
4. Status changes are checked by the server. Orders advance one step at a time, and an order cannot be claimed while it has a balance due.

Do not create a second order to correct an existing one. Ask an admin to void an eligible order or payment with a reason.

## Record a payment and print its receipt

1. Open the order and choose **Record Payment**.
2. Confirm the balance due. Enter an amount no greater than the current balance, choose cash, GCash, or other, and optionally enter a reference number.
3. Submit the payment. WashFlow rechecks the balance on the server and records the signed-in receiver.
4. Print the receipt for the customer.

Payments are append-only. If a payment was recorded incorrectly, an admin can void it with a reason, unless its order has been claimed.

## Admin: dashboard, reports, services, and users

- **Dashboard:** Review today's sales, outstanding balance, order counts, pickup count, and the Needs attention panel.
- **Daily Sales:** Select a date to see payments and new orders for that date.
- **Pending Pickup:** Review Ready orders waiting for pickup.
- **Outstanding Balances:** Review active unpaid or partially paid orders and record a payment from the list.
- **Services:** Add or edit service names, pricing type, and unit price. Inactive services are not available for new orders.
- **Users:** Create accounts, update roles/details, deactivate or reactivate accounts, and reset a password. New/reset accounts must change their password.
- **Corrections:** Open an eligible order or payment and submit a void reason. Voided records remain in history and are excluded from active totals. Claimed orders and their payments cannot be voided.

Admin-only pages are restricted on the server; hiding a navigation link does not grant access to staff.

## Customer: track an order

1. On a phone, open the WashFlow tracking page.
2. Enter the ticket number from the claim ticket and the last four digits of the phone number given to the shop. Grouped ticket numbers with spaces are accepted.
3. Choose **Track order**.
4. Read the order status, services, total, amount paid, balance due, and pickup guidance.

Tracking does not require an account. It shows only a limited set of information for the matching order. If the details do not match, check the ticket and phone digits and try again.
