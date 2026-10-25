<?php
declare(strict_types=1);
namespace App\Invoices;
use App\Agents\InvoiceAgent;
use InvalidArgumentException;
use NeuronAI\Exceptions\AgentException;
use NeuronAI\Exceptions\NeuronException;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\StructuredOutput\Deserializer\DeserializerException;
final readonly class InvoiceRunner
{
    public function __construct(
        private AIProviderInterface $provider,
        private string $directory,
        private InvoiceMessageFactory $messages,
        private InvoiceConsistency $consistency,
        private InvoiceOracle $oracle,
        private InvoiceReport $report,
    ) {
    }
    /** @param list<string> $names */
    public function run(array $names): bool
    {
        $failed = false;
        foreach ($names as $name) {
            try {
                $this->extract($name);
            } catch (InvalidArgumentException $error) {
                $failed = true;
                $this->report->failure($name, 'local_file_problem',
                    $error->getMessage());
            } catch (AgentException | DeserializerException $error) {
                $failed = true;
                $this->report->failure($name, 'validation_failure',
                    $error::class);
            } catch (NeuronException $error) {
                $failed = true;
                $this->report->failure($name, 'request_failure',
                    $error::class);
            }
        }
        return !$failed;
    }
    private function extract(string $name): void
    {
        $source = InvoiceSource::fromDirectory($this->directory, $name);
        $agent = InvoiceAgent::make($this->provider)
            ->setThreadId('invoice-' . bin2hex(random_bytes(8)));
        $start = hrtime(true);
        $invoice = $agent->structured($this->messages->message($source));
        $seconds = (hrtime(true) - $start) / 1e9;
        $history = $agent->getChatHistory();
        $usage = $history->getLastMessage()->getUsage();
        $review = [
            ...$this->consistency->issues($invoice),
            ...$this->oracle->differences($source, $invoice),
        ];
        $this->report->record($source, $invoice, $review,
            $usage?->inputTokens, $seconds);
    }
}
