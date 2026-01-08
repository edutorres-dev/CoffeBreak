# ☕ Cafeteria CoffeBreak 
Sistema Web completo (E-commerce) de uma cafeteria com integração a um ERP simples verticalizado. O projeto inclui:

- Cardápio dinâmico
- Sistema de pedidos com sacola virtual
- Autenticação e gerenciamento de usuários
- Painel administrativo completo
- Relatórios e gráficos financeiros
- Integrações via e-mail e simulação de API para WhatsApp

---

## 🧾 Sumário

- [🧩 Funcionalidades](#-funcionalidades)
- [🖥️ Tecnologias Utilizadas](#-tecnologias-utilizadas)
- [📁 Estrutura do Projeto](#-estrutura-do-projeto)
- [⚙️ Instalação e Configuração](#-instalação-e-configuração)
- [📊 Banco de Dados](#-banco-de-dados)
- [🔐 Autenticação e Acesso](#-autenticação-e-acesso)
- [🎨 UI e Paleta de Cores](#-ui-e-paleta-de-cores)
- [📄 Licença](#-licença)
- [👨‍💻 Autor](#-autor)

---

## 🧩 Funcionalidades


👤 Para Clientes :

- Cadastro com confirmação por e-mail -> Sistema de validação de conta
- Login seguro com tokens -> Autenticação via sessões criptografadas
- Cardápio interativo -> Visualização de produtos com preços dinâmicos
- Carrinho de compras -> Adição/remoção de itens
- Finalização de pedidos -> Seleção de endereço e forma de pagamento
- Notificação via WhatsApp -> Confirmação automática de pedidos
- Histórico de pedidos -> Visualização de pedidos do dia
- Gerenciamento de perfil -> Alteração de e-mail, telefone e senha


🛠️ Para Administradores :

- Dashboard completo -> Visão geral dos pedidos e finanças
- Gestão de produtos -> CRUD completo de itens do cardápio
- Controle de pedidos -> Atualização de status (confirmado → entregue)
- Gerenciamento de clientes -> Visualização e controle de usuários
- Relatórios financeiros -> Gráficos de vendas e métricas
- Controle de acesso -> Sistema de níveis (cliente/master)


---

## 🖥️ Tecnologias Utilizadas e Padrões de projeto

### Frontend

- HTML5
- CSS3 + Bootstrap 5
- JavaScript (ES6)
- jQuery

### Backend

- PHP 8
- MySQL
- Apache
- PhpMyAdmin (opcional)
- Biblioteca PHPMailer para o envio de e-mails

### Padrões de Projeto

- Singleton - Configuração centralizada (Config.php)
- MVC (Model-View-Controller) - Separação de responsabilidades
- DAO (Data Access Object) - Abstração de acesso a dados (Usuario.php)
- Service Layer - Serviços especializados (EmailService.php)
- Validator Pattern - Validação centralizada (Validator.php)

---

## 📁 Estrutura Completa do Projeto

```
coffebreak/
├── 📄 index.php                    # Página inicial (landing page)
├── 📄 cadastrar.php                # Formulário de cadastro de usuários
├── 📄 login.php                    # Sistema de autenticação
├── 📄 restrita.php                 # Área do cliente logado
├── 📄 esqueci.php                  # Recuperação de senha
├── 📄 recupera_senha.php           # Redefinição de senha
├── 📄 confirmacao.php              # Confirmação de e-mail
├── 📄 obrigado.php                 # Página de agradecimento
├── 📄 email_enviado_recupera.php   # Confirmação de envio
├── 📄 logout.php                   # Encerramento de sessão
├── 📄 admin_pedidos.php            # Painel admin: gestão de pedidos
├── 📄 admin_produtos.php           # Painel admin: gestão de produtos
├── 📄 admin_clientes.php           # Painel admin: gestão de clientes
├── 📄 financeiro.php               # Painel admin: relatórios financeiros
├── 📄 autoload.php                 # Carregador automático de classes
│
├── 📁 classes/                     # Classes PHP (Modelo OO)
│   ├── 📄 Config.php               # Conexão com banco de dados e metódos (Singleton)
│   ├── 📄 Usuario.php              # CRUD de usuários (DAO Pattern)
│   ├── 📄 Validator.php            # Validações de formulários
│   └── 📄 EmailService.php         # Envio de e-mails com PHPMailer
│
├── 📁 assets/                     # Recursos estáticos
│   ├── 📁 css/                    # Folhas de estilo
│   │   ├── style.css              # Estilos da landing page
│   │   ├── restrita.css           # Estilos da área restrita
│   │   └── form_aut.css           # Estilos de formulários
│   │
│   ├── 📁 js/                      # Scripts JavaScript
│   │   ├── main.js                # Scripts da página inicial
│   │   └── restrita.js            # Scripts da área restrita
│   │
│   ├── 📁 img/                     # Imagens do sistema
│   │   ├── logo/                  # Logotipos e marcas
│   │   ├── hero/                  # Imagens hero/banner
│   │   ├── sobre/                 # Imagens da seção sobre
│   │   └── favicon/               # Ícones do navegador
│   │
│   └── 📁 lib/                     # Bibliotecas externas
│       ├── PHPMailer/             # Biblioteca de envio de e-mails
│       └── owlcarousel/           # Biblioteca de carrosséis
│
```

> Os arquivos possuem comentários internos explicativos para compreensão da estrutura do código e funcionalidades .

---

## ⚙️ Instalação e Configuração

### 1. Pré-requisitos

- PHP 8+
- MySQL 5.7+
- Apache Web Server
- PHPMailer (biblioteca)
- Editor de código (VSCode recomendado)
- PhpMyAdmin (opcional)

> Para facilitar, use o [XAMPP](https://www.apachefriends.org/pt_br/index.html), que já vem com PHP, MySQL e Apache.

---

### 2. Instalação com XAMPP

#### Windows

1. Baixe o XAMPP e instale com Apache, MySQL, PHP e PhpMyAdmin.
2. Copie o projeto para: `C:\xampp\htdocs\NomeDoProjeto`
3. Inicie Apache e MySQL via XAMPP Control Panel
4. Acesse: `http://localhost/NomeDoProjeto`

#### Linux

```bash
# Baixe e instale o XAMPP
wget https://www.apachefriends.org/xampp-files/8.2.4/xampp-linux-x64-8.2.4-0-installer.run
chmod +x xampp-linux-*.run
sudo ./xampp-linux-*.run
sudo /opt/lampp/lampp start

# Copie seu projeto para o diretório correto
sudo mv bella-vitta /opt/lampp/htdocs/
sudo chown -R $USER:$USER /opt/lampp/htdocs/bella-vitta

# Acesse via navegador
http://localhost/bella-vitta
```

#### macOS

1. Baixe o `.dmg` do XAMPP
2. Instale e execute Apache/MySQL
3. Copie o projeto para: `/Applications/XAMPP/htdocs/bella-vitta`
4. Acesse: `http://localhost/bella-vitta`

---

### 3. Configuração do Projeto

Edite o arquivo Config.php em  `classes/Config.php` com suas credenciais locais ou de produção.
Procure no código os seguintes trechos abaixo e coloque suas credenciais :

```php
 
  // AQUI VOCÊ ESCOLHE SE VAI SER PRODUÇÃO OU LOCAL
  $this->setModo("local");   
  
  
  // AQUI VOCÊ COLOCA AS CREDENCIAS PARA O MODO LOCAL DO BANCO DE DADOS
  if ($modo == "local") {
      $this->servidor = "localhost";
      $this->usuario = "root";
      $this->senha = "";
      $this->banco = "coffe";
  } 
  // AQUI VOCÊ COLOCA AS CREDENCIAS PARA O MODO DE PRODUÇÃO DO BANCO DE DADOS
  else {
      $this->servidor = "localhost";
      $this->usuario = "sitelo26_eduardo";
      $this->senha = "100120010539478edu@";
      $this->banco = "sitelo26_coffe";
  }
    
```

---

## 📊 Banco de Dados

Execute os comandos SQL no PhpMyAdmin ou terminal:

### Tabela `usuarios`

```sql
CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  contato VARCHAR(15) NOT NULL,
  senha VARCHAR(255) NOT NULL,
  recupera_senha VARCHAR(255),
  token VARCHAR(64),
  codigo_confirmacao VARCHAR(64),
  status ENUM('novo','confirmado') DEFAULT 'novo',
  data_cadastro DATE NOT NULL,
  nivel_acesso ENUM('cliente','master') DEFAULT 'cliente'
);
```

### Tabela `produtos`
```sql
CREATE TABLE `produtos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `imagem` varchar(255) COLLATE utf8_unicode_ci NOT NULL ,
  `preco_pequena` decimal(10,2) NOT NULL ,
  `preco_media` decimal(10,2) NOT NULL ,
  `preco_grande` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
```

### Tabela `pedidos`
```sql
CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `numero` varchar(20) COLLATE utf8_unicode_ci NOT NULL ,
  `pedido` text COLLATE utf8_unicode_ci NOT NULL ,
  `data_pedido` datetime NOT NULL,
  `endereco` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `pagamento` enum('Pix','Cartão de Débito','Cartão de Credito','VR') COLLATE utf8_unicode_ci NOT NULL,
  `valor_total` decimal(10,2) NOT NULL,
  `status` enum('confirmado','preparando','entregue','cancelado') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'confirmado',
  PRIMARY KEY (`id`),
  KEY `cliente` (`cliente`),
  KEY `data_pedido` (`data_pedido`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
```
---

## 🔐 Autenticação e Acesso

- Login via `login.php`
- Sessão: `$_SESSION["TOKEN"]`
- Middleware de verificação:

```php
$usuario = auth($_SESSION["TOKEN"]);
if (!$usuario || $usuario['nivel'] !== 'master') {
    header("Location: login.php");
    exit;
}
```

### Níveis de Acesso:

- `cliente`: acesso limitado à página `restrita.php`
- `master`: acesso total ao painel administrativo

---

## 🎨 UI e Paleta de Cores

- **Fundo**: `#252525` (marrom escuro)
- **Botões**: `#ffc107` (amarelo dourado)
- **Seções principais**: `#9f6937e2` (Marrom médio)
- **Cards dos produtos**: `#764e2aab` (Marrom caramelo)
- **Texto**: `#ffffff` (branco)
- **Tipografia**: `'Poppins', sans-serif`

---

## 📄 Licença

📜 Termos de Uso : 

PROPRIETÁRIA - TODOS OS DIREITOS RESERVADOS
© 2025-2026 Eduardo Torres Do Ó – Direitos Autorais e Propriedade Intelectual Reservados.

⚖️ Condições de Uso :

- Uso Pessoal/Educacional: Permitido para estudo e aprendizado
- Modificações: Apenas para fins educacionais com atribuição
- Distribuição: Proibida sem autorização expressa
- Comercialização: Proibida sob qualquer circunstância

🔒 Consequências Legais :

- Qualquer violação destes termos estará sujeita às medidas legais cabíveis conforme:
- Lei de Direitos Autorais (Lei nº 9.610/98)
- Lei de Software (Lei nº 9.609/98)
- Tratados Internacionais de Propriedade Intelectual

📞 Contato para Autorizações :

Para solicitar permissão de uso comercial ou modificações:

- Email: edutorres_dev@hotmail.com
- LinkedIn: https://www.linkedin.com/in/eduardo-torres-do-ó-576085385/

---

## 👨‍💻 Autor

**Eduardo Torres**  
Desenvolvedor Full Stack

- Email: edutorres_dev@hotmail.com
- Linkedin: https://www.linkedin.com/in/eduardo-torres-do-%C3%B3-576085385/

---
