<?php

/*
* Arquivo: restrita.php
* 
* Descrição:
* Área restrita do sistema após autenticação, contendo todas as funcionalidades para clientes:
* - Visualização de cardápio
* - Realização de pedidos
* - Gerenciamento de conta
* 
* Funcionalidades :
* -> Autenticação e controle de acesso via token de sessão
* -> Gerenciamento de perfil do usuário (alteração de email, senha, número , exclusão de conta)
* -> Sistema completo de pedidos com:
*    - Seleção de produtos
*    - Cálculo de valores
*    - Finalização com dados de entrega
*    - envio de mensagem para o whatssap
* -> Visualização de histórico de pedidos do dia
* 
* Fluxo de operação:
*
* 1) Inicialização e verificação de segurança:
*    -> Carrega o autoload das classes
*    -> Inicializa as classes Config, Usuario, EmailService e Validator
*    -> Verifica autenticação do usuário via Config::auth() com token de sessão
*    -> Redireciona usuários não autenticados para login.php
*    -> Redireciona usuários com nível 'master' para painel administrativo
*    -> Normaliza dados do usuário para evitar warnings
* 
* 2) Processamento de ações do usuário (em paralelo, com base nos parâmetros POST):
*    A) Redefinição de email:
*       -> Detecta POST com parâmetro "novo_email"
*       -> Validação dos campos com Validator::validarEmail()
*       -> Verifica unicidade do email no banco
*       -> Gera novo token e código de confirmação
*       -> Atualiza dados no banco via Usuario::atualizarEmail()
*       -> Em modo produção: envia email de confirmação via EmailService
*       -> Em modo local: redireciona para logout
* 
*    B) Redefinição de telefone:
*       -> Detecta POST com parâmetro "novo_tel"
*       -> Valida preenchimento e formato com Validator::validarTelefone()
*       -> Atualiza número no banco via Usuario::atualizarContato()
*       -> Atualiza sessão e redireciona para logout
* 
*    C) Redefinição de senha:
*       -> Detecta POST com parâmetros "senha" e "repete_senha"
*       -> Valida preenchimento e critérios com Validator::validarSenha()
*       -> Criptografa a senha com SHA1 e atualiza via Usuario::atualizarSenhaPorId()
*       -> Atualiza sessão e redireciona para logout
* 
*    D) Exclusão de conta:
*       -> Detecta POST com parâmetro "senha_exclui"
*       -> Valida preenchimento e trata os campos
*       -> Confirma senha comparando com hash no banco
*       -> Se válida: exclui pedidos do usuário e depois o usuário via Usuario::deletar()
*       -> Se inválida: exibe mensagem de erro
*       -> Redireciona para logout após exclusão
* 
*    E) Processamento de pedidos:
*       -> Detecta POST com todos os campos obrigatórios
*       -> Valida descrição do pedido com Validator::validarPedido()
*       -> Valida valor total com Validator::validarValorTotal()
*       -> Valida endereço com Validator::validarEndereco()
*       -> Valida pagamento com Validator::validarPagamento()
*       -> Insere pedido no banco com status "confirmado"
*       -> Gera mensagem formatada para WhatsApp
*       -> Cria link direto para WhatsApp com confirmação
*       -> Exibe alerta JavaScript para usuário abrir WhatsApp
* 
* 3) Busca de dados para interface:
*    -> Consulta pedidos do usuário para o dia atual
*    -> Organiza dados para exibição no histórico
* 
* 4) Renderização da interface:
*    -> Estrutura HTML com Bootstrap 5
*    -> Exibe informações do usuário autenticado
*    -> Carrega modais para cada funcionalidade
*    -> Mantém valores dos campos em caso de erros de validação
*    -> Exibe mensagens de erro específicas
*    -> Interface responsiva com seções organizadas
* 
* Segurança:
* -> Verificação de token de sessão em todas as requisições
* -> Prepared statements para todas as operações no banco (via classes OO)
* -> Criptografia SHA1 para senhas
* -> Sanitização de todos os inputs com Config::trataPost()
* -> Validação server-side com classe Validator
* -> Proteção contra CSRF (embutida no token de sessão)
* -> Redirecionamentos seguros com exit()
* -> Mensagens de erro genéricas para evitar vazamento de informações
* 
* Dependências:
* -> autoload.php - Carregamento automático de classes OO
* -> Config.php - Configurações do sistema e autenticação
* -> Usuario.php - Operações com tabela de usuários
* -> EmailService.php - Envio de emails de confirmação
* -> Validator.php - Validações de formulários
* -> Framework Bootstrap 5 
* -> Biblioteca Font Awesome para ícones
* -> Biblioteca jQuery para interações client-side
* -> Biblioteca Owl Carousel para elementos visuais
* -> Animate.css para animações
* 
* Observações importantes:
* -> Todo o cardápio é carregado dinamicamente via JavaScript (restrita.js)
* -> O sistema envia confirmação via WhatsApp automaticamente
* -> Modais permanecem abertos em caso de erros de validação
* -> Carrinho de compras armazenado no localStorage do navegador
* -> Histórico de pedidos mostra apenas pedidos do dia atual
* -> Alterações sensíveis forçam logout para nova autenticação
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

// INCIALIZA AS CONFIGURAÇÕES 
$config = Config::getInstance();

// ESTABELECE A CONEXÃO COM O BANCO DE DADOS
$pdo = $config->getPDO();
$site = $config->getSite();
$modo = $config->getModo();

// INICIALIZA OS SERVIÇOS
$usuarioService = new Usuario($pdo);
$emailService = new EmailService($site, $modo);
$validator = new Validator();

// VERIFICA AUTENTICAÇÃO (O TOKEN)
$usuario = $config->auth($_SESSION['TOKEN'] ?? '');

/* NORMALIZA A ARRAY PARA EVITAR AVISOS EM PRODUÇÃO */
if (!is_array($usuario)) {
    $usuario = [
        'id'           => null,
        'nome'         => '',
        'email'        => '',
        'contato'      => '',
        'nivel_acesso' => ''
    ];
}

// SE O USUÁRIO NÃO TEM AUTENTICAÇÃO PELO TOKEN ARMAZENADO NA SESSION
if (!$usuario || !$usuario['id']) {
    // REDIRECIONA PARA PÁGINA DE LOGIN
    header("location: login.php");
    exit;
}

// SE ESTIVER AUTENTICADO E ESTIVER COM NÍVEL DE ACESSO MASTER
if ($usuario['nivel_acesso'] === 'master') {
    // REDIREICONA PARA O PAINEL DE ADMINISTRADOR
    header("Location: admin_pedidos.php");
    exit;
}

// =====================================================================
// PROCESSAMENTO DA REDEFINIÇÃO DE EMAIL
// =====================================================================

// SE EXISTE A POSTAGEM PARA REDEFINIR O EMAIL
if (isset($_POST["novo_email"])) {

    // CRIA UMA ARRAY QUE VAI RECEBER OS ERROS 
    $errosEmail = [];
    
    // SE A POSTAGEM FOI ENVIADA COM CAMPO VAZIO
    if (empty($_POST["novo_email"])) {
        // MOSTRA MENSAGEM DE ERRO AVISANDO QUE O CAMPO É OBRIGATÓRIO
        $errosEmail['geral'] = "Campo obrigatório";

        // CASO CONTRÁRIO
    } else {

        // TRATA O CAMPO DO EMAIL
        $email = Config::trataPost($_POST["novo_email"]);
        
        // VALIDA ELE
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errosEmail['email'] = "Formato de e-mail inválido!";
        }
        
        // VERIFICA SE O EMAIL NOVO ESTÁ SENDO USUADO POR OUTRO USUÁRIO
        $sql = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $sql->execute([$email, $usuario['id']]);
        if ($sql->rowCount() > 0) {
            // SE SIM , MOSTRA A MENSAGEM DE ERRO AO USUÁRIO
            $errosEmail['email_usado'] = "Este e-mail já está em uso por outro usuário!";
        }
        
        // SE NÃO EXISTEM ERROS 
        if (empty($errosEmail)) {
            // INICIALIZA AS VARIAVEIS QUE VÃO RECEBER OS DADOS COMO CADASTRO NOVO
            $dadosAtualizacao = [
                'token' => '',
                'codigo_confirmacao' => sha1(uniqid()),
                'status' => 'novo',
                'data_cadastro' => date("Y-m-d")
            ];
            
            // SE CONSEGIU ATUALIZAR O EMAIL
            if ($usuarioService->atualizarEmail($usuario['id'], $email, $dadosAtualizacao)) {
                // NO MODO LOCAL REDIRECIONA PARA LOGOUT
                if ($modo == "local") {
                    header('location: logout.php');
                    exit;
                    // NO MODO DE PRODUÇÃO , ENVIA O EMAIL PARA O USUÁRIO CONFIRMAR
                } elseif ($modo == "producao") {
                    try {
                        $emailService->enviarConfirmacaoCadastro(
                            $email, 
                            $usuario['nome'], 
                            $dadosAtualizacao['codigo_confirmacao']
                        );
                        header('location: obrigado.php');
                        exit;
                        // SE HOUVE PROBLEMA EM ENVIAR O EMAIL DE CONFIRMAÇÃO , MOSTRA MENSAGEM AO USUARIO
                    } catch (Exception $e) {
                        $errosEmail['envio'] = "Houve um problema ao enviar o email de confirmação: " . $e->getMessage();
                    }
                }
            } else {
                // ERRO AO ATUALIZAR O EMAIL
                $errosEmail['atualizacao'] = "Erro ao atualizar o email";
            }
        }
    }
}

// =====================================================================
// PROCESSAMENTO DA REDEFINIÇÃO DO NÚMERO
// =====================================================================

// SE EXISTE A POSTAGEM PARA REDFINIÇÃO DO NÚMERO
if (isset($_POST["novo_tel"])) {

    // CRIA A ARRAY PARA RECEBER OS ERROS QUE PODEM OCORRER
    $errosTelefone = [];
    
    // SE O CAMPO FOR ENVIADO VAZIO
    if (empty($_POST["novo_tel"])) {

        // MOSTRA MENSAGEM DE ERRO AO USUÁRIO
        $errosTelefone['geral'] = "Campo obrigatório";

        // CASO CONTRÁRIO 
    } else {
        
        // TRATA O CAMPO DO NOVO NÚMERO
        $contato = Config::trataPost($_POST["novo_tel"]);
        
        // VALIDA O NÚMERO DE CELULAR
        $erroValidacao = $validator->validarTelefone($contato);
        if ($erroValidacao) {
            // MOSTRA MENSAGEM DE ERRO CASO NÃO SEJA VÁLIDO
            $errosTelefone['telefone'] = $erroValidacao;
        }
        
        // SE NÃO EXISTE ERRO
        if (empty($errosTelefone)) {
            try {
                // SE CONSEGIU ATUALIZAR O NÚMERO 
                if ($usuarioService->atualizarContato($usuario['id'], $contato)) {

                    // ARMAZENA NA SESSION
                    $_SESSION['usuario_contato'] = $contato;

                    // REDIRECIONA O USUÁRIO PARA LOGOUT
                    header("Location: logout.php");
                    
                    exit;


                    // CASO OCORRA ALGUM ERRO NA HORA DE ATUALIZAR
                } else {
                    // CRIA MENSAGEM DE ERRO 
                    $errosTelefone['atualizacao'] = "Erro ao atualizar número";
                }

            } catch (PDOException $e) {
                $errosTelefone['atualizacao'] = "Erro ao atualizar dados: " . $e->getMessage();
            }
        }
    }
}

// =====================================================================
// PROCESSAMENTO DA REDEFINIÇÃO DE SENHA
// =====================================================================


// SE EXISTE A POSTAGEM PARA REDEFINIÇÃO DE SENHA
if (isset($_POST["senha"]) && isset($_POST["repete_senha"])) {

    // CRIA A ARRAY QUE PODE RECEBER OS EVENTUAIS ERROS
    $errosSenha = [];
    
    // SE A POSTAGEM FOR ENVIADA VAZIA
    if (empty($_POST["senha"]) || empty($_POST["repete_senha"])) {

        // MOSTRA MENSAGEM DE ERRO AO USUÁRIO
        $errosSenha['geral'] = "Todos os campos são obrigatórios";

        // CASO CONTRÁRIO , SE FOI PREENCHIDO CORRETAMENTE
    } else {

        // FAZ O TRATAMENTO DOS DADOS
        $senha = Config::trataPost($_POST["senha"]);
        $senha_cript = sha1($senha);
        $repete_senha = Config::trataPost($_POST["repete_senha"]);
        
        // VALIDA A SENHA
        $erroValidacao = $validator->validarSenha($senha);
        if ($erroValidacao) {
            $errosSenha['senha'] = $erroValidacao;
        }
        
        // VEREFICA SE REPETE SENHA = SENHA
        if ($senha !== $repete_senha) {
            $errosSenha['repete_senha'] = "Senha e repetição de senha diferentes!";
        }
        
        // SE NÃO EXISTEM ERROS 
        if (empty($errosSenha)) {

            // TENTA ATUALIZAR A SENHA NO BANCO
            try {
                // SE CONSEGIU
                if ($usuarioService->atualizarSenhaPorId($usuario['id'], $senha_cript)) {
                    
                    // SALVA A ALTERAÇÃO NA SESSÃO
                    $_SESSION['usuario_senha'] = $senha;
                    
                    // REDIREICONA O USUÁRIO PARA LOGOUT 
                    header("Location: logout.php");
                    
                    exit;

                    // SE DEU ERRO NA HORA DE ATUALIZAR A SENHA DO USUÁRIO
                } else {
                    
                    // MOSTRA MENSAGEM DE ERRO PARA O USUÁRIO
                    $errosSenha['atualizacao'] = "Erro ao atualizar senha";
                }
            } catch (PDOException $e) {
                $errosSenha['atualizacao'] = "Erro ao atualizar dados: " . $e->getMessage();
            }
        }
    }
}

// =====================================================================
// PROCESSAMENTO DA EXCLUSÃO DE CONTA
// =====================================================================


// SE EXISTE A POSTAGEM PARA REDEFINIÇÃO DE SENHA
if (isset($_POST['senha_exclui'])) {

    // CRIA A ARRAY QUE PODE RECEBER OS EVENTUAIS ERROS
    $errosExclusao = [];
    
    // SE A POSTAGEM FOR ENVIADA VAZIA
    if (empty($_POST['senha_exclui'])) {

        // MOSTRA MENSAGEM DE ERRO AO USUÁRIO
        $errosExclusao['senha_exclui'] = "Por favor, informe sua senha";

        // CASO CONTRÁRIO , SE FOI PREENCHIDO CORRETAMENTE
    } else {

        // FAZ O TRATAMENTO DOS DADOS
        $senha = Config::trataPost($_POST["senha_exclui"]);
        $senha_cript = sha1($senha);
        
        // VERIFICA SE A SENHA É IGUAL A CADASTRADA NO BANCO PARA PODER DELETAR A CONTA
        $sql = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? AND senha = ? LIMIT 1");
        $sql->execute([$usuario['id'], $senha_cript]);
        $usuarioBanco = $sql->fetch(PDO::FETCH_ASSOC);
        
        // SE A SENHA É IGUAL
        if ($usuarioBanco) {

            // DELETA OS PEDIDOS PRIMEIRO
            $sql_delete_pedidos = $pdo->prepare("DELETE FROM pedidos WHERE cliente = ?");
            $delete_pedidos = $sql_delete_pedidos->execute([$usuario['nome']]);
            
            // DELETA O USUÁRIO EFETIVAMENTE
            if ($usuarioService->deletar($usuario['id'])) {
                header("Location: logout.php");
                exit();

                //CASO TENHA ERRO AO DELETAR O USUÁRIO
            } else {
                // MOSTRA A MENSAGEM AO USUARUI
                $errosExclusao['delete'] = "Houve um erro ao deletar sua conta!";
            }
        } else {

            // SE A SENHA NÃO FOR IGUAL A CADASTRADA PELO USUÁRIO , MOSTRA A MENSAGEM DE ERRO 
            $errosExclusao['senha_auth'] = "Senha incorreta!";
        }
    }
}

// =====================================================================
// PROCESSAMENTO DE PEDIDOS
// =====================================================================


// SE O USUÁRIO FEZ O PEDIDO ( OU SEJA , SE A POSTAGEM EXISTE )
if (isset($_POST["descricao_pedido"]) && isset($_POST["valor_total"]) && 
    isset($_POST["endereco"]) && isset($_POST["metodo_pagamento"])) {
    
    // CRIA A ARRAY QUE PODE RECEBER OS EVENTUAIS ERROS
    $errosPedido = [];
    
    // SE OS CAMPOS FORAM ENVIADOS VAZIOS PARA O BANCO DE PEDIDOS
    if (empty($_POST["descricao_pedido"]) || empty($_POST["valor_total"]) || 
        empty($_POST["endereco"]) || empty($_POST["metodo_pagamento"])) {
        
        // MOSTRA MENSAGEM DE ERRO AO USUÁRIO
        $errosPedido['geral'] = "Todos os campos são obrigatórios";
        
        // CASO TENHAM SIDO PREENCHIDOS CORRETAMENTE
    } else {
        
        // TRATA OS DADOS DO PEDIDO
        $pedido = Config::trataPost($_POST["descricao_pedido"]);
        $valor_total = Config::trataPost($_POST["valor_total"]);
        $endereco = Config::trataPost($_POST["endereco"]);
        $pagamento = Config::trataPost($_POST["metodo_pagamento"]);
        
        // VALIDA OS DADOS DOS PEDIDOS :

        $erroValidacao = $validator->validarPedido($pedido);
        if ($erroValidacao) {
            $errosPedido['pedido'] = $erroValidacao;
        }
        
        $erroValidacao = $validator->validarValorTotal($valor_total);
        if ($erroValidacao) {
            $errosPedido['valor'] = $erroValidacao;
        }
        
        $erroValidacao = $validator->validarEndereco($endereco);
        if ($erroValidacao) {
            $errosPedido['endereco'] = $erroValidacao;
        }
        
        $erroValidacao = $validator->validarPagamento($pagamento);
        if ($erroValidacao) {
            $errosPedido['pagamento'] = $erroValidacao;
        }
        
        // SE NÃO EXISTEM NENHUM ERRO 
        if (empty($errosPedido)) {
            // INICIALIZA AS VARIAVEIS INCIAIS COM AS INFORMAÇÕES DO CLIENTE E PEDIDO
            $data_pedido = date("Y-m-d H:i:s");
            $cliente = $usuario['nome'];
            $numero = $usuario['contato'];
            $status = "confirmado";
            
            // INSERE O PEDIDO NO BANCO
            $sql = $pdo->prepare("INSERT INTO pedidos VALUES(null,?,?,?,?,?,?,?,?)");
            
            // SE CONSEGIU INSERIR NO BANCO
            if ($sql->execute([$cliente, $numero, $pedido, $data_pedido, $endereco, $pagamento, $valor_total, $status])) {
               
                // ENVIA A MENSAGEM COM AS INFORMAÇÕES DO PEDIDO DO USUÁRIO
                $mensagem = 
                    "*PEDIDO CONFIRMADO - COFFE BREAK*\n\n" .
                    "Olá, *" . $cliente . "*! Seu pedido está sendo preparado!\n\n" .
                    "*ITENS DO PEDIDO:*\n" .
                    str_replace(", ", "\n", $pedido) . "\n\n" .
                    "*Data/Hora:* " . date("d/m/Y à\s H:i", strtotime($data_pedido)) . "\n\n" .
                    "*Endereço:* " . str_replace(", ", " ", $endereco) . "\n\n" .
                    "*Pagamento:* " . $pagamento . "\n\n" .
                    "*Total:* " . $valor_total . "\n\n" .
                    "*Tempo estimado:* 40-60 minutos\n" .
                    "(Avisaremos quando sair para entrega!)\n\n" .
                    "Agradecemos sua preferência! \n" .
                    "*Equipe Coffe Break*";
                
                $mensagem_whatsapp = rawurlencode($mensagem);
                $link_whatsapp = "https://wa.me/$numero?text=$mensagem_whatsapp";

                
                // **ADICIONE ESTE CÓDIGO PARA LIMPAR A SACOLA APÓS PAGAMENTO**
                echo "
                <script>
                    // Limpa a sacola após pedido confirmado
                    if (typeof limparSacola === 'function') {
                        limparSacola();
                    }
                    
                    // Redireciona para WhatsApp
                    if (confirm('Pedido confirmado! Clique em OK para abrir o WhatsApp e receber a confirmação.')) {
                        window.open('$link_whatsapp', '_blank');
                    }
                </script>
                ";


                // CASO TENHA OCORRIDO ALGUM ERRO PARA REGISTRAR O PEDIDO NO BANCO DE PEDIDOS
            } else {
                // MOSTRA A MENSAGEM AO USUARIO
                $errosPedido['insercao'] = "Ocorreu um erro ao realizar seu pedido. Tente Novamente!";
            }
        }
    }
}

// =====================================================================
// BUSCA PEDIDOS DO USUÁRIO
// =====================================================================

// CRIA A VARIÁVEL QUE VAI RECEBER OS PEDIDOS DO CLIENTE
$pedidosUsuario = [];

// RECEBE O NOME DO CLIENTE 
$cliente_nome = $usuario['nome'];

// RECEBE A DATA DO PEDIDO
$data_atual = date('Y-m-d');

// BUSCA E ORDENA OS PEDIDOS EM ORDEM DECRESCENTE QUE SERÃO EXIBIDOS NO MODAL DE MEUS PEDIDOS
$sql = $pdo->prepare("SELECT * FROM pedidos WHERE cliente = ? AND DATE(data_pedido) = ? ORDER BY data_pedido DESC");
$sql->execute([$cliente_nome, $data_atual]);
$pedidosUsuario = $sql->fetchAll(PDO::FETCH_ASSOC);



?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <!-- META-TAGS PARA CONFIGURAÇÕES DO SITE (COMPATIBILIDADE , DESCRIÇAO DA PAGINA , PALAVRAS CHAVES DE PESQUISA) -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=7">
    <meta name="keywords" content="Cafeteria ,Café, Leitura com café , café com biscoito , Lugar para Coffebreak , Cafeteria em Niterói , Melhor cafeteria do Brasil , café expresso ,capuccino ">
    <meta name="description" content="Um espaço aconchegante para apreciar cafés especiais, doces e lanches deliciosos. Perfeito para relaxar, trabalhar ou encontrar amigos. Sabor e qualidade em cada xícara! ">
    
    <!-- OPEN GRAPH META TAGS ( CONFIGURAÇAO DE COMPARTILHAMENTO LINKS NA WEB , MELHORA NO SEO) -->
    <meta property="og:title" content="Cafeteria Coffebreak">
    <meta property="og:description" content="Um espaço aconchegante para apreciar cafés especiais, doces e lanches deliciosos. Perfeito para relaxar, trabalhar ou encontrar amigos. Sabor e qualidade em cada xícara!">
    <meta property="og:image" content="https://coffebreak.online/assets/img/hero/carousel-2.jpeg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:url" content="https://www.coffebreak.online">
    <meta property="og:type" content="website">
    <meta name="author" content="Eduardo Torres">
    <title>CoffeBreak</title>
    
    <!-- REFERÊNCIA DA TIPOGRAFIA POPPIN -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    
    <!-- REFERÊNCIA DA BIBLIOTECA FONT AWESOMA PARA ICONES -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
    
    <!-- REFERÊNCIA DA BIBLIOTECA OWL CAROUSEL PARA SEÇÃO DE TESTEMUNHAS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css" rel="stylesheet">
    
    <!-- REFERÊNCIA DOS ICONES DO BOOTSTRAP -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- REFERÊNCIA DO CSS EXTERNO -->
    <link href="assets/css/restrita.css" rel="stylesheet">

</head>

<body data-bs-spy="scroll" data-bs-target="#menu">
    
    <!-- =========================
    SEÇÃO MENU DE NAVEGAÇÃO (NAVBAR)
    ============================== -->
    <div class="container-fluid position-relative p-0" id="menu">
        <nav class="navbar navbar-expand-lg px-4 px-lg-5 py-3 py-lg-0">
            <a href="#" class="navbar-brand p-0">
                <h1 class="m-0 fs-2 " style="color: var(--primary-color)">
                    <img src="assets/img/logo/logo.png" alt="CoffeBreak" class="brand-logo" />
                    CoffeBreak
                </h1>
            </a>

            <!-- BOTÕES -->
            <div class="d-flex align-items-center ms-auto gap-3 ">
                <!-- BOTÃO DO USUÁRIO-->
                <button class="btn-user" type="button" data-bs-toggle="modal" data-bs-target="#userMainModal">
                    <i class="bi bi-person-circle"></i>
                </button>

                <!-- BOTÃO DA SACOLA-->
                <button class="btn-user" type="button" data-bs-toggle="offcanvas"
                    data-bs-target="#meusPedidosOffcanvas">
                    <i class="bi bi-bag-check"></i>
                    <span id="contadorPedidos" class="bag-badge">0</span>
                </button>
            </div>
        </nav>

        <!-- =========================
        SEÇÃO HERO
        ============================== -->
        <div class="container-fluid hero-section">
            <div class="container d-flex align-items-center justify-content-center min-vh-100">
                <div class="container overlay-bottom">
                    <div class="row justify-content-start text-center">
                        <div class="col-lg-6 text-start">
                            <h1 class=" text-white mb-3 fs-1 ">
                                Por trás de mentes brilhantes, <br>
                                sempre há um bom café.
                            </h1>
                            <p class="fs-5 text-white mb-4">
                                " Sabores que acolhem, aromas que encantam. <br>
                                Sinta o prazer de um café especial ! "
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==============================================
    MODAL PRINCIPAL DO USUÁRIO
    =============================================== -->
    <div class="modal fade user-modal" id="userMainModal" tabindex="-1" aria-labelledby="userMainModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body p-0">
                    <!-- AVATAR E INFORMAÇÕES DE CONTA DO USUÁRIO -->
                    <div class="user-avatar">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <div class="user-info">
                        <h4><?php echo htmlspecialchars($usuario['nome']); ?></h4>
                        <p><?php echo htmlspecialchars($usuario['email']); ?></p>
                    </div>

                    <!-- SUBMENU DE OPÇÕES DO USUÁRIO -->
                    <ul class="user-options">
                        <li>
                            <a href="#" data-bs-toggle="modal" data-bs-target="#AlterarEmaillModal"
                                data-bs-dismiss="modal">
                                <i class="bi bi-person-fill-gear"></i> Alterar Email
                            </a>
                        </li>
                        
                        <li>
                            <a href="#" data-bs-toggle="modal" data-bs-target="#MeusPedidosModal"
                                data-bs-dismiss="modal">
                                <i class="bi bi-receipt"></i> Meus Pedidos
                            </a>
                        </li>

                        <li>
                            <a href="#" data-bs-toggle="modal" data-bs-target="#editTelefoneModal"
                                data-bs-dismiss="modal">
                                <i class="bi bi-telephone"></i> Alterar Número
                            </a>
                        </li>

                        <li>
                            <a href="#" data-bs-toggle="modal" data-bs-target="#AlterarSenhaModal"
                                data-bs-dismiss="modal">
                                <i class="bi bi-shield-lock"></i> Alterar Senha
                            </a>
                        </li>

                        <li>
                            <a href="#" data-bs-toggle="modal" data-bs-target="#ModalDeletarConta"
                                data-bs-dismiss="modal">
                                <i class="bi bi-trash-fill"></i> Excluir Conta
                            </a>
                        </li>

                        <li>
                            <a href="logout.php" class="logout">
                                <i class="bi bi-box-arrow-right"></i> Sair
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- ==============================================
    MODAL MEUS PEDIDOS
    =============================================== -->
    <div class="modal fade " id="MeusPedidosModal" tabindex="-1" aria-labelledby="MeusPedidosModalLabel" 
         aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-light" id="MeusPedidosModalLabel">
                        <i class="bi bi-receipt me-2"></i>Meus Pedidos
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" 
                            aria-label="Close"></button>
                </div>
                
                <div class="modal-body">

                    <!-- PRECORE E MOSTRA OS PEDIDOS DO USUÁRIO COM AS INFORMAÇÕES FORMATADOS -->
                    <?php if (count($pedidosUsuario) > 0): ?>
                    <div class="pedidos-list">
                        <?php foreach ($pedidosUsuario as $pedido): 
                            
                            // DATA DO PEDIDO
                            $data_formatada = date("d/m/Y", strtotime($pedido['data_pedido']));
                            
                            // HORA DO PEDIDO FORMATADA
                            $hora_formatada = date("H:i", strtotime($pedido['data_pedido']));
                            
                            // STATUS DO PEDIO ( CONFIRMADO , SAIU PARA ENTREGA , ENTREGUE E CANCELADO)
                            $status_class = '';
                            switch(strtolower($pedido['status'])) {
                                case 'confirmado': $status_class = 'bg-warning text-dark'; break;
                                case 'saiu para entrega': $status_class = 'bg-primary'; break;
                                case 'entregue': $status_class = 'bg-success'; break;
                                case 'cancelado': $status_class = 'bg-danger'; break;
                                default: $status_class = 'bg-secondary';
                            }
                        ?>
                        
                        <div class="pedido-card mb-4 p-3 border-bottom border-white">
                            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start mb-3">
                                <div class="mb-2">

                                    <!-- EXIBE AS INFORMAÇÕES DOS PEDIDOS -->
                                    <div class="d-flex flex-wrap align-items-center mb-1">
                                        <span class="badge <?php echo $status_class; ?> me-3 mb-1">
                                            <?php echo ucfirst($pedido['status']); ?>
                                        </span>
                                        <span class="text-white-60 small mb-1">
                                            <i class="bi bi-calendar-check me-1"></i>
                                            <?php echo $data_formatada; ?>
                                        </span>
                                        <span class="text-white-60 small ms-sm-3 mb-1">
                                            <i class="bi bi-clock me-1"></i>
                                            <?php echo $hora_formatada; ?>
                                        </span>
                                    </div>

                                    <h6 class=" text-white-60 mb-0">Pedido #<?php echo $pedido['id']; ?></h6>
                                </div>
                            </div>
                            
                            <div class="detalhes-pedido">
                                <div class="mb-3">
                                    <div style="background-color: #6e421bff;" class=" p-3 rounded">
                                        <h6 class="text-white mb-2">
                                            <i class="bi bi-basket me-2"></i>Itens do Pedido
                                        </h6>
                                        <p class="text-white mb-0"><?php echo htmlspecialchars($pedido['pedido']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div  style="background-color: #6e421bff;" class="p-3 rounded">
                                        <h6 class="text-white mb-2">
                                            <i class="bi bi-credit-card me-2"></i>Pagamento
                                        </h6>
                                        <p class="text-white mb-0">
                                            <?php echo htmlspecialchars($pedido['pagamento']); ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="mb-0">
                                    <div  style="background-color: #6e421bff;"  class="p-3 rounded">
                                        <h6 class="text-white mb-2">
                                            <i class="bi bi-geo-alt me-2"></i>Endereço de Entrega
                                        </h6>
                                        <p class="text-white mb-0 small">
                                            <?php echo htmlspecialchars($pedido['endereco']); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-3 text-center ">
                        <p class="text-white small mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            <?php echo count($pedidosUsuario); ?> pedido(s) realizado(s) hoje
                        </p>
                    </div>
                    
                    <?php else: ?>
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="bi bi-bag-x text-white" style="font-size: 4rem;"></i>
                        </div>
                        <h5 class="text-white mb-3">Nenhum pedido hoje</h5>
                        <p class="text-white-50 mb-4">
                            Você ainda não realizou nenhum pedido hoje. Explore nosso cardápio e faça seu pedido!
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ==============================================
    MODAL DE ALTERAR EMAIL
    =============================================== -->
    <div class="modal fade " id="AlterarEmaillModal" tabindex="-1" aria-labelledby="AlterarEmailModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="AlterarEmailModalLabel">
                        <i class="bi bi-person-fill-gear me-2"></i>Alterar Email
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <?php if (isset($errosEmail['geral'])): ?>
                        <div class="erro-geral animate__animated animate__rubberBand text-center">
                            <?php echo $errosEmail['geral']; ?>
                        </div>
                        <?php endif; ?>

                        <?php if (isset($errosEmail['email_usado'])): ?>
                        <div class="erro-geral animate__animated animate__rubberBand text-center">
                            <?php echo $errosEmail['email_usado']; ?>
                        </div>
                        <?php endif; ?>

                        <?php if (isset($errosEmail['atualizacao'])): ?>
                        <div class="erro-geral animate__animated animate__rubberBand text-center">
                            <?php echo $errosEmail['atualizacao']; ?>
                        </div>
                        <?php endif; ?>

                        <?php if (isset($errosEmail['envio'])): ?>
                        <div class="erro-geral animate__animated animate__rubberBand text-center">
                            <?php echo $errosEmail['envio']; ?>
                        </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="email_atual" class="form-label text-white">Email Atual</label>
                            <input type="email" class="form-control text-white" id="email_atual"
                                value="<?php echo htmlspecialchars($usuario['email']); ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="novo_email" class="form-label text-white">Novo Email</label>
                            <input class="form-control text-white <?php echo isset($errosEmail['geral']) || isset($errosEmail['email']) ? 'erro-input' : ''; ?>" 
                                   name="novo_email" type="email" placeholder="Digite o Novo Email" 
                                   value="<?php echo isset($_POST['novo_email']) ? htmlspecialchars($_POST['novo_email']) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ==============================================
    MODAL DE ALTERAR TELEFONE
    =============================================== -->
    <div class="modal fade" id="editTelefoneModal" tabindex="-1" aria-labelledby="editTelefoneModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-light" id="editTelefoneModalLabel">
                        <i class="bi bi-telephone me-2"></i>Alterar Número
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <form method="post">
                    <div class="modal-body">
                        <?php if (isset($errosTelefone['geral'])): ?>
                        <div class="erro-geral animate__animated animate__rubberBand text-center">
                            <?php echo $errosTelefone['geral']; ?>
                        </div>
                        <?php endif; ?>

                        <?php if (isset($errosTelefone['atualizacao'])): ?>
                        <div class="erro-geral animate__animated animate__rubberBand text-center">
                            <?php echo $errosTelefone['atualizacao']; ?>
                        </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="telefone_atual" class="form-label text-white">Número Atual</label>
                            <input type="text" class="form-control text-white" id="telefone_atual"
                                value="<?php echo htmlspecialchars($usuario['contato']); ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="novo_tel" class="form-label text-white">Novo Número</label>
                            <input class="form-control text-white <?php echo isset($errosTelefone['geral']) || isset($errosTelefone['telefone']) ? 'erro-input' : ''; ?>" 
                                   name="novo_tel" type="tel" placeholder="Digite o novo número"
                                   value="<?php echo isset($_POST['novo_tel']) ? htmlspecialchars($_POST['novo_tel']) : ''; ?>" required>
                        </div>

                        <?php if (isset($errosTelefone['telefone'])): ?>
                        <div class="erro-validacao">
                            <?php echo $errosTelefone['telefone']; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ==============================================
    MODAL DE ALTERAR SENHA
    =============================================== -->
    <div class="modal fade " id="AlterarSenhaModal" tabindex="-1" aria-labelledby="AlterarSenhaModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header mb-4">
                    <h5 class="modal-title text-white" id="AlterarSenhaModalLabel">Alterar Senha</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <form method="post">
                    <div class="modal-body">
                        <?php if (isset($errosSenha['geral'])): ?>
                        <div class="erro-geral animate__animated animate__rubberBand text-center">
                            <?php echo $errosSenha['geral']; ?>
                        </div>
                        <?php endif; ?>

                        <?php if (isset($errosSenha['atualizacao'])): ?>
                        <div class="erro-geral animate__animated animate__rubberBand text-center">
                            <?php echo $errosSenha['atualizacao']; ?>
                        </div>
                        <?php endif; ?>

                        <div class="mb-4">
                            <input class="form-control bg-dark text-white <?php echo isset($errosSenha['geral']) || isset($errosSenha['senha']) ? 'erro-input' : ''; ?>" 
                                   name="senha" type="password" placeholder="Nova Senha">
                            <?php if (isset($errosSenha['senha'])): ?>
                            <div class="erro-validacao">
                                <?php echo $errosSenha['senha']; ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-4">
                            <input class="form-control bg-dark text-white <?php echo isset($errosSenha['geral']) || isset($errosSenha['repete_senha']) ? 'erro-input' : ''; ?>" 
                                   name="repete_senha" type="password" placeholder="Repita a Nova Senha">
                            <?php if (isset($errosSenha['repete_senha'])): ?>
                            <div class="erro-validacao">
                                <?php echo $errosSenha['repete_senha']; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ==============================================
    MODAL DE EXCLUIR CONTA
    =============================================== -->
    <div class="modal fade " id="ModalDeletarConta" tabindex="-1" aria-labelledby="deleteAccountModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header mb-2">
                    <h5 class="modal-title text-light" id="deleteAccountModalLabel">Excluir Conta</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <form method="post">
                    <div class="modal-body">
                        <?php if (isset($errosExclusao['delete'])): ?>
                        <div class="erro-geral animate__animated animate__rubberBand text-center">
                            <?php echo $errosExclusao['delete']; ?>
                        </div>
                        <?php endif; ?>

                        <?php if (isset($errosExclusao['senha_exclui'])): ?>
                        <div class="erro-geral animate__animated animate__rubberBand text-center">
                            <?php echo $errosExclusao['senha_exclui']; ?>
                        </div>
                        <?php endif; ?>

                        <p class="text-white">Tem certeza que deseja excluir sua conta? Esta ação não pode ser desfeita.</p>

                        <div class="mb-3">
                            <input class="form-control text-white <?php echo isset($errosExclusao['senha_exclui']) || isset($errosExclusao['senha_auth']) ? 'erro-input' : ''; ?>" 
                                   type="password" placeholder="Digite sua senha para confirmar"
                                   name="senha_exclui" required>
                            <?php if (isset($errosExclusao['senha_auth'])): ?>
                            <div class="erro-validacao">
                                <?php echo $errosExclusao['senha_auth']; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Deletar Conta</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ==============================================
    OFFCANVAS (MENU LATERAL DOS PEDIDOS - SACOLA)
    =============================================== -->
    <div style="background-color:rgb(66, 41, 30);" class="offcanvas offcanvas-end" tabindex="-1" id="meusPedidosOffcanvas" 
        aria-labelledby="meusPedidosOffcanvasLabel">
        <div class="offcanvas-header border-secondary">
            <h5 class="offcanvas-title " id="meusPedidosOffcanvasLabel">
                <i class="bi bi-bag-check me-2"></i>Sacola
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
                aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div id="offcanvas-itens" style="max-height: 60vh; overflow-y: auto;">
                <!-- ITENS DE PEDIDO SERÃO INSERIDOS AQUI VIA JAVASCRIPT -->
                <div class="pedido-item">
                    <div class="d-flex justify-content-between align-items-start"></div>
                </div>
            </div>

            <h4 id="offcanvas-total" style="color: #ca7a34ff;" class=" mt-3"></h4>

            <button type="button" class="btn btn-warning w-100 mt-3" onclick="finalizarPedido(event)">
                Finalizar Pedido
            </button>
        </div>
    </div>

    <!-- ==============================================
    MODAL DE PAGAMENTO
    =============================================== -->
    <div class="modal fade" id="modalPagamento" tabindex="-1" aria-labelledby="modalPagamentoLabel" aria-hidden="true"
        data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalPagamentoLabel">Finalizar Pedido</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formPagamento" method="post">
                        <?php if (isset($errosPedido['geral'])): ?>
                        <div class="erro-geral animate__animated animate__rubberBand text-center">
                            <?php echo $errosPedido['geral']; ?>
                        </div>
                        <?php endif; ?>

                        <?php if (isset($errosPedido['insercao'])): ?>
                        <div class="erro-geral animate__animated animate__rubberBand text-center">
                            <?php echo $errosPedido['insercao']; ?>
                        </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="descricao_pedido" class="form-label fw-bold">Detalhes do Pedido:</label>
                            <input class="form-control bg-dark text-white <?php echo isset($errosPedido['geral']) || isset($errosPedido['pedido']) ? 'erro-input' : ''; ?>" 
                                   type="text" id="descricao_pedido" name="descricao_pedido" readonly
                                   style="cursor: default; white-space: pre; overflow-x: auto;" 
                                   value="<?php echo isset($_POST['descricao_pedido']) ? htmlspecialchars($_POST['descricao_pedido']) : ''; ?>">
                        </div>

                        <?php if (isset($errosPedido['pedido'])): ?>
                        <div class="erro-validacao mb-2">
                            <?php echo $errosPedido['pedido']; ?>
                        </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="valor_total" class="form-label fw-bold">Valor Total:</label>
                            <input class="form-control bg-dark text-white <?php echo isset($errosPedido['geral']) || isset($errosPedido['valor']) ? 'erro-input' : ''; ?>" 
                                   type="text" id="valor_total" name="valor_total" readonly
                                   style="cursor: default; font-weight: bold;" 
                                   value="<?php echo isset($_POST['valor_total']) ? htmlspecialchars($_POST['valor_total']) : ''; ?>">
                        </div>

                        <?php if (isset($errosPedido['valor'])): ?>
                        <div class="erro-validacao mb-2">
                            <?php echo $errosPedido['valor']; ?>
                        </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="endereco" class="form-label fw-bold">Endereço:</label>
                            <input class="form-control bg-dark text-white <?php echo isset($errosPedido['geral']) || isset($errosPedido['endereco']) ? 'erro-input' : ''; ?>" 
                                   type="text" id="endereco" name="endereco" placeholder="Digite seu endereço"
                                   value="<?php echo isset($_POST['endereco']) ? htmlspecialchars($_POST['endereco']) : ''; ?>" required>
                        </div>

                        <?php if (isset($errosPedido['endereco'])): ?>
                        <div class="erro-validacao mb-2">
                            <?php echo $errosPedido['endereco']; ?>
                        </div>
                        <?php endif; ?>

                        <div class="mb-4">
                            <label for="metodo_pagamento" class="form-label fw-bold">Método de Pagamento:</label>
                            <select class="form-select text-light <?php echo isset($errosPedido['geral']) || isset($errosPedido['pagamento']) ? 'erro-input' : ''; ?>" 
                                    id="metodo_pagamento" name="metodo_pagamento" required>
                                <option value="">Selecione uma opção</option>
                                <option value="Pix" <?php echo (isset($_POST['metodo_pagamento']) && $_POST['metodo_pagamento'] == 'Pix') ? 'selected' : ''; ?>>Pix</option>
                                <option value="Cartão de Credito" <?php echo (isset($_POST['metodo_pagamento']) && $_POST['metodo_pagamento'] == 'Cartão de Credito') ? 'selected' : ''; ?>>Cartão de Crédito</option>
                                <option value="Cartão de Débito" <?php echo (isset($_POST['metodo_pagamento']) && $_POST['metodo_pagamento'] == 'Cartão de Débito') ? 'selected' : ''; ?>>Cartão de Débito</option>
                                <option value="VR" <?php echo (isset($_POST['metodo_pagamento']) && $_POST['metodo_pagamento'] == 'VR') ? 'selected' : ''; ?>>Vale Refeição</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-warning w-100 fw-bold py-2">CONFIRMAR PEDIDO</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ==============================================
    SEÇÃO PRODUTOS 
    =============================================== -->
    <div class="products-section" id="cardapio">
        <div class="container">
            <div class="text-center mb-5 mt-5">
                <h1 class="text-white" style="letter-spacing: 5px;">Produtos</h1>
            </div>

            <div class="row g-4 " id="insere_produtos">
                <!-- PEDIDOS INSERIDOS AQUI VIA JS -->
            </div>
        </div>
    </div>

    <!-- ==============================================
    SEÇÃO DO RODAPÉ
    =============================================== -->
    <div class="container-fluid text-white pt-3 px-0 position-relative overlay-top" style="background-color: #42291e;"
        id="contatos">
        <div class="row mx-0 pt-5 px-sm-3 px-lg-5 mt-4">
            <div class="col-lg-4 col-md-6 mb-5 text-center text-lg-left">
                <h4 class="text-white text-uppercase mb-4" style="letter-spacing: 3px">Contato</h4>
                <p><i class="fa fa-map-marker-alt mr-2"></i> Centro Niterói</p>
                <p><i class="fa-brands fa-whatsapp mr-2"></i> 21 995262727</p>
                <p class="m-0"><i class="fa fa-envelope mr-2"></i> CoffeBreak@gmail.com</p>
            </div>

            <div class="col-lg-4 col-md-6 mb-5 text-center text-lg-left">
                <h4 class="text-white text-uppercase mb-4" style="letter-spacing: 3px">Redes Sociais</h4>
                <p>Nos siga e fique por dentro das novidades!</p>
                <div class="d-flex justify-content-center justify-content-lg-center">
                    <a class="btn btn-lg btn-outline-light btn-lg-square mr-2" href="#"><i class="fab fa-twitter"></i></a>
                    <a class="btn btn-lg btn-outline-light btn-lg-square mr-2" href="#"><i class="fab fa-facebook-f"></i></a>
                    <a class="btn btn-lg btn-outline-light btn-lg-square mr-2" href="#"><i class="fab fa-linkedin-in"></i></a>
                    <a class="btn btn-lg btn-outline-light btn-lg-square" href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 mb-5 text-center text-lg-right">
                <h4 class="text-white text-uppercase mb-4" style="letter-spacing: 3px">Funcionamento</h4>
                <div class="d-inline-block text-center text-lg-right">
                    <h6 class="text-white">Segunda - Sexta</h6>
                    <p>19:00 - 22:00</p>
                    <h6 class="text-white text-uppercase">Sábado - Domingo</h6>
                    <p>17:00 - 23:00</p>
                </div>
            </div>
        </div>

        <div class="container-fluid text-center text-white border-top mt-4 py-4 px-sm-3 px-md-5"
            style="border-color: rgba(256, 256, 256, 0.1) !important">
            <p class="mb-2 text-white">
                Copyright &copy;
                <a class="font-weight-bold" style="color: #dc994ce2;" href="#">BellaVitta</a>.
                Todos os Direitos Reservados.
            </p>
            <p class="m-0 text-white">
                Desenvolvido por
                <a class="font-weight-bold" style="color: #dc994ce2;"
                    href="https://www.linkedin.com/in/eduardo-torres-do-%C3%B3-576085385/">Eduardo Torres Do Ó</a>
            </p>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
    <script src="assets/js/restrita.js"></script>

    <script>
        
        // EFEITO DO STICKY SCROLL NO MENU NAVBAR
        $(window).scroll(function () {
            if ($(this).scrollTop() > 45) {
                $(".navbar").addClass("sticky-top shadow-sm");
            } else {
                $(".navbar").removeClass("sticky-top shadow-sm");
            }
        });

        // MANTÉM OS MODAIS ABERTOS QUANDO OCORRE OS ERROS
        <?php if (!empty($errosEmail)): ?>
        $(document).ready(function() {
            var modalEmail = new bootstrap.Modal(document.getElementById('AlterarEmaillModal'));
            modalEmail.show();
        });
        <?php endif; ?>

        <?php if (!empty($errosTelefone)): ?>
        $(document).ready(function() {
            var modalTelefone = new bootstrap.Modal(document.getElementById('editTelefoneModal'));
            modalTelefone.show();
        });
        <?php endif; ?>

        <?php if (!empty($errosSenha)): ?>
        $(document).ready(function() {
            var modalSenha = new bootstrap.Modal(document.getElementById('AlterarSenhaModal'));
            modalSenha.show();
        });
        <?php endif; ?>

        <?php if (!empty($errosExclusao)): ?>
        $(document).ready(function() {
            var modalExclusao = new bootstrap.Modal(document.getElementById('ModalDeletarConta'));
            modalExclusao.show();
        });
        <?php endif; ?>

        <?php if (!empty($errosPedido)): ?>
        $(document).ready(function() {
            var modalPedido = new bootstrap.Modal(document.getElementById('modalPagamento'));
            modalPedido.show();
        });
        <?php endif; ?>

        // TEMPORIZADOR DAS MENSAGENS DE ERRO
        setTimeout(() => {
            $('.erro-geral').fadeOut(600, function() {
                $(this).remove();
            });
            $('.erro-validacao').fadeOut(600, function() {
                $(this).remove();
            });
            $('.erro-input').removeClass('erro-input').css('border', ''); 
        }, 4000);
    </script>
</body>
</html>