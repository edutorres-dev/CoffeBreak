<?php
/*
* Arquivo: cadastrar.php
* 
* Descrição:
* Este arquivo é responsável pelo processamento do formulário de cadastro de usuários no sistema.
* Ele realiza validação dos dados , registro no banco de dados e integra com sistema de confirmação por e-mail
*
*
* Funcionalidades :
* -> Validação completa de campos obrigatórios (nome, e-mail, telefone, senha)
* -> Verificação de formato e unicidade de e-mail
* -> Critérios avançados de segurança para senhas
* -> Prevenção contra cadastros duplicados
* -> Criptografia SHA1 para armazenamento seguro de senhas
* -> Sistema de confirmação por e-mail (modo produção)
* -> Redirecionamento inteligente baseado no ambiente (local ou produção)
* 
* Fluxo de Operação:
* 1) Recebe dados do formulário via método POST
* 2) Valida campos obrigatórios e formato básico
* 3) Aplica validações específicas usando classe Validator
* 4) Verifica se o usuário por acaso já foi cadastrado no sistema
* 5) Insere no banco de dados se tudo estiver válido
* 6) Envia e-mail de confirmação e redireciona conforme ambiente (local ou produção)
* 
* 
* Dependências do Sistema:
* -> autoload.php - Carregamento automático de classes ( Config.php , Usuario.php , Validator.php  , EmailService.php)
* -> Config.php - Configurações do sistema e conexão com banco
* -> Usuario.php - Modelo para operações com usuários
* -> Validator.php - Validações de formulários
* -> EmailService.php - Envio de e-mails (apenas modo produção)
* -> Bibliotecas externas (Bootstrap, Font Awesome, jQuery)
* -> Biblioca PHPMailer para envio de e-mails
* 
* 
* Interface do Usuário:
* -> Formulário responsivo com Bootstrap 5
* -> Ícones do Bootstrap Icons
* -> Animações com Animate.css
* -> Validação visual em tempo real
* -> Temporizador para mensagens de feedback
* 
* 
* @ Autor - Eduardo Torres Do Ó
* @ Direitos Reservados - 2026 CoffeBreak
* 
*/

/****************************************************************
 * CONFIGURAÇÕES INICIAIS E SEGURANÇA
****************************************************************/

// REQUISITA O AUTOLOADER QUE CARREGA AS CLASSES
require_once "autoload.php";


// INSTACIA CONFIGURAÇÕES
$config = Config::getInstance();

// INICIALIZA OS SERVIÇOS DAS CLASSES USUARIO , EMAILSERVICE E VALIDATOR
$usuarioModel = new Usuario($config->getPDO());
$emailService = new EmailService($config->getSite(), $config->getModo());
$validator = new Validator();

// VARIÁVEIS PARA AS MENSAGENS DE ERRO
$erro_geral = null;
$erro_nome = null;
$erro_email = null;
$erro_numero = null;
$erro_senha = null;
$erro_repete_senha = null;
$erro_checkbox = null;

/****************************************************************
 PROCESSAMENTO DO FORMULÁRIO DE CADASTRO
****************************************************************/

// SE A POSTAGEM EXISTIR
if(isset($_POST["nome_completo"]) && isset ($_POST["email"]) && isset ($_POST["numero"]) && isset ($_POST["senha"]) && isset ($_POST["repete_senha"])){

  // SE TEM CAMPO VAZIO
  if(empty($_POST["nome_completo"]) || empty($_POST["email"]) || empty($_POST["numero"]) || empty($_POST["senha"]) || empty($_POST["repete_senha"]) || !isset($_POST["termos"])){
      
    // MOSTRA AO USUÁRIO MENSAGEM DE ERRO
    $erro_geral = "Todos os campos são obrigatórios";

  } else {
    // TRATA OS DADOS
    $nome = Config::trataPost($_POST["nome_completo"]);
    $email = Config::trataPost($_POST["email"]);
    $contato = Config::trataNumero($_POST["numero"]);
    $senha = Config::trataPost($_POST["senha"]);
    $senha_cript = sha1($senha); //criptografa a senha
    $repete_senha = Config::trataPost($_POST["repete_senha"]);

    // VALIDA OS CAMPOS :
    
    // CAMPO NOME
    $erro_nome = $validator->validarNome($nome);
    
    // CAMPO EMAIL
    $erro_email = $validator->validarEmail($email);
    
    // CAMPO NÚMERO
    $erro_numero = $validator->validarTelefone($contato);
    
    // CAMPO SENHA
    $erro_senha = $validator->validarSenha($senha);
    
    // VERIFICA SE REPETE SENHA = SENHA
    if($senha !== $repete_senha){
        $erro_repete_senha = "Senha e repetição de senha diferentes!";
    }
    
    // VERIFICA SE CHECKBOX FOI MARCADO
    if (!isset($_POST["termos"]) || $_POST["termos"] !== "on") {
        $erro_checkbox = "Você deve aceitar os termos.";
    }

    // SE NÃO HOUVE ERROS
    if(!$erro_geral && !$erro_nome && !$erro_email && !$erro_numero && !$erro_senha && !$erro_repete_senha && !$erro_checkbox){
        
      // VERIFICA SE O USUARIO JA FOI CADASTRADO
      $usuarioExistente = $usuarioModel->buscarPorEmail($email);
      
      // SE O USUARIO NÃO FOI CADASTRADO
      if(!$usuarioExistente){
          
          // PREPARA OS DADOS PARA CADASTRO
          $dadosUsuario = [
              'nome' => $nome,
              'email' => $email,
              'contato' => $contato,
              'senha' => $senha_cript,
              'recupera_senha' => "",
              'token' => "",
              'codigo_confirmacao' => sha1(uniqid()),
              'status' => "novo",
              'data_cadastro' => date("Y-m-d"),
              'nivel_acesso' => "cliente"
          ];
          
          // CADASTRA O USUÁRIO
          if($usuarioModel->criar($dadosUsuario)){
              
              /********************************************
              * MODO LOCAL - REDIRECIONA PARA LOGIN
              ********************************************/
              if($config->getModo() == "local"){
                  // REDIRECIONA O USUARIO PARA LOGIN
                  header('location: login.php?result=ok');
                  exit;
              }
              
              /********************************************
              * MODO PRODUÇÃO - ENVIA EMAIL DE CONFIRMAÇÃO
              ********************************************/
              if($config->getModo() == "producao"){
                try {
                  // ENVIA O EMAIL DE CONFIRMAÇÃO
                  $emailService->enviarConfirmacaoCadastro($email, $nome, $dadosUsuario['codigo_confirmacao']);
                  
                  // REDIRECIONA PARA PÁGINA DE OBRIGADO APÓS ENVIO BEM-SUCEDIDO
                  header('location: obrigado.php');
                  exit;
                                            
                } catch (Exception $e) {
                  // MOSTRA A MENSAGEM DE ERRO AO USUÁRIO
                  $erro_geral = "Houve um problema ao enviar o email de confirmação: " . $e->getMessage();
                }
              }
              
              // SE DER ERRO AO REALIZAR O CADASTRO DO USUÁRIO BANCO
          } else {
            $erro_geral = "Erro ao cadastrar usuário. Tente novamente.";
          }
          
      } else {
        // SE O USUÁRIO JÁ FOI CADASTRADO NO BANCO
        $erro_geral = "Usuário já Cadastrado";
      }
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
    <meta http-equiv="X-UA-Compatible" content="IE=7">
    <title>Cadastro</title>

    <!-- REFERÊNCIA BOOTSTRAP CSS E BOOTSTRAP ICONS -->
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
      crossorigin="anonymous"
    />
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    />
  
    <!-- REFERÊNCIA DA ANIMAÇÃO DAS VALIDACOES -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    
    <!-- REFERÊNCIA DO CSS EXTERNO -->
    <link rel="stylesheet" href="assets/css/form_aut.css" />

  </head>

  <body>

    <div class="aut-container">

      <!-- ==============================================
      FORMUÁRIO DE CADASTRO
      =============================================== -->

      <h3>Cadastrar</h3>

      <form method="post">
       
        <!-- MENSAGEM DE ERRO-GERAL(CAMPO VAZIO OU USUÁRIO JÁ CADASTRADO) -->
        <?php if($erro_geral){ ?>
          <div class="erro-geral animate__animated animate__rubberBand text-center">
            <?php echo $erro_geral ?>
          </div> 
        <?php } ?>
              
        <!-- MENSAGEM DE SUCESSO (USUARIO CADASTRADO COM SUCESSO) -->
        <?php if (isset($_GET['result']) && ($_GET['result']=="ok")){ ?>
          <div class="animate__animated animate__rubberBand sucesso">
            Usuário cadastrado com sucesso!
          </div>               
        <?php } ?>

        <!-- CAMPO NOME -->
        <div <?php if($erro_geral || $erro_nome){echo 'class="input-group erro-borda"';}else{echo 'class="input-group"';}?>>
          <span class="input-group-text">
            <i <?php if($erro_geral || $erro_nome){echo 'class="bi bi-person text-danger"';}else{echo 'class="bi bi-person"';}?>></i>
          </span>
          <input class="form-control" name="nome_completo" type="text" placeholder="Nome Completo" 
                 value="<?php echo isset($_POST['nome_completo']) ? htmlspecialchars($_POST['nome_completo']) : ''; ?>" required/>
        </div>

        <!-- SE OCORRER SOMENTE O ERRO_NOME -->
        <?php if($erro_nome){ ?>
          <div class="erro-validacao">
            <?php echo $erro_nome ?>
          </div>
        <?php } ?>

        <!-- CAMPO EMAIL -->
        <div <?php if($erro_geral || $erro_email){echo 'class="input-group erro-borda"';}else{echo 'class="input-group"';}?>>
          <span class="input-group-text">
            <i <?php if($erro_geral || $erro_email){echo 'class="bi bi-envelope text-danger"';}else{echo 'class="bi bi-envelope"';}?>></i>
          </span>
          <input class="form-control" name="email" type="email" placeholder="Email" 
                 value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required />
        </div>

        <!-- SE OCORRER SOMENTE O ERRO_EMAIL -->
        <?php if($erro_email){ ?>
          <div class="erro-validacao">
            <?php echo $erro_email ?>
          </div>
        <?php } ?>

        <!-- CAMPO NUMERO -->
        <div <?php if($erro_geral || $erro_numero){echo 'class="input-group erro-borda"';}else{echo 'class="input-group"';}?>>
          <span class="input-group-text">
            <i <?php if($erro_geral || $erro_numero){echo 'class="bi bi-telephone text-danger"';}else{echo 'class="bi bi-telephone"';}?>></i>
          </span>
          <input class="form-control" name="numero" type="tel" placeholder="Seu Número" 
                 value="<?php echo isset($_POST['numero']) ? htmlspecialchars($_POST['numero']) : ''; ?>" required/>
        </div>

        <!-- SE OCORRER SOMENTE O ERRO_NUMERO -->
        <?php if($erro_numero){ ?>
          <div class="erro-validacao">
            <?php echo $erro_numero ?>
          </div>
        <?php } ?>

        <!-- CAMPO SENHA -->
        <div <?php if($erro_geral || $erro_senha){echo 'class="input-group erro-borda"';}else{echo 'class="input-group"';}?>>
          <span class="input-group-text">
            <i <?php if($erro_geral || $erro_senha){echo 'class="bi bi-lock text-danger"';}else{echo 'class="bi bi-lock"';}?>></i>
          </span>
          <input class="form-control" name="senha" type="password" placeholder="Senha" required />
        </div>

        <!-- SE OCORRER SOMENTE O ERRO_SENHA -->
        <?php if($erro_senha){ ?>
          <div class="erro-validacao">
            <?php echo $erro_senha ?>
          </div>
        <?php } ?>

        <!-- CAMPO REPETE SENHA -->
        <div <?php if($erro_geral || $erro_repete_senha){echo 'class="input-group erro-borda"';}else{echo 'class="input-group"';}?>>
          <span class="input-group-text">
            <i <?php if($erro_geral || $erro_repete_senha){echo 'class="bi bi-lock-fill text-danger"';}else{echo 'class="bi bi-lock-fill"';}?>></i>
          </span>
          <input class="form-control" name="repete_senha" type="password" placeholder="Repita a senha" required />
        </div>

        <!-- SE OCORRER SOMENTE O ERRO_REPETE_SENHA -->
        <?php if($erro_repete_senha){ ?>
          <div class="erro-validacao">
            <?php echo $erro_repete_senha ?>
          </div>
        <?php } ?>

        <!-- CHECKBOX -->
        <div class="form-check">
          <input <?php if($erro_geral || $erro_checkbox){echo 'class="form-check-input erro-borda"';}else{echo 'class="form-check-input"';}?>
                 name="termos"
                 type="checkbox"
                 id="termos"
                 <?php echo (isset($_POST['termos']) && $_POST['termos'] === 'on') ? 'checked' : ''; ?>/>
          <label class="form-check-label" for="termos">
            Ao se cadastrar você concorda com a nossa
            <a href="#" class="privacidade-link">Política de Privacidade</a>
            e os <a href="#" class="privacidade-link">Termos de uso</a>
          </label>
        </div>

        <!-- SE OCORRER SOMENTE O ERRO_CHECKBOX -->
        <?php if($erro_checkbox){ ?>
          <div class="erro-validacao">
            <?php echo $erro_checkbox ?>
          </div>
        <?php } ?>

        <!-- BOTÃO CADASTRAR -->
        <button type="submit" class="btn-aut">Cadastrar</button>

        <!-- LINK PARA LOGIN-->
        <div class="aut-links">
          <p><a href="login.php">Já tenho uma conta</a></p>
        </div>

      </form>

    </div>
    
    <!-- REFERÊNCIA DO BOOTSTRAP JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- REFERÊNCIA DO JQUERY -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
      // TEMPORIZADOR DAS MENSAGENS DE ERRO
      setTimeout(() => {
        // REMOVE OS ERROS GERAL(CAMPO VAZIO) E ERRO-VALIDAÇÃO(VALIDAÇÃO ESPECIFICA DE CADA CAMPO COM MENSAGEM)
        $('.erro-geral').fadeOut(600, function() { $(this).remove(); });
        $('.erro-validacao').fadeOut(600, function() { $(this).remove(); });
        $('.sucesso').fadeOut(600, function() { $(this).remove(); });
        
        // REMOVE O ERRO-INPUT (BORDA AVERMELHADA) E TEXT-DANGER
        $('.erro-borda').removeClass('erro-borda');
        $('.text-danger').removeClass('text-danger');
      }, 4300);

      // TEMPORIZADOR PARA SUBSTITUIR PARAMETRO DA URL RESULT=?OK, EVITANDO CONFLITO DE MENSAGENS
      setTimeout(function() {
        if (window.location.search.includes('result=')) {
          const novaURL = window.location.origin + window.location.pathname;
          history.replaceState({}, '', novaURL);
        }
      }, 4000);
    </script>
  </body>
</html>