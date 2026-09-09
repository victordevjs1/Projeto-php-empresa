CREATE DATABASE IF NOT EXISTS empresa_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE empresa_db;


-- ============================================================
-- DEPARTAMENTOS
-- ============================================================

CREATE TABLE departamentos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descricao TEXT,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- ============================================================
-- CARGOS
-- ============================================================

CREATE TABLE cargos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descricao TEXT,
    salario_base DECIMAL(12,2) NOT NULL DEFAULT 0,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- ============================================================
-- FUNCIONÁRIOS
-- ============================================================

CREATE TABLE funcionarios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    departamento_id BIGINT UNSIGNED NULL,
    cargo_id BIGINT UNSIGNED NULL,

    nome VARCHAR(150) NOT NULL,
    cpf VARCHAR(14) UNIQUE,
    rg VARCHAR(20),
    data_nascimento DATE,
    email VARCHAR(150) UNIQUE,
    telefone VARCHAR(30),

    data_admissao DATE NOT NULL,
    data_demissao DATE,

    salario DECIMAL(12,2) NOT NULL DEFAULT 0,

    status ENUM(
        'ATIVO',
        'AFASTADO',
        'FERIAS',
        'DEMITIDO'
    ) NOT NULL DEFAULT 'ATIVO',

    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_func_departamento
        FOREIGN KEY (departamento_id)
        REFERENCES departamentos(id),

    CONSTRAINT fk_func_cargo
        FOREIGN KEY (cargo_id)
        REFERENCES cargos(id)
) ENGINE=InnoDB;


-- ============================================================
-- ENDEREÇOS
-- ============================================================

CREATE TABLE enderecos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    logradouro VARCHAR(200) NOT NULL,
    numero VARCHAR(20),
    complemento VARCHAR(100),
    bairro VARCHAR(100),
    cidade VARCHAR(100) NOT NULL,
    estado CHAR(2) NOT NULL,
    cep VARCHAR(10),

    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- ============================================================
-- CLIENTES
-- ============================================================

CREATE TABLE clientes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    nome VARCHAR(150) NOT NULL,

    tipo ENUM(
        'PF',
        'PJ'
    ) NOT NULL DEFAULT 'PF',

    cpf_cnpj VARCHAR(18) UNIQUE,

    email VARCHAR(150),
    telefone VARCHAR(30),

    endereco_id BIGINT UNSIGNED NULL,

    ativo BOOLEAN NOT NULL DEFAULT TRUE,

    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_cliente_endereco
        FOREIGN KEY (endereco_id)
        REFERENCES enderecos(id)
) ENGINE=InnoDB;


-- ============================================================
-- FORNECEDORES
-- ============================================================

CREATE TABLE fornecedores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    razao_social VARCHAR(200) NOT NULL,
    nome_fantasia VARCHAR(150),
    cnpj VARCHAR(18) UNIQUE,

    email VARCHAR(150),
    telefone VARCHAR(30),

    endereco_id BIGINT UNSIGNED NULL,

    ativo BOOLEAN NOT NULL DEFAULT TRUE,

    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_fornecedor_endereco
        FOREIGN KEY (endereco_id)
        REFERENCES enderecos(id)
) ENGINE=InnoDB;


-- ============================================================
-- CATEGORIAS
-- ============================================================

CREATE TABLE categorias (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    nome VARCHAR(100) NOT NULL UNIQUE,
    descricao TEXT,

    categoria_pai_id BIGINT UNSIGNED NULL,

    ativo BOOLEAN NOT NULL DEFAULT TRUE,

    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_categoria_pai
        FOREIGN KEY (categoria_pai_id)
        REFERENCES categorias(id)
) ENGINE=InnoDB;


-- ============================================================
-- PRODUTOS
-- ============================================================

CREATE TABLE produtos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    categoria_id BIGINT UNSIGNED NULL,
    fornecedor_id BIGINT UNSIGNED NULL,

    codigo VARCHAR(50) NOT NULL UNIQUE,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT,

    unidade VARCHAR(10) NOT NULL DEFAULT 'UN',

    preco_custo DECIMAL(12,2) NOT NULL DEFAULT 0,
    preco_venda DECIMAL(12,2) NOT NULL DEFAULT 0,

    estoque_minimo DECIMAL(12,3) NOT NULL DEFAULT 0,
    estoque_maximo DECIMAL(12,3),

    ativo BOOLEAN NOT NULL DEFAULT TRUE,

    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_produto_categoria
        FOREIGN KEY (categoria_id)
        REFERENCES categorias(id),

    CONSTRAINT fk_produto_fornecedor
        FOREIGN KEY (fornecedor_id)
        REFERENCES fornecedores(id)
) ENGINE=InnoDB;


-- ============================================================
-- ESTOQUES
-- ============================================================

CREATE TABLE estoques (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    produto_id BIGINT UNSIGNED NOT NULL UNIQUE,

    quantidade DECIMAL(12,3) NOT NULL DEFAULT 0,

    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_estoque_produto
        FOREIGN KEY (produto_id)
        REFERENCES produtos(id)
) ENGINE=InnoDB;


-- ============================================================
-- MOVIMENTAÇÕES DE ESTOQUE
-- ============================================================

CREATE TABLE movimentacoes_estoque (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    produto_id BIGINT UNSIGNED NOT NULL,

    tipo ENUM(
        'ENTRADA',
        'SAIDA',
        'AJUSTE'
    ) NOT NULL,

    quantidade DECIMAL(12,3) NOT NULL,

    quantidade_anterior DECIMAL(12,3) NOT NULL,
    quantidade_nova DECIMAL(12,3) NOT NULL,

    motivo VARCHAR(255),

    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_movimentacao_produto
        FOREIGN KEY (produto_id)
        REFERENCES produtos(id)
) ENGINE=InnoDB;


-- ============================================================
-- VENDAS
-- ============================================================

CREATE TABLE vendas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    cliente_id BIGINT UNSIGNED NULL,
    funcionario_id BIGINT UNSIGNED NULL,

    data_venda TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    desconto DECIMAL(12,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,

    status ENUM(
        'ABERTA',
        'PAGA',
        'CANCELADA',
        'PARCIAL'
    ) NOT NULL DEFAULT 'ABERTA',

    observacoes TEXT,

    CONSTRAINT fk_venda_cliente
        FOREIGN KEY (cliente_id)
        REFERENCES clientes(id),

    CONSTRAINT fk_venda_funcionario
        FOREIGN KEY (funcionario_id)
        REFERENCES funcionarios(id)
) ENGINE=InnoDB;


-- ============================================================
-- ITENS DA VENDA
-- ============================================================

CREATE TABLE venda_itens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    venda_id BIGINT UNSIGNED NOT NULL,
    produto_id BIGINT UNSIGNED NOT NULL,

    quantidade DECIMAL(12,3) NOT NULL,
    preco_unitario DECIMAL(12,2) NOT NULL,
    desconto DECIMAL(12,2) NOT NULL DEFAULT 0,
    subtotal DECIMAL(12,2) NOT NULL,

    CONSTRAINT fk_item_venda
        FOREIGN KEY (venda_id)
        REFERENCES vendas(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_item_produto
        FOREIGN KEY (produto_id)
        REFERENCES produtos(id)
) ENGINE=InnoDB;


-- ============================================================
-- COMPRAS
-- ============================================================

CREATE TABLE compras (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    fornecedor_id BIGINT UNSIGNED NOT NULL,
    funcionario_id BIGINT UNSIGNED NULL,

    numero_nota VARCHAR(50),

    data_compra TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    desconto DECIMAL(12,2) NOT NULL DEFAULT 0,
    frete DECIMAL(12,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,

    status ENUM(
        'ABERTA',
        'RECEBIDA',
        'CANCELADA',
        'PARCIAL'
    ) NOT NULL DEFAULT 'ABERTA',

    observacoes TEXT,

    CONSTRAINT fk_compra_fornecedor
        FOREIGN KEY (fornecedor_id)
        REFERENCES fornecedores(id),

    CONSTRAINT fk_compra_funcionario
        FOREIGN KEY (funcionario_id)
        REFERENCES funcionarios(id)
) ENGINE=InnoDB;


-- ============================================================
-- ITENS DA COMPRA
-- ============================================================

CREATE TABLE compra_itens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    compra_id BIGINT UNSIGNED NOT NULL,
    produto_id BIGINT UNSIGNED NOT NULL,

    quantidade DECIMAL(12,3) NOT NULL,
    preco_unitario DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,

    CONSTRAINT fk_item_compra
        FOREIGN KEY (compra_id)
        REFERENCES compras(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_compra_produto
        FOREIGN KEY (produto_id)
        REFERENCES produtos(id)
) ENGINE=InnoDB;


-- ============================================================
-- FORMAS DE PAGAMENTO
-- ============================================================

CREATE TABLE formas_pagamento (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    nome VARCHAR(50) NOT NULL UNIQUE,

    ativo BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;


-- ============================================================
-- PAGAMENTOS DAS VENDAS
-- ============================================================

CREATE TABLE venda_pagamentos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    venda_id BIGINT UNSIGNED NOT NULL,
    forma_pagamento_id BIGINT UNSIGNED NOT NULL,

    valor DECIMAL(12,2) NOT NULL,

    data_pagamento TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_pagamento_venda
        FOREIGN KEY (venda_id)
        REFERENCES vendas(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_pagamento_forma
        FOREIGN KEY (forma_pagamento_id)
        REFERENCES formas_pagamento(id)
) ENGINE=InnoDB;


-- ============================================================
-- CONTAS A RECEBER
-- ============================================================

CREATE TABLE contas_receber (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    cliente_id BIGINT UNSIGNED NULL,
    venda_id BIGINT UNSIGNED NULL,

    descricao VARCHAR(255) NOT NULL,

    valor_original DECIMAL(12,2) NOT NULL,
    valor_pago DECIMAL(12,2) NOT NULL DEFAULT 0,

    vencimento DATE NOT NULL,
    data_pagamento DATE,

    status ENUM(
        'PENDENTE',
        'PAGO',
        'ATRASADO',
        'CANCELADO'
    ) NOT NULL DEFAULT 'PENDENTE',

    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_receber_cliente
        FOREIGN KEY (cliente_id)
        REFERENCES clientes(id),

    CONSTRAINT fk_receber_venda
        FOREIGN KEY (venda_id)
        REFERENCES vendas(id)
) ENGINE=InnoDB;


-- ============================================================
-- CONTAS A PAGAR
-- ============================================================

CREATE TABLE contas_pagar (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    fornecedor_id BIGINT UNSIGNED NULL,
    compra_id BIGINT UNSIGNED NULL,

    descricao VARCHAR(255) NOT NULL,

    valor_original DECIMAL(12,2) NOT NULL,
    valor_pago DECIMAL(12,2) NOT NULL DEFAULT 0,

    vencimento DATE NOT NULL,
    data_pagamento DATE,

    status ENUM(
        'PENDENTE',
        'PAGO',
        'ATRASADO',
        'CANCELADO'
    ) NOT NULL DEFAULT 'PENDENTE',

    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_pagar_fornecedor
        FOREIGN KEY (fornecedor_id)
        REFERENCES fornecedores(id),

    CONSTRAINT fk_pagar_compra
        FOREIGN KEY (compra_id)
        REFERENCES compras(id)
) ENGINE=InnoDB;


-- ============================================================
-- USUÁRIOS
-- ============================================================

CREATE TABLE usuarios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    funcionario_id BIGINT UNSIGNED UNIQUE,

    nome_usuario VARCHAR(50) NOT NULL UNIQUE,

    senha_hash VARCHAR(255) NOT NULL,

    ativo BOOLEAN NOT NULL DEFAULT TRUE,

    ultimo_login TIMESTAMP NULL,

    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_usuario_funcionario
        FOREIGN KEY (funcionario_id)
        REFERENCES funcionarios(id)
) ENGINE=InnoDB;


-- ============================================================
-- PERFIS
-- ============================================================

CREATE TABLE perfis (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    nome VARCHAR(50) NOT NULL UNIQUE,

    descricao TEXT
) ENGINE=InnoDB;


-- ============================================================
-- USUÁRIOS X PERFIS
-- ============================================================

CREATE TABLE usuario_perfis (
    usuario_id BIGINT UNSIGNED NOT NULL,
    perfil_id BIGINT UNSIGNED NOT NULL,

    PRIMARY KEY (usuario_id, perfil_id),

    CONSTRAINT fk_usuario_perfil_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_usuario_perfil_perfil
        FOREIGN KEY (perfil_id)
        REFERENCES perfis(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;


-- ============================================================
-- PERMISSÕES
-- ============================================================

CREATE TABLE permissoes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    nome VARCHAR(100) NOT NULL UNIQUE,

    descricao TEXT
) ENGINE=InnoDB;


-- ============================================================
-- PERFIS X PERMISSÕES
-- ============================================================

CREATE TABLE perfil_permissoes (
    perfil_id BIGINT UNSIGNED NOT NULL,
    permissao_id BIGINT UNSIGNED NOT NULL,

    PRIMARY KEY (perfil_id, permissao_id),

    CONSTRAINT fk_perfil_permissao_perfil
        FOREIGN KEY (perfil_id)
        REFERENCES perfis(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_perfil_permissao_permissao
        FOREIGN KEY (permissao_id)
        REFERENCES permissoes(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;


-- ============================================================
-- AUDITORIA
-- ============================================================

CREATE TABLE auditoria (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    usuario_id BIGINT UNSIGNED NULL,

    tabela VARCHAR(100) NOT NULL,

    registro_id BIGINT UNSIGNED,

    operacao ENUM(
        'INSERT',
        'UPDATE',
        'DELETE'
    ) NOT NULL,

    dados_anteriores JSON,
    dados_novos JSON,

    ip VARCHAR(45),

    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_auditoria_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
) ENGINE=InnoDB;


-- ============================================================
-- DADOS INICIAIS
-- ============================================================

INSERT INTO departamentos (nome, descricao) VALUES
('Administrativo', 'Gestão administrativa da empresa'),
('Financeiro', 'Controle financeiro e contábil'),
('Comercial', 'Vendas e relacionamento com clientes'),
('Estoque', 'Controle de estoque e logística'),
('Recursos Humanos', 'Gestão de funcionários');


INSERT INTO cargos (nome, descricao, salario_base) VALUES
('Administrador', 'Administrador do sistema', 5000.00),
('Gerente', 'Gerente de departamento', 4000.00),
('Vendedor', 'Funcionário responsável por vendas', 2500.00),
('Assistente Administrativo', 'Assistente administrativo', 2000.00),
('Estoquista', 'Responsável pelo estoque', 2000.00);


INSERT INTO formas_pagamento (nome) VALUES
('Dinheiro'),
('PIX'),
('Cartão de Crédito'),
('Cartão de Débito'),
('Boleto'),
('Transferência Bancária');


INSERT INTO perfis (nome, descricao) VALUES
('Administrador', 'Acesso completo ao sistema'),
('Gerente', 'Acesso gerencial'),
('Vendedor', 'Acesso ao módulo de vendas'),
('Financeiro', 'Acesso ao módulo financeiro'),
('Estoque', 'Acesso ao módulo de estoque');


INSERT INTO permissoes (nome, descricao) VALUES
('CLIENTE_VISUALIZAR', 'Visualizar clientes'),
('CLIENTE_CRIAR', 'Cadastrar clientes'),
('CLIENTE_EDITAR', 'Editar clientes'),
('CLIENTE_EXCLUIR', 'Excluir clientes'),

('PRODUTO_VISUALIZAR', 'Visualizar produtos'),
('PRODUTO_CRIAR', 'Cadastrar produtos'),
('PRODUTO_EDITAR', 'Editar produtos'),
('PRODUTO_EXCLUIR', 'Excluir produtos'),

('VENDA_VISUALIZAR', 'Visualizar vendas'),
('VENDA_CRIAR', 'Criar vendas'),
('VENDA_CANCELAR', 'Cancelar vendas'),

('COMPRA_VISUALIZAR', 'Visualizar compras'),
('COMPRA_CRIAR', 'Criar compras'),

('ESTOQUE_VISUALIZAR', 'Visualizar estoque'),
('ESTOQUE_AJUSTAR', 'Realizar ajustes no estoque'),

('FINANCEIRO_VISUALIZAR', 'Visualizar financeiro'),
('FINANCEIRO_PAGAR', 'Registrar pagamentos'),
('FINANCEIRO_RECEBER', 'Registrar recebimentos'),

('FUNCIONARIO_VISUALIZAR', 'Visualizar funcionários'),
('FUNCIONARIO_CRIAR', 'Cadastrar funcionários'),
('FUNCIONARIO_EDITAR', 'Editar funcionários'),
('FUNCIONARIO_EXCLUIR', 'Excluir funcionários');


-- ============================================================
-- VIEWS
-- ============================================================

CREATE VIEW vw_estoque_atual AS
SELECT
    p.id,
    p.codigo,
    p.nome,
    c.nome AS categoria,
    COALESCE(e.quantidade, 0) AS quantidade,
    p.estoque_minimo,

    CASE
        WHEN COALESCE(e.quantidade, 0) <= p.estoque_minimo
        THEN 'REPOR'
        ELSE 'NORMAL'
    END AS situacao

FROM produtos p

LEFT JOIN categorias c
    ON c.id = p.categoria_id

LEFT JOIN estoques e
    ON e.produto_id = p.id

WHERE p.ativo = TRUE;


-- ============================================================
-- CONTAS A RECEBER
-- ============================================================

CREATE VIEW vw_contas_receber_abertas AS
SELECT
    cr.id,
    c.nome AS cliente,
    cr.descricao,
    cr.valor_original,
    cr.valor_pago,
    cr.valor_original - cr.valor_pago AS saldo,
    cr.vencimento,

    CASE
        WHEN cr.vencimento < CURRENT_DATE
             AND cr.status = 'PENDENTE'
        THEN 'ATRASADO'
        ELSE cr.status
    END AS situacao

FROM contas_receber cr

LEFT JOIN clientes c
    ON c.id = cr.cliente_id

WHERE cr.status IN ('PENDENTE', 'ATRASADO');


-- ============================================================
-- CONTAS A PAGAR
-- ============================================================

CREATE VIEW vw_contas_pagar_abertas AS
SELECT
    cp.id,
    f.razao_social AS fornecedor,
    cp.descricao,
    cp.valor_original,
    cp.valor_pago,
    cp.valor_original - cp.valor_pago AS saldo,
    cp.vencimento,

    CASE
        WHEN cp.vencimento < CURRENT_DATE
             AND cp.status = 'PENDENTE'
        THEN 'ATRASADO'
        ELSE cp.status
    END AS situacao

FROM contas_pagar cp

LEFT JOIN fornecedores f
    ON f.id = cp.fornecedor_id

WHERE cp.status IN ('PENDENTE', 'ATRASADO');


-- ============================================================
-- RESUMO DE VENDAS
-- ============================================================

CREATE VIEW vw_resumo_vendas AS
SELECT
    DATE(data_venda) AS data,
    COUNT(*) AS quantidade_vendas,
    SUM(total) AS faturamento

FROM vendas

WHERE status <> 'CANCELADA'

GROUP BY DATE(data_venda);


-- ============================================================
-- PRODUTOS MAIS VENDIDOS
-- ============================================================

CREATE VIEW vw_produtos_mais_vendidos AS
SELECT
    p.id,
    p.codigo,
    p.nome,

    SUM(vi.quantidade) AS quantidade_vendida,

    SUM(vi.subtotal) AS faturamento

FROM venda_itens vi

INNER JOIN vendas v
    ON v.id = vi.venda_id

INNER JOIN produtos p
    ON p.id = vi.produto_id

WHERE v.status <> 'CANCELADA'

GROUP BY
    p.id,
    p.codigo,
    p.nome;
