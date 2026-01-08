<?php

/*
* Arquivo: recupera_senha.php
* 
* Descrição:
* Processa a redefinição de senha de usuários através de um código de recuperação único.
* Verifica a validade do código e aplica as novas credenciais com todas as validações necessárias.
* 
* Funcionalidades :
* -> Validação de código de recuperação via URL
* -> Processamento seguro do formulário de nova senha
* -> Validação de critérios para nova senha
* -> Atualização segura da senha no banco de dados
* -> Feedback visual claro para o usuário
* -> Redirecionamento após sucesso
* 
* Fluxo de operação:
* 1) Verifica existência do código de recuperação via GET
* 2) Valida o código no banco de dados através da classe Usuario
* 3) Processa o formulário de nova senha quando submetido
* 4) Aplica validações de senha via classe Validator
* 5) Atualiza a senha no banco de dados com criptografia SHA1
* 6) Redireciona para login após sucesso
* 
* Segurança:
* -> Uso de prepared statements através da classe Usuario
* -> Criptografia SHA1 para senhas
* -> Sanitização de inputs via Config::trataPost()
* -> Validação server-side via classe Validator
* -> Tokens únicos para recuperação
* -> Verificação da existência do usuário antes da atualização
* 
* Dependências:
* -> autoload.php - Carregamento automático das classes
* -> Config.php - Configurações do sistema (Banco de dados) e métodos utilitários
* -> Usuario.php - Operações com tabela de usuários
* -> Validator.php - Validações de formulários
* -> Bootstrap 5 - Framework CSS para interface
* -> Bootstrap Icons - Biblioteca de ícones
* -> jQuery - Manipulação DOM para interações
* -> CSS externo (fomr_aut.css) 
* 
* Observações:
* -> O código de recuperação é válido por tempo indeterminado
* -> Não há limite de tentativas para redefinição
* -> A senha é atualizada imediatamente após confirmação
* -> Redireciona automaticamente após sucesso
* 
* @ Autor - Eduardo Torres Do Ó
* @ Direitos Reservados - 2026 CoffeBreak


/****************************************************************
 * CONFIGURAÇÕES INICIAIS E SEGURANÇA
****************************************************************/

// CARREGA O AUTOLOAD PARA AS CLASSES
require_once("autoload.php");

// OBTÊM INSTÂNCIA DO CONFIG
$config = Config::getInstance();
$pdo = $config->getPDO();

// INSTANCIA O USUÁRIO E VALIDATOR
$usuarioService = new Usuario($pdo);

/****************************************************************
 * PROCESSAMENTO DA RECUPERAÇÃO DE SENHA
****************************************************************/

// INICIALIZA VARIÁVEIS DE ERRO
$erro_geral = null;
$erro_senha = null;
$erro_repete_senha = null;

// SE O CÓDIGO DE RECUPERAÇÃO DE SENHA EXISTIR (VERIFICA VIA GET)
if (isset($_GET["cod"]) && !empty($_GET["cod"])) {
    
    // TRATA ESSE CÓDIGO
    $cod = Config::trataPost($_GET["cod"]);
    
    // BUSCA USUÁRIO PELO CÓDIGO DE RECUPERAÇÃO
    $usuario = $usuarioService->buscarPorCodigoRecuperacao($cod);
    
    // SE NÃO ENCONTRAR USUÁRIO COM ESSE CÓDIGO
    if (!$usuario) {
        echo "Recuperação de Senha Inválida!";
        exit();
    }
    
    // SE A POSTAGEM DE RECUPERAR SENHA EXISTIR
    if (isset($_POST["senha"]) && isset($_POST["repete_senha"])) {
        
        // SE OS CAMPOS FOREM VAZIOS
        if (empty($_POST['senha']) || empty($_POST['repete_senha'])) {
            $erro_geral = "Todos os campos são obrigatórios!";
            
        // SE FORAM PREENCHIDOS
        } else {
            // TRATA OS DADOS
            $senha = Config::trataPost($_POST["senha"]);
            $senha_cript = sha1($_POST["senha"]);
            $repete_senha = Config::trataPost($_POST["repete_senha"]);
            
            // VALIDAÇÃO DOS CAMPOS:
            
            // VALIDA A SENHA USANDO A CLASSE VALIDATOR
            $erro_senha = Validator::validarSenha($senha);
            
            // VERIFICA SE REPETE SENHA É IGUAL A SENHA
            if ($senha !== $repete_senha) {
                $erro_repete_senha = "Senha e repetição de senha diferentes!";
            }
            
            // SE NÃO TEVE ERRO
            if (!$erro_geral && !$erro_senha && !$erro_repete_senha) {
                
                // ATUALIZA A SENHA DO USUÁRIO
                $atualizado = $usuarioService->atualizarSenha($cod, $senha_cript);
                
                // SE CONSEGUIU ATUALIZAR
                if ($atualizado) {
                    
                    // REDIRECIONA O USUÁRIO PARA O LOGIN
                    header("Location: login.php");
                    exit();
                }
            }
        }
    }
    
    // SE O CODIGO DE RECUPERAÇÃO NÃO EXISTIR
} else {
    
    // MOSTRA A MENSAGEM PARA O USUÁRIO
    echo "Código de recuperação não fornecido!";
    exit();
}

?>









<!DOCTYPE html>
<html lang="pt-br">
<head>

  <!-- META-TAGS PARA CONFIGURAÇÕES DO SITE (COMPATIBILIDADE , DESCRIÇAO DA PAGINA , PALAVRAS CHAVES DE PESQUISA) -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Trocar Senha</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
  
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

  <!-- REFERÊNCIA DA ANIMAÇÃO PARA AS VALIDAÇÕES -->
  <link rel="stylesheet"href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

  <!-- REFERÊNCIA DO CSS EXTERNO  -->
  <link rel="stylesheet" href="assets/css/form_aut.css" />


</head>
<body>
    <div class="aut-container">
        <h3>Trocar a Senha</h3>

        <!-- ==============================================
        FORMUÁRIO DE TROCA DE SENHA
        =============================================== -->

        <form method="post">
            
            <!-- SE OS CAMPOS NAO FORAM PREENCHIDOS(ERRO_GERAL) -->
          <?php if(isset($erro_geral)){ ?>
        
            <div class="erro-geral animate__animated animate__rubberBand text-center">
              
              <?php echo $erro_geral ?>
            
            </div>
        
          <?php  } ?>

          <!-- CAMPO SENHA -->
            <div <?php if(isset($erro_geral) || isset($erro_senha)){echo 'class=" input-group erro-borda "';}else{ echo' class="input-group"';}?> >
                <span class="input-group-text "><i <?php if(isset($erro_geral) || isset($erro_senha)){echo 'class=" bi bi-lock text-danger "';}else{ echo' class="bi bi-lock-fill"';}?>></i></span>
                <input 
                    class="form-control"
                    name="senha"
                    type="password"
                    placeholder="Nova Senha "
                    required
                />
            </div>

            <!-- SE OCORRER SOMENTE O ERRO_SENHA -->
            <?php if(isset($erro_senha)){ ?>
              <div class="erro-validacao">
                <?php echo $erro_senha  ?>
                
              </div>
    
            <?php } ?>


            <!-- CAMPO REPETIR SENHA -->
            <div  <?php if(isset($erro_geral) || isset($erro_repete_senha)){echo 'class=" input-group erro-borda "';}else{ echo' class="input-group"';}?>> 
                <span class="input-group-text "><i <?php if(isset($erro_geral) || isset($erro_repete_senha)){echo 'class=" bi bi-lock-fill text-danger "';}else{ echo' class="bi bi-lock-fill"';}?>></i></span>
                <input 
                    class="form-control"
                    name="repete_senha"
                    type="password"
                    placeholder="Repita a Nova Senha"
                    required
                />
            </div>
            
            <!-- SE OCORRER SOMENTE O ERRO_REPETE_SENHA -->
            <?php if(isset($erro_repete_senha)){ ?>
              <div class="erro-validacao">
                <?php echo $erro_repete_senha  ?>
                
              </div>
    
            <?php } ?>

            

            <!-- BOTÃO ALTERAR SENHA -->
            <button type="submit" class="btn-aut">Alterar a Senha</button>

            <!-- LINK PARA VOLTAR -->
            <div class="aut-links">
                <p><a href="login.php">Voltar para o login</a></p>
            </div>
        </form>
    </div>
    
    
    <!-- REFERÊNCIA JQUERY -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
    
    // TEMPORIZADOR PARA AS MENSAGENS DE ERRO
      setTimeout(() => {
        //   REMOVE ERRO GERAL(CAMPO VAZIO) E ERRO_VALIDACAO(CAMPO ESPECEIFICO)
          $('.erro-geral').fadeOut(600, function() { $(this).remove(); });
          $('.erro-validacao').fadeOut(600, function() { $(this).remove(); });
          
            //REMOVE O ERRO-INPUT ( BORDA AVERMELHADA) E TEXT-DANGER
            $('.erro-borda').removeClass('erro-borda')
            $('.text-danger').removeClass('text-danger')      
          
      }, 3200);
    </script>


</body>
</html>