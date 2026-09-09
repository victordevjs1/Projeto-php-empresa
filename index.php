<?php

require_once "conexao.php";

// ============================================================
// INDICADORES DO DASHBOARD
// ============================================================

// Clientes
$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM clientes
    WHERE ativo = TRUE
");

$totalClientes = $stmt->fetch()['total'];


// Produtos
$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM produtos
    WHERE ativo = TRUE
");

$totalProdutos = $stmt->fetch()['total'];


// Funcionários
$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM funcionarios
    WHERE status = 'ATIVO'
");

$totalFuncionarios = $stmt->fetch()['total'];


// Faturamento
$stmt = $pdo->query("
    SELECT COALESCE(SUM(total), 0) AS total
    FROM vendas
    WHERE status <> 'CANCELADA'
");

$faturamento = $stmt->fetch()['total'];


// Contas a receber
$stmt = $pdo->query("
    SELECT COALESCE(
        SUM(valor_original - valor_pago), 0
    ) AS total
    FROM contas_receber
    WHERE status IN ('PENDENTE', 'ATRASADO')
");

$contasReceber = $stmt->fetch()['total'];


// Contas a pagar
$stmt = $pdo->query("
    SELECT COALESCE(
        SUM(valor_original - valor_pago), 0
    ) AS total
    FROM contas_pagar
    WHERE status IN ('PENDENTE', 'ATRASADO')
");

$contasPagar = $stmt->fetch()['total'];


// Produtos com estoque baixo
$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM produtos p
    LEFT JOIN estoques e
        ON e.produto_id = p.id
    WHERE p.ativo = TRUE
      AND COALESCE(e.quantidade, 0) <= p.estoque_minimo
");

$estoqueBaixo = $stmt->fetch()['total'];



// Últimas vendas
$stmt = $pdo->query("
    SELECT
        v.id,
        v.data_venda,
        c.nome AS cliente,
        v.total,
        v.status
    FROM vendas v
    LEFT JOIN clientes c
        ON c.id = v.cliente_id
    ORDER BY v.data_venda DESC
    LIMIT 8
");

$ultimasVendas = $stmt->fetchAll();


// Produtos mais vendidos
$stmt = $pdo->query("
    SELECT
        nome,
        quantidade_vendida,
        faturamento
    FROM vw_produtos_mais_vendidos
    ORDER BY quantidade_vendida DESC
    LIMIT 5
");

$produtosMaisVendidos = $stmt->fetchAll();


// ============================================================
// FUNÇÕES
// ============================================================

function moeda($valor)
{
    return 'R$ ' . number_format(
        (float)$valor,
        2,
        ',',
        '.'
    );
}

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Empresa - Dashboard</title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Bootstrap Icons -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >


    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f5f7fb;
            font-family: Arial, Helvetica, sans-serif;
        }


        /* =====================================================
           SIDEBAR
        ===================================================== */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;

            width: 250px;
            height: 100vh;

            background: #111827;
            color: white;

            padding: 25px 15px;

            z-index: 1000;
        }


        .logo {
            font-size: 22px;
            font-weight: bold;

            padding: 10px 15px 30px;
        }


        .logo i {
            color: #3b82f6;
        }


        .menu-title {
            font-size: 11px;
            color: #9ca3af;

            margin: 20px 15px 8px;

            text-transform: uppercase;
        }


        .sidebar a {
            display: flex;
            align-items: center;

            gap: 12px;

            padding: 12px 15px;

            margin-bottom: 4px;

            border-radius: 8px;

            color: #d1d5db;

            text-decoration: none;

            transition: .2s;
        }


        .sidebar a:hover,
        .sidebar a.active {
            background: #2563eb;
            color: white;
        }


        .sidebar a i {
            font-size: 18px;
        }


        /* =====================================================
           CONTEÚDO
        ===================================================== */

        .main {
            margin-left: 250px;
            padding: 25px;
        }


        .topbar {
            display: flex;

            justify-content: space-between;
            align-items: center;

            margin-bottom: 25px;
        }


        .topbar h1 {
            font-size: 26px;
            font-weight: 700;

            margin: 0;
        }


        .user {
            display: flex;

            align-items: center;

            gap: 10px;
        }


        .avatar {
            width: 40px;
            height: 40px;

            border-radius: 50%;

            background: #2563eb;

            color: white;

            display: flex;

            align-items: center;
            justify-content: center;

            font-weight: bold;
        }


        /* =====================================================
           CARDS
        ===================================================== */

        .dashboard-card {
            border: none;

            border-radius: 14px;

            background: white;

            padding: 20px;

            box-shadow: 0 3px 15px rgba(0,0,0,.05);

            height: 100%;
        }


        .card-icon {
            width: 48px;
            height: 48px;

            border-radius: 12px;

            display: flex;

            align-items: center;
            justify-content: center;

            font-size: 22px;
        }


        .blue {
            background: #dbeafe;
            color: #2563eb;
        }


        .green {
            background: #dcfce7;
            color: #16a34a;
        }


        .orange {
            background: #ffedd5;
            color: #ea580c;
        }


        .red {
            background: #fee2e2;
            color: #dc2626;
        }


        .purple {
            background: #ede9fe;
            color: #7c3aed;
        }


        .card-label {
            color: #6b7280;

            font-size: 14px;

            margin-top: 15px;
        }


        .card-value {
            font-size: 25px;

            font-weight: 700;

            color: #111827;
        }


        /* =====================================================
           TABELAS
        ===================================================== */

        .section-card {
            background: white;

            border-radius: 14px;

            padding: 20px;

            box-shadow: 0 3px 15px rgba(0,0,0,.05);
        }


        .section-title {
            font-size: 18px;

            font-weight: 700;

            margin-bottom: 20px;
        }


        table {
            vertical-align: middle;
        }


        .status {
            padding: 5px 10px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 600;
        }


        .status-paga {
            background: #dcfce7;
            color: #15803d;
        }


        .status-aberta {
            background: #fef3c7;
            color: #92400e;
        }


        .status-cancelada {
            background: #fee2e2;
            color: #b91c1c;
        }


        /* =====================================================
           RESPONSIVO
        ===================================================== */

        @media(max-width: 900px) {

            .sidebar {
                width: 70px;
            }

            .sidebar .logo span,
            .sidebar a span,
            .menu-title {
                display: none;
            }

            .sidebar a {
                justify-content: center;
            }

            .main {
                margin-left: 70px;
            }

        }


        @media(max-width: 600px) {

            .main {
                padding: 15px;
            }

            .topbar h1 {
                font-size: 21px;
            }

        }

    </style>

</head>


<body>


<!-- ========================================================
     SIDEBAR
========================================================= -->

<aside class="sidebar">


    <div class="logo">

        <i class="bi bi-buildings"></i>

        <span>Empresa</span>

    </div>


    <div class="menu-title">
        Principal
    </div>


    <a href="index.php" class="active">

        <i class="bi bi-grid"></i>

        <span>Dashboard</span>

    </a>


    <div class="menu-title">
        Gestão
    </div>


    <a href="clientes.php">

        <i class="bi bi-people"></i>

        <span>Clientes</span>

    </a>


    <a href="produtos.php">

        <i class="bi bi-box-seam"></i>

        <span>Produtos</span>

    </a>


    <a href="vendas.php">

        <i class="bi bi-cart-check"></i>

        <span>Vendas</span>

    </a>


    <a href="compras.php">

        <i class="bi bi-bag"></i>

        <span>Compras</span>

    </a>


    <a href="estoque.php">

        <i class="bi bi-boxes"></i>

        <span>Estoque</span>

    </a>


    <div class="menu-title">
        Financeiro
    </div>


    <a href="contas_receber.php">

        <i class="bi bi-cash-stack"></i>

        <span>Contas a Receber</span>

    </a>


    <a href="contas_pagar.php">

        <i class="bi bi-credit-card"></i>

        <span>Contas a Pagar</span>

    </a>


    <div class="menu-title">
        Administração
    </div>


    <a href="funcionarios.php">

        <i class="bi bi-person-badge"></i>

        <span>Funcionários</span>

    </a>


    <a href="configuracoes.php">

        <i class="bi bi-gear"></i>

        <span>Configurações</span>

    </a>

</aside>



<!-- ========================================================
     CONTEÚDO PRINCIPAL
========================================================= -->

<main class="main">


    <!-- TOPBAR -->

    <div class="topbar">

        <div>

            <h1>Dashboard</h1>

            <small class="text-muted">
                Visão geral da empresa
            </small>

        </div>


        <div class="user">

            <div>

                <strong>Administrador</strong>

                <br>

                <small class="text-muted">
                    Sistema
                </small>

            </div>


            <div class="avatar">
                A
            </div>

        </div>

    </div>



    <!-- ====================================================
         INDICADORES
    ===================================================== -->

    <div class="row g-4 mb-4">


        <!-- FATURAMENTO -->

        <div class="col-xl-3 col-md-6">

            <div class="dashboard-card">

                <div class="card-icon green">

                    <i class="bi bi-currency-dollar"></i>

                </div>


                <div class="card-label">
                    Faturamento
                </div>


                <div class="card-value">

                    <?= moeda($faturamento) ?>

                </div>

            </div>

        </div>



        <!-- CLIENTES -->

        <div class="col-xl-3 col-md-6">

            <div class="dashboard-card">

                <div class="card-icon blue">

                    <i class="bi bi-people"></i>

                </div>


                <div class="card-label">
                    Clientes
                </div>


                <div class="card-value">

                    <?= number_format(
                        $totalClientes,
                        0,
                        ',',
                        '.'
                    ) ?>

                </div>

            </div>

        </div>



        <!-- PRODUTOS -->

        <div class="col-xl-3 col-md-6">

            <div class="dashboard-card">

                <div class="card-icon purple">

                    <i class="bi bi-box-seam"></i>

                </div>


                <div class="card-label">
                    Produtos
                </div>


                <div class="card-value">

                    <?= number_format(
                        $totalProdutos,
                        0,
                        ',',
                        '.'
                    ) ?>

                </div>

            </div>

        </div>



        <!-- FUNCIONÁRIOS -->

        <div class="col-xl-3 col-md-6">

            <div class="dashboard-card">

                <div class="card-icon orange">

                    <i class="bi bi-person-badge"></i>

                </div>


                <div class="card-label">
                    Funcionários
                </div>


                <div class="card-value">

                    <?= number_format(
                        $totalFuncionarios,
                        0,
                        ',',
                        '.'
                    ) ?>

                </div>

            </div>

        </div>

    </div>



    <!-- ====================================================
         FINANCEIRO
    ===================================================== -->

    <div class="row g-4 mb-4">


        <!-- RECEBER -->

        <div class="col-md-4">

            <div class="dashboard-card">

                <div class="card-icon green">

                    <i class="bi bi-arrow-down-circle"></i>

                </div>


                <div class="card-label">
                    Contas a Receber
                </div>


                <div class="card-value">

                    <?= moeda($contasReceber) ?>

                </div>

            </div>

        </div>


        <!-- PAGAR -->

        <div class="col-md-4">

            <div class="dashboard-card">

                <div class="card-icon red">

                    <i class="bi bi-arrow-up-circle"></i>

                </div>


                <div class="card-label">
                    Contas a Pagar
                </div>


                <div class="card-value">

                    <?= moeda($contasPagar) ?>

                </div>

            </div>

        </div>


        <!-- ESTOQUE -->

        <div class="col-md-4">

            <div class="dashboard-card">

                <div class="card-icon orange">

                    <i class="bi bi-exclamation-triangle"></i>

                </div>


                <div class="card-label">
                    Estoque baixo
                </div>


                <div class="card-value">

                    <?= number_format(
                        $estoqueBaixo,
                        0,
                        ',',
                        '.'
                    ) ?>

                </div>

            </div>

        </div>

    </div>



    <!-- ====================================================
         TABELAS
    ===================================================== -->

    <div class="row g-4">


        <!-- ÚLTIMAS VENDAS -->

        <div class="col-lg-8">

            <div class="section-card">

                <div class="section-title">

                    <i class="bi bi-cart-check me-2"></i>

                    Últimas vendas

                </div>


                <div class="table-responsive">

                    <table class="table table-hover">

                        <thead>

                            <tr>

                                <th>#</th>

                                <th>Cliente</th>

                                <th>Data</th>

                                <th>Total</th>

                                <th>Status</th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php if (count($ultimasVendas) > 0): ?>

                            <?php foreach ($ultimasVendas as $venda): ?>

                                <tr>

                                    <td>
                                        #<?= $venda['id'] ?>
                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $venda['cliente']
                                                ?? 'Cliente não informado'
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= date(
                                            'd/m/Y H:i',
                                            strtotime(
                                                $venda['data_venda']
                                            )
                                        ) ?>

                                    </td>


                                    <td>

                                        <strong>

                                            <?= moeda(
                                                $venda['total']
                                            ) ?>

                                        </strong>

                                    </td>


                                    <td>

                                        <?php

                                        if ($venda['status'] === 'PAGA') {

                                            $classe = 'status-paga';

                                        } elseif (
                                            $venda['status'] === 'CANCELADA'
                                        ) {

                                            $classe = 'status-cancelada';

                                        } else {

                                            $classe = 'status-aberta';

                                        }

                                        ?>


                                        <span
                                            class="status <?= $classe ?>"
                                        >

                                            <?= htmlspecialchars(
                                                $venda['status']
                                            ) ?>

                                        </span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="5"
                                    class="text-center text-muted py-4"
                                >

                                    Nenhuma venda cadastrada.

                                </td>

                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>



        <!-- PRODUTOS MAIS VENDIDOS -->

        <div class="col-lg-4">

            <div class="section-card">

                <div class="section-title">

                    <i class="bi bi-trophy me-2"></i>

                    Mais vendidos

                </div>


                <?php if (count($produtosMaisVendidos) > 0): ?>


                    <?php foreach (
                        $produtosMaisVendidos
                        as $produto
                    ): ?>

                        <div
                            class="d-flex justify-content-between align-items-center mb-3"
                        >

                            <div>

                                <strong>

                                    <?= htmlspecialchars(
                                        $produto['nome']
                                    ) ?>

                                </strong>

                                <br>

                                <small class="text-muted">

                                    <?= number_format(
                                        $produto[
                                            'quantidade_vendida'
                                        ],
                                        0,
                                        ',',
                                        '.'
                                    ) ?>

                                    unidades

                                </small>

                            </div>


                            <strong>

                                <?= moeda(
                                    $produto['faturamento']
                                ) ?>

                            </strong>

                        </div>

                    <?php endforeach; ?>


                <?php else: ?>

                    <p class="text-muted">

                        Nenhuma venda registrada.

                    </p>

                <?php endif; ?>

            </div>

        </div>

    </div>

</main>


</body>

</html>
