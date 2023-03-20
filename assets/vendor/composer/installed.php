<?php return array(
    'root' => array(
        'pretty_version' => '1.0.0+no-version-set',
        'version' => '1.0.0.0',
        'type' => 'library',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'reference' => NULL,
        'name' => '__root__',
        'dev' => true,
    ),
    'versions' => array(
        '__root__' => array(
            'pretty_version' => '1.0.0+no-version-set',
            'version' => '1.0.0.0',
            'type' => 'library',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'reference' => NULL,
            'dev_requirement' => false,
        ),
        'ezsql/ezsql' => array(
            'pretty_version' => '5.1.1',
            'version' => '5.1.1.0',
            'type' => 'library',
            'install_path' => __DIR__ . '/../ezsql/ezsql',
            'aliases' => array(),
            'reference' => '14968136b7bd4fc6e96ac19cd1fb9d88c87ea4c8',
            'dev_requirement' => false,
        ),
        'psr/container' => array(
            'pretty_version' => '1.1.2',
            'version' => '1.1.2.0',
            'type' => 'library',
            'install_path' => __DIR__ . '/../psr/container',
            'aliases' => array(),
            'reference' => '513e0666f7216c7459170d56df27dfcefe1689ea',
            'dev_requirement' => false,
        ),
        'psr/container-implementation' => array(
            'dev_requirement' => false,
            'provided' => array(
                0 => '1.0',
            ),
        ),
    ),
);
