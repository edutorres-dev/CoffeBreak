<!-- /**

* Arquivo: index.php
* 
* Descrição:
* Página inicial do CoffeBreak, apresentando a marca, serviços e cardápio.
* Landing page principal com foco em conversão e experiência do usuário.
* 
* Funcionalidades :
* -> Interface de apresentação da marca e conceito da cafeteria
* -> Seção "Sobre Nós" com imagem e descrição da empresa
* -> Seção para apresentação de serviços oferecidos pela cafeteria
* -> Seção de exibição do cardápio de produtos (cafés e itens do menu)
* -> Carrossel de feedbacks e testemunhos de clientes
* -> Seção de contato com informações de localização e redes sociais
* 
* Fluxo de operação:
* 1) Carrega estrutura HTML com metadados otimizados para SEO
* 2) Inicializa navbar com links de navegação e botão de login
* 3) Apresenta seção hero com mensagem impactante e visual atrativo
* 4) Exibe seção "Sobre Nós" com imagem e descrição da cafeteria
* 5) Carrega seção de serviços via JavaScript dinâmico
* 6) Apresenta cardápio de produtos carregado via JavaScript
* 7) Inicializa carrossel de feedbacks com testemunhos de clientes
* 8) Exibe rodapé com contatos, redes sociais e horário de funcionamento
* 9) Carrega todas as bibliotecas JavaScript necessárias
* 
* 
* Dependências:
* -> Bootstrap 5.3.3 - Framework CSS para layout responsivo
* -> Bootstrap Icons - Biblioteca de ícones para interface
* -> Google Fonts (Poppins) - Tipografia moderna e legível
* -> Font Awesome 6.6.0 - Conjunto adicional de ícones
* -> Owl Carousel 2.3.4 - Biblioteca para carrossel de feedbacks
* -> jQuery 3.7.1 - Manipulação DOM e interações
* -> CSS personalizado (style.css) - Estilos específicos da marca
* 
* 
* @ Autor - Eduardo Torres Do Ó
* @ Direitos Reservados - 2026 CoffeBreak
* 

/ -->


<!DOCTYPE html>
<html lang="pt-br">
  <head>
        <!-- META-TAGS PARA CONFIGURAÇÕES DO SITE (COMPATIBILIDADE , DESCRIÇAO DA PAGINA , PALAVRAS CHAVES DE PESQUISA) -->
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="IE=7">
        <meta name="keywords" content="Cafeteria ,Café, Leitura com café , café com biscoito , Lugar para Coffebreak , Cafeteria em Niterói , Melhor cafeteria do Brasil , café expresso ,capuccino ">
        <meta name="description" content="Um espaço aconchegante para apreciar cafés especiais, doces e lanches deliciosos. Perfeito para relaxar, trabalhar ou encontrar amigos. Sabor e qualidade em cada xícara! ">
        
        <!-- OPEN GRAPH META TAGS ( CONFIGURAÇAO DE COMPARTILHAMENTO LINKS NA WEB , MELHORA NO SEO) -->
        <meta property="og:title" content="Cafeteria Coffebreak">
        <meta property="og:description" content="Um espaço aconchegante para apreciar cafés especiais, doces e lanches deliciosos. Perfeito para relaxar, trabalhar ou encontrar amigos. Sabor e qualidade em cada xícara!">
        <meta property="og:image" content="https://coffebreak.online/assets/img/hero/carousel-2.jpeg">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:url" content="https://www.coffebreak.online">
        <meta property="og:type" content="website">
        <meta name="author" content="Eduardo Torres">
        <title>CoffeBreak</title>
        
        <!-- FAVICON -->
        <link rel="icon" href="assets\img\favicon\favicon.ico" type="image/x-icon">

        <!-- LINKS DE REFERÊNCIA PARA O PROJETO , INDICANDO TIPOGRAFIA USADA NA PAGINA , BIBLIOTECAS , CSS E FRAMEWORKS -->
        
        <!-- GOOGLE FONT (TIPOGRAFIA USADA NA PAGINA)-->
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

        <!--FONT AWESOME (ICONES FONT AWESOME USADOS NA PAGINA)-->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
        
        <!-- OWL CAROUSEL ( BIBLIOTECA DE EFEITO VISUAL PARA A SESSAO DE FEEDBACKS)-->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css" rel="stylesheet">

        <!-- BOOTSTRAP CSS E BOOTSTRAP ICONS--->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
            integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

        <!-- CSS  ( FOLHA DE ESTILO PARA CUSTOMIZACÃO VISUAL USADA NA PAGINA) -->
        <link href="assets/css/style.css" rel="stylesheet">
        


    </head>

 <body data-bs-spy="scroll" data-bs-target="#menu">

    <!-- RESOLVE A QUESTAO DE CLICAR NO LINK DO MENU E REDIRECIOANR CORTANDO O TITULO -->
    <style>
        html {
        scroll-padding-top: 80px; /* Ajuste conforme a altura da navbar */
    }   
    </style>





   <div class="container-fluid position-relative p-0" id="home">
        <!-- =========================
        SEÇÃO MENU DE NAVEGAÇÃO (NABAR)
        ============================== -->
        <nav class="navbar navbar-expand-lg px-4 px-lg-5 py-3 py-lg-0">

            <a href="#" class="navbar-brand p-0">
                <h1 class="m-0 fs-2 " style="color: var(--primary-color)">
                    <img src="assets/img/logo/logo.png" alt="BellaVitta" class="brand-logo" />
                    CoffeBreak
                </h1>
            </a>

            <!-------- -------------
                BOTAO TOGGLER MOBILE
            ----------------------->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-------- -------------
             LINKS DE NAVEGAÇÃO DO NAVBAR
            ----------------------->
            <div class="collapse navbar-collapse justify-content-center text-center" id="navbarCollapse">
                <div class="navbar-nav ms-auto py-0">
                    <a href="#home" class="nav-link active">Home</a>
                    <a href="#sobre" class="nav-link">Sobre</a>
                    <a href="#cardapio" class="nav-link">Cardápio</a>
                    <a href="#contatos" class="nav-link">Contato</a>
                </div>

                <!-------- -------------
                    BOTÃO DE LOGIN
                ----------------------->
                <button type="button" class="btn btn-warning rounded-pill py-2 px-4 ms-3 " style="margin-right:15px"
                    onclick="window.location.href='login.php'">
                    Login
                </button>

            </div>

        </nav>

        <!-- =========================
        SEÇÃO HERO
        ============================== -->
       
        <div class="container-fluid hero-section">

            <div class="container d-flex align-items-center justify-content-center min-vh-100">
                <div class="container overlay-bottom">
                    <div class="row justify-content-start text-center">
                        <div class="col-lg-6 text-start">
                            <h1 class=" text-white mb-3 fs-1 ">
                                Por trás de mentes brilhantes, <br>
                                sempre há um bom café.
                            </h1>
                            <p class="fs-5 text-white mb-4">
                                " Sabores que acolhem, aromas que encantam. <br>
                                Sinta o prazer de um café especial ! "
                            </p>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <!-- ==============================================
     SEÇÃO SOBRE NÓS
    =============================================== -->

    <div class="about-section" style="background: #9f6937e2;" id="sobre">
        <div class="container py-4 py-lg-5">
            <div class="row align-items-center">
                <!-- IMAGEM -->
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="about-image-wrapper">
                        <img src="assets/img/sobre/image1.png" alt="Cafeteria CoffeBreak" 
                                class="img-fluid rounded shadow" style="border: 5px solid #6f3e0f9a;">
                    </div>
                </div>
                
                <!-- TEXTO -->
                <div class="col-lg-6">
                    <div class="about-content ps-lg-4">
                        <h2 class="text-white fw-bold mb-3">Sobre Nós</h2>
                        
                        <div class="text-white mb-3" style="font-size: 1.1rem;">
                            <p>Bem-vindo à CoffeBreak, um espaço aconchegante feito para os amantes de café e momentos especiais. Nosso café se destaca pela seleção dos melhores grãos, torra artesanal e preparo cuidadoso, garantindo sabor e aroma inigualáveis.</p>
                            
                            <p class="mb-4">Cada xícara é feita com paixão, proporcionando uma experiência única. Seja para um café da manhã delicioso, uma pausa na tarde ou um encontro especial, nossa cafeteria é o lugar perfeito para relaxar e aproveitar.</p>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>




    <!-- ==============================================
            SEÇÃO DE SERVIÇOS
        =============================================== -->

    <div class="container-fluid pt-5" style="background-color: #9f6937e2;" id="servicos" >
        <div class="container" >
            <div class="section-title text-center mb-5">
                <h1 class="text-white fw-bold " style="letter-spacing: 5px;">Serviços</h1>
            </div>
            <div class="row" id="insere_servicos">
                <!-- SERVIÇOS SÃO INSERIDOS AQUI VIA JAVASCRIPT( ARQUIVO MAIN.JS) -->
            </div>
        </div>
    </div>






    <!-- ==============================================
        SEÇÃO DOS PRODUTOS
    =============================================== -->
    <div class="products-section" id="cardapio">
        <div class="container">
            <div class="text-center mb-5 mt-4 ">
                <h1 class="text-white" style="letter-spacing: 5px;">Menu</h1>
            </div>

            <div class="row g-4 " id="insere_produtos">
                <!--PRODUTOS(CAFÉS) INSERIDOS AQUI VIA JAVASCRIPT (MAIN.JS)-->
                    
            </div>
            
        </div>
        
    </div>





    <!-- ==============================================
    SEÇÃO DE FEEDBACKS(TESTEMUNHAS)
    =============================================== -->
    <div class="container-fluid py-5" style="background-color: #9f6937e2; overflow-x: hidden" id="feedbacks">
        <div class="container">
            <h1 class="mb-5 text-white text-center">Feedbacks</h1>

            <div class="owl-carousel testimonial-carousel position-relative w-100" id="testemunho">
                <!-- TESTEMUNHAS INSERIDAS AQUI VIA JAVASCRIPT(MAIN.JS)-->
            </div>
        </div>
    </div>





    <!-- ==============================================
    SEÇÃO DO RODAPÉ
    =============================================== -->
    <div class="container-fluid text-white pt-3 px-0 position-relative overlay-top" style="background-color: #42291e;"
        id="contatos">

        <div class="row mx-0 pt-5 px-sm-3 px-lg-5 mt-4">

            <!-- CONTATO -->
            <div class="col-lg-4 col-md-6 mb-5 text-center text-lg-left">
                <h4 class="text-white text-uppercase mb-4" style="letter-spacing: 3px">
                    Contato
                </h4>
                <p><i class="fa fa-map-marker-alt mr-2"></i> Centro Niterói</p>
                <p><i class="fa-brands fa-whatsapp mr-2"></i> 21 995262727</p>
                <p class="m-0">
                    <i class="fa fa-envelope mr-2"></i> CoffeBreak@gmail.com
                </p>
            </div>

            <!-- REDES SOCIAIS  -->
            <div class="col-lg-4 col-md-6 mb-5 text-center text-lg-left">
                <h4 class="text-white text-uppercase mb-4" style="letter-spacing: 3px">
                    Redes Sociais
                </h4>
                <p>Nos siga e fique por dentro das novidades!</p>
                <div class="d-flex justify-content-center justify-content-lg-center">
                    <a class="btn btn-lg btn-outline-light btn-lg-square mr-2" href="#"><i
                            class="fab fa-twitter"></i></a>
                    <a class="btn btn-lg btn-outline-light btn-lg-square mr-2" href="#"><i
                            class="fab fa-facebook-f"></i></a>
                    <a class="btn btn-lg btn-outline-light btn-lg-square mr-2" href="#"><i
                            class="fab fa-linkedin-in"></i></a>
                    <a class="btn btn-lg btn-outline-light btn-lg-square" href="#"><i
                            class="fab fa-instagram"></i></a>
                </div>
            </div>

            <!-- HORÁRIO DE FUNCIONAMENTO  -->
            <div class="col-lg-4 col-md-6 mb-5 text-center text-lg-right">
                <h4 class="text-white text-uppercase mb-4" style="letter-spacing: 3px">
                    Funcionamento
                </h4>
                <div class="d-inline-block text-center text-lg-right">
                    <h6 class="text-white">Segunda - Sexta</h6>
                    <p>19:00 - 22:00</p>
                    <h6 class="text-white text-uppercase">Sábado - Domingo</h6>
                    <p>17:00 - 23:00</p>
                </div>
            </div>


        </div>

        <!-- DIREITOS AUTORAIS -->
        <div class="container-fluid text-center text-white border-top mt-4 py-4 px-sm-3 px-md-5"
            style="border-color: rgba(256, 256, 256, 0.1) !important">
            <p class="mb-2 text-white">
                Copyright &copy;
                <a class="font-weight-bold" style="color: #dc994ce2;" href="#">BellaVitta</a>.
                Todos os Direitos Reservados.
            </p>
            <p class="m-0 text-white">
                Desenvolvido por
                <a class="font-weight-bold" style="color: #dc994ce2;"
                    href="https://www.linkedin.com/in/eduardo-torres-do-%C3%B3-576085385/">Eduardo Torres Do Ó</a>
            </p>
        </div>
    </div>




    <!--  REFERÊNCIA DO JQUERY -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <!--  REFERÊNCIA DO BOOTSTRAP JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>

    <!--  REFERÊNCIA DA BILIOTECA OWL CAROUSEL JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>

    <!-- REFERÊNCIA JS ( MANIPULACAO DO CAROUSEL) -->
    <script src="assets/js/main.js"></script>
    <script src="assets/lib/owlcarousel/owl.carousel.js"></script>

    
</body>
</html>
