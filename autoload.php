<?php

/*
* Arquivo: autoload.php
* 
* Descrição:
* Sistema de carregamento automático de classes (autoloader) 
*
* 
* Funcionalidades Principais:
* -> Carregamento automático de classes sob demanda
* -> Mapeamento de nomes de classes para arquivos físicos
* -> Prevenção de múltiplos requires do mesmo arquivo
* -> Suporte a hierarquia de diretórios baseada em namespaces
* 
* Fluxo de Operação:
* 1) Quando uma classe não definida é instanciada ou referenciada
* 2) O PHP chama a função registrada via spl_autoload_register()
* 3) O autoloader converte o nome da classe em um caminho de arquivo
* 4) Verifica se o arquivo existe no diretório /classes/
* 5) Carrega o arquivo se encontrado, usando require_once
* 6) Se não encontrado, permite que outros autoloaders tentem
* 
* Convenções de Estrutura:
* -> Classes devem estar no diretório /classes/ relativo a este arquivo
* -> Nome do arquivo deve corresponder exatamente ao nome da classe
* -> Formato: NomeDaClasse.php (case-sensitive em sistemas Unix/Linux)
* -> Namespaces devem refletir a estrutura de diretórios
* 
* 
* @ Autor - Eduardo Torres Do Ó
* @ Direitos Reservados - 2026 CoffeBreak
* 
*
*/



// AUTOLOAD DAS CLASSES
spl_autoload_register(function ($className) {

    // CRIA O DIRETÓRIO QUE CONTEM TODAS AS CLASSES DENTRO
    $file = __DIR__ . '/classes/' . $className . '.php';
    
    // SE ESSE DIRETÓRIO EXISTIR , CHAMA TODOS OS ARQUIVOS (CLASSES) DENTRO
    if (file_exists($file)) {
        require_once $file;
    }
});

?>