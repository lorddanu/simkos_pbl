<?php

function getConnection(): PDO
{
    $host     = 'localhost';
    $port     = '5432';
    $dbname   = 'simkos';
    $user     = 'postgres';
    $password = 'admin123';

    try {
        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
        $conn = new PDO($dsn, $user, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $conn;
    } catch (PDOException $e) {
        die('Koneksi database gagal: ' . $e->getMessage());
    }
}