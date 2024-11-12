<?php

namespace nostriphant\Transpher\Relay;

use nostriphant\Transpher\Relay\Incoming\Constraint;

readonly class Limits {

    public function __construct(
            private array $checks
    ) {
        
    }

    static function fromEnv(string $group, string $class): self {
        $arguments = [];
        $environment_variables = getenv(null);
        foreach ((new \ReflectionMethod($class, 'construct'))->getParameters() as $parameter) {
            $parameter_name = $parameter->getName();
            $env_var_name = strtoupper('LIMIT_' . $group . '_' . $parameter_name);
            if (isset($environment_variables[$env_var_name]) === false) {
                continue;
            }

            if (str_contains($parameter->getType(), 'array')) {
                $arguments[$parameter_name] = explode(',', $environment_variables[$env_var_name]);
            } else {
                $arguments[$parameter_name] = $environment_variables[$env_var_name];
            }
        }
        return [$class, 'construct'](...$arguments);
    }

    public function __invoke(): Constraint {
        foreach ($this->checks as $reason => $check) {
            if ($check(...func_get_args())) {
                return Constraint::reject($reason);
            }
        }
        return Constraint::accept();
    }
}
