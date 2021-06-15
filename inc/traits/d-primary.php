<?php

namespace D\FULCRUM\TRAITS;

trait PRIME
{
    var $scripts = [];

    var $styles = [];

    /**
     * _enqueue($type, $targets = [])
     */
    function _enqueue($type, $targets)
    {
        if (is_array($targets) && count($targets) > 0) {
            foreach ($targets as $reg_ns => $reged) {
                if ($reged) {
                    switch ($type):
                        case 'scripts':
                            wp_enqueue_script($reg_ns);
                            break;
                        case 'styles':
                            wp_enqueue_style($reg_ns);
                            break;
                    endswitch;
                }
            }
        }
    }

    var $actions = [];
    // Register actions dynamically //
    /**
     * Expects:
     * $items = [
     * '{unique_function_name}' => [
     *       'hook' => '',
     *       'function' => '',
     *       'priority' => 0,
     *       'args' => 0
     *   ]
     * ];
     */
    function _actions($items = [], $that = false)
    {
        if (!$that) $that = &$this;
        if (count($items) > 0) {
            foreach ($items as $key => $params) {
                if (isset($params['function'])) {
                    add_action(
                        (!isset($params['hook']) ? $key : $params['hook']),
                        [$that, $params['function']],
                        (isset($params['priority']) ? $params['priority'] : 1),
                        (isset($params['accepted_args']) ? $params['accepted_args'] : 1)
                    );
                }
            }
        }
    }

    var $filters = [];
    /**
     * Expects:
     * $items = [
     * '{unique_function_name}' => [
     *       'hook' => '',
     *       'function' => '',
     *       'priority' => 0,
     *       'args' => 0
     *   ]
     * ];
     */
    function _filters($items = [], $that = [])
    {
        if (count($items) > 0) {
            foreach ($items as $key => $params) {
                if (isset($params['function'])) {
                    $funct = (isset($params['self']) && $params['self']) ? $params['function'] : [$that, $params['function']];

                    add_filter(
                        (!isset($params['hook']) ? $key : $params['hook']),
                        $funct,
                        (isset($params['priority']) ? $params['priority'] : 1),
                        (isset($params['accepted_args']) ? $params['accepted_args'] : 1)
                    );
                }
            }
        }
    }
}
