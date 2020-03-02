<?php

namespace WHMCS\Module\Server\Wray;

/**
 * Sample Admin Area Dispatch Handler
 */
class Dispatcher
{

    /**
     * Dispatch request.
     *
     * @param string $action
     * @param array $parameters
     *
     * @return array
     */
    public function dispatch($action, $parameters)
    {
        if (!$action) {
            $action = 'index';
        }

        $controller = new Controller();
        if (is_callable(array($controller, $action))) {
            return $controller->$action($parameters);
        }

        return [];
    }
}
