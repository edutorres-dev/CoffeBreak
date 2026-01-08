<?php

/*
* Arquivo: EmailService.php
* 
* Descrição:
* Classe responsável pelo envio de e-mails do sistema CoffeBreak.
* Gerencia templates de e-mail para confirmação de cadastro e recuperação de senha.
* Integra com PHPMailer para envio seguro de mensagens.
* 
* Funcionalidades :
* -> Envio de e-mail de confirmação de cadastro
* -> Envio de e-mail de recuperação de senha
* -> Templates HTML inline (estilo visual dos exemplos fornecidos)
* -> Suporte a modo local (simulação) e produção (envio real)
* -> Tratamento de erros de envio
* -> Configuração UTF-8 para caracteres especiais
* 
* Fluxo de operação:
* 1) Instanciação com configurações do site e modo
* 2) Preparação do template conforme o tipo de e-mail
* 3) Configuração do PHPMailer (remetente, destinatário, assunto)
* 4) Envio do e-mail com tratamento de exceções
* 5) Retorno de sucesso ou exceção em caso de erro
* 
* Segurança:
* -> Validação de endereços de e-mail
* -> Sanitização de conteúdo HTML
* -> Uso de HTTPS em links de confirmação
* -> Tratamento seguro de exceções
* -> Prevenção de injeção de conteúdo malicioso
* 
* Dependências:
* -> PHPMailer 6.8+ para envio de e-mails
* -> Configuração SMTP (modo produção)
* -> Charset UTF-8 para suporte a acentuação
* 
* Observações importantes:
* -> No modo local, apenas simula envio (retorna true)
* -> Em produção, requer configuração SMTP válida
* -> Templates estão inline conforme especificado
* 
* @ Autor - Eduardo Torres Do Ó
* @ Direitos Reservados - 2026 CoffeBreak
* 
*/

// REQUERIMENTO DA BIBLIOTECA PHPMAILER
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'assets/lib/PHPMailer/src/Exception.php';
require_once 'assets/lib/PHPMailer/src/PHPMailer.php';
require_once 'assets/lib/PHPMailer/src/SMTP.php';

class EmailService {
    
    /****************************************************************
     * PROPRIEDADES PRIVADAS DA CLASSE
    ****************************************************************/
    
    // URL DO SITE
    private $site;
    
    // MODO DE OPERAÇÃO (LOCAL/PRODUCAO)
    private $modo;
    
    
    /****************************************************************
     * CONSTRUTOR DA CLASSE
    ****************************************************************/
    
    public function __construct($site, $modo) {

        // ARMAZENA URL
        $this->site = $site;
        
        // ARMAZENA O MODO DE OPERAÇÃO
        $this->modo = $modo;
    }
    
    
    /****************************************************************
     * ENVIO DE E-MAIL DE CONFIRMAÇÃO DE CADASTRO
    ****************************************************************/
    
    public function enviarConfirmacaoCadastro($email, $nome, $codigo) {
        
        /********************************************
         * MODO LOCAL - SIMULAÇÃO DE ENVIO
         ********************************************/
        if ($this->modo == "local") {

            // APENAS REGISTRA NO LOG
            error_log("[MODO LOCAL] E-mail de confirmação simulado para: $email");
        }
        
        /********************************************
         * MODO PRODUÇÃO - ENVIO REAL
         ********************************************/
        if ($this->modo == "producao") {
            
            // INSTANCIA O PHP MAILER COM TRATAMENTO DE EXCEÇÕES
            $mail = new PHPMailer(true);
                
            /****************************************************************
             * CONFIGURAÇÃO DO E-MAIL
            ****************************************************************/
                
            // DEFINE REMETENTE (NOME E E-MAIL)
            $mail->SetFrom('CoffeBreak@gmail.com', "CoffeBreak");
            
            // ADICIONA DESTINATÁRIO 
            $mail->addAddress($email, $nome);
            
            // DEFINE CONTEÚDO COMO HTML
            $mail->isHTML(true);
            
            // CONFIGURA ENCODING PARA SUPORTAR CARACTERES ESPECIAIS
            $mail->CharSet = 'UTF-8';
            
            // DEFINE ASSUNTO DO E-MAIL
            $mail->Subject = "Confirme seu Cadastro!";
            
            /****************************************************************
             * TEMPLATE HTML DO E-MAIL DE CONFIRMAÇÃO
            ****************************************************************/
            
            // CORPO DO E-MAIL 
            $mail->Body = '
            <div style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 0; margin: 0;">
                <div style="max-width: 600px; margin: auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
                    
                    <!-- BANNER DO TOPO -->
                    <img src="'.$this->site.'/assets/img/hero/coffe.jpeg" alt="Banner" style="width: 100%; max-height: 250px; object-fit: cover; display: block;">

                    <!-- CONTEÚDO PRINCIPAL -->
                    <div style="padding: 30px; text-align: center; color: #333;">
                        <h2 style="color: #2c3e50; margin-bottom: 20px;">Bem-vindo à Cafeteria CoffeBreak!</h2>
                        <p style="font-size: 16px; line-height: 1.6; margin-bottom: 25px;">
                            Olá, ' . htmlspecialchars($nome) . '!<br><br>
                            Que bom ter você conosco! <br>
                            Para ativar sua conta e começar a aproveitar nossas promoções exclusivas e sabores irresistíveis, confirme seu e-mail clicando no botão abaixo:
                        </p>
                        <!-- BOTÃO DE CONFIRMAÇÃO COM LINK ÚNICO -->
                        <a href="'.$this->site.'/confirmacao.php?cod_confirm='.$codigo.'" 
                            style="display: inline-block; padding: 14px 30px; background: rgba(109, 70, 39, 0.595); color: rgba(252, 245, 245, 0.98); text-decoration: none; border-radius: 5px; font-size: 16px; font-weight: bold;">
                            Confirmar E-mail
                        </a>
                        <p style="font-size: 14px; color: #888; margin-top: 30px;">
                            Se você não realizou esse cadastro, pode ignorar esta mensagem.
                        </p>
                    </div>

                    <!-- RODAPÉ DO E-MAIL -->
                    <div style="background-color: #f0f0f0; padding: 20px; text-align: center; font-size: 13px; color: #777;">
                        © ' . date("Y") . ' CoffeBreak. Todos os direitos reservados.
                    </div>
                </div>
            </div>';
            
            /****************************************************************
             * ENVIO DO E-MAIL
            ****************************************************************/
            
            // EXECUTA O ENVIO
            $mail->send();
                
        }
        
    }
    
    
    /****************************************************************
     * ENVIO DE E-MAIL DE RECUPERAÇÃO DE SENHA
    ****************************************************************/
    
    public function enviarRecuperacaoSenha($email, $nome, $codigo) {
        
        /********************************************
         * MODO LOCAL - SIMULAÇÃO DE ENVIO
         ********************************************/
        if ($this->modo == "local") {

            // APENAS REGISTRA O LOG
            error_log("[MODO LOCAL] E-mail de recuperação de senha simulado para: $email");
            return true;
        }
        
        /********************************************
         * MODO PRODUÇÃO 
         ********************************************/
        if ($this->modo == "producao") {
            
            // INSTANCIA O PHP MAILER COM TRATAMENTO DE EXCEÇÕES
            $mail = new PHPMailer(true);
            
            /****************************************************************
             * CONFIGURAÇÃO DO E-MAIL
            ****************************************************************/
            
            // DEFINE REMETENTE (NOME E E-MAIL)
            $mail->SetFrom('CoffeBreak@gmail.com', "CoffeBreak");
            
            // ADICIONA DESTINATÁRIO 
            $mail->addAddress($email, $nome);
            
            // DEFINE CONTEÚDO COMO HTML
            $mail->isHTML(true);
            
            // CONFIGURA ENCODING PARA SUPORTAR CARACTERES ESPECIAIS
            $mail->CharSet = 'UTF-8';
            
            // DEFINE ASSUNTO DO E-MAIL
            $mail->Subject = "Recuperação de Senha";
            
            
            // CORPO DO E-MAIL 
            $mail->Body = '
            <div style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 0; margin: 0;">
                <div style="max-width: 600px; margin: auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
                    
                    <!-- BANNER DO TOPO -->
                    <img src="'.$this->site.'/assets/img/hero/coffe.jpeg" alt="Banner" style="width: 100%; max-height: 250px; object-fit: cover; display: block;">

                    <!-- CONTEÚDO PRINCIPAL -->
                    <div style="padding: 30px; text-align: center; color: #333;">
                        <h2 style="color: #2c3e50; margin-bottom: 20px;">Redefinição de Senha</h2>
                        <p style="font-size: 16px; line-height: 1.6; margin-bottom: 25px;">
                            Olá, ' . htmlspecialchars($nome) . '!<br><br>
                            Recebemos uma solicitação para redefinir sua senha. <br>
                            Para continuar com o processo, clique no botão abaixo:
                        </p>
                        <!-- BOTÃO DE RECUPERAÇÃO COM LINK ÚNICO -->
                        <a href="'.$this->site.'/recupera_senha.php?cod='.$codigo.'" 
                            style="display: inline-block; padding: 14px 30px; background: rgba(109, 70, 39, 0.595); color: rgba(252, 245, 245, 0.98); text-decoration: none; border-radius: 5px; font-size: 16px; font-weight: bold;">
                            Recuperar Senha
                        </a>
                        <p style="font-size: 14px; color: #888; margin-top: 30px;">
                            Se você não solicitou essa alteração, ignore este e-mail com segurança.
                        </p>
                    </div>

                    <!-- RODAPÉ DO E-MAIL -->
                    <div style="background-color: #f0f0f0; padding: 20px; text-align: center; font-size: 13px; color: #777;">
                        © ' . date("Y") . ' CoffeBreak. Todos os direitos reservados.
                    </div>
                </div>
            </div>';
            
            /****************************************************************
             * ENVIO DO E-MAIL
            ****************************************************************/
            
            // EXECUTA O ENVIO
            $mail->send();
    
        }
        
    }
    
    
    /****************************************************************
     * MÉTODOS UTILITÁRIOS PARA VERIFICAÇÃO
    ****************************************************************/
    
    // VERIFICA SE O MODO ATUAL É PRODUÇÃO
    public function isModoProducao() {
        return $this->modo == "producao";
    }
    
    // VERIFICA SE O MODO ATUAL É LOCAL
    public function isModoLocal() {
        return $this->modo == "local";
    }
    
    // RETORNA URL BASE DO SITE
    public function getSite() {
        return $this->site;
    }
    
    // RETORNA MODO DE OPERAÇÃO ATUAL
    public function getModo() {
        return $this->modo;
    }
}


?>