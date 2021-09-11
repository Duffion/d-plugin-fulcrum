<?php

namespace D\FULCRUM\TRAITS;

trait TEMPLATES
{
    var $template_dir = '/templates/';

    function partial($sub, $filename, $args = [])
    {
        global $d_plugin_dirs, $d_fulcrum;

        $output = '';
        $dir = $d_plugin_dirs['plugin'] . $this->template_dir . $sub . '/';
        $file = $filename . '.php';
        if (file_exists($dir . $file)) {
            $args['current_version'] = $d_fulcrum->version;
            echo '<div class="versioning"><small>Current Version: ' . $args['current_version'] . '</small></div>';
            include_once($dir . $file);
        }
    }

    function start()
    {
    }

    function create_nonce()
    {
        return wp_create_nonce('fulcrum_nonce');
    }
}
