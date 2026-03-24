<?php
//config/autoload/doctrine.global.php
return array(
    'doctrine' => array(
        'connection' => array(
            'orm_default' => array(
                'driverClass' => 'Doctrine\DBAL\Driver\PDOMySql\Driver',
                'params' => array(
                    'user'      => getenv('DB_USER') ?: 'root',
                    'password'  => getenv('DB_PASSWORD') ?: '',
                    'host'      => getenv('DB_HOST') ?: 'localhost',
                    'port'      => getenv('DB_PORT') ?: '3306',
                    'dbname'    => getenv('DB_NAME') ?: 'certificados',
                    'driverOptions' => array(
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'",
                    )

                ),
                'doctrine_type_mappings' => array(
                    'enum' => 'string'
                ),
            ),
        ),
    ),
    'php_settings' => array(
        'date.timezone'                 => 'America/Bahia',
        'mbstring.internal_encoding'    => 'UTF-8',
    )
);