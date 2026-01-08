<?php

/*
* Arquivo: admin_clientes.php
* 
* Descrição:
* Painel administrativo para gerenciamento de clientes cadastrados, exclusivo para usuários com nível 'master'.
* Permite visualizar, filtrar e navegar pelos clientes de forma eficiente com sistema de paginação.
* 
* Funcionalidades :
* -> Autenticação e controle de acesso exclusivo para nível 'master'
* -> Sistema de filtros dinâmicos (ID, e-mail, telefone)
* -> Paginação automática com cálculo de totais
* -> Tabela responsiva com dados dos clientes
* -> Formatação de dados para exibição
* -> Interface administrativa com menu lateral
* 
* Fluxo de operação:
* 1) Inicialização e verificação de segurança:
*    -> Carrega autoload das classes OO
*    -> Inicializa configuração via Config::getInstance()
*    -> Verifica autenticação do usuário via Config::auth()
*    -> Redireciona usuários não autenticados ou não-master para login.php
* 
* 2) Configuração de filtros:
*    -> Detecta parâmetros GET para filtragem (id, email, contato)
*    -> Aplica sanitização dos dados com Config::trataPost()
*    -> Constrói query dinâmica baseada nos filtros
*    -> Tratamento especial para telefone (remoção de caracteres especiais)
* 
* 3) Sistema de paginação:
*    -> Configura itens por página (padrão: 10)
*    -> Calcula offset baseado na página atual
*    -> Executa duas consultas: uma para contagem total, outra para dados paginados
*    -> Calcula total de páginas para navegação
* 
* 4) Execução das consultas:
*    -> Prepara e executa consulta de contagem para paginação
*    -> Prepara e executa consulta principal com LIMIT e OFFSET
*    -> Tratamento de exceções PDO com fallback para array vazio
* 
* 5) Renderização da interface:
*    -> Exibe formulário de filtros com valores mantidos
*    -> Mostra tabela com dados dos clientes
*    -> Gera links de paginação dinâmicos preservando filtros
*    -> Interface responsiva com offcanvas para mobile
* 
* Segurança:
* -> Verificação de token de sessão e nível de acesso
* -> Prepared statements para todas as consultas
* -> Sanitização de inputs com Config::trataPost()
* -> Proteção contra SQL injection via parâmetros nomeados
* 
* Dependências:
* -> autoload.php - Carregamento automático das classes OO
* -> Config.php - Configurações do sistema e autenticação
* -> Framework Bootstrap 5 para interface
* -> Biblioteca Font Awesome para ícones
* -> Biblioteca jQuery para interações client-side
* -> CSS personalizado (painel_admin.css)   
* 
* Observações importantes:
* -> Filtro de telefone remove caracteres especiais para busca consistente
* -> Paginação preserva todos os filtros aplicados
* -> Ordenação padrão por ID decrescente 
* -> Offcanvas menu para dispositivos móveis
* -> 10 itens por paginação 
* 
* @ Autor - Eduardo Torres Do Ó
* @ Direitos Reservados - 2026 CoffeBreak
* 
*/

// =====================================================================
// CONFIGURAÇÕES INICIAIS E SEGURANÇA
// =====================================================================

// REQUISITA O AUTOLOADER QUE CARREGA AS CLASSES 
require_once __DIR__ . '/autoload.php';

// INCIALIZA AS CONFIGURAÇÕES E METÓDOS DA CLASSE CONFIG
$config = Config::getInstance();

// ESTABLECE CONEXÃO COM O BANCO
$pdo = $config->getPDO();

// VERIFICA A AUTENTICAÇÃO
$usuario = $config->auth($_SESSION["TOKEN"] ?? '');

// SE NÃO ESTIVER AUTENTICADO OU NÃO FOR MASTER
if(!$usuario || $usuario['nivel_acesso'] !== 'master') {
    
    // REDIRECIONA PARA O LOGIN
    header("Location: login.php");
    exit; 
}

// =====================================================================
// INICIALIZAÇÃO DOS FILTROS
// =====================================================================

$filtro_id = "";
$filtro_email = "";
$filtro_contato = "";

// =====================================================================
// CONSTRUÇÃO DA QUERY BASE
// =====================================================================

$sql = "SELECT id, nome, email, contato, data_cadastro FROM usuarios WHERE 1=1";
$params = [];

// =====================================================================
// APLICAÇÃO DOS FILTROS
// =====================================================================

// SE HOUVER FILTROS
if(isset($_GET['id']) || isset($_GET['email']) || isset($_GET['contato'])) {

    /********************************************************
    * TRATAMENTO DOS DADOS DOS FILTROS 
    ********************************************************/
    $filtro_id = isset($_GET['id']) ? Config::trataPost($_GET['id']) : '';
    $filtro_email = isset($_GET['email']) ? Config::trataPost($_GET['email']) : '';
    $filtro_contato = isset($_GET['contato']) ? Config::trataPost($_GET['contato']) : '';
    
    // FILTRO POR ID 
    if (!empty($filtro_id)) {
        $sql .= " AND id = :id";
        $params[':id'] = $filtro_id;
    }

    // FILTRO POR EMAIL 
    if (!empty($filtro_email)) {
        $sql .= " AND email LIKE :email";
        $params[':email'] = '%' . $filtro_email . '%';
    }

    // FILTRO POR CONTATO (BUSCA POR NÚMERO)
    if (!empty($filtro_contato)) {
        $numeroBusca = preg_replace('/[^0-9]/', '', $filtro_contato);
        $sql .= " AND REPLACE(REPLACE(REPLACE(REPLACE(contato, '(', ''), ')', ''), ' ', ''), '-', '') LIKE :contato";
        $params[':contato'] = '%' . $numeroBusca . '%';
    }
}

// =====================================================================
// CONFIGURAÇÃO DA PAGINAÇÃO
// =====================================================================

$itensPorPagina = 10;
$paginaAtual = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($paginaAtual - 1) * $itensPorPagina;

// ORDENAÇÃO PARA QUERY 
$sql .= " ORDER BY id DESC";

// QUERY PARA CONTAGEM TOTAL DE REGISTROS (USADA PARA CALCULAR PAGINAÇÃO)
$totalSql = str_replace("SELECT id, nome, email, contato, data_cadastro", "SELECT COUNT(*) AS total", $sql);

// ADICIONA LIMITES PARA PAGINAÇÃO NA QUERY PRINCIPAL
$sql .= " LIMIT $itensPorPagina OFFSET $offset";

// =====================================================================
// EXECUÇÃO DAS CONSULTAS
// =====================================================================

try {
    // CONSULTA PARA OBTER O TOTAL DE REGISTROS (PARA PAGINAÇÃO)
    $stmtTotal = $pdo->prepare($totalSql);
    $stmtTotal->execute($params);
    $totalRegistros = $stmtTotal->fetch()['total'];
    $totalPaginas = ceil($totalRegistros / $itensPorPagina);

    // CONSULTA PARA OBTER OS DADOS PAGINADOS
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    // RETORNA ARRAY VAZIA EM CASO DE ERRO
    $usuarios = []; 
    
    // MOSTRA MENSAGEM DE ERRO AO USUÁRIO
    echo "Erro ao buscar usuários. Por favor, tente novamente.";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <!-- META-TAGS PARA CONFIGURAÇÕES DO SITE (COMPATIBILIDADE ,FORMATAÇÃO DO ESCOPO , ETC) -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- TITULO DA PAGINA -->
    <title>Clientes - CoffeBreak Admin</title>

    <!-- FAVICON -->
    <link rel="icon" href="assets\img\favicon\favicon.ico" type="image/x-icon">

     <!-- REFERÊNCIA DO BOOTSTRAP CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- REFERÊNCIA DO FONT-AWESOME (BIBLIOTECA DE ICONES) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- REFERÊNCIA DO CSS EXTERNO -->
    <link href="assets/css/painel_admin.css" rel="stylesheet">

</head>
<body>

    <div class="wrapper">
        
        <!-- ==============================================
         SESSÃO DO BOTÃO MOBILE PARA ABRIR O OFFANCAVAS(MENU LATERAL)
        =============================================== -->       
        
        <nav style="background-color: #2a1c13;" class="navbar  d-lg-none fixed-top">
            <div class="container-fluid">
                 <button class="btn d-lg-none position-fixed" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas" style="left: 10px; top: 10px; z-index: 100; background-color: #6C471D; color: white;">
                    <i class="fas fa-bars"></i>
                </button>
                <span class="navbar-brand ms-auto me-auto text-white">Coffe Admin</span>
            </div>
        </nav>
        
        <div class="offcanvas offcanvas-start text-white" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel" style="width: 250px;">
            
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="sidebarOffcanvasLabel">Coffe Admin</h5>
            </div>
            <!-- AREA DE SUBMENUS -->
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
                            <a href="admin_clientes.php" class="nav-link active">
                                <i class="fas fa-users me-2"></i>
                                <span>Clientes</span>
                            </a>
                        </li>
                        
                        <!-- PRODUTOS -->
                        <li class="nav-item">
                            <a href="admin_produtos.php" class="nav-link">
                                <i class="fas fa-pizza-slice me-2"></i>
                                <span>Produtos</span>
                            </a>
                        </li>
                        
                        <!-- FINANCEIRO -->
                        <li class="nav-item">
                            <a href="admin_financeiro.php" class="nav-link">
                                <i class="fas fa-dollar-sign me-2"></i>
                                <span>Financeiro</span>
                            </a>
                        </li>
                        
                        <!-- LOGOUT -->
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

        <div class="content-wrapper">

           <!-- ==============================================
            SEÇÃO DE FILTROS 
            =============================================== -->
            <section class="content">
                <div class="container-fluid">

                    <div class="card mb-3">

                        <div class="card-header">
                            <h3 class="card-title mb-0">Filtrar Clientes</h3>
                        </div>

                        <div class="card-body">
                            
                            <form method="get">
                                <div class="row g-3 align-items-end">

                                    <!-- FILTRO ID -->
                                    <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                                        <label for="filtro_id" class="form-label text-white">ID</label>
                                        <input type="number" class="form-control" id="filtro_id" name="id" 
                                            value="<?= htmlspecialchars($filtro_id) ?>" >
                                    </div>
                                    
                                    <!-- FILTRO EMAIL -->
                                    <div class="col-xl-3 col-lg-4 col-md-5 col-6">
                                        <label for="filtro_email" class="form-label text-white">E-mail</label>
                                        <input type="text" class="form-control" id="filtro_email" name="email" 
                                            value="<?= htmlspecialchars($filtro_email) ?>" >
                                    </div>
                                    
                                    <!-- FILTRO TELEFONE -->
                                    <div class="col-xl-3 col-lg-5 col-md-6 col-12">
                                        <label for="filtro_contato" class="form-label text-white">Telefone</label>
                                        <input type="text" class="form-control" id="filtro_contato" name="contato" 
                                            value="<?= htmlspecialchars($filtro_contato) ?>" >
                                    </div>
                                    
                                    <!-- BOTÕES -->
                                    <div class="col-xl-4 col-lg-12 col-md-12 col-12">
                                        <div class="d-flex flex-wrap gap-2 mt-md-0 mt-2">
                                            
                                            <button type="submit" class="btn btn-primary flex-grow-1 flex-md-grow-0" style="min-width: 120px;">
                                                <i class="fas fa-filter me-1"></i> Filtrar
                                            </button>
                                            
                                            <a href="admin_clientes.php" class="btn btn-primary flex-grow-1 flex-md-grow-0" style="min-width: 120px;">
                                                <i class="fas fa-broom me-1"></i> Limpar
                                            </a>

                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>                        
                    </div>

                    <!-- ==============================================
                        SEÇÃO DA TABELA DINÂMICA 
                     =============================================== -->
                    <div class="card">
                        
                        <div class="card-header">
                            <h3 class="card-title mb-0">Clientes Cadastrados</h3>
                        </div>

                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover">
                                    <!-- COLUNAS DA TABELA -->
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nome</th>
                                            <th>E-mail</th>
                                            <th>Telefone</th>
                                            <th>Cadastro</th>
                                        </tr>
                                    </thead>

                                    <!-- CORPO DA TABELA(LINHAS) -->
                                    <tbody>
                                        <!-- PERCORE A TABELA USUARIOS DO BD VAI MOSTRANDO AS INFORMAÇÕES RESPECTIVAS DE CADA USUÁRIO -->
                                        <?php foreach($usuarios as $usuario): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($usuario['id']) ?></td>
                                                <td><?= htmlspecialchars($usuario['nome']) ?></td>
                                                <td><?= htmlspecialchars($usuario['email']) ?></td>
                                                <td><?= htmlspecialchars($usuario['contato']) ?></td>
                                                <td><?= date('d/m/Y', strtotime($usuario['data_cadastro'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if(empty($usuarios)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center">Nenhum cliente encontrado</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                       <!-- ==============================================
                            SEÇÃO DE PAGINAÇÃO
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
            </section>
        </div>

        <!-- ==============================================
         SEÇÃO DE RODAPÉ
        =============================================== -->
        <footer class="main-footer">
            <strong>© <?= date('Y') ?> Coffe Admin</strong>
        </footer>

    </div>

    <!-- REFERÊNCIA DO JQUERY -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- REFERÊNCIA DO JAVASCRIPT -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
     
    <!--ADICIONA UMA LARGURA DE 100 A TABELA -->
    <script>
        $(document).ready(function() {
            $('.table').addClass('w-100');
        });
    </script>

</body>
</html>