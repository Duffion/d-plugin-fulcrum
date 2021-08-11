<?php

require 'plugin-update-checker.php';

$config = [
    'git' => 'https://github.com/Duffion/d-plugin-fulcrum',
    'target_branch' => 'production',
    'auth_token' => 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABgQCcYIb8MIY0NzmLbcHY/sOKW+GV4XKKwNr0bm0IPV6idZSZubQYLlgCHnCX85o66sEhZP8hdi2t+XJUmfNlkCmQWw4kqa1088A9R3x5Eb3nvdSfU5L3ToCeaTCySIFA+1FQiL530xtRiPvfEsIR9kg5Dk076MG7YHEESLSuj5A/VkgHfCmEmxQbTmNlmkR9uals3gh0QxvVx8IMt0i6blunohof429JWJIdeExtlWF3a6AJNOzqR95KR0+7bUOgwBLZLGXsYFyFBIrowzuCvNf2wJ4qxwhMJ0UnFiXp0O9Xg6v7R+5hVWXKjaHE6MATMF5pWPAGYMuhWMoykw1Cti80D/Pp5+LuInNPMXk0QGngQmtQi3cyySJt2J8hjwPgP8ZO0T8pBV/5jgllmaVMCeKgXZAXJsXfqNGSM3NjOKd0jTOAV0gsGiizrr2WtQOALpoNnwsjWyD8+DOdpaUp/u7vMeDP92TQHvjnVHJ6C4y/Yd4cRS32K7pQwGNILln/wz8= duffion@LAPTOP-VO88619D'
];


$update_checker = Puc_v4_Factory::buildUpdateChecker(
    $config['git'],
    __FILE__,
    'fulcrum'
);

//Set the branch that contains the stable release.
$update_checker->setBranch($config['target_branch']);

//Optional: If you're using a private repository, specify the access token like this:
// $update_checker->setAuthentication($config['auth_token']);

$d__plugin_info = $update_checker->getVcsApi()->enableReleaseAssets();
