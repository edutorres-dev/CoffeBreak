<?php

/*
* Arquivo: login.php
* 
* Descrição: Sistema de autenticação de usuários . 
* Gerencia o processo de login com validação de credenciais, verificação de status
* e estabelecimento de sessões seguras para acesso à área restrita.
* 
* Funcionalidades:
* -> Validação de credenciais (e-mail e senha)
* -> Verificação do status de confirmação de e-mail do usuário
* -> Geração de tokens de sessão únicos e criptografados
* -> Armazenamento seguro de tokens em sessões PHP
* -> Redirecionamento para área restrita após autenticação bem sucedida
* -> Feedback visual para usuário ( mensagens de erro/sucesso)
* -> Gerenciamento de sessões com segurança
* 
* Fluxo de operação:
* 1) Recebe credenciais via método POST do formulário 
* 2) Valida e trata os campos 
* 3) Criptografa a senha usando algoritmo SHA1
* 4) Busca usuário correspondente no banco de dados
* 5) Verifica se usuário foi encontrado
* 6) Confirma se o status do usuário é "confirmado"
* 7) Gera token de sessão único   
* 8) Atualiza token do usuário no banco de dados
* 9) Armazena token na sessão PHP do usuário
* 10) Redireciona para área restrita do sistema
* 
* 
* Segurança:
* -> Uso de prepared statements para prevenir SQL injection
* -> Criptografia SHA1 para senhas
* -> Tokens de sessão únicos
* -> Validação server-side 
* -> Sanitização de inputs
* 
* Dependências :
* -> autoload.php - Carregamento automático das classes ( Config.php , Usuario.php )
* -> Config.php - Obtenção de conexão PDO ao banco de dados e métodos de sanitização
* -> Usuario.php -  Busca por e-mail/senha e atualização de tokens
* 
* 
* Tecnologias Utilizadas:
* -> Bootstrap 5.3.3 - Framework CSS responsivo
* -> Bootstrap Icons - Conjunto de ícones modernos
* -> Animate.css 4.1.1 - Animações CSS pré-definidas
* -> jQuery 3.6.0 - Manipulação DOM e eventos
* -> CSS Customizado (form_aut.css) - Estilos específicos do formulário
* 
* 
* 
* @ Autor - Eduardo Torres Do Ó
* @ Direitos Reservados - 2026 CoffeBreak


/****************************************************************
 * CONFIGURAÇÕES INICIAIS E SEGURANÇA
****************************************************************/

// REQUISITA O AUTOLOADER QUE CARREGA AS CLASSES
require_once "autoload.php";

// INSTANCIA CONFIGURAÇOES DO BANCO
$config = Config::getInstance();

// INICIALIZA OS SERVIÇOS DA CLASSE CONFIG PEGANDO CONEXAO PDO
$usuarioModel = new Usuario($config->getPDO());

// VARIÁVEL PARA AS MENSAGENS DE ERRO
$erro_login = null;

/****************************************************************
 PROCESSAMENTO DO FORMULÁRIO DE LOGIN
 ****************************************************************/

// SE A POSTAGEM EXISTIR
if (isset($_POST["email"]) && isset($_POST["senha"]) && !empty($_POST["email"]) && !empty($_POST["senha"])) {

    // TRATA OS DADOS
    $email = Config::trataPost($_POST["email"]);
    $senha = Config::trataPost($_POST["senha"]);
    $senha_cript = sha1($senha);

    // VERIFICA SE O USUÁRIO ESTÁ CADASTRADO
    $usuario = $usuarioModel->buscarPorEmailESenha($email, $senha_cript);

    // SE FOI CADASTRADO
    if ($usuario) {

        // SE ELE ESTÁ CONFIRMADO
        if ($usuario["status"] == "confirmado") {

            // CRIA UM TOKEN RANDÔMICO CRIPTOGRAFADO COM A DATA ATUAL
            $token = sha1(uniqid() . date("d-m-Y-H-i-s"));

            // ATUALIZA O TOKEN DESSE USUÁRIO NO BANCO
            if ($usuarioModel->atualizarToken($email, $senha_cript, $token)) {

                // ARMAZENA O TOKEN NA SESSION
                $_SESSION["TOKEN"] = $token;

                // REDIRECIONA O USUÁRIO PARA PAGINA RESTRITA
                header("location: restrita.php");
                exit;
            }

        // SE O USUÁRIO NÃO ESTIVER COM O STATUS CONFIRMADO
        } else {
            $erro_login = "Por favor confirme seu e-mail";
        }

    // SE O USUÁRIO NEM FOI CADASTRADO
    } else {
        // GERA MENSAGEM DE ERRO 
        $erro_login = "Usuário ou senha incorretos";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    
    <!-- META-TAGS PARA CONFIGURAÇÕES DO SITE (COMPATIBILIDADE , DESCRIÇAO DA PAGINA , PALAVRAS CHAVES DE PESQUISA) -->
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login</title>

    <!-- BOOTSTRAP CSS E BOOTSTRAP ICONS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />

    <!-- REFERÊNCIA DA ANIMAÇÃO PARA AS VALIDAÇÕES -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />

    <!-- REFERÊNCIA DO CSS EXTERNO  -->
    <link rel="stylesheet" href="assets/css/form_aut.css" />

</head>

<body>
    <div class="aut-container">

        <!-- ==============================================
        FORMUÁRIO DE LOGIN
        =============================================== -->

        <h3>Login</h3>
        <form method="post">

            <!-- SE O USUÁRIO FOI CADASTRADO COM SUCESSO -->
            <?php if (isset($_GET['result']) && ($_GET['result'] == "ok")) { ?>
                <div class="animate__animated animate__rubberBand sucesso">
                    Usuário cadastrado com sucesso!
                </div>
            <?php } ?>

            <!-- SE O USUÁRIO NÃO ESTIVER CADASTRADO (ERRO_LOGIN) -->
            <?php if ($erro_login) { ?>
                <div class="erro-geral animate__animated animate__rubberBand text-center">
                    <?php echo $erro_login ?>
                </div>
            <?php } ?>

            <!-- CAMPO EMAIL -->
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control" placeholder="Digite seu email" 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required />
            </div>

            <!-- CAMPO SENHA -->
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="senha" class="form-control" placeholder="Digite sua senha" required />
            </div>

            <!-- BOTÃO DE SUBMIT -->
            <button type="submit" class="btn-aut">Logar</button>

            <!-- LINKS DE REDIRECIONAMENTO  -->
            <div class="aut-links">
                <p><a href="cadastrar.php">Ainda não tenho cadastro</a></p>
                <p><a href="esqueci.php">Esqueci a senha</a></p>
            </div>
        </form>
    </div>

    <!-- REFERÊNCIA DO BOOTSTRAP -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- REFERÊNCIA DO JQUERY -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
    // TEMPORIZADOR PARA REMOVER A MENSAGEM DE SUCESSO E ERRO-GERAL
    setTimeout(() => {
        $('.erro-geral').fadeOut(600, function() {
            $(this).remove();
        });
        $('.sucesso').fadeOut(600, function() {
            $(this).remove();
        });
    }, 3200);

    // TEMPORIZADOR PARA SUBSTITUIR PARAMETRO DA URL RESULT=?OK, EVITANDO CONFLITO DE MENSAGENS
    setTimeout(function() {
        if (window.location.search.includes('result=')) {
            const novaURL = window.location.origin + window.location.pathname;
            history.replaceState({}, '', novaURL);
        }
    }, 2000);
    
    // FOCO NO PRIMEIRO CAMPO DE ENTRADA
    $(document).ready(function() {
        $('input[name="email"]').focus();
    });
    </script>
</body>

</html>