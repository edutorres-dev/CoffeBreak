<?php


/*
* Arquivo: Validator.php
* 
* Descrição:
* Classe utilitária para validação de dados de entrada em formulários do sistema CoffeBreak.
* Contém métodos estáticos para validar diferentes tipos de dados com expressões regulares e funções nativas.
* 
* Funcionalidades :
* -> Validação de nomes com suporte a caracteres especiais em português
* -> Validação de endereços de e-mail com filter_var()
* -> Validação de números de telefone brasileiros (formato específico)
* -> Validação de senhas com critérios de segurança
* -> Validação de endereços com formato complexo
* -> Validação de pedidos com estrutura específica
* -> Validação de valores monetários formatados
* -> Validação de formas de pagamento permitidas
* -> Validação de status de pedidos
* -> Validação de upload de imagens com múltiplas verificações
* 
* Fluxo de operação:
* 1) Chamada do método estático correspondente ao tipo de validação
* 2) Aplicação de expressão regular ou função de validação
* 3) Retorno de mensagem de erro específica em caso de falha
* 4) Retorno de null em caso de sucesso (dados válidos)
* 
* Segurança:
* -> Uso de expressões regulares para validação rigorosa
* -> Sanitização implícita através de validação de formato
* -> Verificação de tipos de arquivo em uploads
* -> Limitação de tamanho de arquivos
* -> Prevenção contra upload de arquivos maliciosos
* -> Validação server-side independente do client-side
* 
* 
* Observações importantes:
* -> Todos os métodos são estáticos, não requerem instanciação
* -> Retornam null para sucesso e string para erro
* -> Expressões regulares são case-insensitive quando aplicável
* -> Validação de telefone segue formato brasileiro: 55 + DDD + 9 dígitos
* -> Senha requer: 6+ caracteres, 1 especial, 2 letras
* -> Imagens permitidas: JPG, JPEG, PNG, GIF, WebP (máx 5MB)
* -> Endereço requer formato específico com CEP
* 
* @ Autor - Eduardo Torres Do Ó
* @ Direitos Reservados - 2026 CoffeBrek
* 
*/



class Validator {
    
    /****************************************************************
     * VALIDAÇÃO DE NOME COMPLETO
    ****************************************************************/
    
    public static function validarNome($nome) {
        // EXPRESSÃO REGULAR PARA NOMES COM ACENTO
        if (!preg_match("/^[A-Za-záàâãéèêíïóôõöúçñÁÀÂÃÉÈÍÏÓÔÕÖÚÇÑ'\s]+$/", $nome)) {
            return "Somente permitido letras e espaços!";
        }
        return null;
    }
    
    
    /****************************************************************
     * VALIDAÇÃO DE ENDEREÇO DE E-MAIL
    ****************************************************************/
    
    public static function validarEmail($email) {

        // FUNÇÃO NATIVA DO PHP PARA FAZER VALIDAÇÃO
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return "Formato de e-mail inválido!";
        }
        return null;
    }
    
    
    /****************************************************************
     * VALIDAÇÃO DE NÚMERO 
    ****************************************************************/
    
    public static function validarTelefone($telefone) {
        // FOMATO ACEITO: 55 (país) + DDD (2 dígitos, não pode ser 0) + 9 (9º dígito) + 8 dígitos
        if (!preg_match('/^55[1-9]{2}9\d{8}$/', $telefone)) {
            return "Formato inválido! Precisa ter 13 dígitos (55 + DDD + 9 dígitos)";
        }
        return null;
    }
    
    
    /****************************************************************
     * VALIDAÇÃO DE SENHA 
    ****************************************************************/
    
    public static function validarSenha($senha) {
        if (
           strlen($senha) < 6 ||  // MÍNIMO 6 CARACTERES
            !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $senha) ||  // PELO MENOS 1 CARACTER ESPECIAL
            !preg_match('/[a-zA-Z].*[a-zA-Z]/', $senha) // PELO MENOS DUAS LETRAS (MAIÚSCULAS OU MINÚSCULAS)
        ) {
            return "A senha deve conter pelo menos 6 caracteres, 1 caractere especial e 2 letras";
        }
        
        return null;
    }
    
    
    /****************************************************************
     * VALIDAÇÃO DE ENDEREÇO 
    ****************************************************************/
    
    public static function validarEndereco($endereco) {

        // FORMATO ACEITO PARA ENDEREÇO : RUA, NÚMERO, [CASA/APT/BLOCO], CEP
        $pattern = '/^[a-zA-ZáàâãéèêíïóôõöúçñÁÀÂÃÉÈÊÍÏÓÔÕÖÚÇÑ\s]+(?:\s*,\s*|\s+)\d+(?:\s*,\s*|\s+)(?:casa\s+\d+|apto?\s+\d+|bloco\s+\d+)(?:(?:\s*,\s*|\s+)(?:casa\s+\d+|apto?\s+\d+|bloco\s+\d+))*(?:\s*,\s*|\s+)(\d{5}-?\d{3})$/i';
        
        if (!preg_match($pattern, $endereco)) {
            return "Formato de endereço inválido! Use: Nome da Rua, Número, [casa X], [apt Y], [bloco Z], CEP";
        }
        return null;
    }
    
    
    /****************************************************************
     * VALIDAÇÃO DE PEDIDO 
    ****************************************************************/
    
    public static function validarPedido($pedido) {
        // FORMATO ACEITO: QUANTIDADE X SABOR [– R$ VALOR]
        $pattern = '/^(\d+[xX]\s+[a-zA-ZáàâãéèêíïóôõöúçñÁÀÂÃÉÈÊÍÏÓÔÕÖÚÇÑ\s]+(?:\s*[–-]\s*R\$\s*\d+\.\d{2})?)(?:\s*,\s*\d+[xX]\s+[a-zA-ZáàâãéèêíïóôõöúçñÁÀÂÃÉÈÊÍÏÓÔÕÖÚÇÑ\s]+(?:\s*[–-]\s*R\$\s*\d+\.\d{2})?)*$/i';
        
        if (!preg_match($pattern, $pedido)) {
            return "Formato inválido! Use: Qtdx Sabor [– R$ Valor]. Ex: 1x Black Coffee – R$ 75.00,";
        }
        return null;
    }
    
    
    /****************************************************************
     * VALIDAÇÃO DE VALOR MONETÁRIO FORMATADO
    ****************************************************************/
    
    public static function validarValorTotal($valor) {
        // FORMATO ACEITO: R$ seguido de espaço, números inteiros, ponto e dois decimais
        if (!preg_match('/^R\$\s\d+\.\d{2}$/', $valor)) {
            return "Formato inválido! Use: R$ 999.99";
        }
        return null;
    }
    
    
    /****************************************************************
     * VALIDAÇÃO DE FORMA DE PAGAMENTO PERMITIDA
    ****************************************************************/
    
    public static function validarPagamento($pagamento) {
        // FORMATO ACEITO : APENAS AS FORMAS DE PAGAMENTO ACEITOS PELO SISTEMA
        if (!preg_match('/^(Pix|Cartão de Débito|Cartão de Credito|VR)$/i', $pagamento)) {
            return "Forma de pagamento inválida! Use: Pix, Cartão de Débito, Cartão de Credito ou VR";
        }
        return null;
    }
    
    
    /****************************************************************
     * VALIDAÇÃO DE STATUS DE PEDIDO
    ****************************************************************/
    
    public static function validarStatus($status) {
        // FORMATO ACEITO : APENAS OS STATUS DEIFINIDOS NO SISTEMA
        if (!preg_match('/^(confirmado|saiu para entrega|entregue|cancelado)$/i', $status)) {
            return "Status inválido! Deve ser 'confirmado', 'saiu para entrega', 'entregue' ou 'cancelado'";
        }
        return null;
    }
    
    
    /****************************************************************
     * VALIDAÇÃO DE UPLOAD DE IMAGEM
    ****************************************************************/
    
    public static function validarImagem($file, $maxSize = 5242880, $allowedTypes = null) {
         
        // VERIFICA SE O ARQUIVO FOI ENVIADO ( NÃO OBRIGATÓRIO CASO NÃO TENHA ARQUIVO)
        if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null; 
        }
        
        // VERIFICA ERROS DE UPLOAD 
        if ($file['error'] !== UPLOAD_ERR_OK) {
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    return "O arquivo é muito grande. Tamanho máximo: " . round($maxSize / 1024 / 1024, 1) . "MB";
                case UPLOAD_ERR_PARTIAL:
                    return "O upload do arquivo foi interrompido";
                case UPLOAD_ERR_NO_TMP_DIR:
                    return "Erro no servidor: diretório temporário não encontrado";
                case UPLOAD_ERR_CANT_WRITE:
                    return "Erro no servidor: não foi possível gravar o arquivo";
                case UPLOAD_ERR_EXTENSION:
                    return "Upload bloqueado por extensão do servidor";
                default:
                    return "Erro desconhecido no upload do arquivo";
            }
        }
        
        // VERIFICA SE REALMENTE É UMA IMAGEM VÁLIDA
        $check = getimagesize($file['tmp_name']);
        if ($check === false) {
            return "O arquivo enviado não é uma imagem válida";
        }
        
        // VERIFICA TAMANHO MÁXIMO DA IMAGEM ( GERALMENTE 5MB)
        if ($file['size'] > $maxSize) {
            return "A imagem é muito grande. Tamanho máximo: " . round($maxSize / 1024 / 1024, 1) . "MB";
        }
        
        // VERIFICA EXTENSÃO DO ARQUIVO
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            return "Extensão de arquivo não permitida. Use: " . implode(', ', $allowedExtensions);
        }
        
        // TUDO VÁLIDO , RETORNA NULL SEM ERROS
        return null;
    }

}
?>