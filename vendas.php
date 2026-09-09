<?php

require_once "conexao.php";

$mensagem = "";
$tipoMensagem = "";


// ============================================================
// REGISTRAR VENDA
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $clienteId = !empty($_POST['cliente_id'])
        ? (int) $_POST['cliente_id']
        : null;

    $formaPagamentoId = (int)(
        $_POST['forma_pagamento_id'] ?? 0
    );

    $produtoId = (int)(
        $_POST['produto_id'] ?? 0
    );

    $quantidade = (float)(
        $_POST['quantidade'] ?? 0
    );

    $desconto = (float)(
        $_POST['desconto'] ?? 0
    );


    try {

        if ($produtoId <= 0) {
            throw new Exception(
                "Selecione um produto."
            );
        }


        if ($quantidade <= 0) {
            throw new Exception(
                "Informe uma quantidade válida."
            );
        }


        if ($formaPagamentoId <= 0) {
            throw new Exception(
                "Selecione uma forma de pagamento."
            );
        }


        // ====================================================
        // BUSCAR PRODUTO
        // ====================================================

        $stmt = $pdo->prepare("
            SELECT
                p.*,
                COALESCE(e.quantidade, 0) AS estoque
            FROM produtos p
            LEFT JOIN estoques e
                ON e.produto_id = p.id
            WHERE p.id = ?
              AND p.ativo = TRUE
        ");

        $stmt->execute([$produtoId]);

        $produto = $stmt->fetch();


        if (!$produto) {

            throw new Exception(
                "Produto não encontrado."
            );
        }


        // ====================================================
        // VERIFICAR ESTOQUE
        // ====================================================

        if ($quantidade > $produto['estoque']) {

            throw new Exception(
                "Estoque insuficiente. Estoque disponível: "
                . $produto['estoque']
            );
        }


        // ====================================================
        // CALCULAR VENDA
        // ====================================================

        $precoUnitario =
            (float)$produto['preco_venda'];

        $subtotal =
            $precoUnitario * $quantidade;

        $total =
            $subtotal - $desconto;


        if ($total < 0) {
            $total = 0;
        }


        // ====================================================
        // TRANSAÇÃO
        // ====================================================

        $pdo->beginTransaction();


        // Criar venda

        $stmt = $pdo->prepare("
            INSERT INTO vendas
            (
                cliente_id,
                data_venda,
                subtotal,
                desconto,
                total,
                status
            )
            VALUES
            (?, NOW(), ?, ?, ?, 'PAGA')
        ");

        $stmt->execute([
            $clienteId,
            $subtotal,
            $desconto,
            $total
        ]);


        $vendaId = $pdo->lastInsertId();


        // ====================================================
        // ITEM DA VENDA
        // ====================================================

        $stmt = $pdo->prepare("
            INSERT INTO venda_itens
            (
                venda_id,
                produto_id,
                quantidade,
                preco_unitario,
                desconto,
                subtotal
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $vendaId,
            $produtoId,
            $quantidade,
            $precoUnitario,
            $desconto,
            $total
        ]);


        // ====================================================
        // PAGAMENTO
        // ====================================================

        $stmt = $pdo->prepare("
            INSERT INTO venda_pagamentos
            (
                venda_id,
                forma_pagamento_id,
                valor
            )
            VALUES (?, ?, ?)
        ");

        $stmt->execute([
            $vendaId,
            $formaPagamentoId,
            $total
        ]);


        // ====================================================
        // ATUALIZAR ESTOQUE
        // ====================================================

        $estoqueAnterior =
            (float)$produto['estoque'];

        $estoqueNovo =
            $estoqueAnterior - $quantidade;


        $stmt = $pdo->prepare("
            UPDATE estoques
            SET quantidade = ?
            WHERE produto_id = ?
        ");

        $stmt->execute([
            $estoqueNovo,
            $produtoId
        ]);


        // ====================================================
        // REGISTRAR MOVIMENTAÇÃO
        // ====================================================

        $stmt = $pdo->prepare("
            INSERT INTO movimentacoes_estoque
            (
                produto_id,
                tipo,
                quantidade,
                quantidade_anterior,
                quantidade_nova,
                motivo
            )
            VALUES (?, 'SAIDA', ?, ?, ?, ?)
        ");

        $stmt->execute([
            $produtoId,
            $quantidade,
            $estoqueAnterior,
            $estoqueNovo,
            "Venda #" . $vendaId
        ]);


        // Finalizar transação

        $pdo->commit();


        $mensagem =
            "Venda #"
            . $vendaId
            . " registrada com sucesso!";

        $tipoMensagem = "success";


    } catch (Exception $e) {

        if ($pdo->inTransaction()) {

            $pdo->rollBack();
        }


        $mensagem =
            "Erro ao registrar venda: "
            . $e->getMessage();

        $tipoMensagem = "danger";
    }
}


// ============================================================
// CLIENTES
// ============================================================

$stmt = $pdo->query("
    SELECT
        id,
        nome
    FROM clientes
    WHERE ativo = TRUE
    ORDER BY nome
");

$clientes = $stmt->fetchAll();


// ============================================================
// PRODUTOS
// ============================================================

$stmt = $pdo->query("
    SELECT
        p.id,
        p.codigo,
        p.nome,
        p.preco_venda,
        COALESCE(e.quantidade, 0) AS estoque
    FROM produtos p
    LEFT JOIN estoques e
        ON e.produto_id = p.id
    WHERE p.ativo = TRUE
    ORDER BY p.nome
");

$produtos = $stmt->fetchAll();


// ============================================================
// FORMAS DE PAGAMENTO
// ============================================================

$stmt = $pdo->query("
    SELECT
        id,
        nome
    FROM formas_pagamento
    WHERE ativo = TRUE
    ORDER BY nome
");

$formasPagamento = $stmt->fetchAll();


// ============================================================
// ÚLTIMAS VENDAS
// ============================================================

$stmt = $pdo->query("
    SELECT
        v.id,
        v.data_venda,
        c.nome AS cliente,
        v.subtotal,
        v.desconto,
        v.total,
        v.status
    FROM vendas v
    LEFT JOIN clientes c
        ON c.id = v.cliente_id
    ORDER BY v.id DESC
    LIMIT 20
");

$vendas = $stmt->fetchAll();


// ============================================================
// FUNÇÃO MOEDA
// ============================================================

function moedaVenda($valor)
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

<title>Vendas - Empresa</title>


<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
    rel="stylesheet"
>

<link rel="stylesheet" href="css/styleVendas.css">


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


    <a href="index.php">

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


    <a href="vendas.php" class="active">

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
     CONTEÚDO
========================================================= -->

<main class="main">


    <div class="page-header">

        <div>

            <h1>Vendas</h1>

            <p class="text-muted mb-0">
                Registro e gerenciamento de vendas
            </p>

        </div>

    </div>



    <?php if ($mensagem): ?>

        <div
            class="alert alert-<?= $tipoMensagem ?> alert-dismissible fade show"
        >

            <?= htmlspecialchars($mensagem) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>



    <!-- =====================================================
         NOVA VENDA
    ====================================================== -->

    <div class="card card-custom mb-4">

        <div class="card-body">

            <h5 class="mb-4">

                <i class="bi bi-cart-plus me-2"></i>

                Nova venda

            </h5>


            <form method="POST">


                <div class="row g-3">


                    <!-- CLIENTE -->

                    <div class="col-md-6">

                        <label class="form-label">
                            Cliente
                        </label>

                        <select
                            name="cliente_id"
                            class="form-select"
                        >

                            <option value="">
                                Cliente não informado
                            </option>


                            <?php foreach (
                                $clientes
                                as $cliente
                            ): ?>

                                <option
                                    value="<?= $cliente['id'] ?>"
                                >

                                    <?= htmlspecialchars(
                                        $cliente['nome']
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>



                    <!-- PAGAMENTO -->

                    <div class="col-md-6">

                        <label class="form-label">
                            Forma de pagamento *
                        </label>

                        <select
                            name="forma_pagamento_id"
                            class="form-select"
                            required
                        >

                            <option value="">
                                Selecione
                            </option>


                            <?php foreach (
                                $formasPagamento
                                as $forma
                            ): ?>

                                <option
                                    value="<?= $forma['id'] ?>"
                                >

                                    <?= htmlspecialchars(
                                        $forma['nome']
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>



                    <!-- PRODUTO -->

                    <div class="col-md-6">

                        <label class="form-label">
                            Produto *
                        </label>

                        <select
                            name="produto_id"
                            id="produto"
                            class="form-select"
                            required
                        >

                            <option value="">
                                Selecione o produto
                            </option>


                            <?php foreach (
                                $produtos
                                as $produto
                            ): ?>

                                <option
                                    value="<?= $produto['id'] ?>"
                                    data-preco="<?= $produto['preco_venda'] ?>"
                                    data-estoque="<?= $produto['estoque'] ?>"
                                >

                                    <?= htmlspecialchars(
                                        $produto['codigo']
                                    ) ?>

                                    -
                                    <?= htmlspecialchars(
                                        $produto['nome']
                                    ) ?>

                                    | Estoque:
                                    <?= $produto['estoque'] ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>



                    <!-- QUANTIDADE -->

                    <div class="col-md-2">

                        <label class="form-label">
                            Quantidade *
                        </label>

                        <input
                            type="number"
                            name="quantidade"
                            id="quantidade"
                            class="form-control"
                            min="0.01"
                            step="0.01"
                            value="1"
                            required
                        >

                    </div>



                    <!-- DESCONTO -->

                    <div class="col-md-2">

                        <label class="form-label">
                            Desconto
                        </label>

                        <input
                            type="number"
                            name="desconto"
                            id="desconto"
                            class="form-control"
                            min="0"
                            step="0.01"
                            value="0"
                        >

                    </div>



                    <!-- TOTAL -->

                    <div class="col-md-2">

                        <div class="total-box">

                            <small>
                                Total
                            </small>

                            <div
                                class="total-value"
                                id="total"
                            >
                                R$ 0,00
                            </div>

                        </div>

                    </div>



                    <!-- BOTÃO -->

                    <div class="col-12">

                        <button
                            type="submit"
                            class="btn btn-success"
                        >

                            <i class="bi bi-check-circle"></i>

                            Finalizar venda

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>



    <!-- =====================================================
         VENDAS
    ====================================================== -->

    <div class="card card-custom">

        <div class="card-body">

            <h5 class="mb-4">

                <i class="bi bi-receipt me-2"></i>

                Últimas vendas

            </h5>


            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead>

                        <tr>

                            <th>#</th>

                            <th>Cliente</th>

                            <th>Data</th>

                            <th>Subtotal</th>

                            <th>Desconto</th>

                            <th>Total</th>

                            <th>Status</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if (!$vendas): ?>

                        <tr>

                            <td
                                colspan="7"
                                class="text-center text-muted py-5"
                            >

                                Nenhuma venda registrada.

                            </td>

                        </tr>

                    <?php endif; ?>


                    <?php foreach (
                        $vendas
                        as $venda
                    ): ?>

                        <tr>

                            <td>
                                #<?= $venda['id'] ?>
                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $venda['cliente']
                                    ?? 'Não informado'
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

                                <?= moedaVenda(
                                    $venda['subtotal']
                                ) ?>

                            </td>


                            <td>

                                <?= moedaVenda(
                                    $venda['desconto']
                                ) ?>

                            </td>


                            <td>

                                <strong>

                                    <?= moedaVenda(
                                        $venda['total']
                                    ) ?>

                                </strong>

                            </td>


                            <td>

                                <?php if (
                                    $venda['status']
                                    === 'PAGA'
                                ): ?>

                                    <span
                                        class="badge bg-success"
                                    >
                                        Paga
                                    </span>

                                <?php elseif (
                                    $venda['status']
                                    === 'CANCELADA'
                                ): ?>

                                    <span
                                        class="badge bg-danger"
                                    >
                                        Cancelada
                                    </span>

                                <?php else: ?>

                                    <span
                                        class="badge bg-warning text-dark"
                                    >

                                        <?= htmlspecialchars(
                                            $venda['status']
                                        ) ?>

                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>


                    </tbody>

                </table>

            </div>

        </div>

    </div>

</main>



<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<script>

const produto =
    document.getElementById("produto");

const quantidade =
    document.getElementById("quantidade");

const desconto =
    document.getElementById("desconto");

const total =
    document.getElementById("total");


function calcularTotal() {

    if (!produto.value) {

        total.innerText = "R$ 0,00";

        return;
    }


    const opcao =
        produto.options[
            produto.selectedIndex
        ];


    const preco =
        parseFloat(
            opcao.dataset.preco || 0
        );


    const qtd =
        parseFloat(
            quantidade.value || 0
        );


    const desc =
        parseFloat(
            desconto.value || 0
        );


    let valor =
        (preco * qtd) - desc;


    if (valor < 0) {

        valor = 0;
    }


    total.innerText =
        valor.toLocaleString(
            "pt-BR",
            {
                style: "currency",
                currency: "BRL"
            }
        );
}


produto.addEventListener(
    "change",
    calcularTotal
);

quantidade.addEventListener(
    "input",
    calcularTotal
);

desconto.addEventListener(
    "input",
    calcularTotal
);

</script>


</body>

</html>
