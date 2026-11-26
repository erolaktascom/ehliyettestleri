<?php

namespace App\Support\Installation;

use PDO;
use PDOException;
use RuntimeException;

class MysqlDatabase
{
    public static function connect(array $data): PDO
    {
        try {
            $pdo = new PDO('mysql:host='.$data['db_host'].';port='.$data['db_port'].';dbname='.$data['db_database'].';charset=utf8mb4',
                $data['db_username'], $data['db_password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 10]);
        } catch (PDOException) {
            throw new RuntimeException('MySQL bağlantısı kurulamadı. Sunucu, port, veritabanı adı, kullanıcı adı, şifre ve kullanıcı yetkilerini kontrol edin.');
        }
        $version = (string)$pdo->query('SELECT VERSION()')->fetchColumn();
        $maria = stripos($version, 'MariaDB') !== false;
        $normalized = preg_replace('/^5\.5\.5-/', '', $version);
        if (!preg_match('/^\d+\.\d+\.\d+/', $normalized, $match)
            || version_compare($match[0], $maria ? '10.3.0' : '8.0.0', '<')) {
            throw new RuntimeException('Kurulum için MySQL 8.0 veya MariaDB 10.3 ve üzeri gerekli.');
        }
        return $pdo;
    }

    public static function configuration(array $data): array
    {
        return ['driver' => 'mysql', 'url' => null, 'host' => $data['db_host'], 'port' => $data['db_port'],
            'database' => $data['db_database'], 'username' => $data['db_username'], 'password' => $data['db_password'],
            'unix_socket' => '', 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '', 'prefix_indexes' => true, 'strict' => true, 'engine' => 'InnoDB',
            'options' => [PDO::ATTR_TIMEOUT => 10]];
    }
}
