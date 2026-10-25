CREATE TABLE orders (
    id INTEGER PRIMARY KEY,
    customer_id TEXT NOT NULL,
    status TEXT NOT NULL,
    amount TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
INSERT INTO orders VALUES (4471, 'C-118', 'refunded', '49.00', '2026-10-20');
INSERT INTO orders VALUES (4472, 'C-118', 'shipped', '129.00', '2026-10-21');
INSERT INTO orders VALUES (5120, 'C-204', 'refunded', '19.00', '2026-10-12');
INSERT INTO orders VALUES (5133, 'C-204', 'refunded', '64.00', '2026-10-19');
INSERT INTO orders VALUES (5140, 'C-204', 'delivered', '35.00', '2026-10-22');
INSERT INTO orders VALUES (5152, 'C-118', 'refunded', '22.00', '2026-09-28');
