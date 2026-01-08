<?php


/*
* Arquivo: Usuario.php
* 
* Descrição:
* Classe modelo para operações CRUD com a tabela de usuários do sistema CoffeBreak.
* Responsável por todas as interações com dados de usuários no banco de dados.
* 
* Funcionalidades :
* -> Operações de busca por diferentes critérios (token, email, códigos)
* -> Criação de novos usuários com dados completos
* -> Atualização de informações de usuários existentes
* -> Exclusão segura de contas de usuários
* -> Gerenciamento de tokens e códigos de confirmação/recuperação
* -> Controle de status de conta e nível de acesso
* 
* Fluxo de operação:
* 1) Instanciação com conexão PDO fornecida
* 2) Execução de métodos específicos para operações no banco
* 3) Uso de prepared statements para todas as consultas
* 4) Retorno de resultados em formato associativo
* 5) Tratamento de parâmetros com valores padrão quando necessário
* 
* Segurança:
* -> Uso de prepared statements para prevenir SQL injection
* -> Validação de parâmetros antes da execução
* -> Limitação de resultados com LIMIT 1 para buscas únicas
* -> Controle de acesso via tokens e códigos únicos
* -> Sanitização implícita através do PDO

* 
* Observações importantes:
* -> Todos os métodos retornam false ou null em caso de falha
* -> Buscas por token/email usam LIMIT 1 para garantir unicidade
* -> Atualizações de email redefinem status para 'novo' (requer confirmação)
* -> Códigos de recuperação são únicos por solicitação
* -> Nível de acesso padrão é definido no método de criação
* 
* @ Autor - Eduardo Torres Do Ó
* @ Direitos Reservados - 2026 CoffeBreak
* 
*/



class Usuario {
    
    /****************************************************************
     * PROPRIEDADES DA CLASSE
    ****************************************************************/
    
    // Conexão PDO com o banco de dados
    private $pdo;
    
    
    /****************************************************************
     * CONSTRUTOR
    ****************************************************************/
    
    public function __construct($pdo) {
        // Armazena conexão PDO para uso em todos os métodos
        $this->pdo = $pdo;
    }
    
    
    /****************************************************************
     * MÉTODOS DE BUSCA (READ)
    ****************************************************************/
    
    // Busca usuário pelo token de sessão
    public function buscarPorToken($token) {
        $sql = $this->pdo->prepare("SELECT * FROM usuarios WHERE token=? LIMIT 1");
        $sql->execute([$token]);
        return $sql->fetch(PDO::FETCH_ASSOC);
    }
    
    // Busca usuário pelo endereço de email
    public function buscarPorEmail($email) {
        $sql = $this->pdo->prepare("SELECT * FROM usuarios WHERE email=? LIMIT 1");
        $sql->execute([$email]);
        return $sql->fetch(PDO::FETCH_ASSOC);
    }
    
    // Busca usuário por email e senha (para login)
    public function buscarPorEmailESenha($email, $senha) {
        $sql = $this->pdo->prepare("SELECT * FROM usuarios WHERE email=? AND senha=? LIMIT 1");
        $sql->execute([$email, $senha]);
        return $sql->fetch(PDO::FETCH_ASSOC);
    }
    
    // Busca usuário pelo código de confirmação de email
    public function buscarPorCodigoConfirmacao($codigo) {
        $sql = $this->pdo->prepare("SELECT * FROM usuarios WHERE codigo_confirmacao=? LIMIT 1");
        $sql->execute([$codigo]);
        return $sql->fetch(PDO::FETCH_ASSOC);
    }
    
    // Busca usuário pelo código de recuperação de senha
    public function buscarPorCodigoRecuperacao($codigo) {
        $sql = $this->pdo->prepare("SELECT * FROM usuarios WHERE recupera_senha=? LIMIT 1");
        $sql->execute([$codigo]);
        return $sql->fetch(PDO::FETCH_ASSOC);
    }
    
    
    /****************************************************************
     * MÉTODOS DE CRIAÇÃO (CREATE)
    ****************************************************************/
    
    // Cria um novo usuário no sistema
    public function criar($dados) {
        $sql = $this->pdo->prepare("
            INSERT INTO usuarios 
            (nome, email, contato, senha, recupera_senha, token, codigo_confirmacao, status, data_cadastro, nivel_acesso) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $sql->execute([
            $dados['nome'],
            $dados['email'],
            $dados['contato'],
            $dados['senha'],
            $dados['recupera_senha'],
            $dados['token'],
            $dados['codigo_confirmacao'],
            $dados['status'],
            $dados['data_cadastro'],
            $dados['nivel_acesso']
        ]);
    }
    
    
    /****************************************************************
     * MÉTODOS DE ATUALIZAÇÃO (UPDATE)
    ****************************************************************/
    
    // Atualiza token de sessão do usuário
    public function atualizarToken($email, $senha, $token) {
        $sql = $this->pdo->prepare("UPDATE usuarios SET token =? WHERE email=? AND senha=?");
        return $sql->execute([$token, $email, $senha]);
    }
    
    // Atualiza status da conta (ex: 'novo' para 'confirmado')
    public function atualizarStatus($codigo, $status) {
        $sql = $this->pdo->prepare("UPDATE usuarios SET status=? WHERE codigo_confirmacao=?");
        return $sql->execute([$status, $codigo]);
    }
    
    // Atualiza senha usando código de recuperação
    public function atualizarSenha($codigo, $senha) {
        $sql = $this->pdo->prepare("UPDATE usuarios SET senha=? WHERE recupera_senha=?");
        return $sql->execute([$senha, $codigo]);
    }
    
    // Atualiza email e redefine dados de confirmação
    public function atualizarEmail($id, $email, $dadosAdicionais = []) {
        $sql = $this->pdo->prepare("
            UPDATE usuarios SET 
            email = ?, token = ?, codigo_confirmacao = ?, status = ?, data_cadastro = ? 
            WHERE id = ?
        ");
        
        return $sql->execute([
            $email,
            $dadosAdicionais['token'] ?? '',
            $dadosAdicionais['codigo_confirmacao'] ?? '',
            $dadosAdicionais['status'] ?? 'novo',
            $dadosAdicionais['data_cadastro'] ?? date("Y-m-d"),
            $id
        ]);
    }
    
    // Atualiza número de contato (telefone)
    public function atualizarContato($id, $contato) {
        $sql = $this->pdo->prepare("UPDATE usuarios SET contato = ? WHERE id = ?");
        return $sql->execute([$contato, $id]);
    }
    
    // Atualiza senha usando ID do usuário
    public function atualizarSenhaPorId($id, $senha) {
        $sql = $this->pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
        return $sql->execute([$senha, $id]);
    }
    
    // Atualiza código de recuperação de senha
    public function atualizarCodigoRecuperacao($email, $codigo) {
        $sql = $this->pdo->prepare("UPDATE usuarios SET recupera_senha=? WHERE email=?");
        return $sql->execute([$codigo, $email]);
    }
    
    
    /****************************************************************
     * MÉTODOS DE EXCLUSÃO (DELETE)
    ****************************************************************/
    
    // Remove usuário do sistema pelo ID
    public function deletar($id) {
        $sql = $this->pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        return $sql->execute([$id]);
    }
}
?>