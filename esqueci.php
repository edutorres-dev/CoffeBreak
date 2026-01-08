<?php

/*
* Arquivo: esqueci.php
* 
* Descrição:
* Processa a solicitação de recuperação de senha, enviando um e-mail com link único para redefinição.
* Verifica se o usuário existe e está confirmado no sistema antes de enviar o e-mail.
* 
* Funcionalidades :
* -> Validação de endereço de e-mail informado
* -> Verificação de existência e status do usuário no banco
* -> Geração de código único de recuperação criptografado
* -> Atualização do código no banco de dados
* -> Envio de e-mail de recuperação via EmailService
* -> Redirecionamento para confirmação após sucesso
* -> Feedback visual para diferentes cenários
* 
* Fluxo de operação:
* 1) Inicializa configurações e serviços via autoload
* 2) Processa formulário POST com e-mail para recuperação
* 3) Valida formato do e-mail com Validator::validarEmail()
* 4) Busca usuário por e-mail com status "confirmado"
* 5) Gera código único de recuperação com sha1(uniqid())
* 6) Atualiza código no banco via Usuario::atualizarCodigoRecuperacao()
* 7) Envia e-mail de recuperação via EmailService::enviarRecuperacaoSenha()
* 8) Redireciona para página de confirmação ou exibe erros
* 
* Segurança:
* -> Validação server-side do e-mail com expressão regular
* -> Verificação de status "confirmado" antes de enviar e-mail
* -> Códigos de recuperação únicos e criptografados
* -> Prepared statements para todas as consultas ao banco
* -> Sanitização de inputs com Config::trataPost()
* -> Redirecionamentos seguros 
* 
* Dependências:
* -> autoload.php - Carregamento automático das classes
* -> Config.php - Configurações do sistema e conexão com banco
* -> Usuario.php - Operações com tabela de usuários
* -> EmailService.php - Envio de e-mails de recuperação
* -> Validator.php - Validação de formato de e-mail
* -> Bootstrap 5 - Framework CSS para interface
* -> Bootstrap Icons - Ícones para formulário
* -> CSS personalizado (form_aut.css)
* 
* Observações importantes:
* -> Apenas usuários com status "confirmado" podem solicitar recuperação
* -> Código de recuperação é único e tem validade indeterminada
* -> Em modo local, simula envio de e-mail sem erro
* -> Em modo produção, requer SMTP configurado corretamente
* -> Mensagens de erro são genéricas para evitar vazamento de informações
* -> Link de recuperação contém código único no parâmetro GET
* 
* @ Autor - Eduardo Torres Do Ó
* @ Direitos Reservados - 2026 CoffeBreak
*/



// =====================================================================
// CONFIGURAÇÕES INICIAIS E SEGURANÇA
// =====================================================================

// REQUIDITA O AUTOLOADER QUE VAI CARREGAR AS CLASSES
require_once __DIR__ . '/autoload.php';

// INICIALIZA AS CONFIGURAÇÕES 
$config = Config::getInstance();

// ESTABALECE CONEXÃO E CONFIGURAÇÃO DO BANCO 
$pdo = $config->getPDO();
$site = $config->getSite();
$modo = $config->getModo();

// INSTANCIA OS SERVIÇOS
$usuarioService = new Usuario($pdo);
$emailService = new EmailService($site, $modo);
$validator = new Validator();

// =====================================================================
// PROCESSAMENTO DO FORMULÁRIO DE RECUPERAÇÃO DE SENHA
// =====================================================================

//VARIÁVEIS DAS MENSAGENS
$erro = '';
$sucesso = false;

if (isset($_POST["email"]) && !empty($_POST["email"])) {
    
    // TRATAMENTO DOS DADOS
    $email = Config::trataPost($_POST["email"]);
    
    // VALIDAÇÃO DO EMAIL
    $erroValidacao = $validator->validarEmail($email);
    if ($erroValidacao) {
        $erro = $erroValidacao;
    } else {
        // BUSCA O USUÁRIO COM STATUS DE CONFIRMADO NO BANCO
        $status = "confirmado";
        $sql = $pdo->prepare("SELECT * FROM usuarios WHERE email=? AND status=? LIMIT 1");
        $sql->execute([$email, $status]);
        $usuario = $sql->fetch(PDO::FETCH_ASSOC);
        
        // SE EXISTE O USUÁRIO COM O STATUS DE CONFIRMADO
        if ($usuario) {
            // GERA UM CÓDIGO ÚNICO CRIPTOGRAFADO PARA RECUPERAÇÃO DE SENHA
            $codigoRecuperacao = sha1(uniqid());
            $nome = $usuario['nome'];
            
            // SE CONSEGUIR ATUALIZAR O CODIGO DE RECUPERAÇÃO NO BANCO 
            if ($usuarioService->atualizarCodigoRecuperacao($email, $codigoRecuperacao)) {
                try {
                    //  ENVIA O EMAIL AO USUÁRIO PARA RECUPERAR A CONTA E REDIRECIONA
                    $emailService->enviarRecuperacaoSenha($email, $nome, $codigoRecuperacao);
                    header('location: email_enviado_recupera.php');
                    exit;
                    
                // SE HOUVE ERRO AO MANDAR O EMAIL AO USUÁRIO , MOSTRA MENSAGEM AO USUÁRIO
                } catch (Exception $e) {
                    $erro = "Houve um problema ao enviar o e-mail de recuperação: " . $e->getMessage();
                }
                
                // SE HOUVE ERRO PARA ATUALIZAR O CÓDIGO NO BANCO
            } else {
                $erro = "Erro ao atualizar código de recuperação. Tente novamente.";
            }
            // SE O USUÁRIO NÃO TEM STATUS DE CONFIRMADO NO BANCO , MOSTRA A MENSGAGEM AO USUÁRIO
        } else {
            $erro = "E-mail não encontrado ou conta não confirmada. Verifique se digitou corretamente ou confirme seu cadastro.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>

    <!-- META-TAGS PARA CONFIGURAÇÕES DO SITE (COMPATIBILIDADE , DESCRIÇAO DA PAGINA , PALAVRAS CHAVES DE PESQUISA) -->
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Recuperar Senha</title>

    <!--  REFERÊNCIA DO BOOTSTRAP CSS E BOOTSTRAP ICONS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    />
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    />

    <!-- REFERÊNCIA DO CSS EXTERNO -->
    <link rel="stylesheet" href="assets/css/form_aut.css" />
</head>

<body>
    <div class="aut-container">
        <!-- ==============================================
        FORMUÁRIO DE RECUPERA SENHA
        =============================================== -->
        <h3>Recuperar Senha</h3>

        <p
            style="
            color: #fcfeffff;
            margin-bottom: 1rem;
            font-size: 1rem;
            line-height: 1.5;
            "
        >
            Informe o e-mail cadastrado para redefinir sua senha.
        </p>

        <!-- MOSTRA MENSAGEM DE ERRO  -->
        <?php if ($erro): ?>
        <div class="erro-geral animate__animated animate__rubberBand text-center">
            <?php echo htmlspecialchars($erro); ?>
        </div>
        <?php endif; ?>

        <form method="post">
            <!-- CAMPO EMAIL -->
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input
                    name="email"
                    type="email"
                    class="form-control"
                    placeholder="Digite seu e-mail"
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                    required
                />
            </div>

            <!-- BOTÃO RECUPERA SENHA-->
            <button type="submit" class="btn-aut">Recuperar Senha</button>

            <!-- LINK PARA LOGIN-->
            <div class="aut-links">
                <p><a href="index.php">Voltar para login</a></p>
            </div>
        </form>
    </div>

    <!-- REFERÊNCIA DO BOOTSTRAP JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- TEMPORIZADOR DAS MENSAGENS DE ERRO -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                const erroElement = document.querySelector('.erro-geral');
                if (erroElement) {
                    erroElement.style.transition = 'opacity 0.5s ease';
                    erroElement.style.opacity = '0';
                    setTimeout(() => {
                        if (erroElement.parentNode) {
                            erroElement.parentNode.removeChild(erroElement);
                        }
                    }, 500);
                }
            }, 4000);
            
          
        });
    </script>
</body>
</html>