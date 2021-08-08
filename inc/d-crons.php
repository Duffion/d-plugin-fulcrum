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
            'name' => 'three_minutes',
            'interval' => 180,
            'display' => 'Every Three Minutes'
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
            // it is scheduled lets see if we have to update the call //

        } else {
            // schedule the unscheduled hook //
            wp_schedule_event(current_time('timestamp'), $frequency, $action);
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
