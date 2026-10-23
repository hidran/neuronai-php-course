<?php
declare(strict_types=1);
namespace App\Observability;
use App\Agents\ObservedWeatherAgent;
class MonitoredAgentFactory
{
    public static function make(string $thread): ObservedWeatherAgent
    {
        $agent = ObservedWeatherAgent::make()->setThreadId($thread);
        AgentMonitoring::local($agent);
        AgentMonitoring::tools($agent);
        AgentMonitoring::backend($agent);
        return $agent;
    }
}
