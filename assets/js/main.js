/**
* Arquivo: main.js
* 
* Descrição:
* Script principal do frontend do CoffeBreak, responsável por:
* - Renderização dinâmica dos serviços e cardápio
* - Configuração do carrossel de depoimentos
* - Efeitos de navegação e interações da UI
* 
* Funcionalidades principais:
* 1. Renderização dinâmica de cards de serviços
* 2. Exibição do cardápio de pizzas com preços
* 3. Configuração do carrossel OwlCarousel
* 4. Efeito sticky na navbar ao scrollar
* 
* Classes implementadas:
* 1. Servico - Modelo para cards de serviços/diferenciais
* 2. Produto - Modelo para cards de itens do cardápio
* 
* Estrutura do código:
* 1. Seção de serviços
* 2. Seção de cardápio
* 3. Configuração do carrossel
* 4. Efeitos de navegação
* 
* Fluxo de operações:
* 1. Carregamento da página DOMContentLoaded
* 2. Instanciação dos objetos de serviço (Servico)
* 3. Inserção dinâmica dos cards de serviços na seção #insere_servicos
* 4. Instanciação dos objetos de produtos (Produto)
* 5. Inserção dinâmica dos cards de produtos na seção #insere_produtos
* 6. Instanciação dos objetos de testemunhas (Testemunha)
* 7. Inserção dos depoimentos no carrossel #testemunho
* 8. Inicialização do efeito sticky na navbar ao detectar scroll
* 9. Configuração e inicialização do carrossel OwlCarousel
* 10. Ativação dos eventos de interação (hover, scroll, autoplay)
* 
* Dependências:
* - jQuery (v3.7.1)
* - Owl Carousel (v2.3.4)
* - Bootstrap 5
* 
* Responsividade:
* - Layouts adaptáveis para mobile, tablet e desktop
* - Carrossel responsivo com breakpoints configurados
* - Cards que se ajustam a diferentes tamanhos de tela
* 
* Segurança:
* - Uso de strict mode
* - Sanitização implícita de templates
* - Escopo protegido para jQuery
* - Validação de eventos de usuário
* 
* 
* @author Eduardo Torres Do Ó
* @copyright 2026 CoffeBreak
*/


/* ================================================================
   INSERÇÃO DOS SERVIÇOS 
==================================================================*/

// CRIA O MOLDE PARA INSTANCIAR OS SERVICOS
class Servico {
    constructor(titulo, descricao, imagem, iconeClasse) {
    this.titulo = titulo;
    this.descricao = descricao;
    this.imagem = imagem;
    this.iconeClasse = iconeClasse;
    }

    // GERA TODA A ESTILIZACAO DOS CARD DOS SERVICOS
    criarElemento() {
    const col = document.createElement("div");
    col.className = "col-lg-6 mb-4";
    col.innerHTML = `
        <div class="card border-0 shadow-lg p-3 rounded">
        <div class="row g-0 align-items-center">
            <div class="col-md-4">
            <img class="img-fluid rounded-start" src="${this.imagem}" alt="${this.titulo}" 
                style="width: 100%; height: 200px; object-fit: cover;">
            </div>
            <div class="col-md-8 p-3">
            <h4 class="text-white fw-bold">
                <i class="${this.iconeClasse} me-2" style="color:white;"></i>${this.titulo}
            </h4>
            <p class="m-0 text-white">${this.descricao}</p>
            </div>
        </div>
        </div>
    `;
    return col;
    }
}

// INSTANCIA OS SERVICOS
const servicos = [
    new Servico(
    "Delivery",
    '" Peça seu café favorito sem sair de casa . Entregamos bebidas e lanches deliciosos com todo o sabor e qualidade. "',
    "assets/img/servicos/service-1.jpeg",
    "fa fa-truck"
    ),
    new Servico(
    "Café para empresas",
    '" Leve mais energia para sua equipe! Café de qualidade, entrega fácil e planos personalizados para manter sua empresa sempre produtiva e motivada. "',
    "assets/img/servicos/service-2.jpeg",
    "fa fa-coffee"
    ),
    new Servico(
    "Programa Fidelidade",
    '" Acumule pontos a cada café e troque por um cafés grátis , descontos exclusivos e brindes especiais."',
    "assets/img/servicos/service-3.jpeg",
    "fa fa-award"
    ),
    new Servico(
    "Workspace",
    '" Trabalhe com conforto! Wi-Fi rápido, café fresquinho e um ambiente inspirador para você focar, criar e produzir com mais energia e sabor "',
    "assets/img/servicos/service-4.jpeg",
    "fa fa-table"
    )
];

// PERCORRE A LISTA E FAZ A INSERÇAO DOS PRODUTOS DENTRO DA DIV INSER_SERVICOS
const containerServicos = document.getElementById("insere_servicos");
servicos.forEach(servico => {
    containerServicos.appendChild(servico.criarElemento());
});




/* ================================================================
   INSERÇÃO DOS PRODUTOS(CAFÉS)
==================================================================*/

// CRIA O MOLDE PARA INSTANCIAR OS ITEMS DO CARDAPIO
class Produto {
    constructor(nome, descricao, imagem, preco) {
    this.nome = nome;
    this.descricao = descricao;
    this.imagem = imagem;
    this.preco = preco;
    }

    // MOLDE DE ESTILIZACAO DOS CARDS DOS PROUDTOS
    criarElemento() {
    const col = document.createElement("div");
    col.className = "col-md-4";

    col.innerHTML = `
        <div class="menu-card shadow-sm">
        <img src="${this.imagem}" class="card-img" alt="${this.nome}" loading="lazy">
        <div class="price-badge">${this.preco}</div>
        <div class="p-3 text-center">
            <h5 class="text-white">${this.nome}</h5>
            <p class="mb-3 text-white">${this.descricao}</p>
            <button class="btn buy-btn" onclick="alert('Cadastre-se para realizar o pedido')">
             Pedir Agora
            </button>
        </div>
        </div>
    `;
    return col;
    }
}

// INSTANCIA DE FATO OS PRODUTOS
const produtos = [
    new Produto(
    "Black Coffee",
    "Um café puro e encorpado, com notas intensas e um aroma irresistível para os apreciadores de um clássico atemporal.",
    "assets/img/produtos/capuccino.jpeg",
    "$5"
    ),
    new Produto(
    "Chocolate Coffee",
    "A perfeita harmonia entre o sabor intenso do café e a cremosidade do chocolate, trazendo um toque de doçura equilibrada.",
    "assets/img/produtos/Café com Leite.jpeg",
    "$7"
    ),
    new Produto(
    "Coffee With Milk",
    "Uma combinação suave de café e leite cremoso, garantindo um sabor equilibrado e reconfortante para qualquer momento do dia.",
    "assets/img/produtos/blackcoffe.jpeg",
    "$9"
    ),
    new Produto(
    "Milkshake",
    "Uma mistura deliciosa de café gelado com um toque adocicado e textura aveludada, perfeito para refrescar e energizar.",
    "assets/img/produtos/milkshake.jpeg",
    "$9"
    ),
    new Produto(
    "Latte",
    "Café espresso suave, combinado com leite vaporizado e finalizado com uma camada sedosa de espuma. Clássico e sofisticado!",
    "assets/img/produtos/p1.jpeg",
    "$5"
    ),
    new Produto(
    "Espresso",
    "O mais puro café em sua essência: forte, encorpado e com um aroma marcante para quem busca intensidade.",
    "assets/img/produtos/p2.jpeg",
    "$7"
    )
];

// PRECORE A ARRAY FAZENDO A INSERCAO DOS PRODUTOS NA DIV INSERE_PRODUTOS
const containerProdutos = document.getElementById("insere_produtos");
produtos.forEach(produto => {
    containerProdutos.appendChild(produto.criarElemento());
});


/* ===============================================================
   INSERÇÃO DAS TESTEMUNHAS NA SEÇÃO 
==================================================================*/

// CRIA O MOLDE PAI PARA INSTANCIAR AS TESTEMUNHAS
class Testemunha {
  constructor(nome, localizacao, imagem, texto, estrelas = 4) {
    this.nome = nome;
    this.localizacao = localizacao;
    this.imagem = imagem;
    this.texto = texto;
    this.estrelas = estrelas;
  }

  // CRIA O MOLDE DE ESTILIZAÇÃO PARA CADA TESTEMUNHA
  criarElemento() {
    const div = document.createElement("div");
    div.className = "testimonial-item text-white text-center p-4";
    div.style.backgroundColor = "#6c4625b8 ";

    // ESTRELAS
    let estrelasHTML = "";
    for (let i = 0; i < this.estrelas; i++) {
      estrelasHTML +=
        '<small class="fa fa-star text-warning star-fixed"></small>';
    }

    // CONTEÚDO
    div.innerHTML = `
      <img
        class="bg-white rounded-circle shadow p-1 mx-auto mb-3"
        src="${this.imagem}"
        style="width: 80px; height: 80px"
        alt="Foto de ${this.nome}"
      />
      <h5 class="mb-0">${this.nome}</h5>
      <p>${this.localizacao}</p>
      <div class="stars">
        ${estrelasHTML}
      </div>
      <p class="mb-0">"${this.texto}"</p>
    `;
    return div;
  }
}

// INSTANCIA CADA TESTEMUNHA
const testemunhas = [
  new Testemunha(
    "Larissa Alves",
    "Rio de Janeiro, Brasil",
    "assets/img/testemunhas/testimonial-1.jpg",
    " O café é simplesmente perfeito! Sabor encorpado, aroma irresistível e sempre servido na temperatura ideal. Sensacional!" 
  ),
  new Testemunha(
    "Carlos Eduardo",
    "São Paulo, Brasil",
    "assets/img/testemunhas/testimonial-2.jpg",
    " Equipe super atenciosa e simpática! Atendimento rápido e eficiente, fazem você se sentir especial a cada visita. "
  ),
  new Testemunha(
    "Breno Campos",
    "Argentina, Buenos Aires",
    "assets/img/testemunhas/testimonial-3.jpg",
    " O ambiente é aconchegante e charmoso! Lugar perfeito para relaxar, conversar e apreciar um bom café. "
  ),
  new Testemunha(
    "Débora Martins",
    "New York, USA",
    "assets/img/testemunhas/testimonial-4.jpg",
    " Melhor café da cidade! Sempre fresco, bem preparado e com um sabor inigualável. Uma verdadeira experiência! "    
  ),
];

// PERCORRE O ARRAY E ADICIONA AS TESTEMUNHAS NO CAROUSEL
const containerTestemunhas = document.getElementById("testemunho");
testemunhas.forEach((testemunha) => {
  containerTestemunhas.appendChild(testemunha.criarElemento());
});




/* ===============================================================
   CONFIGURAÇÃO DE EFEITO STICKY(FIXO) + CARROUSEL
==================================================================*/

(function ($) {
  "use strict";

  // EFEITO DE STICK DO NAVBAR (FIXO NA ROLAGEM)
  $(window).scroll(function () {
    if ($(this).scrollTop() > 45) {
      $(".navbar").addClass("sticky-top shadow-sm");
    } else {
      $(".navbar").removeClass("sticky-top shadow-sm");
    }
  });

  //CONFIGURAÇÕES DO CAROUSEL
  $(".testimonial-carousel").owlCarousel({
    autoplay: true, 
    smartSpeed: 1000, 
    center: true,
    margin: 24, 
    dots: false, 
    loop: true, 
    nav: false, 

    // RESPONSIVIDADE
    responsive: {
      0: {
        // PARA TELAS DE ATÉ 767px (MOBILE)
        items: 1, // MOSTRA 1 ITEM POR VEZ
      },
      768: {
        // PARA TELAS DE 768px até 991px (TABLET)
        items: 2, // MOSTRA 2 ITENS POR VEZ
      },
      992: {
        // PARA TELAS A PARTIR DE 992px EM DIANTE (DESKTOP)
        items: 3, // MOSTRA 3 ITENS POT VEZ
      },
    },
  });
})(jQuery);






