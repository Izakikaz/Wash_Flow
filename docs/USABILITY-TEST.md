# WashFlow Usability Test

## Purpose

Observe whether people who are not on the WashFlow team can complete common laundry-shop tasks without being coached through the interface. Run one session with each of three testers:

1. A staff tester using a staff account.
2. An admin tester using an admin account.
3. A customer tester using their own phone, without signing in.

Ask testers to think aloud, but do not guide them to particular buttons, menus, or pages. Use a disposable test database or clearly identified test records. Do not ask testers to use real customer data, payments, or account passwords.

## Session setup and recording

- Tester must not be a member of the WashFlow project team.
- Record the tester's role, device/browser, viewport or phone model, and session date.
- Before each task, reset the app to the stated starting condition and prepare the required test customer, service, order, account, or printed claim ticket.
- Start the task timer when the scenario is read; stop it when the tester states they are finished or reaches the intended result.
- Count a **mistake** when the tester takes an incorrect or abandoned action, enters incorrect information, needs to undo/repeat a step, or asks for task help. Do not count ordinary exploration that does not cause an error.
- Do not correct or rescue the tester during a task. If a task is unsafe or would affect non-test data, stop it and record it as blocked.
- Write down exact words, labels, or messages the tester finds confusing. Do not paraphrase their quote.
- Ask the tester not to say passwords aloud. Do not record passwords, phone numbers, or other unnecessary personal data.

## Tester 1 — Staff

### Two-minute walkthrough script

Read this aloud before the tasks. Keep it to about two minutes and do not demonstrate clicks or task steps.

> WashFlow is an order and service tracking system for a laundry shop. Staff use it to create orders, print claim tickets, find existing orders, record payments, and move orders through their work status. The ticket number on a claim ticket identifies an order. Payments can be partial, and the remaining balance is shown with the order.  
>
> Please work as you normally would at a laundry counter. I am testing how well the system supports your work, not testing you. There are no wrong questions, but I will not tell you where to click or what to enter while a task is underway. Please think aloud: tell me what you expect to happen and what you are looking for. You may stop or ask to stop at any time.  
>
> The data and account for this session are prepared for testing. Please do not use real customer information or a real payment. I will time each task, note mistakes, and write down any words or messages that are unclear. Please do not say your password aloud. After each scenario, tell me when you believe you are done.

### Tasks

Read only the scenario for each task. Do not explain how to complete it.

| ID | Scenario | Success measure | Time taken | Mistakes | Confusing words / observations |
|---|---|---|---|---|---|
| STAFF-1 | A customer named **[test customer]** has brought in **[test service and quantity]**. They will pay **[partial amount]** now and the rest later. Create the order and give them a claim ticket they can take with them. | An order is saved with the expected customer, service, quantity, and partial payment; its ticket is printed or opened ready to print in **under 90 seconds**. | | | |
| STAFF-2 | A customer calls and gives you ticket number **[prepared ticket number]**. Find the order so you can confirm it is the right one. | Opens the matching order from the ticket number in **under 15 seconds**, without opening a different order. | | | |
| STAFF-3 | The customer from the first scenario is ready to pay **[second amount]** toward the remaining balance. Record that payment. | The payment is recorded for the correct order and the displayed paid amount and balance reflect it. | | | |
| STAFF-4 | The laundry from the first scenario has moved to the next stage of work. Update its progress so the next staff member can see where it is. | The order advances exactly one valid step and the resulting status is clear. | | | |
| STAFF-5 | A customer asks for another copy of the claim ticket for the order you created. Show or print the ticket, and, if useful, copy its ticket number. | The correct claim ticket is available; its number is readable and any copy action copies the exact ticket number. | | | |

### Staff success summary

| Measure | Target | Result / notes |
|---|---|---|
| Order created and ticket ready | Under 90 seconds | |
| Existing order found by ticket number | Under 15 seconds | |
| Partial payment recorded on the correct order | Completed without help or overpayment | |
| Order advanced to its next valid status | Completed without help or skipped status | |
| Overall task completion | Record completed tasks out of 5 | |

## Tester 2 — Admin

### Two-minute walkthrough script

Read this aloud before the tasks. Keep it to about two minutes and do not demonstrate clicks or task steps.

> WashFlow supports day-to-day laundry orders as well as administrative work. Admins can review reports and items needing attention, manage services and users, and make corrections by voiding eligible orders or payments with a reason. Voiding is a recorded correction; it does not erase the original record.  
>
> Please work as you normally would when checking a shop's operations. I am testing whether the system makes the work understandable, not testing you. Think aloud: say what you expect, what you are trying to find, and whether the wording makes sense. I will not point you to a menu, button, or page during a task.  
>
> This is a test account and the records are disposable. Do not change real prices or real customer records. The payment selected for the correction is test data and is eligible for voiding. Do not say your password aloud. I will time each task and note mistakes and confusing wording. Tell me when you believe each scenario is complete.

### Tasks

| ID | Scenario | Success measure | Time taken | Mistakes | Confusing words / observations |
|---|---|---|---|---|---|
| ADMIN-1 | You have just started your shift. Find out which orders may need follow-up today and what the shop should pay attention to first. | Correctly identifies the available attention categories and opens or identifies at least one relevant order, or confirms there are none. | | | |
| ADMIN-2 | The shop has begun offering **[test service name]** at **[test price]** per **[kg/piece]**. Add it so staff can use it on future orders. | Creates an active service with the intended name, price, and pricing type; the service appears in the service list. | | | |
| ADMIN-3 | A test payment **[receipt/payment identifier or order ticket]** was recorded by mistake. Correct the record and explain what information you need to provide. | Voids the eligible payment with a reason of at least five characters; the payment remains identifiable as void and no longer counts toward the order's balance or sales. | | | |
| ADMIN-4 | A manager asks you to confirm the shop's current daily sales and the orders still waiting for pickup. Find the figures and explain what date or order set they refer to. | Finds the daily sales and pending-pickup information and correctly describes the selected date and/or waiting orders. | | | |

### Admin success summary

| Measure | Target | Result / notes |
|---|---|---|
| Needs-attention work identified | Completed without help | |
| Service created with correct settings | Completed without invalid or duplicate record | |
| Eligible payment voided with an adequate reason | Completed; totals/balance exclude the void | |
| Daily sales and pickup information located | Correct figures/context identified | |
| Overall task completion | Record completed tasks out of 4 | |

## Tester 3 — Customer using their own phone

### Two-minute walkthrough script

Read this aloud before handing over the claim ticket. The tester should use their own phone and should not sign in.

> Imagine you have left laundry at a shop and have received this claim ticket. You want to check its progress from your own phone. WashFlow has a public tracking page for that purpose; you do not need a customer account.  
>
> Please use the information printed on this test claim ticket. I am testing whether the instructions and result are clear, not testing you. Try to complete the scenarios on your own phone as you normally would. Think aloud about what you expect to happen and what is confusing. I will not point you to a button or tell you where information is printed.  
>
> This ticket and order are disposable test data. Please do not enter your personal phone number or other private information; use only the test details on the ticket. I will time each task and note mistakes and exact confusing words. You may stop at any time. Tell me when you believe you have finished each scenario.

### Tasks

Give the tester the prepared test claim ticket and the test phone suffix **[last four digits]**. Do not point to the ticket number or identify which digits to enter.

| ID | Scenario | Success measure | Time taken | Mistakes | Confusing words / observations |
|---|---|---|---|---|---|
| CUSTOMER-1 | You are at home and want to find out whether the laundry on this claim ticket is ready. Use your phone to check its progress. | Reaches the correct order result on the first attempt using only the supplied ticket and suffix; target **under 60 seconds**. | | | |
| CUSTOMER-2 | Tell me, in your own words, what the current status means for your laundry. | Correctly explains the current stage without relying only on a color or icon. | | | |
| CUSTOMER-3 | You are planning to pick up the laundry. Find out whether you still need to pay anything and what you should bring. | Correctly identifies the displayed balance due and any next-step guidance, such as bringing the claim ticket. | | | |
| CUSTOMER-4 | You want to make sure the ticket number you used is the same one shown with the order result. | Finds and confirms the displayed ticket number; reports whether the spacing or grouping is clear. | | | |

### Customer success summary

| Measure | Target | Result / notes |
|---|---|---|
| Correct order found on first attempt | Yes | |
| Time to track order | Under 60 seconds | |
| Status understood in plain words | Yes | |
| Balance and next step understood | Yes | |
| Overall task completion | Record completed tasks out of 4 | |

## Top five issues and fixes

After all three sessions, rank issues by how often they occurred and how much they blocked or slowed a task. Use exact tester wording where possible. Prefer specific fixes that can be checked in a retest.

| Rank | Issue / tester quote | Tester and task | Frequency / impact | Proposed fix | Owner | Retest result |
|---|---|---|---|---|---|---|
| 1 | | | | | | |
| 2 | | | | | | |
| 3 | | | | | | |
| 4 | | | | | | |
| 5 | | | | | | |
