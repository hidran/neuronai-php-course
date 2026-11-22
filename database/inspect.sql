SELECT CURRENT_USER() AS account;
SELECT TABLE_NAME, PRIVILEGE_TYPE
FROM information_schema.TABLE_PRIVILEGES
WHERE TABLE_SCHEMA = 'neuron_shop'
ORDER BY TABLE_NAME;
SELECT o.id, c.country, p.name, o.quantity, p.price,
       o.quantity * p.price AS revenue, o.ordered_at
FROM orders o
JOIN customers c ON c.id = o.customer_id
JOIN products p ON p.id = o.product_id
ORDER BY o.id;
SELECT COUNT(*) AS order_count,
       SUM(o.quantity * p.price) AS revenue
FROM orders o
JOIN products p ON p.id = o.product_id
WHERE o.ordered_at >= '2026-10-01'
  AND o.ordered_at < '2026-11-01';
