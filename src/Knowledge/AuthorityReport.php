<?php
declare(strict_types=1);
namespace App\Knowledge;
use PDO;
/** Prints the routing of recorded requests: the one class that prints. */
final readonly class AuthorityReport
{
    public function __construct(
        private AuthorityRouter $router,
        private PDO $orders,
    ) {
    }
    public function print(string $requestsFile): void
    {
        $rows = $this->router->rows();
        printf("Decision table: %d mechanisms\n", count($rows));
        foreach ($rows as $mechanism => $authority) {
            printf("  %s <- %s\n", $mechanism, $authority->value);
        }
        $json = (string) file_get_contents($requestsFile);
        $requests = json_decode($json, true,
            flags: JSON_THROW_ON_ERROR);
        foreach ($requests as $request) {
            $authority = Authority::from($request['authority']);
            $mechanism = $this->router->mechanismFor($authority);
            printf("Request: %s\n", $request['question']);
            printf("  authority %s -> %s\n", $authority->value,
                $mechanism ?? 'no mechanism yet');
            if ($mechanism !== null) {
                printf("  accepting: %s\n",
                    implode(', ',
                        $this->router->accepting($mechanism)));
            }
            if (isset($request['source'])) {
                printf("  expected source: %s\n", $request['source']);
            }
            if (($request['tool'] ?? null) === 'order_status') {
                foreach (['C-118', 'C-204'] as $customer) {
                    $tool = new OrderStatusTool($this->orders,
                        $customer);
                    printf("  order_status as %s: %s\n", $customer,
                        $tool(...$request['arguments'])->getText());
                }
            }
        }
        echo "Authority check complete.\n";
    }
}
