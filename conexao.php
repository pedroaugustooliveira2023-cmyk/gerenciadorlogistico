<?php

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'logistica');
define('DB_USER', 'root');
define('DB_PASS', '');

$dbError = null;
$pdo = null;

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $pdo->exec('CREATE DATABASE IF NOT EXISTS ' . DB_NAME . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $pdo->exec("CREATE TABLE IF NOT EXISTS produtos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(30) NOT NULL,
        nome VARCHAR(120) NOT NULL,
        descricao VARCHAR(255) DEFAULT NULL,
        peso DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        valor DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS enderecos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cep VARCHAR(9) NOT NULL,
        logradouro VARCHAR(150) NOT NULL,
        numero VARCHAR(10) NOT NULL,
        complemento VARCHAR(80) DEFAULT NULL,
        bairro VARCHAR(80) NOT NULL,
        cidade VARCHAR(80) NOT NULL,
        uf CHAR(2) NOT NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS transportadores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(120) NOT NULL,
        cnpj VARCHAR(18) NOT NULL,
        telefone VARCHAR(20) DEFAULT NULL,
        email VARCHAR(120) DEFAULT NULL,
        modal VARCHAR(40) NOT NULL DEFAULT 'Rodoviario',
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS fretes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(30) NOT NULL,
        transportador_id INT NOT NULL,
        origem_id INT NOT NULL,
        destino_id INT NOT NULL,
        data_frete DATE NOT NULL,
        valor_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_frete_transportador FOREIGN KEY (transportador_id) REFERENCES transportadores(id),
        CONSTRAINT fk_frete_origem FOREIGN KEY (origem_id) REFERENCES enderecos(id),
        CONSTRAINT fk_frete_destino FOREIGN KEY (destino_id) REFERENCES enderecos(id)
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS frete_produtos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        frete_id INT NOT NULL,
        produto_id INT NOT NULL,
        quantidade INT NOT NULL DEFAULT 1,
        CONSTRAINT fk_fp_frete FOREIGN KEY (frete_id) REFERENCES fretes(id) ON DELETE CASCADE,
        CONSTRAINT fk_fp_produto FOREIGN KEY (produto_id) REFERENCES produtos(id)
    ) ENGINE=InnoDB");

    $transportadores = $pdo->query('SELECT COUNT(*) FROM transportadores')->fetchColumn();
    if ((int) $transportadores === 0) {
        $pdo->exec("INSERT INTO transportadores (nome, cnpj, telefone, email, modal) VALUES
            ('Transportes Silva Ltda', '12.345.678/0001-90', '(44) 3333-1111', 'contato@silvatransportes.com', 'Rodoviario'),
            ('Rapido Express', '98.765.432/0001-10', '(44) 3333-2222', 'vendas@rapidoexpress.com', 'Aereo')");
    }

    $enderecos = $pdo->query('SELECT COUNT(*) FROM enderecos')->fetchColumn();
    if ((int) $enderecos === 0) {
        $pdo->exec("INSERT INTO enderecos (cep, logradouro, numero, bairro, cidade, uf) VALUES
            ('87010-000', 'Av. Brasil', '1200', 'Centro', 'Maringa', 'PR'),
            ('87500-000', 'Rua das Flores', '45', 'Jardim A', 'Umuarama', 'PR')");
    }

    $produtos = $pdo->query('SELECT COUNT(*) FROM produtos')->fetchColumn();
    if ((int) $produtos === 0) {
        $pdo->exec("INSERT INTO produtos (codigo, nome, descricao, peso, valor) VALUES
            ('P001', 'Cimento CP-II 50kg', 'Saco de cimento', 50.00, 38.90),
            ('P002', 'Tijolo Baiano', 'Milheiro de tijolo', 1200.00, 890.00)");
    }
} catch (Throwable $e) {
    $dbError = $e->getMessage();
    $pdo = null;
}
