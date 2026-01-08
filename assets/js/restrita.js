/**
* Arquivo: restrita.js
* 
* Descrição:
* Sistema de pedidos para CoffeBreak, responsável por:
* - Gerenciamento de sacola de compras
* - Renderização dinâmica do cardápio
* - Fluxo completo de pedido
* 
* Funcionalidades:
* -> Carregamento de produtos da API
* -> Renderização dos cards de produtos
* -> Sistema de sacola com agrupamento
* -> Cálculo automático de valores
* -> Modais interativos
* 
* Fluxo de operação:
* 1) Carrega produtos da API (com fallback)
* 2) Renderiza cards no container
* 3) Usuário clica "Pedir Agora"
* 4) Item é adicionado à sacola
* 5) Interface é atualizada (contador, lista)
* 6) Usuário finaliza pedido
* 7) Modal de pagamento é aberto
* 8) Após pagamento, sacola é limpa
* 
* Funções principais:
* -> carregarCafe(): Busca produtos da API
* -> confirmarPedido(): Adiciona item à sacola
* -> atualizarModalPedidos(): Atualiza lista
* -> finalizarPedido(): Inicia pagamento
* -> limparSacola(): Reseta sacola
* 
* Dependências:
* - Bootstrap 5 (Modais)
* - API produtos.php
* 
* @autor - Eduardo Torres Do Ó
* @direitos - 2026 CoffeBreak
*/



/* ================================================================
   CARREGAMENTO E EXIBIÇÃO DOS PRODUTOS
==================================================================*/

// CARREGA OS PRODUTOS QUE ESTÃO NO BANCO DE DADOS PELA API
async function carregarCafe() {
  try {
    const response = await fetch("assets/api/produtos.php");
    if (!response.ok) throw new Error("Erro API");
    return await response.json();
  } catch (error) {
    console.error("Erro:", error);
    
    // FALBACK CASO API FALHE
    return [
      {
        "nome": "Black Coffee",
        "imagem": "assets/img/produtos/capuccino.jpeg",
        "preco": 5.00,
        "descricao": "Café puro e encorpado"
      },
      {
        "nome": "Chocolate Coffee",
        "imagem": "assets/img/produtos/Café com Leite.jpeg",
        "preco": 7.00,
        "descricao": "Café com chocolate"
      }
    ];
  }
}

// FORMATA PREÇO DO PEDIDO
function formatarPreco(valor) {
  return "R$ " + valor.toFixed(2).replace(".", ",");
}

// CRIA O MOLDE PARA INSTANCIAR OS PRODUTOS
class Cafe {
  constructor(nome, imagem, preco, descricao) {
    this.nome = nome;
    this.imagem = imagem;
    this.preco = preco;
    this.descricao = descricao || "Descrição não disponível";
  }

  // MOLDE DE ESTILIZACAO DOS CARDS DOS PROUDTOS
  criarElemento() {
    const col = document.createElement("div");
    col.className = "col-md-4";

    col.innerHTML = `
        <div class="menu-card shadow-sm">
            <img src="${this.imagem}" class="card-img" alt="${this.nome}" loading="lazy">
            <div class="price-badge">${formatarPreco(this.preco)}</div>
            <div class="p-3 text-center">
                <h5 class="text-white">${this.nome}</h5>
                <p class="mb-3 text-white">${this.descricao}</p>
                <button class="btn buy-btn mt-auto" onclick="confirmarPedido('${this.nome}', '${this.imagem}', ${this.preco})">Pedir Agora</button>
            </div>
        </div>
    `;
    return col;
  }
}

// INSTANCIA DE FATO OS PRODUTOS(CARREGA E EXIBE OS CAFÉS)
carregarCafe().then((cafes) => {
  const container = document.getElementById("insere_produtos");
  container.innerHTML = "";

  cafes.forEach((cafe) => {
    const cafeObj = new Cafe(
      cafe.nome,
      cafe.imagem,
      parseFloat(cafe.preco),
      cafe.descricao
    );
    container.appendChild(cafeObj.criarElemento());
  });
});

/* ================================================================
   SISTEMA DE GERENCIAMENTO DE PEDIDOS
==================================================================*/

// ARRAY DA QUE RECEBE OS PEDIDOS PARA A SACOLA DE COMPRAS
let sacola = [];

// INTANCIA DO MODAL DE PAGAMENTO
let modalPagamentoInstance = null;

// INICIALIZA O O MODAL DE PAGAMENTO
document.addEventListener("DOMContentLoaded", function () {

  modalPagamentoInstance = new bootstrap.Modal(
    document.getElementById("modalPagamento"),
    {
      keyboard: false,
      backdrop: "static"
    }
  );

});

// ADICIONA ITEMS Á SACOLA NA HORA DE CONFIRMAR O PEDIDO
function confirmarPedido(nome, imagem, preco) {
  const item = {
    nome,
    imagem,
    preco
  };

  // ADICIONA EFETIVAMENTE
  sacola.push(item);

  // ATUALIZA MODAL DE PEDIDOS E CONTADOR(BADGE) DA SACOLA)
  atualizarModalPedidos();
  atualizarContadorSacola();
}

// ATUALIZAR CONTADOR DA SACOLA
function atualizarContadorSacola() {
  const contador = document.getElementById("contadorPedidos");
  const totalItens = sacola.length;

  if (contador) {
    contador.textContent = totalItens;
    contador.style.display = totalItens > 0 ? "absolute" : "none";
  }
}

// ATUALIZA LISTA DE PEDIDOS NO OFCANVAS
function atualizarModalPedidos() {
  const listaPedidos = document.getElementById("offcanvas-itens");
  const totalElement = document.getElementById("offcanvas-total");

  listaPedidos.innerHTML = "";
  let totalPreco = 0;

  if (sacola.length === 0) {
    listaPedidos.innerHTML = `<p class="text-center py-4 text-white">Nenhum item adicionado</p>`;
    totalElement.textContent = "Total: R$ 0,00";
    return;
  }

  // AFRUPA ITEMS(PEDIDOS) IGUAIS
  const itensAgrupados = {};
  sacola.forEach((item) => {
    const chave = `${item.nome}|${item.preco}`;
    if (!itensAgrupados[chave]) {
      itensAgrupados[chave] = { ...item, quantidade: 1 };
    } else {
      itensAgrupados[chave].quantidade++;
    }
  });

  // RENDERIZA ESSES ITEMS AGRUPADOS
  Object.values(itensAgrupados).forEach((item) => {
    const itemElement = document.createElement("div");
    itemElement.className = "d-flex justify-content-between align-items-center py-2 border-bottom border-secondary";

    itemElement.innerHTML = `
      <div class="d-flex align-items-center">
          <img src="${item.imagem}" alt="${item.nome}" 
               width="40" height="40" class="rounded me-2">
          <span>${item.quantidade}x ${item.nome}</span>
      </div>
      <div class="d-flex align-items-center">
          <span class="me-3">R$ ${(item.preco * item.quantidade).toFixed(2)}</span>
          <button class="btn btn-sm btn-outline-danger" 
                  onclick="removerItemAgrupado('${item.nome}', ${item.preco})">
              <i class="bi bi-trash"></i>
          </button>
      </div>`;

    listaPedidos.appendChild(itemElement);
    totalPreco += item.preco * item.quantidade;
  });

  totalElement.textContent = `Total: R$ ${totalPreco.toFixed(2)}`;
}

//REMOVE ITEM DA SAOCLA
function removerItemAgrupado(nome, preco) {
  const index = sacola.findIndex(
    (item) => item.nome === nome && item.preco === preco
  );

  if (index !== -1) {
    sacola.splice(index, 1);
    atualizarModalPedidos();
    atualizarContadorSacola();
  }
}

// FINALIZA PEDIDO E ABRE O MODAL DE PAGAMENTO
function finalizarPedido(event) {
  if (event) event.preventDefault();

  if (!sacola.length) {
    alert("Seu carrinho está vazio!");
    return;
  }

  const offcanvasPedidos = bootstrap.Offcanvas.getInstance(
    document.getElementById("meusPedidosOffcanvas")
  );

  // FECHA O OFFCANVAS DOS PEDIDOS E ABRE O MODAL DE PAGAMENTO , COM UM LEVE DALAY
  if (offcanvasPedidos) {
    offcanvasPedidos.hide();
    setTimeout(() => abrirModalPagamento(), 300);
  } else {
    abrirModalPagamento();
  }
}

// ABRE O MODAL DE PAGAMENTO COM OS DADOS FORMATADOS
function abrirModalPagamento() {

  // AGRUPA OS ITEMS CASO NECESSITE
  const itensAgrupados = sacola.reduce((acc, item) => {
    const chave = `${item.nome}|${item.preco}`;
    acc[chave] = acc[chave] || { ...item, quantidade: 0 };
    acc[chave].quantidade++;
    return acc;
  }, {});

  // FORMATA DESCRIÇÃO
  const descricaoFormatada = Object.values(itensAgrupados)
    .map((item) => `${item.quantidade}x ${item.nome}`)
    .join(", ");

  // CALCULA TOTAL
  const total = sacola
    .reduce((sum, item) => sum + item.preco, 0)
    .toFixed(2);

  // PREENCHE FORMULÁRIO
  document.getElementById("descricao_pedido").value = descricaoFormatada;
  document.getElementById("valor_total").value = `R$ ${total}`;

  // ABRE O MODAL
  modalPagamentoInstance.show();
}

// LIMPA SACOLA APÓS PAGAMENTO
function limparSacola() {
  sacola = [];
  atualizarModalPedidos();
  atualizarContadorSacola();
}


