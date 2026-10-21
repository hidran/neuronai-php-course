-- Setup account runs these grants before the agent connects.
GRANT SELECT ON neuron_shop.orders TO 'analyst_ro'@'%';
GRANT SELECT ON neuron_shop.customers TO 'analyst_ro'@'%';
GRANT SELECT ON neuron_shop.products TO 'analyst_ro'@'%';
-- No database-wide grant, write grant, FILE or GRANT OPTION.
