<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

$option_name = 'RYVER_WEBHOOK';

delete_option($option_name);
?>
