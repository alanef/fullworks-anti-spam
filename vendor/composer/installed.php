<?php return array(
    'root' => array(
        'name' => 'fullworks/fullworks-anti-spam',
        'pretty_version' => 'dev-master',
        'version' => 'dev-master',
        'reference' => 'c14547daee7ba8e811728e67d1397cc6901389ee',
        'type' => 'wordpress-plugin',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => true,
    ),
    'versions' => array(
        'composer/installers' => array(
            'pretty_version' => 'v1.0.12',
            'version' => '1.0.12.0',
            'reference' => '4127333b03e8b4c08d081958548aae5419d1a2fa',
            'type' => 'composer-installer',
            'install_path' => __DIR__ . '/./installers',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'freemius/wordpress-sdk' => array(
            'pretty_version' => 'dev-master',
            'version' => 'dev-master',
            'reference' => 'ea3a288e52ff74c755b3c1eb857f41c5a2d899e5',
            'type' => 'library',
            'install_path' => __DIR__ . '/../freemius/wordpress-sdk',
            'aliases' => array(
                0 => '9999999-dev',
            ),
            'dev_requirement' => false,
        ),
        'fullworks/fullworks-anti-spam' => array(
            'pretty_version' => 'dev-master',
            'version' => 'dev-master',
            'reference' => 'c14547daee7ba8e811728e67d1397cc6901389ee',
            'type' => 'wordpress-plugin',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'gamajo/template-loader' => array(
            'pretty_version' => 'dev-master',
            'version' => 'dev-master',
            'reference' => '05057216f60baebfc4939e1a2b9aae6e89d25c91',
            'type' => 'library',
            'install_path' => __DIR__ . '/../gamajo/template-loader',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'shama/baton' => array(
            'dev_requirement' => false,
            'replaced' => array(
                0 => '*',
            ),
        ),
    ),
);
