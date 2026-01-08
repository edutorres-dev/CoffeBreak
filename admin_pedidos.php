<?php
/*
* Arquivo: admin_pedidos.php
* 
* Descrição:
* Painel administrativo para gerenciamento de pedidos da CoffeBreak, exclusivo para usuários com nível 'master'.
* Permite visualizar, filtrar e atualizar o status dos pedidos de forma eficiente com sistema de paginação.
* 
* Funcionalidades:
* -> Autenticação e controle de acesso exclusivo para nível 'master'
* -> Atualização dinâmica do status dos pedidos (confirmado, entregue, cancelado)
* -> Sistema de filtros dinâmicos (ID, status, data)
* -> Paginação automática com cálculo de totais
* -> Tabela responsiva com dados completos dos pedidos
* -> Formatação de dados para exibição (data, valores)
* -> Sistema de mensagens de feedback (sucesso/erro)
* -> Interface administrativa com menu lateral
* 
* Fluxo de operação:
* 1) Inicialização e verificação de segurança:
*    -> Carrega autoload das classes OO
*    -> Inicializa configuração via Config::getInstance()
*    -> Verifica autenticação do usuário via Config::auth()
*    -> Redireciona usuários não autenticados ou não-master para login.php
* 
* 2) Processamento de atualização de status:
*    -> Detecta POST com novo status de pedido
*    -> Valida status com Validator::validarStatus()
*    -> Verifica existência do pedido no banco
*    -> Atualiza status se válido (confirmado, entregue, saiu para entrega, cancelado)
*    -> Exibe mensagem de sucesso ou erro via sessão
* 
* 3) Configuração de filtros:
*    -> Detecta parâmetros GET para filtragem (pedido_id, status, data)
*    -> Aplica sanitização dos dados com Config::trataPost()
*    -> Constrói query dinâmica baseada nos filtros
* 
* 4) Sistema de paginação:
*    -> Configura itens por página (padrão: 10)
*    -> Calcula offset baseado na página atual
*    -> Executa duas consultas: uma para contagem total, outra para dados paginados
*    -> Calcula total de páginas para navegação
* 
* 5) Execução das consultas:
*    -> Prepara e executa consulta de contagem para paginação
*    -> Prepara e executa consulta principal com LIMIT e OFFSET
*    -> Tratamento de exceções PDO com fallback para mensagem de erro
* 
* 6) Renderização da interface:
*    -> Exibe mensagens de sessão (sucesso/erro) se existirem
*    -> Mostra formulário de filtros com valores mantidos
*    -> Renderiza tabela com dados dos pedidos
*    -> Inclui formulário inline para atualização rápida de status
*    -> Gera links de paginação dinâmicos preservando filtros
*    -> Interface responsiva com offcanvas para mobile
* 
* Segurança:
* -> Verificação de token de sessão e nível de acesso
* -> Prepared statements para todas as consultas
* -> Sanitização de inputs com Config::trataPost()
* -> Validação de status com classe Validator
* -> Proteção contra SQL injection via parâmetros nomeados
* 
* Dependências:
* -> autoload.php - Carregamento automático das classes OO
* -> Config.php - Configurações do sistema e autenticação
* -> Validator.php - Validação de dados
* -> Framework Bootstrap 5 para interface
* -> Biblioteca Font Awesome para ícones
* -> Biblioteca jQuery para interações client-side
* -> CSS personalizado (painel_admin.css)
* 
* Observações importantes:
* -> Status permitidos: 'confirmado', 'entregue', 'saiu para entrega', 'cancelado'
* -> Paginação preserva todos os filtros aplicados
* -> Ordenação padrão por data_pedido decrescente
* -> Offcanvas menu para dispositivos móveis
* -> 10 itens por paginação
* -> Mensagens desaparecem automaticamente após 5 segundos
* -> Data no rodapé atualizada automaticamente via JavaScript
* 
* @ Autor - Eduardo Torres Do Ó
* @ Direitos Reservados - 2026 CoffeBreak
*/

// REQUISITA AUTOLOADER PARA CARREGAR AS CLASSES 
require_once __DIR__ . '/autoload.php';

// INCIALIZA AS CONFIGURAÇÕES E METÓDOS DA CLASSE CONFIG
$config = Config::getInstance();

// ESTABELECE CONEXÃO COM O BANCO DE DADOS 
$pdo = $config->getPDO();

// VERIFICA AUTENTICAÇÃO DO USUÁRIO PELO TOKEN
$usuario = $config->auth($_SESSION["TOKEN"] ?? '');

// SE NÃO ESTIVER AUTENTICADO OU NÃO FOR MASTER
if(!$usuario || $usuario['nivel_acesso'] !== 'master') {
    // REDIRECIONA PARA O LOGIN
    header("Location: login.php");
    exit; 
}

// =====================================================================
// PROCESSO DE ATUALIZAÇÃO DE STATUS DE PEDIDOS
// =====================================================================

// SE EXISTE A POSTAGEM DE STATUS
if (isset($_POST['status']) && !empty($_POST['status'])) {
    
    // TRATA O QUE VEM DO POST
    $pedidoId = Config::trataPost($_POST['pedido_id']);
    $status = Config::trataPost($_POST['status']);
    
    // VALIDA O STATUS 
    $erro_status = Validator::validarStatus($status);

    
    try {
        
        // VERIFICA SE TEM O PEDIDO
        $sql = $pdo->prepare("SELECT id FROM pedidos WHERE id = ?");
        $sql->execute([$pedidoId]);
        
        // SE NÃO ENCONTROU
        if (!$sql->fetch()) {
            // MOSTRA A MENSAGEM DE ERRO AO USUÁRIO
            throw new Exception("Pedido não encontrado!");
        }
        
        // SE O PEDIDO EXISTIR NA ARRAY E ESTIVER OS STATUS , ATUALIZA NO BANCO
        if (in_array($status, ['confirmado', 'entregue', 'saiu para entrega','cancelado'] )) {  
            
            // ATUALIZA O STATUS LA NO SISTEMA
            $sql = $pdo->prepare("UPDATE pedidos SET status = ? WHERE id = ?");

            // SE CONSEGIU ATUALIZAR O STATUS NO SISTEMA
            if($sql->execute([$status, $pedidoId])){
                
                // MOSTRA MENSAGEM DE SUCESSO
                $_SESSION['mensagem'] = "Status do pedido #$pedidoId atualizado para " . ucfirst($status) . ".";
                $_SESSION['tipo_mensagem'] = "success";
            }
        }
        
    } catch (Exception $e) {

        // MOSTRA MENSAGEM DE ERRO SE NAO CONSEGIU ATUALIZAR O STATUS DO PEDIDO 
        $_SESSION['mensagem'] = "Erro: " . $e->getMessage();
        $_SESSION['tipo_mensagem'] = "danger";
    }
}

// =====================================================================
// INICIALIZAÇÃO DOS FILTROS E PAGINAÇÃO DE PEDIDOS
// =====================================================================

// INICIALIZAÇÃO DAS VARIÁVEIS DE FILTRO
$filtroStatus = '';
$filtroData = '';
$filtroId = '';
$sql = "SELECT * FROM pedidos WHERE 1=1"; // QUERY BASE
$filtro = []; // ARRAY PARA ARMAZENAR OS PARÂMETROS DOS FILTROS
$totalSql = ""; // QUERY PARA CONTAGEM TOTAL
$itensPorPagina = 10; // NÚMERO DE ITENS POR PÁGINA

// =====================================================================
// CONSTRUÇÃO DOS FILTROS
// =====================================================================

// SE A POSTAGEM QUE SERÁ ENVIADA PARA OS FILTROS EXISTIR 
if(isset($_GET['status']) || isset($_GET['data']) || isset($_GET['pedido_id'])) {

    // TRATA ESSES DADOS E ASSOCIA A CADA FILTRO
    $filtroStatus = isset($_GET['status']) ? Config::trataPost($_GET['status']) : '';
    $filtroData = isset($_GET['data']) ? Config::trataPost($_GET['data']) : '';
    $filtroId = isset($_GET['pedido_id']) ? Config::trataPost($_GET['pedido_id']) : '';
    
    // SE O FILTROSTATUS EXISTIR E NAO FOR VAZIO
    if($filtroStatus && $filtroStatus !== '') {
        // CONSULTA NO BANCO O STATUS
        $sql .= " AND status = ?";
        // RECEBE O VALOR DA CONSULTA ( ENTREGUE , SAIU PARA ENTREGA , CANCELADO , CONFIRMADO) NA ARRAY 
        $filtro[] = strtolower($filtroStatus);
    }
    
    // SE O FILTRODATA EXISITR E NÃO FOR VAZIO
    if($filtroData) {
        // CONSUTLA A DATA NO BANCO
        $sql .= " AND DATE(data_pedido) = ?";
        // O VALOR DA CONSULTA É RECEBIDO NA ARAY
        $filtro[] = $filtroData;
    }
    
    // SE O FILTROID ESTIVER EXISTIR E NAO FOR VAZIO
    if($filtroId !== '') {
        // CONSULTA A ID NO BANCO
        $sql .= " AND id = ?";
        // RECEBE O VALOR DA CONSULTA NA ARRAY
        $filtro[] = $filtroId;
    }
}

// =====================================================================
// CONFIGURAÇÃO DA PAGINAÇÃO
// =====================================================================

$paginaAtual = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($paginaAtual - 1) * $itensPorPagina; // CÁLCULO DO OFFSET

// PREPARA A QUERY PARA CONTAGEM TOTAL DE REGISTROS
$totalSql = str_replace("SELECT *", "SELECT COUNT(*) AS total", $sql);

// ADICIONA ORDENAÇÃO E LIMITES À QUERY PRINCIPAL
$sql .= " ORDER BY data_pedido DESC LIMIT $itensPorPagina OFFSET $offset";

// =====================================================================
// EXECUÇÃO DAS CONSULTAS
// =====================================================================

try {
    
    // CONSULTA PARA OBTER O TOTAL DE REGISTROS
    $stmtTotal = $pdo->prepare($totalSql);
    $stmtTotal->execute($filtro);
    $totalRegistros = $stmtTotal->fetch()['total'];
    $totalPaginas = ceil($totalRegistros / $itensPorPagina);

    // CONSULTA PARA OBTER OS REGISTROS DA PÁGINA ATUAL
    $stmt = $pdo->prepare($sql);
    $stmt->execute($filtro);
    $pedidos = $stmt->fetchAll();
    
    // SE OCORRE ALGUM ERRO AO BUSCA OS PEDIDOS
} catch (PDOException $e) {
    // MOSTRA MENSAGEM DE ERRO AO USUÁRIO
    die("Erro ao buscar pedidos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>

    <!-- META-TAGS PARA CONFIGURAÇÕES DO SITE (COMPATIBILIDADE , DESCRIÇAO DA PAGINA , PALAVRAS CHAVES DE PESQUISA) -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- TITULO DA PAGINA -->
    <title>Pedidos - CoffeBreak Admin</title>

    <!-- FAVICON -->
    <link rel="icon" href="assets\img\favicon\favicon.ico" type="image/x-icon">
   
    <!-- REFERÊNCIA DO BOOTSTRAP CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- REFERÊNCIA DO FONT-AWESOME(BIBLIOTECA DE ICONES ) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- REFERÊNCIA DO CSS EXTERNO -->
    <link href="assets/css/painel_admin.css" rel="stylesheet">
</head>

<body>
    <div class="wrapper">
    
        <!-- ==============================================
         SESSÃO DO BOTÃO PARA ABRIR O OFFCNAVAS(MENU LATERAL)
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
            SESSÃO DO OFFCANVAS (MENU LATERAL)
        =============================================== -->
        
        <div class="offcanvas offcanvas-start text-white" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel" style="width: 250px;">
            
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="sidebarOffcanvasLabel">Coffe Admin</h5>
            </div>
                
            <!-- SUBMENUS -->
            <div class="offcanvas-body p-0">
                <div class="mt-3">
                    <ul class="nav flex-column">
                        
                        <!-- PEDIDO -->
                        <li class="nav-item">
                            <a href="admin_pedidos.php" class="nav-link active">
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

                        <!-- PRODUTOS -->
                        <li class="nav-item">
                            <a href="admin_produtos.php" class="nav-link">
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
                    
                        <!-- SAIR -->
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
            <section class="content">
                <div class="container-fluid">

                    <!-- ==============================================
                     SESSÃO DE EXBIÇÃO DAS MENSAGENS DE ERRO E SUCESSO
                    =============================================== -->
                    
                    <?php if (isset($_SESSION['mensagem'])): ?>
                        <div class="alert alert-<?= $_SESSION['tipo_mensagem'] ?> alert-dismissible fade show">
                            <?= $_SESSION['mensagem'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']); ?>
                    <?php endif; ?>
                    
                    <!-- ==============================================
                        SESSÃO DOS FILTROS
                    =============================================== -->
                    <div class="card mb-4">
                        
                        <div class="card-header">
                            <h3 class="card-title mb-0">Filtrar Pedidos</h3>
                        </div>
                        
                       <div class="card-body">
                            <form method="GET">
                                <div class="row align-items-end g-3"> 
                                    <!--FILTRO PELA ID-->
                                    <div class="col-12 col-sm-6 col-md-3">
                                        <label for="pedido_id" class="form-label text-white mb-1">ID</label>
                                        <input type="number" class="form-control" name="pedido_id" value="<?= htmlspecialchars($filtroId) ?>">
                                    </div>
                                    
                                    <!--FILTRO POR STATUS-->
                                    <div class="col-12 col-sm-6 col-md-3">
                                        <label for="status" class="form-label text-white mb-1">Status</label>
                                        <select class="form-select" name="status">
                                            <option value="" <?= empty($filtroStatus) ? 'selected' : '' ?>>Todos</option>
                                            <option value="confirmado" <?= $filtroStatus === 'confirmado' ? 'selected' : '' ?>>Confirmado</option>
                                            <option value="entregue" <?= $filtroStatus === 'entregue' ? 'selected' : '' ?>>Entregue</option>
                                            <option value="entregue" <?= $filtroStatus === 'saiu para entrega' ? 'selected' : '' ?>>Saiu para entrega</option>
                                            <option value="entregue" <?= $filtroStatus === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>


                                        </select>
                                    </div>
                                    
                                    <!--FILTRO POR DATA-->
                                    <div class="col-12 col-sm-6 col-md-3">
                                        <label for="data" class="form-label text-white mb-1">Data</label>
                                        <input type="date" class="form-control" name="data" value="<?= $filtroData ?>">
                                    </div>
                                    
                                    <!--BOTÕES DE FILTRO-->
                                    <div class="col-12 col-sm-6 col-md-3">
                                        <div class="d-grid gap-2 d-md-flex" style="height: 100%;">
                                            <button type="submit" class="btn btn-primary btn-sm flex-grow-1 d-flex align-items-center justify-content-center">
                                                <i class="fas fa-filter me-1"></i> Filtrar
                                            </button>
                                            <a href="admin_pedidos.php" class="btn btn-primary btn-sm flex-grow-1 d-flex align-items-center justify-content-center">
                                                <i class="fas fa-broom me-1"></i> Limpar
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- ==============================================
                        SESSÃO DA TABELA DINÂMICA COM OS PEDIDOS
                    =============================================== -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title mb-0">Lista de Pedidos</h3>
                        </div>

                        <!-- AREA DA TABELA-->
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover">
                                    
                                    <!-- COLUNAS DA TABELA -->
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Cliente</th>
                                            <th>Pedido</th>
                                            <th>Contato</th>
                                            <th>Valor</th>
                                            <th>Data</th>
                                            <th>Pagamento</th>
                                            <th>Endereço</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>

                                    <!-- CORPO DA TABELA (LINHAS) -->
                                    <tbody>
                                        <?php if (empty($pedidos)): ?>
                                            <tr>
                                                <td colspan="9" class="text-center">Nenhum pedido encontrado</td>
                                            </tr>
                                        <?php else: ?>
                                            <!-- PRECORE A TABELA PEDIDOS E EXIBE AS INFORMAÇÕES DOS PEDIDOS -->
                                            <?php foreach ($pedidos as $pedido): ?>
                                                <tr>
                                                    <td>#<?= htmlspecialchars($pedido['id']) ?></td>
                                                    <td><?= htmlspecialchars($pedido['cliente']) ?></td>
                                                    <td><?= htmlspecialchars($pedido['pedido']) ?></td>
                                                    <td><?= htmlspecialchars($pedido['numero']) ?></td>
                                                    <td>R$ <?= htmlspecialchars($pedido['valor_total']) ?></td>
                                                    <td><?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?></td>
                                                    <td><?= htmlspecialchars($pedido['pagamento']) ?></td>
                                                    <td><?= htmlspecialchars($pedido['endereco']) ?></td>
                                                   
                                                    <td>
                                                        <form method="post">
                                                            <input type="hidden" name="pedido_id" value="<?= $pedido['id'] ?>">
                                                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                                <option value="confirmado" <?= $pedido['status'] === 'confirmado' ? 'selected' : '' ?>>Confirmado</option>
                                                                <option value="entregue" <?= $pedido['status'] === 'entregue' ? 'selected' : '' ?>>Entregue</option>
                                                                <option value="saiu para entrega" <?= $pedido['status'] === 'saiu para entrega' ? 'selected' : '' ?>>Saiu para entrega</option>
                                                                <option value="cancelado" <?= $pedido['status'] === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                                                            </select>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- ==============================================
                            SESSÃO DA PAGINAÇÃO
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
            RODAPÉ DA PAGINA
        =============================================== -->                                
        <footer class="main-footer">
            <strong>© <?= date('Y') ?> Coffe Admin</strong>
        </footer>
    </div>
    
    <!-- REFERÊNCIA DO JQUERY E BOOTSTRAP -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // FECHAR ALERTA APÓS 5 SEGUNDOS
            setTimeout(() => $('.alert').alert('close'), 5000);
            
            // ATUALIZAR DATA NO RODAPÉ AUTOMATICAMENTE
            const yearSpan = document.querySelector('.main-footer strong');
            yearSpan.textContent = `© ${new Date().getFullYear()} Coffe Admin`;
        });
    </script>
</body>
</html>
