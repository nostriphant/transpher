<?php

namespace nostriphant\Transpher\Relay\Incoming\Req;

use nostriphant\Transpher\Relay\Incoming\Constraint;
use nostriphant\Transpher\Relay\Subscriptions;

readonly class Limits {

    private array $checks;

    public function __construct(
            ?int $max_per_client = 10
    ) {
        $checks = [];

        if (isset($max_per_client)) {
            $checks['max number of subscriptions per client (' . $max_per_client . ') reached'] = fn(Subscriptions $subscriptions) => $subscriptions() >= $max_per_client;
        }

        $this->checks = $checks;
    }

    static function fromEnv(): self {
        $arguments = [];
        $environment_variables = getenv(null);
        foreach ((new \ReflectionMethod(__CLASS__, '__construct'))->getParameters() as $parameter) {
            $parameter_name = $parameter->getName();
            $env_var_name = 'LIMIT_SUBSCRIPTIONS_' . strtoupper($parameter_name);
            if (isset($environment_variables[$env_var_name]) === false) {
                continue;
            }

            if (str_contains($parameter->getType(), 'array')) {
                $arguments[$parameter_name] = explode(',', $environment_variables[$env_var_name]);
            } else {
                $arguments[$parameter_name] = $environment_variables[$env_var_name];
            }
        }
        return new self(...$arguments);
    }

    public function __invoke(Subscriptions $subscriptions): Constraint {
        foreach ($this->checks as $reason => $check) {
            if ($check($subscriptions)) {
                return Constraint::reject($reason);
            }
        }
        return Constraint::accept();
    }
}
