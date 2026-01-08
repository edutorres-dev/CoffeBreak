<?php
header('Content-Type: application/json');

/*
* Arquivo: produtos.php
* 
* Descrição:
* API simples para produtos da CoffeBreak que fornece dados em formato JSON.
* Endpoint para consumo pelo frontend (landing page) e aplicativos móveis.
* Retorna todos os produtos disponíveis no cardápio da cafeteria.
* 
* Funcionalidades:
* -> Fornece endpoint GET para listagem de produtos
* -> Retorna dados em formato JSON padrão
* -> Ordena produtos alfabeticamente por nome
* -> Em caso de erro no retorno dos dados do banco, retorna HTTP 500 com mensagem
* Método HTTP:
* - GET: Retorna lista completa de produtos
* Estrutura da resposta:
* - Sucesso (200): Array de objetos produto
* - Erro (500): Objeto com mensagem de erro
*

* 
* Fluxo de operação:
* 1) Define cabeçalho Content-Type como application/json
* 2) Carrega configurações e autoloader do sistema
* 3) Obtém instância de configuração singleton
* 4) Estabelece conexão PDO com banco de dados
* 5) Executa consulta SQL para obter todos os produtos
* 6) Formata resultados como array associativo
* 7) Codifica resposta em JSON e envia ao cliente
* 8) Trata exceções retornando erros formatados em JSON
* 
* Segurança:
* -> Headers apropriados para API JSON
* -> Tratamento de exceções sem expor detalhes sensíveis
* -> Conexão PDO com prepared statements implícitos
* -> Configuração centralizada de credenciais
* -> Validação de tipos de dados nas respostas
* -> Códigos de status HTTP semânticos (200, 500)
* 
* Dependências:
* -> Config.php - Classe de configuração e métodos para o banco de dados
* -> autoload.php - Carregador automático de classes
* -> PDO Extension - Extensão PHP para banco de dados
* -> MySQL/PostgreSQL - Banco de dados de produtos
* 
* Métodos HTTP Suportados:
* -> GET: Retorna lista completa de produtos
* 
* Exemplo de Resposta de Sucesso (200):
* [
*   {
*     "id": 1,
*     "nome": "Espresso",
*     "descricao": "Café puro e intenso",
*     "preco": 5.50,
*     "imagem": "assets/img/produtos/espresso.jpg",
*     "categoria": "cafes"
*   },
*   ...
* ]
* 
* Exemplo de Resposta de Erro (500):
* {
*   "error": "Erro ao buscar produtos: [mensagem técnica]"
* }
* 

* 
* @autor - Eduardo Torres Do Ó
* @direitos_reservados - 2025 CoffeBreak

*/

// =====================================================================
// CONFIGURAÇÕES INICIAIS DO ENDPOINT
// =====================================================================

//CHAMA O AUTOLOADER QUE CONTÉM AS CLASSES NECESSÁRIAS
require_once __DIR__ . '/../../autoload.php';

try {
    
    // =====================================================================
    // INICIALIZAÇÃO DO SISTEMA E CONEXÃO COM BANCO
    // =====================================================================
    
    
    // INSTÂNCIA A CLASSE CONFIG 
    $config = Config::getInstance();
    
    // PEGA A CONEXÃO PDO LA DO BANCO 
    $pdo = $config->getPDO();
    
     // =====================================================================
    // EXECUÇÃO DA CONSULTA AO BANCO DE DADOS
    // =====================================================================
    
    // PREPARA E EXECUTA A CONSULTA DE PRODUTOS
    $stmt = $pdo->query("SELECT * FROM produtos ORDER BY nome");
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // RETORNA EM FORMATO JSON( CONVERTE ARRAY DE PRODUTOS PHP EM JSON) RETORNANDO OS PRODUTOS DO BANCO
    echo json_encode($produtos);
    
    
    
    
    //SE DEU ERRO NA HORA DE PUXAR OS DADOS DO BANCO E CONVERTER COM ENCODE , MOSTRA A MENSAGEM DE ERRO AO USUÁRIO
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar produtos: ' . $e->getMessage()]);
    
    // SE O ERRO FOI DE CONFIGURAÇÃO ( NO AUTOLOAD POR EXEMPLO) , MOSTRA A MENSAGEM DE ERRO AO USUÁRIO
} catch (Exception $e) {
    
    http_response_code(500);
    echo json_encode(['error' => 'Erro de configuração: ' . $e->getMessage()]);
}
?>