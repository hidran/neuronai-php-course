-- October averages by country. Expected DE 90.00, IT 156.67.
SELECT c.country, COUNT(*) AS orders,
       ROUND(AVG(o.quantity * p.price), 2) AS average_order
FROM orders o
JOIN customers c ON c.id = o.customer_id
JOIN products p ON p.id = o.product_id
WHERE o.ordered_at >= '2026-10-01'
  AND o.ordered_at < '2026-11-01'
GROUP BY c.country
ORDER BY c.country;
-- All-time product revenue. Expected 600.00, 270.00, 90.00.
SELECT p.name, SUM(o.quantity * p.price) AS revenue
FROM orders o
JOIN products p ON p.id = o.product_id
GROUP BY p.id, p.name
ORDER BY revenue DESC, p.id
LIMIT 3;
