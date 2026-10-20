# WashFlow database setup (WAMP)

This guide imports the database in WAMP's phpMyAdmin. The schema creates the
`washflow` database and its tables; the seed file adds example accounts and
related laundry data. Tables use InnoDB so that database transactions and
foreign keys work. Import the schema only into a fresh database setup.

## Import the schema

1. Start WAMP and wait until its tray icon is green.
2. Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
3. Sign in using the local MySQL account configured in WAMP (often `root` with
   a blank password on a default local installation).
4. Select the **Import** tab on the phpMyAdmin home page.
5. Choose `sql/schema.sql` from this project folder. Keep the format set to
   **SQL**, then click **Import** or **Go** at the bottom of the page.
6. Confirm that phpMyAdmin reports a successful import. The `washflow` database
   should now appear in the left sidebar.
7. Select each table and confirm its **Storage engine** is **InnoDB** in the
   **Operations** tab.

### Existing database imported with MyISAM

Back up the database in phpMyAdmin before changing table engines. To keep its
existing rows while enabling transactions, select **washflow**, open the **SQL**
tab, and run:

```sql
ALTER TABLE users ENGINE=InnoDB;
ALTER TABLE customers ENGINE=InnoDB;
ALTER TABLE services ENGINE=InnoDB;
ALTER TABLE orders ENGINE=InnoDB;
ALTER TABLE order_items ENGINE=InnoDB;
ALTER TABLE payments ENGINE=InnoDB;
ALTER TABLE tracking_attempts ENGINE=InnoDB;
ALTER TABLE orders ADD COLUMN ready_at DATETIME NULL AFTER created_at;
```

MyISAM does not retain foreign-key constraints. Check for orphaned related rows
before adding any missing foreign keys to an existing database. Do not re-import
`schema.sql` over an existing database; it does not convert tables or preserve
your existing setup by rebuilding it.

For existing orders that were already Ready before `ready_at` was introduced,
the original ready date is unknown. Leave `ready_at` as `NULL`; the dashboard
will not incorrectly report those older orders as overdue. Future transitions
to Ready record the time automatically.

## Import the sample data

1. In phpMyAdmin, select the **washflow** database in the left sidebar.
2. Select the **Import** tab.
3. Choose `sql/seed.sql`, keep the format set to **SQL**, and click **Import**
   or **Go**.
4. Confirm that phpMyAdmin reports successful inserts. Import the seed file
   only once; it uses fixed sample IDs and usernames.
5. Expand **washflow** in the left sidebar. Confirm these seven tables appear:
   `users`, `customers`, `services`, `orders`, `order_items`, `payments`, and
   `tracking_attempts`.

The sample login accounts are `admin` and `staff`. Their temporary passwords
are provided in the project handoff. Both accounts have
`must_change_password = 1`; change the passwords before using these accounts
outside local development.

## Verify the imported data

Select the **washflow** database, open the **SQL** tab, and run each query
below. Queries 1, 3, and 4 should report the seeded counts or `0` inconsistent
rows. Query 2 should show the order lines and service names. Query 5 should
show all five statuses and both void examples.

### 1. Count rows in every table

```sql
SELECT 'users' AS table_name, COUNT(*) AS row_count FROM users
UNION ALL SELECT 'customers', COUNT(*) FROM customers
UNION ALL SELECT 'services', COUNT(*) FROM services
UNION ALL SELECT 'orders', COUNT(*) FROM orders
UNION ALL SELECT 'order_items', COUNT(*) FROM order_items
UNION ALL SELECT 'payments', COUNT(*) FROM payments
UNION ALL SELECT 'tracking_attempts', COUNT(*) FROM tracking_attempts;
```

Expected counts: 2 users, 8 customers, 8 services, 18 orders, 40 order items,
14 payments, and 0 tracking attempts.

### 2. Follow orders through their item and service rows

```sql
SELECT o.ticket_no, o.status, s.name AS service_name, oi.quantity,
       oi.unit_price_snapshot, oi.subtotal
FROM orders AS o
JOIN order_items AS oi ON oi.order_id = o.id
JOIN services AS s ON s.id = oi.service_id
ORDER BY o.id, oi.id;
```

### 3. Find orders whose stored total differs from their item subtotals

```sql
SELECT o.id, o.ticket_no, o.total_amount,
       COALESCE(SUM(oi.subtotal), 0.00) AS items_total
FROM orders AS o
LEFT JOIN order_items AS oi ON oi.order_id = o.id
GROUP BY o.id, o.ticket_no, o.total_amount
HAVING o.total_amount <> COALESCE(SUM(oi.subtotal), 0.00);
```

Expected result: no rows.

### 4. Check payment statuses and claimed-order balances

```sql
SELECT o.id, o.ticket_no, o.status, o.total_amount,
       COALESCE(SUM(CASE WHEN p.is_void = 0 THEN p.amount ELSE 0 END), 0.00) AS paid,
       o.payment_status
FROM orders AS o
LEFT JOIN payments AS p ON p.order_id = o.id
GROUP BY o.id, o.ticket_no, o.status, o.total_amount, o.payment_status
HAVING o.payment_status <>
         CASE
           WHEN COALESCE(SUM(CASE WHEN p.is_void = 0 THEN p.amount ELSE 0 END), 0.00) = 0 THEN 'unpaid'
           WHEN COALESCE(SUM(CASE WHEN p.is_void = 0 THEN p.amount ELSE 0 END), 0.00) < o.total_amount THEN 'partial'
           WHEN COALESCE(SUM(CASE WHEN p.is_void = 0 THEN p.amount ELSE 0 END), 0.00) = o.total_amount THEN 'paid'
           ELSE 'overpaid'
         END
    OR (o.status = 'claimed'
        AND COALESCE(SUM(CASE WHEN p.is_void = 0 THEN p.amount ELSE 0 END), 0.00) <> o.total_amount);
```

Expected result: no rows.

### 5. Check status coverage, void rows, and phone digits

```sql
SELECT 'status' AS check_name, status AS check_value, COUNT(*) AS row_count
FROM orders
GROUP BY status
UNION ALL
SELECT 'voided order', 'is_void = 1', COUNT(*)
FROM orders
WHERE is_void = 1
UNION ALL
SELECT 'voided payment', 'is_void = 1', COUNT(*)
FROM payments
WHERE is_void = 1
UNION ALL
SELECT 'customer missing 4 phone digits', 'invalid', COUNT(*)
FROM customers
WHERE phone_digits IS NULL OR CHAR_LENGTH(phone_digits) < 4
   OR phone_digits REGEXP '[^0-9]';
```

Expected: all five status values appear, exactly one voided order, exactly one
voided payment, and zero customers with invalid phone digits.
