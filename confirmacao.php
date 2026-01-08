<?php

/*
* Arquivo: confirmacao.php
* 
* Descrição:
* Processa a confirmação de cadastro de usuários através de um código único enviado por e-mail.
* Atualiza o status do usuário no banco de dados para "confirmado" quando o código é válido.
* 
* Funcionalidades :
* -> Validação de códigos de confirmação via parâmetros GET
* -> Verificação de existência de usuário no banco de dados
* -> Atualização de status de "novo" para "confirmado"
* -> Redirecionamento inteligente com feedback visual
* -> Tratamento de erros de código inválido
* -> Integração com sistema de autenticação
* 
* Fluxo de Operação:
* 1) Recebe código de confirmação via parâmetro GET (cod_confirm)
* 2) trata o código  
* 3) Busca usuário correspondente ao código no banco de dados
* 4) Valida se o usuário existe e está no status correto
* 5) Atualiza status do usuário para "confirmado" se válido
* 6) Redireciona para login com mensagem de sucesso
* 7) Exibe mensagem de erro se código for inválido
* 
* Segurança :
* -> Sanitização de inputs com Config::trataPost()
* -> Uso de Prepared Statements via classe Usuario
* -> Verificação de existência do usuário antes de atualização
* -> Redirecionamento seguro após confirmação
* 
* Dependências :
* -> autoload.php - Carregamento automático de classes ( config.php e Usuario.php )
* -> Config.php - Configuração e conexão com banco
* -> Usuario.php - Busca por código e atualização de status
* 
* 
* Observações :
* -> Códigos não expiram (validade indeterminada)
* -> Cada código é único por usuário
* -> Este arquivo é um endpoint de ativação, não uma página completa
* -> Parte crítica do fluxo de cadastro de usuários
* 
*
* @ Autor - Eduardo Torres Do Ó
* @ Direitos Reservados - 2026 CoffeBreak
*/



/****************************************************************
 * CONFIGURAÇÕES INICIAIS
****************************************************************/

// CARREGA O AUTOLOAD PARA AS CLASSES
require_once("autoload.php");

// OBTÉM INSTÂNCIA DO CONFIG
$config = Config::getInstance();
$pdo = $config->getPDO();

// INSTANCIA O USUÁRIO
$usuarioService = new Usuario($pdo);

/****************************************************************
 * PROCESSAMENTO DE CONFIRMAÇÃO DE CADASTRO
****************************************************************/

// SE TEM O CÓDIGO DE CONFIRMAÇÃO, PEGA ELE VIA GET
if (isset($_GET["cod_confirm"]) && !empty($_GET["cod_confirm"])) {

    // TRATA ESSE CÓDIGO EVITANDO SQL INJECTION
    $cod = Config::trataPost($_GET["cod_confirm"]);

    // BUSCA USUÁRIO PELO CÓDIGO DE CONFIRMAÇÃO
    $usuario = $usuarioService->buscarPorCodigoConfirmacao($cod);

    // SE EXISTIR ALGUM USUÁRIO COM O CÓDIGO
    if ($usuario) {

        // ATUALIZA O STATUS DO USUÁRIO PARA CONFIRMADO NO SISTEMA
        $atualizado = $usuarioService->atualizarStatus($cod, "confirmado");

        // SE CONSEGUIU ATUALIZAR O STATUS DO USUÁRIO
        if ($atualizado) {

            // REDIRECIONA O USUÁRIO PARA O LOGIN
            header("location: login.php?result=ok");

        }

    // SE O USUÁRIO NÃO ESTIVER COM O CÓDIGO
    } else { 

        // MOSTRA A MENSAGEM DE ERRO AO USUÁRIO
        echo "<h1>Código de confirmação inválido!</h1>";

    }

}

?>