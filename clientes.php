<?php

require_once "conexao.php";

$mensagem = "";
$tipoMensagem = "";


// ============================================================
// EXCLUIR CLIENTE
// ============================================================

if (isset($_GET['excluir'])) {

    $id = (int) $_GET['excluir'];

    try {

        $stmt = $pdo->prepare("
            DELETE FROM clientes
            WHERE id = ?
        ");

        $stmt->execute([$id]);

        $mensagem = "Cliente excluído com sucesso!";
        $tipoMensagem = "success";

    } catch (PDOException $e) {

        $mensagem = "Não foi possível excluir este cliente.";
        $tipoMensagem = "danger";
    }
}


// ============================================================
// CADASTRAR / EDITAR CLIENTE
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = !empty($_POST['id'])
        ? (int) $_POST['id']
        : null;

    $nome = trim($_POST['nome'] ?? "");
    $tipo = $_POST['tipo'] ?? "PF";
    $cpfCnpj = trim($_POST['cpf_cnpj'] ?? "");
    $email = trim($_POST['email'] ?? "");
    $telefone = trim($_POST['telefone'] ?? "");

    $ativo = isset($_POST['ativo']) ? 1 : 0;


    if ($nome === "") {

        $mensagem = "Informe o nome do cliente.";
        $tipoMensagem = "danger";

    } else {

        try {

            if ($id) {

                // EDITAR

                $stmt = $pdo->prepare("
                    UPDATE clientes
                    SET
                        nome = ?,
                        tipo = ?,
                        cpf_cnpj = ?,
                        email = ?,
                        telefone = ?,
                        ativo = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $nome,
                    $tipo,
                    $cpfCnpj ?: null,
                    $email ?: null,
                    $telefone ?: null,
                    $ativo,
                    $id
                ]);

                $mensagem = "Cliente atualizado com sucesso!";

            } else {

                // CADASTRAR

                $stmt = $pdo->prepare("
                    INSERT INTO clientes
                    (
                        nome,
                        tipo,
                        cpf_cnpj,
                        email,
                        telefone,
                        ativo
                    )
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $nome,
                    $tipo,
                    $cpfCnpj ?: null,
                    $email ?: null,
                    $telefone ?: null,
                    $ativo
                ]);

                $mensagem = "Cliente cadastrado com sucesso!";
            }

            $tipoMensagem = "success";

        } catch (PDOException $e) {

            $mensagem = "Erro ao salvar cliente: " . $e->getMessage();
            $tipoMensagem = "danger";
        }
    }
}


// ============================================================
// CLIENTE PARA EDIÇÃO
// ============================================================

$clienteEdicao = null;

if (isset($_GET['editar'])) {

    $id = (int) $_GET['editar'];

    $stmt = $pdo->prepare("
        SELECT *
        FROM clientes
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    $clienteEdicao = $stmt->fetch();
}


// ============================================================
// LISTAR CLIENTES
// ============================================================

$stmt = $pdo->query("
    SELECT *
    FROM clientes
    ORDER BY id DESC
");

$clientes = $stmt->fetchAll();

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Clientes - Empresa</title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

<link rel="stylesheet" href="css/styleClientes.css">

</head>


<body>


<!-- ========================================================
     MENU
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


    <a href="clientes.php" class="active">

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
     CONTEÚDO
========================================================= -->

<main class="main">


    <div class="page-header">

        <div>

            <h1>Clientes</h1>

            <p class="text-muted mb-0">
                Gerenciamento de clientes
            </p>

        </div>


        <button
            class="btn btn-primary"
            data-bs-toggle="modal"
            data-bs-target="#clienteModal"
        >

            <i class="bi bi-plus-lg"></i>

            Novo cliente

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

                            <th>Nome</th>

                            <th>Tipo</th>

                            <th>CPF/CNPJ</th>

                            <th>E-mail</th>

                            <th>Telefone</th>

                            <th>Status</th>

                            <th>Ações</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (!$clientes): ?>

                        <tr>

                            <td
                                colspan="8"
                                class="text-center text-muted py-5"
                            >

                                <i
                                    class="bi bi-people fs-1 d-block mb-3"
                                ></i>

                                Nenhum cliente cadastrado.

                            </td>

                        </tr>

                    <?php endif; ?>


                    <?php foreach ($clientes as $cliente): ?>

                        <tr>

                            <td>
                                <?= $cliente['id'] ?>
                            </td>


                            <td>

                                <strong>

                                    <?= htmlspecialchars(
                                        $cliente['nome']
                                    ) ?>

                                </strong>

                            </td>


                            <td>

                                <?php if ($cliente['tipo'] === 'PF'): ?>

                                    Pessoa Física

                                <?php else: ?>

                                    Pessoa Jurídica

                                <?php endif; ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $cliente['cpf_cnpj'] ?? '-'
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $cliente['email'] ?? '-'
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $cliente['telefone'] ?? '-'
                                ) ?>

                            </td>


                            <td>

                                <?php if ($cliente['ativo']): ?>

                                    <span class="badge-ativo">
                                        Ativo
                                    </span>

                                <?php else: ?>

                                    <span class="badge-inativo">
                                        Inativo
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <a
                                    href="?editar=<?= $cliente['id'] ?>"
                                    class="btn btn-sm btn-outline-primary"
                                >

                                    <i class="bi bi-pencil"></i>

                                </a>


                                <a
                                    href="?excluir=<?= $cliente['id'] ?>"
                                    class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Deseja realmente excluir este cliente?')"
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
     MODAL CLIENTE
========================================================= -->

<div
    class="modal fade"
    id="clienteModal"
    tabindex="-1"
>

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <form method="POST">

                <div class="modal-header">

                    <h5 class="modal-title">

                        <?php if ($clienteEdicao): ?>

                            Editar cliente

                        <?php else: ?>

                            Novo cliente

                        <?php endif; ?>

                    </h5>


                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>

                </div>


                <div class="modal-body">


                    <?php if ($clienteEdicao): ?>

                        <input
                            type="hidden"
                            name="id"
                            value="<?= $clienteEdicao['id'] ?>"
                        >

                    <?php endif; ?>


                    <div class="row g-3">


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
                                    $clienteEdicao['nome'] ?? ''
                                ) ?>"
                            >

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                Tipo
                            </label>

                            <select
                                name="tipo"
                                class="form-select"
                            >

                                <option
                                    value="PF"
                                    <?= (
                                        ($clienteEdicao['tipo'] ?? 'PF')
                                        === 'PF'
                                    )
                                    ? 'selected'
                                    : ''
                                    ?>
                                >
                                    Pessoa Física
                                </option>

                                <option
                                    value="PJ"
                                    <?= (
                                        ($clienteEdicao['tipo'] ?? '')
                                        === 'PJ'
                                    )
                                    ? 'selected'
                                    : ''
                                    ?>
                                >
                                    Pessoa Jurídica
                                </option>

                            </select>

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                CPF / CNPJ
                            </label>

                            <input
                                type="text"
                                name="cpf_cnpj"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $clienteEdicao['cpf_cnpj'] ?? ''
                                ) ?>"
                            >

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                E-mail
                            </label>

                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $clienteEdicao['email'] ?? ''
                                ) ?>"
                            >

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                Telefone
                            </label>

                            <input
                                type="text"
                                name="telefone"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $clienteEdicao['telefone'] ?? ''
                                ) ?>"
                            >

                        </div>


                        <div class="col-md-12">

                            <div class="form-check">

                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="ativo"
                                    id="ativo"
                                    <?= (
                                        !$clienteEdicao
                                        || $clienteEdicao['ativo']
                                    )
                                    ? 'checked'
                                    : ''
                                    ?>
                                >

                                <label
                                    class="form-check-label"
                                    for="ativo"
                                >
                                    Cliente ativo
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

                        Salvar cliente

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>



<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<?php if ($clienteEdicao): ?>

<script>

    document.addEventListener(
        "DOMContentLoaded",
        function () {

            const modal =
                new bootstrap.Modal(
                    document.getElementById(
                        "clienteModal"
                    )
                );

            modal.show();

        }
    );

</script>

<?php endif; ?>


</body>

</html>
