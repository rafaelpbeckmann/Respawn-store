DROP DATABASE IF EXISTS loja;

CREATE DATABASE loja
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE loja;


-- CLIENTES: quem compra na loja

CREATE TABLE clientes (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  nome        VARCHAR(100) NOT NULL,
  email       VARCHAR(150) NOT NULL UNIQUE,
  senha       VARCHAR(255) NOT NULL,
  cpf         CHAR(11)     NOT NULL UNIQUE,
  telefone    VARCHAR(20),
  cep         CHAR(8),
  logradouro  VARCHAR(150),
  numero      VARCHAR(10),
  complemento VARCHAR(60),
  bairro      VARCHAR(80),
  cidade      VARCHAR(80),
  uf          CHAR(2),
  is_admin    TINYINT(1)   NOT NULL DEFAULT 0,
  criado_em   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- CATEGORIAS: organiza os produtos

CREATE TABLE categorias (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;


-- PRODUTOS: cada produto tem UMA categoria

CREATE TABLE produtos (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  categoria_id INT           NOT NULL,
  nome         VARCHAR(120)  NOT NULL,
  descricao    TEXT,
  preco        DECIMAL(10,2) NOT NULL,
  estoque      INT           NOT NULL DEFAULT 0,
  imagem       VARCHAR(255),
  ativo        TINYINT(1)    NOT NULL DEFAULT 1,
  criado_em    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (categoria_id) REFERENCES categorias(id)
    ON DELETE RESTRICT
) ENGINE=InnoDB;


-- COMPRAS: cada compra é de UM cliente

CREATE TABLE compras (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id  INT           NOT NULL,
  data_compra DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  cep         CHAR(8)       NOT NULL,
  logradouro  VARCHAR(150)  NOT NULL,
  numero      VARCHAR(10)   NOT NULL,
  complemento VARCHAR(60),
  bairro      VARCHAR(80)   NOT NULL,
  cidade      VARCHAR(80)   NOT NULL,
  uf          CHAR(2)       NOT NULL,
  subtotal    DECIMAL(10,2) NOT NULL,
  frete       DECIMAL(10,2) NOT NULL,
  total       DECIMAL(10,2) NOT NULL,
  status      VARCHAR(20)   NOT NULL DEFAULT 'Pendente',
  FOREIGN KEY (cliente_id) REFERENCES clientes(id)
    ON DELETE RESTRICT
) ENGINE=InnoDB;


-- ITENS_COMPRA: quais produtos estão em cada compra

CREATE TABLE itens_compra (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  compra_id      INT           NOT NULL,
  produto_id     INT           NOT NULL,
  quantidade     INT           NOT NULL,
  preco_unitario DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (compra_id) REFERENCES compras(id)
    ON DELETE CASCADE,
  FOREIGN KEY (produto_id) REFERENCES produtos(id)
    ON DELETE RESTRICT
) ENGINE=InnoDB;