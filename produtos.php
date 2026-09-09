<?php

require_once "conexao.php";

$mensagem = "";
$tipoMensagem = "";


// ============================================================
// EXCLUIR PRODUTO
// ============================================================

if (isset($_GET['excluir'])) {

    $id = (int) $_GET['excluir'];

    try {

        $stmt = $pdo->prepare("
            DELETE FROM produtos
            WHERE id = ?
        ");

        $stmt->execute([$id]);

        $mensagem = "Produto excluído com sucesso!";
        $tipoMensagem = "success";

    } catch (PDOException $e) {

        $mensagem = "Não foi possível excluir o produto. Ele pode estar relacionado a vendas ou compras.";
        $tipoMensagem = "danger";
    }
}


// ============================================================
// CADASTRAR / EDITAR PRODUTO
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = !empty($_POST['id'])
        ? (int) $_POST['id']
        : null;

    $codigo = trim($_POST['codigo'] ?? "");
    $nome = trim($_POST['nome'] ?? "");
    $descricao = trim($_POST['descricao'] ?? "");

    $categoriaId = !empty($_POST['categoria_id'])
        ? (int) $_POST['categoria_id']
        : null;

    $fornecedorId = !empty($_POST['fornecedor_id'])
        ? (int) $_POST['fornecedor_id']
        : null;

    $unidade = trim($_POST['unidade'] ?? "UN");

    $precoCusto = (float) str_replace(
        ',',
        '.',
        $_POST['preco_custo'] ?? 0
    );

    $precoVenda = (float) str_replace(
        ',',
        '.',
        $_POST['preco_venda'] ?? 0
    );

    $estoqueMinimo = (float) str_replace(
        ',',
        '.',
        $_POST['estoque_minimo'] ?? 0
    );

    $estoqueMaximo = !empty($_POST['estoque_maximo'])
        ? (float) str_replace(
            ',',
            '.',
            $_POST['estoque_maximo']
        )
        : null;

    $ativo = isset($_POST['ativo']) ? 1 : 0;


    if ($codigo === "" || $nome === "") {

        $mensagem = "Código e nome são obrigatórios.";
        $tipoMensagem = "danger";

    } else {

        try {

            if ($id) {

                // EDITAR PRODUTO

                $stmt = $pdo->prepare("
                    UPDATE produtos
                    SET
                        categoria_id = ?,
                        fornecedor_id = ?,
                        codigo = ?,
                        nome = ?,
                        descricao = ?,
                        unidade = ?,
                        preco_custo = ?,
                        preco_venda = ?,
                        estoque_minimo = ?,
                        estoque_maximo = ?,
                        ativo = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $categoriaId,
                    $fornecedorId,
                    $codigo,
                    $nome,
                    $descricao ?: null,
                    $unidade,
                    $precoCusto,
                    $precoVenda,
                    $estoqueMinimo,
                    $estoqueMaximo,
                    $ativo,
                    $id
                ]);

                $mensagem = "Produto atualizado com sucesso!";

            } else {

                // CADASTRAR PRODUTO

                $stmt = $pdo->prepare("
                    INSERT INTO produtos
                    (
                        categoria_id,
                        fornecedor_id,
                        codigo,
                        nome,
                        descricao,
                        unidade,
                        preco_custo,
                        preco_venda,
                        estoque_minimo,
                        estoque_maximo,
                        ativo
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $categoriaId,
                    $fornecedorId,
                    $codigo,
                    $nome,
                    $descricao ?: null,
                    $unidade,
                    $precoCusto,
                    $precoVenda,
                    $estoqueMinimo,
                    $estoqueMaximo,
                    $ativo
                ]);

                $produtoId = $pdo->lastInsertId();

                // Cria estoque inicial zerado

                $stmt = $pdo->prepare("
                    INSERT INTO estoques
                    (
                        produto_id,
                        quantidade
                    )
                    VALUES (?, 0)
                ");

                $stmt->execute([$produtoId]);

                $mensagem = "Produto cadastrado com sucesso!";
            }

            $tipoMensagem = "success";

        } catch (PDOException $e) {

            if ($e->getCode() == 23000) {

                $mensagem = "O código do produto já existe.";

            } else {

                $mensagem = "Erro ao salvar produto: "
                    . $e->getMessage();
            }

            $tipoMensagem = "danger";
        }
    }
}


// ============================================================
// PRODUTO PARA EDIÇÃO
// ============================================================

$produtoEdicao = null;

if (isset($_GET['editar'])) {

    $id = (int) $_GET['editar'];

    $stmt = $pdo->prepare("
        SELECT *
        FROM produtos
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    $produtoEdicao = $stmt->fetch();
}


// ============================================================
// CATEGORIAS
// ============================================================

$stmt = $pdo->query("
    SELECT id, nome
    FROM categorias
    WHERE ativo = TRUE
    ORDER BY nome
");

$categorias = $stmt->fetchAll();


// ============================================================
// FORNECEDORES
// ============================================================

$stmt = $pdo->query("
    SELECT id, razao_social
    FROM fornecedores
    WHERE ativo = TRUE
    ORDER BY razao_social
");

$fornecedores = $stmt->fetchAll();


// ============================================================
// LISTAR PRODUTOS
// ============================================================

$stmt = $pdo->query("
    SELECT
        p.*,
        c.nome AS categoria,
        f.razao_social AS fornecedor,
        COALESCE(e.quantidade, 0) AS estoque
    FROM produtos p

    LEFT JOIN categorias c
        ON c.id = p.categoria_id

    LEFT JOIN fornecedores f
        ON f.id = p.fornecedor_id

    LEFT JOIN estoques e
        ON e.produto_id = p.id

    ORDER BY p.id DESC
");

$produtos = $stmt->fetchAll();


// ============================================================
// FUNÇÃO MOEDA
// ============================================================

function moedaProduto($valor)
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

<title>Produtos - Empresa</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
    rel="stylesheet"
>

<link rel="stylesheet" href="css/styleProdutos.css">

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


    <a href="produtos.php" class="active">

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
     CONTEÚDO
========================================================= -->

<main class="main">


    <div class="page-header">

        <div>

            <h1>Produtos</h1>

            <p class="text-muted mb-0">
                Gerenciamento de produtos e estoque
            </p>

        </div>


        <button
            class="btn btn-primary"
            data-bs-toggle="modal"
            data-bs-target="#produtoModal"
        >

            <i class="bi bi-plus-lg"></i>

            Novo produto

        </button>

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
         TABELA
    ====================================================== -->

    <div class="card card-custom">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead>

                        <tr>

                            <th>#</th>

                            <th>Código</th>

                            <th>Produto</th>

                            <th>Categoria</th>

                            <th>Preço</th>

                            <th>Estoque</th>

                            <th>Status</th>

                            <th>Ações</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (!$produtos): ?>

                        <tr>

                            <td
                                colspan="8"
                                class="text-center text-muted py-5"
                            >

                                Nenhum produto cadastrado.

                            </td>

                        </tr>

                    <?php endif; ?>


                    <?php foreach ($produtos as $produto): ?>

                        <tr>

                            <td>
                                <?= $produto['id'] ?>
                            </td>


                            <td>
                                <?= htmlspecialchars(
                                    $produto['codigo']
                                ) ?>
                            </td>


                            <td>

                                <strong>

                                    <?= htmlspecialchars(
                                        $produto['nome']
                                    ) ?>

                                </strong>

                                <?php if ($produto['descricao']): ?>

                                    <br>

                                    <small class="text-muted">

                                        <?= htmlspecialchars(
                                            $produto['descricao']
                                        ) ?>

                                    </small>

                                <?php endif; ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $produto['categoria'] ?? '-'
                                ) ?>

                            </td>


                            <td>

                                <strong>

                                    <?= moedaProduto(
                                        $produto['preco_venda']
                                    ) ?>

                                </strong>

                            </td>


                            <td>

                                <?php

                                $estoque =
                                    (float)$produto['estoque'];

                                $minimo =
                                    (float)$produto['estoque_minimo'];

                                ?>


                                <span class="<?=
                                    $estoque <= $minimo
                                    ? 'estoque-baixo'
                                    : 'estoque-normal'
                                ?>">

                                    <?= number_format(
                                        $estoque,
                                        2,
                                        ',',
                                        '.'
                                    ) ?>

                                    <?= htmlspecialchars(
                                        $produto['unidade']
                                    ) ?>

                                </span>

                            </td>


                            <td>

                                <?php if ($produto['ativo']): ?>

                                    <span
                                        class="badge bg-success"
                                    >
                                        Ativo
                                    </span>

                                <?php else: ?>

                                    <span
                                        class="badge bg-danger"
                                    >
                                        Inativo
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <a
                                    href="?editar=<?= $produto['id'] ?>"
                                    class="btn btn-sm btn-outline-primary"
                                >

                                    <i class="bi bi-pencil"></i>

                                </a>


                                <a
                                    href="?excluir=<?= $produto['id'] ?>"
                                    class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Deseja realmente excluir este produto?')"
                                >

                                    <i class="bi bi-trash"></i>

                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</main>



<!-- ========================================================
     MODAL PRODUTO
========================================================= -->

<div
    class="modal fade"
    id="produtoModal"
    tabindex="-1"
>

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <form method="POST">

                <div class="modal-header">

                    <h5 class="modal-title">

                        <?= $produtoEdicao
                            ? 'Editar produto'
                            : 'Novo produto'
                        ?>

                    </h5>


                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>

                </div>


                <div class="modal-body">

                    <?php if ($produtoEdicao): ?>

                        <input
                            type="hidden"
                            name="id"
                            value="<?= $produtoEdicao['id'] ?>"
                        >

                    <?php endif; ?>


                    <div class="row g-3">


                        <div class="col-md-4">

                            <label class="form-label">
                                Código *
                            </label>

                            <input
                                type="text"
                                name="codigo"
                                class="form-control"
                                required
                                value="<?= htmlspecialchars(
                                    $produtoEdicao['codigo'] ?? ''
                                ) ?>"
                            >

                        </div>


                        <div class="col-md-8">

                            <label class="form-label">
                                Nome *
                            </label>

                            <input
                                type="text"
                                name="nome"
                                class="form-control"
                                required
                                value="<?= htmlspecialchars(
                                    $produtoEdicao['nome'] ?? ''
                                ) ?>"
                            >

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Categoria
                            </label>

                            <select
                                name="categoria_id"
                                class="form-select"
                            >

                                <option value="">
                                    Sem categoria
                                </option>


                                <?php foreach (
                                    $categorias
                                    as $categoria
                                ): ?>

                                    <option
                                        value="<?= $categoria['id'] ?>"
                                        <?= (
                                            ($produtoEdicao['categoria_id'] ?? null)
                                            == $categoria['id']
                                        )
                                        ? 'selected'
                                        : ''
                                        ?>
                                    >

                                        <?= htmlspecialchars(
                                            $categoria['nome']
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Fornecedor
                            </label>

                            <select
                                name="fornecedor_id"
                                class="form-select"
                            >

                                <option value="">
                                    Sem fornecedor
                                </option>


                                <?php foreach (
                                    $fornecedores
                                    as $fornecedor
                                ): ?>

                                    <option
                                        value="<?= $fornecedor['id'] ?>"
                                        <?= (
                                            ($produtoEdicao['fornecedor_id'] ?? null)
                                            == $fornecedor['id']
                                        )
                                        ? 'selected'
                                        : ''
                                        ?>
                                    >

                                        <?= htmlspecialchars(
                                            $fornecedor['razao_social']
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="col-md-12">

                            <label class="form-label">
                                Descrição
                            </label>

                            <textarea
                                name="descricao"
                                class="form-control"
                                rows="3"
                            ><?= htmlspecialchars(
                                $produtoEdicao['descricao'] ?? ''
                            ) ?></textarea>

                        </div>


                        <div class="col-md-3">

                            <label class="form-label">
                                Unidade
                            </label>

                            <select
                                name="unidade"
                                class="form-select"
                            >

                                <?php

                                $unidades = [
                                    'UN',
                                    'KG',
                                    'G',
                                    'L',
                                    'ML',
                                    'M',
                                    'CX',
                                    'PC'
                                ];

                                ?>

                                <?php foreach (
                                    $unidades
                                    as $unidade
                                ): ?>

                                    <option
                                        value="<?= $unidade ?>"
                                        <?= (
                                            ($produtoEdicao['unidade'] ?? 'UN')
                                            === $unidade
                                        )
                                        ? 'selected'
                                        : ''
                                        ?>
                                    >

                                        <?= $unidade ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="col-md-3">

                            <label class="form-label">
                                Preço de custo
                            </label>

                            <input
                                type="number"
                                step="0.01"
                                name="preco_custo"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $produtoEdicao['preco_custo'] ?? '0'
                                ) ?>"
                            >

                        </div>


                        <div class="col-md-3">

                            <label class="form-label">
                                Preço de venda
                            </label>

                            <input
                                type="number"
                                step="0.01"
                                name="preco_venda"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $produtoEdicao['preco_venda'] ?? '0'
                                ) ?>"
                            >

                        </div>


                        <div class="col-md-3">

                            <label class="form-label">
                                Estoque mínimo
                            </label>

                            <input
                                type="number"
                                step="0.01"
                                name="estoque_minimo"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $produtoEdicao['estoque_minimo'] ?? '0'
                                ) ?>"
                            >

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                Estoque máximo
                            </label>

                            <input
                                type="number"
                                step="0.01"
                                name="estoque_maximo"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $produtoEdicao['estoque_maximo'] ?? ''
                                ) ?>"
                            >

                        </div>


                        <div class="col-md-12">

                            <div class="form-check">

                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    name="ativo"
                                    id="ativo"
                                    <?= (
                                        !$produtoEdicao
                                        || $produtoEdicao['ativo']
                                    )
                                    ? 'checked'
                                    : ''
                                    ?>
                                >

                                <label
                                    class="form-check-label"
                                    for="ativo"
                                >
                                    Produto ativo
                                </label>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    >
                        Cancelar
                    </button>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i class="bi bi-check-lg"></i>

                        Salvar produto

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<?php if ($produtoEdicao): ?>

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const modal =
            new bootstrap.Modal(
                document.getElementById(
                    "produtoModal"
                )
            );

        modal.show();

    }
);

</script>

<?php endif; ?>


</body>

</html>
