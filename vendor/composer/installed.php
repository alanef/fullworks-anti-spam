<?php return array(
    'root' => array(
        'name' => 'fullworks/fullworks-anti-spam',
        'pretty_version' => 'dev-master',
        'version' => 'dev-master',
        'reference' => 'c5f5fe438c2f02f9260cc183e1b00f4a440e5c74',
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
            'reference' => '35782eb9f19eefc63723afb3196c768bca6dfbd9',
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
            'reference' => 'c5f5fe438c2f02f9260cc183e1b00f4a440e5c74',
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
        'squizlabs/php_codesniffer' => array(
            'pretty_version' => '3.7.1',
            'version' => '3.7.1.0',
            'reference' => '1359e176e9307e906dc3d890bcc9603ff6d90619',
            'type' => 'library',
            'install_path' => __DIR__ . '/../squizlabs/php_codesniffer',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'wp-coding-standards/wpcs' => array(
            'pretty_version' => '2.3.0',
            'version' => '2.3.0.0',
            'reference' => '7da1894633f168fe244afc6de00d141f27517b62',
            'type' => 'phpcodesniffer-standard',
            'install_path' => __DIR__ . '/../wp-coding-standards/wpcs',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
    ),
);
