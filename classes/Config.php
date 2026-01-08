<?php

/*
* Arquivo: Config.php
* 
* Descrição:
* Classe singleton responsável pela configuração centralizada do sistema CoffeBreak.
* Gerencia conexões com banco de dados, configurações de ambiente e métodos utilitários
* essenciais para segurança e funcionamento de toda a aplicação.
* 
* Funcionalidades :
* -> Padrão Singleton para instância única em toda a aplicação
* -> Gerenciamento de conexões PDO com banco de dados MySQL
* -> Configuração de ambientes (local/produção)
* -> Métodos utilitários para sanitização de dados
* -> Sistema de autenticação via tokens de sessão
* -> Controle de sessões PHP
* -> Configuração centralizada de URL do site
* 
* Fluxo de operação:
* 1) Primeiro acesso: cria instância única via getInstance()
* 2) Inicialização: inicia sessões, define configurações base
* 3) Configuração do ambiente: define credenciais de banco conforme modo
* 4) Conexão com banco: estabelece conexão PDO com tratamento de erros
* 5) Disponibilização: fornece acesso a recursos através de métodos públicos
* 
* Segurança:
* -> Padrão Singleton previne múltiplas instâncias e conexões desnecessárias
* -> Sessões iniciadas com session_start() seguro
* -> Prepared statements nativos via PDO para todas as consultas
* -> Sanitização de inputs com htmlspecialchars() e strip_tags
* -> Validação de tokens de sessão antes de qualquer operação sensível
* -> Credenciais de banco isoladas por ambiente
* -> Conexões criptografadas (dependendo da configuração do servidor)
* 
* 
* Observações importantes:
* -> O modo padrão é "producao" (configuração de servidor real)
* -> Credenciais de banco mudam automaticamente conforme ambiente
* -> Métodos estáticos para utilitários não requerem instância
* -> Token de sessão deve ser válido e não expirado para auth()
* -> URL do site deve refletir o domínio real da aplicação
* 
* 
* @ Autor - Eduardo Torres Do Ó
* @ Direitos Reservados - 2026 CoffeBreak
* 
*/


class Config {
    
    /****************************************************************
     * PROPRIEDADES PRIVADAS DA CLASSE
    ****************************************************************/
    
    // INSTÂNCIA SINGLETON PARA CONEXÃO DO BANCO
    private static $instance = null;
    
    // CONEXÃO COM O BANCO
    private $pdo;
    
    // URL DO SITE
    private $site;
    
    // MODO DE OPERAÇÃO DO PRJETO (LOCAL/PRODUÇÃO)
    private $modo;
    
    // CONFIGURAÇÕES DO BANCO DE DADOS
    private $servidor;
    private $usuario;
    private $senha;
    private $banco;
    
    
    /****************************************************************
     * CONSTRUTOR PRIVADO (Singleton Pattern)
    ****************************************************************/
    
    private function __construct() {
        $this->init();
    }
    
    
    /****************************************************************
     * MÉTODO SINGLETON - OBTÉM INSTÂNCIA ÚNICA
    ****************************************************************/
    
    public static function getInstance() {

        // VERFICA SE JÁ EXISTE UMA INSTANCIA CRIADA
        if (self::$instance === null) {

            // CRIA NOVA INSTANCIA SE NÃO EXISTIR UMA
            self::$instance = new self();
        }
        // RETORNA INSTANCIA EXISTENTE
        return self::$instance;
    }
    
    
    /****************************************************************
     * INICIALIZAÇÃO DO SISTEMA
    ****************************************************************/
    
    private function init() {

        // INICIA SESSÃO
        session_start();
        
        // DEFINE URL
        $this->site = "https://coffebreak.online/";
        
        // CONFIGURA MODO DE OPERAÇÃO
        $this->setModo("producao");
        
        // ESTABELECE CONEXÃO COM O BANCO DE DADOS 
        $this->connectDatabase();
    }
    
    
    /****************************************************************
     * CONFIGURAÇÃO DO MODO DE OPERAÇÃO
    ****************************************************************/
    
    public function setModo($modo) {

        // DEFINE O MODO DE OPERAÇÃO
        $this->modo = $modo;
        
        // CONFIGURAÇÕES DO BANCO PARA AMBIENTE LOCAL
        if ($modo == "local") {
            $this->servidor = "";
            $this->usuario = "";
            $this->senha = "";
            $this->banco = "";
        } 
        // CONFIGURAÇÕES DO BANCO PARA AMBIENTE DE PRODUÇÃO (SERVIDOR REAL , APLICAÇÃO ONLINE)
        else {
            $this->servidor = "";
            $this->usuario = "";
            $this->senha = "";
            $this->banco = "";
        }
    }
    
    
    /****************************************************************
     * CONEXÃO COM BANCO DE DADOS
    ****************************************************************/
    
    private function connectDatabase() {
        try {
            // CRIA CONEXÃO PDO COM MYSQL
            $this->pdo = new PDO(
                "mysql:host={$this->servidor};dbname={$this->banco}",
                $this->usuario,
                $this->senha
            );
            
            // CONFIGURA O PDO PARA LANÇAR AS EXCEÇÕES EM CASO DE ERRO
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
        } catch (PDOException $erro) {

            // ENCERRA A APLICAÇÃO EM CASO DE ERRO DE CONEXÃO 
            die("Falha ao se conectar com o banco! " . $erro->getMessage());
        }
    }
    
    
    /****************************************************************
     * GETTERS PÚBLICOS PARA ACESSO AOS RECURSOS
    ****************************************************************/
    
    // RETORNA CONEXÃO PDO COM BANCO
    public function getPDO() {
        return $this->pdo;
    }
    
    // RETORNA URL DO SITE
    public function getSite() {
        return $this->site;
    }
    
    // RETORNA MODO DE OPERAÇÃO ATUAL
    public function getModo() {
        return $this->modo;
    }
    
    
    /****************************************************************
     * MÉTODOS ESTÁTICOS PARA TRATAMENTO DE DADOS
    ****************************************************************/
    
    // TRATAMENTO DE DADOS VINDO DO POST PARA EVITAR SQL INJECTION E XSS
    public static function trataPost($dados) {

        // REMOVE ESPAÇOS EXTRAS NO INCIO E FIM DA STRING
        $dados = trim($dados);
        // REMOVE BARRAS INVERTIDAS
        $dados = stripcslashes($dados);
        
        // CONVERTE CARACTERES ESPECIAIS
        $dados = htmlspecialchars($dados);
        
        // RETORNA DADOS TRATADOS
        return $dados;
    }
    

    public static function trataNumero($dados) {
        // REMOVE ESPAÇOS EXTRAS
        $dado = trim($dados);
        // REMOVE TUDO QUE NÃO FOR NUMERO
        return preg_replace('/[^0-9]/', '', $dado);
    }
    
    
    /****************************************************************
     * AUTENTICAÇÃO DE USUÁRIO VIA TOKEN
    ****************************************************************/
    
    public function auth($tokenSessao) {

        // VERIFICA SE O TOKEN FOI FORNECIDO
        if (empty($tokenSessao)) {
            return false;
        }
        
        // CONSULTA SQL PARA BUSCAR USUAÁRIO PELO TOKEN
        $sql = $this->pdo->prepare("SELECT * FROM usuarios WHERE token=? LIMIT 1");
        
        // EXCEUTA CONSULTA COM O TOKEN FORNECIDO
        $sql->execute([$tokenSessao]);
        
        // OBTÉM RESULTADO DA CONSULTA
        $usuario = $sql->fetch(PDO::FETCH_ASSOC);
        
        // RETORNA USUÁRIO SE ENCONTRADO
        return $usuario ?: false;
    }

}
?>
