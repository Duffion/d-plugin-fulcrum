<?php

namespace D\FULCRUM\CRONS;

/*
D-Cron - Not a disease but a core for helping run / monitor and log wp-cronjobs

*/

use  D\FULCRUM\TRAITS\PRIME as D_PRIME;


class d_crons
{
    use D_PRIME;

    private $frequencies = [
        [
            'name' => 'five_minutes',
            'interval' => 300,
            'display' => 'Every Five Minutes'
        ],
        [
            'name' => 'ten_minutes',
            'interval' => 600,
            'display' => 'Every Ten Minutes'
        ],
        [
            'name' => 'one_minute',
            'interval' => 60,
            'display' => 'Every Minute'
        ],
        [
            'name' => 'three_minutes',
            'interval' => 180,
            'display' => 'Every Three Minutes'
        ],
        [
            'name' => 'thirty_minutes',
            'interval' => 1800,
            'display' => 'Every Thirty Minutes'
        ]
    ];

    function __construct()
    {
    }

    function init()
    {
        $this->_define();
        // $this->_actions($this->actions);
        $this->_filters($this->filters);
    }

    function _define()
    {
        $this->filters = [
            'add_subpage' => [
                'hook' => 'cron_schedules',
                'function' => 'custom_frequency',
                'args' => 1
            ]
        ];
    }

    function print_tasks()
    {
        wpp(_get_cron_array());
        die;
    }

    function custom_frequency($schedules)
    {
        $freqs = $this->frequencies;
        foreach ($freqs as $freq) {
            $ns = $freq['name'];
            unset($freq['name']);

            $schedules[$ns] = $freq;
        }

        return $schedules;
    }

    function is_scheduled($ns)
    {

        return wp_next_scheduled($ns);
    }

    function schedule($action, $frequency = 'five_minutes')
    {
        // run a check if the event is scheduled
        $check = $this->is_scheduled($action);

        if ($check) {
            $crons = _get_cron_array();

            // it is scheduled lets see if we have to update the call //
            if ($crons[$check] && $cron = $crons[$check][$action]) {
                $cron = $cron[key($cron)];
                if ($frequency !== $cron['schedule']) {
                    // we need to update the cron //
                    $this->unschedule_hooks($action);
                    $this->schedule($action, $frequency);
                }
            }
        } else {
            // lets make sure we register the hook into our options for removal later //
            $actions = get_option('fulcrum_cron_actions');
            if (!$actions) $actions = [];

            $actions[$action] = $action;
            update_option('fulcrum_cron_actions', $actions);
            // schedule the unscheduled hook //
            wp_schedule_event(current_time('timestamp'), $frequency, $action);
        }
    }

    function unschedule_hooks($action = false)
    {
        $actions = (!$action ? get_option('fulcrum_cron_actions') : [$action]);

        if ($actions && count($actions) > 0) {
            foreach ($actions as $action) {
                $timestamp = $this->is_scheduled($action);
                wp_unschedule_event($timestamp, $action);
                wp_unschedule_hook($action);
            }
        }
    }

    function logger()
    {
        // run this on an action to dump logs from cron jobs //

    }
}

if (!function_exists('d__init_crons')) {
    function d__init_crons()
    {
        global $d__crons;

        return (!isset($d__crons) ? new d_crons() : $d__crons);
    }
}

$d__crons = d__init_crons();
$d__crons->init();
