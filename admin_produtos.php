<?php
/*
* Arquivo: admin_produtos.php
* 
* Descrição:
* Painel administrativo para gerenciamento de produtos (cafés) do menu, exclusivo para usuários com nível 'master'.
* Permite adicionar, editar, visualizar e excluir produtos com seus respectivos preços e imagens.
* 
* Funcionalidades :
* -> Autenticação com verificação de nível de acesso 'master'
* -> Sistema CRUD completo para produtos (Create, Read, Update, Delete)
* -> Upload e gerenciamento de imagens dos produtos
* -> Validação de imagens com critérios específicos
* -> Paginação de resultados para melhor organização
* -> Inicialização automática do cardápio com produtos padrão
* -> Formatação de valores monetários
* -> Interface administrativa com menu lateral
* 
* Fluxo de operação:
* 1) Verifica autenticação e nível de acesso via Config::auth()
* 2) Configura parâmetros de paginação (5 produtos por página)
* 3) Processa ações CRUD baseadas em parâmetros POST/GET:
*    a) Salvar produto (inserir ou atualizar) com upload de imagem
*    b) Excluir produto com remoção da imagem do servidor
*    c) Preparar edição ao carregar dados do produto
* 4) Carrega lista de produtos com paginação
* 5) Inicializa cardápio com produtos padrão se estiver vazio
* 6) Renderiza interface com formulário e tabela de produtos
* 
* Segurança:
* -> Verificação de token de sessão via Config::auth()
* -> Restrição de acesso apenas para nível 'master'
* -> Sanitização de todos os inputs com Config::trataPost()
* -> Prepared statements para todas as operações de banco
* -> Validação de uploads de imagem com Validator::validarImagem()
* -> Proteção contra directory traversal em uploads
* -> Validação de tipos de arquivo e tamanhos
* -> Confirmação de exclusão via JavaScript
* 
* Dependências:
* -> autoload.php - Carregamento automático das classes OO
* -> Config.php - Configurações do sistema e autenticação
* -> Validator.php - Validação de upload de imagens
* -> Bootstrap 5 - Framework CSS
* -> Font Awesome 6 - Ícones
* -> jQuery 3.6.0 - Manipulação DOM e validação client-side
* -> CSS personalizado (painel_admin.css)
* 
* Observações importantes:
* -> Imagens são armazenadas em assets/img/produtos/
* -> Nomes de arquivos são únicos usando uniqid()
* -> Validação de imagem: JPG, PNG, GIF, WebP, máximo 5MB
* -> Produtos padrão são inseridos apenas quando tabela está vazia
* -> Paginação mantém 5 produtos por página
* -> Formatação automática de preços (R$ 0,00)
* -> Preview de imagem em tempo real durante upload
* -> Remoção de imagem antiga durante atualização
* -> Mensagens de feedback via sessão PHP
* 
* @ Autor - Eduardo Torres Do Ó
* @ Direitos Reservados - 2026 CoffeBreak
* 
*/


// =====================================================================
// CONFIGURAÇÕES INICIAIS E SEGURANÇA
// =====================================================================

// REQUISITA O AUTOLOADER QUE VAI CARREGAR AS CLASSES 
require_once __DIR__ . '/autoload.php';

// INCIALIZA AS CONFIGURAÇÕES E METÓDOS DA CLASSE CONFIG
$config = Config::getInstance();

// ESTABELECE CONEXãO COM O BANCO DE DADOS 
$pdo = $config->getPDO();

// VERIFICA AUTENTICAÇÃO DO USUÁRIO PELO TOKEN
$usuario = $config->auth($_SESSION["TOKEN"] ?? '');

// SE NÃO ESTIVER AUTENTICADO OU NÃO FOR MASTER
if(!$usuario || $usuario['nivel_acesso'] !== 'master') {
    // REDIRECIONA PARA O LOGIN
    header("Location: login.php");
    exit; 
}

// ATIVA O RELATORIO DE ERROS 
error_reporting(E_ALL);
ini_set('display_errors', 1);

// =====================================================================
// CONFIGURAÇÃO DA PAGINAÇÃO
// =====================================================================

$itensPorPagina = 5; // 5 produtos por página
$paginaAtual = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($paginaAtual - 1) * $itensPorPagina;

// =====================================================================
// PROCESSAMENTO DO FORMULÁRIO DE PRODUTOS
// =====================================================================

// SE EXISTE A POSTAGEM PARA ATUALIZAR OU INSERIR NOVO PRODUTO AO SISTEMA 
if (isset($_POST['salvar'])) {
    
    /************************************************************
     TRATAMENTO DOS DADOS 
    ************************************************************/
    
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $nome = Config::trataPost($_POST['nome']);
    $descricao = Config::trataPost($_POST['descricao']);
    $preco = (float)str_replace(['R$', '.', ','], ['', '', '.'], Config::trataPost($_POST['preco']));

   
    /************************************************************
    * TRATAMENTO DO UPLOAD DA IMAGEM (COM VALIDAÇÃO)
    ************************************************************/   
    
    //INICIALIZA AS VARIÁVEIS PARA O DIRETOÓRIO DAS IMAGENS
    $diretorio_imagens = "assets/img/produtos/";
    $imagem = isset($_POST['imagem_atual']) ? $_POST['imagem_atual'] : '';
    
    // SE EXISTE A IMAGEM E FOI ENVIADA
    if (isset($_FILES['imagem_upload']) && $_FILES['imagem_upload']['error'] !== UPLOAD_ERR_NO_FILE) {
        
        // VALIDA A IMAGEM USANDO O VALIDATOR
        $erroImagem = Validator::validarImagem($_FILES['imagem_upload']);
        
        if ($erroImagem) {
            // SE HOUVER ERRO NA VALIDAÇÃO
            $_SESSION['mensagem'] = $erroImagem;
            $_SESSION['tipo_mensagem'] = "danger";
            header("Location: admin_produtos.php");
            exit;
        }
        
        // SE PASSOU NA VALIDAÇÃO , PROCESSA O UPLOAD
        $nome_arquivo = uniqid() . '_' . basename($_FILES['imagem_upload']['name']);
        $caminho_completo = $diretorio_imagens . $nome_arquivo;
        
        // MOVE O ARQUIVO PARA O DIRETÓRIO DE IMAGENS
        if (move_uploaded_file($_FILES['imagem_upload']['tmp_name'], $caminho_completo)) {
            $imagem = $caminho_completo;
            
            // REMOVE A IMAGEM ANTIGA SE ESTIVER EDITANDO
            if ($id && isset($_POST['imagem_atual']) && $_POST['imagem_atual'] && file_exists($_POST['imagem_atual'])) {
                unlink($_POST['imagem_atual']);
            }
            
        } else {
            // CASO OCORRA ERRO EM MOVER O ARQUIVO 
            $_SESSION['mensagem'] = "Erro ao fazer upload da imagem. Verifique as permissões do diretório.";
            $_SESSION['tipo_mensagem'] = "danger";
            header("Location: admin_produtos.php");
            exit;
        }
        
    } elseif (!$id && empty($_FILES['imagem_upload']['name'])) {

        // CASO O CAMPO DA IMAGEM SEJA ENVIADO VAZIO PARA NOVO PRODUTO
        $_SESSION['mensagem'] = "Por favor, selecione uma imagem para o produto.";
        $_SESSION['tipo_mensagem'] = "danger";
        header("Location: admin_produtos.php");
        exit;
    }
    
    /************************************************************
     * PROCESSO PARA SALVAR PRODUTO NO SISTEMA
    ************************************************************/

    try {
        
        // SE EXISTE A ID DO PRODUTO (SE EXISTE O PRODUTO) - ATUALIZAÇÃO
        if ($id) {
            
            // ATUALIZAR O PRODUTOS EXISTENTE
            $sql = $pdo->prepare("UPDATE produtos SET nome = ?, descricao = ?, imagem = ?, preco = ? WHERE id = ?");
            
            // SE ATUALIZOU 
            if($sql->execute([$nome, $descricao, $imagem, $preco, $id])){
                $_SESSION['mensagem'] = "Produto atualizado com sucesso!";
                $_SESSION['tipo_mensagem'] = "success";
                header("Location: admin_produtos.php");
                exit;
            }
            
        } else {
            // INSERIR NOVO PRODUTO
            $sql = $pdo->prepare("INSERT INTO produtos (nome, descricao, imagem, preco) VALUES (?, ?, ?, ?)");
            
            // SE INSERIU
            if($sql->execute([$nome, $descricao, $imagem, $preco])){
                $_SESSION['mensagem'] = "Produto adicionado com sucesso!";
                $_SESSION['tipo_mensagem'] = "success";
                header("Location: admin_produtos.php");
                exit;
            }
        }

    } catch (PDOException $e) {
        
        //CASO TENHA OCORRIDO ALGUM ERRO NA HORA DE ATUALIZAR OU INSERIR  
        $_SESSION['mensagem'] = "Erro ao salvar produto: " . $e->getMessage();
        $_SESSION['tipo_mensagem'] = "danger";
    }
}


// =====================================================================
// PROCESSO PARA EXCLUIR PRODUTOS
// =====================================================================

// SE EXISTE A POSTAGEM DE EXCLUSAO DO PROUDTO
if(isset($_POST['excluir'])) {
    $id = (int)$_POST['id'];
    
    try {
        // PRIMEIRO OBTÉM O CAMINHO DA IMAGEM
        $sql = $pdo->prepare("SELECT imagem FROM produtos WHERE id = ?");
        $sql->execute([$id]);
        $produto = $sql->fetch();
        
        // REMOVE A IMAGEM DO SERVIDOR
        if ($produto && !empty($produto['imagem']) && file_exists($produto['imagem'])) {
            unlink($produto['imagem']);
        }
        
        // DELETA O PRODUTO
        $sql = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
        
        // SE DELETOU
        if($sql->execute([$id])){
            
            // MOSTRA A MENSAGEM DE SUCESSO AO USUÁRIO 
            $_SESSION['mensagem'] = "Produto excluído com sucesso!";
            $_SESSION['tipo_mensagem'] = "success";
            
            // RECARREGA A PAGINA
            header("Location: admin_produtos.php");
            exit;
        }
        
     // SE OCORREU ERRO AO DELETAR O PRODUTO 
    } catch (PDOException $e) {
        
        // MOSTRA A MENSAGEM DE ERRO AO USUÁRIO 
        $_SESSION['mensagem'] = "Erro ao excluir produto: " . $e->getMessage();
        $_SESSION['tipo_mensagem'] = "danger";
    }
}

// INICIAZALIA VARIAVEIS (FLAGS)
$editando = false;
$produto_edit = null;

// =====================================================================
// PROCESSO DE PREPARAÇÃO PARA EDIÇÃO DE PRODUTO
// =====================================================================

// SE EXISTE A POSTAGEM PARA EDITAR O PRODUTO
if (isset($_GET['editar'])) {
    $id = (int)$_GET['editar'];
    
    try {
        //FAZ A CONSULTA DO PRODUTO PELO ID 
        $sql = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
        $sql->execute([$id]);
        
        // ARMAZENA O PRODUTO NA VARIAVEL
        $produto_edit = $sql->fetch();
        
        // SE O PRODUTO FOI ENCONTRADO , PODE FAZER A EDIÇÃO
        if ($produto_edit) {
            $editando = true;
        }
        
        // CASO NÃO ENCONTRE O PRODUTO
    } catch (PDOException $e) {
        
        // MOSTRA A MENSAGEM DE ERRO AO USUARIO 
        die("Erro ao buscar produto: " . $e->getMessage());
    }
}

// =====================================================================
// CARREGAMENTO DA LISTA DE PRODUTOS COM PAGINAÇÃO
// =====================================================================

// BUSCANDO TODOS OS PRODUTOS COM PAGINAÇÃO
try {
    // Query para contar o total de registros
    $sqlTotal = "SELECT COUNT(*) AS total FROM produtos";
    $stmtTotal = $pdo->query($sqlTotal);
    $totalRegistros = $stmtTotal->fetch()['total'];
    $totalPaginas = ceil($totalRegistros / $itensPorPagina);

    // Query principal com paginação
    $sql = "SELECT * FROM produtos ORDER BY nome LIMIT $itensPorPagina OFFSET $offset";
    $stmt = $pdo->query($sql);
    $produtos = $stmt->fetchAll();

    // SE NAO CONSEGUIU
} catch (PDOException $e) {
    die("Erro ao buscar produtos: " . $e->getMessage());
}

// =====================================================================
// INICIALIZAÇÃO DO CARDÁPIO (SE VAZIO)
// =====================================================================

if (empty($produtos) && $paginaAtual == 1) {
    
    //CRIA O ARRAY QUE VAI RECEBER OS PRODUTOS E INSTANCIA-LOS NA SESSAO DE CARDAPIO
    $coffe_padrao = [
        ["Black Coffee", "assets/img/produtos/capuccino.jpeg", 5.00, "Um café puro e encorpado, com notas intensas e um aroma irresistível para os apreciadores de um clássico atemporal"],
        ["Chocolate Coffee", "assets/img/produtos/Café com Leite.jpeg", 7.00, "A perfeita harmonia entre o sabor intenso do café e a cremosidade do chocolate, trazendo um toque de doçura equilibrada."],
        ["Coffee With Milk", "assets/img/produtos/blackcoffe.jpeg", 9.00, "Uma combinação suave de café e leite cremoso, garantindo um sabor equilibrado e reconfortante para qualquer momento do dia."],
        ["Milkshake", "assets/img/produtos/milkshake.jpeg", 9.00, "Uma mistura deliciosa de café gelado com um toque adocicado e textura aveludada, perfeito para refrescar e energizar."],
        ["Latte", "assets/img/produtos/p1.jpeg", 5.00, "Café espresso suave, combinado com leite vaporizado e finalizado com uma camada sedosa de espuma. Clássico e sofisticado!"],
        ["Espresso", "assets/img/produtos/p2.jpeg", 7.00, "O mais puro café em sua essência: forte, encorpado e com um aroma marcante para quem busca intensidade."]
    ];
    try {
        
        // COMANDO PARA INSERIR OS PRODUTOS 
        $sql= $pdo->prepare("INSERT INTO produtos (nome, imagem, preco, descricao) VALUES (?, ?, ?, ?)");
        
        // PERCORE A ARRAY E FAZ A INSERÇÃO DE CADA PRODUTO 
        foreach ($coffe_padrao as $coffe) {
            $sql->execute($coffe);
        }
        
        // RECARREGA OS PRODUTOS APÓS INSERÇÃO 
        $sql = $pdo->query("SELECT * FROM produtos ORDER BY nome");
        $produtos = $sql->fetchAll();
        
        //SE OCORREU ERRO AO INSERIR OS CAFES
    } catch (PDOException $e) {
        // MOSTRA A MENSAGEM DE ERRO E MATA A INSERÇÃO
        die("Erro ao inserir cafés padrão: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    
    <!-- META-TAGS PARA CONFIGURACOES DO SITE : -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- TITULO DA PAGINA -->
    <title>Produtos</title>

    <!-- FAVICON -->
    <link rel="icon" href="assets\img\favicon\favicon.ico" type="image/x-icon">
   
    <!-- REFERENCIA DO BOOTSTRAP CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- REFERÊNCIA DO FONT-AWESOME(BIBLIOTECA DE ICONES ) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- REFERÊNCIA DO CSS EXTERNO -->
    <link href="assets/css/painel_admin.css" rel="stylesheet">
    
    <!-- CSS INTERNO PARA OS CARDS DOS PRODUTOS DENTRO DA TABELA -->
    <style>
        .card-img-top {
            height: 150px;
            object-fit: cover;
        }
        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .table-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
        }

        .btn-warning{
            background-color: #75441a;
            color:white;
        }

        .btn-warning:hover{
            background-color: #6a3e17ff;
            color:white;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        
        <!-- ==============================================
          SESSÃO DO BOTÃO PARA ABRIR OFFANCAVAS(MENU LATERAL)
        =============================================== -->
        
        <nav style="background-color: #2a1c13;" class="navbar d-lg-none fixed-top">
            <div class="container-fluid">
                 <button class="btn d-lg-none position-fixed" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas" style="left: 10px; top: 10px; z-index: 100; background-color: #6C471D; color: white;">
                    <i class="fas fa-bars"></i>
                </button>
                <span class="navbar-brand ms-auto me-auto text-white">Coffe Admin</span>
            </div>
        </nav>
        
       <!-- ==============================================
          SESSÃO DO OFFCANVAS(MENU LATERAL)
        =============================================== -->
        
        <div class="offcanvas offcanvas-start text-white" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel" style="width: 250px;">
            
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="sidebarOffcanvasLabel">Coffe Admin</h5>
            </div>
            
            <!-- AREA DE SUBMENUS DO OFFACANVAS -->
            <div class="offcanvas-body p-0">
                <div class="mt-3">
                    <ul class="nav flex-column">
                        
                        <!-- PEDIDOS -->
                        <li class="nav-item">
                            <a href="admin_pedidos.php" class="nav-link ">
                                <i class="fas fa-shopping-cart me-2"></i>
                                <span>Pedidos</span>
                            </a>
                        </li>

                        <!-- CLIENTES -->
                        <li class="nav-item">
                            <a href="admin_clientes.php" class="nav-link">
                                <i class="fas fa-users me-2"></i>
                                <span>Clientes</span>
                            </a>
                        </li>
                        

                        <!-- PRODUTOS  -->
                        <li class="nav-item">
                            <a href="admin_produtos.php" class="nav-link active">
                                <i class="fas fa-pizza-slice me-2"></i>
                                <span>Produtos</span>
                            </a>
                        </li>
                        
                        <!-- FINANCEIRO  -->
                        <li class="nav-item">
                            <a href="admin_financeiro.php" class="nav-link">
                                <i class="fas fa-dollar-sign me-2"></i>
                                <span>Financeiro</span>
                            </a>
                        </li>
                        

                        <!-- SAIR(lOGOUT DO SISTEMA) -->
                        <li class="nav-item">
                            <a href="logout.php" class="nav-link text-danger">
                                <i class="fas fa-sign-out-alt me-2"></i>
                                <span>Sair</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!--SESSAO PRINCIPAL-->
        <div class="content-wrapper">
            <section class="content">
                <div class="container-fluid">
                    
                    <!-- ==============================================
                     SESSÃO DE EXIBIÇÃO DAS MENSAGENS DE ERRO E SUCESSO
                    =============================================== -->
                    
                    <?php if (isset($_SESSION['mensagem'])): ?>
                        <div class="alert alert-<?= $_SESSION['tipo_mensagem'] ?> alert-dismissible fade show">
                            <?= $_SESSION['mensagem'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']); ?>
                    <?php endif; ?>
                    
                    <!-- ==============================================
                     SESSÃO DO FORMULARIO PARA ADICIONAR / EDITAR PRODUTO
                    =============================================== -->
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title mb-0"><?= $editando ? 'Editar Produto' : 'Adicionar Novo Produto' ?></h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="form-container" enctype="multipart/form-data">
                                <?php if ($editando): ?>
                                    <input type="hidden" name="id" value="<?= $produto_edit['id'] ?>">
                                <?php endif; ?>
                                
                                <!--CAMPO NOME DO CAFÉ-->
                                <div class="mb-3">
                                    <label for="nome" class="form-label text-white">Nome do Produto</label>
                                    <input type="text" class="form-control" id="nome" name="nome" 
                                    value="<?= $editando ? htmlspecialchars($produto_edit['nome']) : '' ?>" required>
                                </div>
                                
                                <!-- CAMPO DESCRIÇÃO -->
                                <div class="mb-3">
                                    <label for="descricao" class="form-label text-white">Descrição do Produto</label>
                                    <textarea class="form-control" id="descricao" name="descricao" rows="3"><?= $editando ? htmlspecialchars($produto_edit['descricao']) : '' ?></textarea>
                                </div>
                                
                                <!--CAMPO PARA IMAGEM-->
                                <div class="mb-3">
                                    <label for="imagem_upload" class="form-label text-white">Imagem do Produto</label>
                                    <input type="file" class="form-control" id="imagem_upload" name="imagem_upload" accept="image/*">
                                    <?php if ($editando): ?>
                                        <div class="mt-2">
                                            <img src="<?= htmlspecialchars($produto_edit['imagem']) ?>" alt="Preview" style="max-height: 100px;" class="img-thumbnail">
                                            <input type="hidden" name="imagem_atual" value="<?= htmlspecialchars($produto_edit['imagem']) ?>">
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!--CAMPO PARA INSERIR O VALORES DO CAFE-->
                                <div class="row mb-3">
                                    <div class="col-12 col-md-4 mb-3 mb-md-0">
                                        <label for="preco" class="form-label text-white">Preço </label>
                                        <div class="input-group">
                                            <span class="input-group-text">R$</span>
                                            <input type="text" class="form-control" id="preco" name="preco" 
                                                   value="<?= $editando ? number_format($produto_edit['preco'], 2, ',', '.') : '' ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-end">
                                    <?php if ($editando): ?>
                                        <a href="admin_produtos.php" class="btn btn-secondary me-2">Cancelar</a>
                                    <?php endif; ?>
                                    <button type="submit" name="salvar" class="btn btn-primary">
                                        <?= $editando ? 'Atualizar Produto' : 'Adicionar Produto' ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- ==============================================
                     SESSÃO DA TABELA DE PRODUTOS DINÂMICA
                    =============================================== -->
                    
                    <div class="card">
                        
                        <!--TITULO DA TABELA-->
                        <div class="card-header">
                            <h3 class="card-title mb-0">Lista de Produtos</h3>
                        </div>
                    
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover">
                                    
                                    <!--LINHAS DA TABELA(INDICE)-->
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Imagem</th>
                                            <th>Nome</th>
                                            <th>Descrição</th>
                                            <th>Preço</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    
                                    <!--CORPO DA TABELA(LINHAS)-->
                                    <tbody>
                                        <!--CASO NAO TENHA NENHUM PRODUTO NO BANCO -->
                                        <?php if (empty($produtos)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center">Nenhum produto cadastrado</td>
                                            </tr>
                                        <?php else: ?>
                                        
                                            <!--PERCORE OS DADOS DA TABELA PRODUTOS COMO ARRAY , EXIBINDO AS INFORMACOES E TRATANDO -->
                                            <?php foreach ($produtos as $produto): ?>
                                                <tr>
                                                    <td><img src="<?= htmlspecialchars($produto['imagem']) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>" class="table-img rounded"></td>
                                                    <td><?= htmlspecialchars($produto['nome']) ?></td>
                                                    <td><?= htmlspecialchars($produto['descricao']) ?></td>
                                                    <td>R$ <?= number_format($produto['preco'], 2, ',', '.') ?></td>
                                                    <td>
                                                        <!-- Botão para Editar Produto -->
                                                        <a href="admin_produtos.php?editar=<?= $produto['id'] ?>" class="btn btn-sm btn-warning me-1 mb-1">
                                                            <i class="fas fa-edit"></i>
                                                            <span class="d-none d-md-inline"> Editar</span>
                                                        </a>
                                                        
                                                        <!-- Botão para Remover Produto -->
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="id" value="<?= $produto['id'] ?>">
                                                            <button type="submit" name="excluir" class="btn btn-sm btn-danger me-1 mb-1 " 
                                                                    onclick="return confirm('Tem certeza que deseja excluir este produto?')">
                                                                <i class="fas fa-trash"></i>
                                                                <span class="d-none d-md-inline"> Excluir</span>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- ==============================================
                                SESSÃO DE PAGINAÇÃO
                            =============================================== -->
                            
                            <div class="card-footer">
                                <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-center mb-0">
                                        <?php if ($paginaAtual > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $paginaAtual - 1])) ?>">Anterior</a>
                                            </li>
                                        <?php endif; ?>
                        
                                        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                                            <li class="page-item <?= $i === $paginaAtual ? 'active' : '' ?>">
                                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>
                        
                                        <?php if ($paginaAtual < $totalPaginas): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $paginaAtual + 1])) ?>">Próxima</a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        
        <!-- ==============================================
            SESSÃO DO RODAPÉ 
        =============================================== -->
        
        <footer class="main-footer">
            <strong>© <?= date('Y') ?> Coffe Admin</strong>
        </footer>
    </div>

    <!--REFERENCIAS DE JQUERY E BOOTSTRAP CSS-->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // FECHAR OS ALERTAS DE MENSAGEM APOS 5 SEGUNDOS 
            setTimeout(() => $('.alert').alert('close'), 5000);
            
            // ATUALIZAR PREVIEW DA IMAGEM
            $('#imagem_upload').on('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        // Cria ou atualiza o elemento de preview
                        let preview = $('#imagem_upload').next().find('img');
                        if (preview.length === 0) {
                            preview = $('<img>', {
                                alt: 'Preview',
                                style: 'max-height: 100px;',
                                class: 'img-thumbnail mt-2'
                            });
                            $('#imagem_upload').after(preview);
                        }
                        preview.attr('src', e.target.result).show();
                    }
                    reader.readAsDataURL(file);
                }
            });
            
            // FORMATAR VALORES MONETÁRIOS 
            $('input[name="preco"]').on('blur', function() {
                let value = $(this).val().replace(/[^\d,]/g, '').replace(',', '.');
                value = parseFloat(value || 0).toFixed(2);
                $(this).val(value.toString().replace('.', ','));
            });
        });
    </script>
</body>
</html>