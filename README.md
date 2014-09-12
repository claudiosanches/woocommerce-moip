# WooCommerce Moip #
**Contributors:** claudiosanches  
**Donate link:** http://claudiosmweb.com/doacoes/  
**Tags:** woocommerce, checkout, moip  
**Requires at least:** 3.8  
**Tested up to:** 4.0  
**Stable tag:** 2.2.9  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  

Adds Moip gateway to the WooCommerce plugin

## Description ##

### Add Moip gateway to WooCommerce ###

This plugin adds **Moip** (available **Transparent Checkout**) gateway to WooCommerce.

Please notice that WooCommerce must be installed and active.

### Contribute ###

You can contribute to the source code in our [GitHub](https://github.com/claudiosmweb/woocommerce-moip) page.

### Descrição em Português: ###

Adicione o **Moip** (disponível **Checkout Transparente**) como método de pagamento em sua loja WooCommerce.

[Moip](http://site.moip.com.br/) é um método de pagamento brasileiro desenvolvido pela IG.

O plugin WooCommerce Moip foi desenvolvido sem nenhum incentivo do Moip ou IG. Nenhum dos desenvolvedores deste plugin possuem vínculos com estas duas empresas.

Este plugin foi feito baseado na [documentação oficial do Moip](http://labs.moip.com.br/).

### Compatibilidade ###

Compatível com as versões 2.0.x e 2.1.x do WooCommerce.

### Instalação: ###

Confira o nosso guia de instalação e configuração do WooCommerce Moip na aba [Installation](http://wordpress.org/extend/plugins/woocommerce-moip/installation/).

### Dúvidas? ###

Você pode esclarecer suas dúvidas usando:

* A nossa sessão de [FAQ](http://wordpress.org/extend/plugins/woocommerce-moip/faq/).
* Criando um tópico no [fórum de ajuda do WordPress](http://wordpress.org/support/plugin/woocommerce-moip) (apenas em inglês).
* Criando um tópico no [GitHub](https://github.com/claudiosmweb/woocommerce-moip/issues).
* Ou entre em contato com os desenvolvedores do plugin em nossa [página](http://claudiosmweb.com/plugins/moip-para-woocommerce/).

### Coloborar ###

Você pode contribuir com código-fonte em nossa página no [GitHub](https://github.com/claudiosmweb/woocommerce-moip).

## Installation ##

* Upload plugin files to your plugins folder, or install using WordPress built-in Add New Plugin installer;
* Activate the plugin;
* Navigate to WooCommerce -> Settings -> Payment Gateways, choose Moip and fill the options.

### Instalação e configuração em Português: ###

### Instalação do plugin: ###

* Envie os arquivos do plugin para a pasta wp-content/plugins ou usando o instalador de plugins do WordPress;
* Ative o plugin.

### Requerimentos: ###

É necessário possuir uma conta no [Moip](http://site.moip.com.br/) e instalar a última versão do [WooCommerce](http://wordpress.org/extend/plugins/woocommerce/).

### Configurações no Moip: ###

No Moip você precisa validar sua conta e configurar ela para receber pagamentos.

Para que seja possível receber notificações sobre as transações direto no seu WooCommerce você deve ativar a opção "**Notificação de Alteração de Status de Pagamento**" em `Menus Dados > Preferências > Notificação das transações` e preencher a opção "**URL de notificação**" da seguinte forma:

	http://seusite.com/?wc-api=WC_MOIP_Gateway

### Configurações do Plugin: ###

Com o plugin instalado acesse o admin do WordPress e entre em `WooCommerce > Configurações > Portais de pagamento > Moip`.

Você tem três opções de API de pagamento:

1. **HTML** - padrão e menos segura
2. **XML** - segura e mais flexível
3. **Checkout Transparente** - seguro, flexível e funciona sem precisar levar o cliente para o site do Moip.

Para a versão em **HTML** basta adicionar o seu nome de usuário ou e-mail em **Moip Login** para habilitar.

Já as versões em **XML** e **Checkout Transparente** é necessário configurar o **Token de Acesso** e a **Chave de Acesso**. Você pode obter estas informações utilizando o seguinte tutorial: [Pergunta do usuário: Como obter o token e a chave de acesso da API do Moip?](https://labs.moip.com.br/blog/pergunta-do-usuario-como-obter-o-token-e-a-chave-de-acesso-da-api-do-moip/).

### Configurações no WooCommerce ###

No WooCommerce 2.0 ou superior existe uma opção para cancelar a compra e liberar o estoque depois de alguns minutos.

Esta opção não funciona muito bem com o Moip, pois pagamentos por boleto bancário pode demorar até 48 horas para serem validados.

Para corrigir isso é necessário ir em "WooCommerce" > "Configurações" > "Inventário" e limpar (deixe em branco) o valor da opção **Manter Estoque (minutos)**.

## Frequently Asked Questions ##

### What is the plugin license? ###

This plugin is released under a GPL license.

### What is needed to use this plugin? ###

* WooCommerce version 2.0 or latter installed and active.
* Only one account on [Moip](http://site.moip.com.br/).

### FAQ em Português: ###

### Qual é a licença do plugin? ###

Este plugin esta licenciado como GPL.

### O que eu preciso para utilizar este plugin? ###

* Ter instalado o plugin WooCommerce 2.0 ou superior.
* Possuir uma conta no [Moip](http://site.moip.com.br/).

### O que eu preciso para utilizar o Checkout Transparente? ###

Você vai precisar do Token e Chave de acesso.

Veja mais a baixo como conseguir estas informações.

### Como consigo o Token e a Chave de acesso do Moip? ###

Tutorial de como conseguir o Token e a Chave de acesso: [Pergunta do usuário: Como obter o token e a chave de acesso da API do Moip?](https://labs.moip.com.br/blog/pergunta-do-usuario-como-obter-o-token-e-a-chave-de-acesso-da-api-do-moip/)

### Como funciona o Moip? ###

Saiba mais em: [O que é - Moip](http://site.moip.com.br/o-que-e/).

### Quais são os meios de pagamento que o plugin aceita? ###

São aceitos todos os meios de pagamentos que o Moip disponibiliza.
Entretanto você precisa ativa-los na sua conta no Moip.

### Quais são as taxas de transações que o Moip cobra? ###

Consulte a página: [Quanto custa - Moip](http://site.moip.com.br/quanto-custa/).

### Como que plugin faz integração com Moip? ###

Fazemos a integração baseada na documentação oficial do Moip que pode ser encontrada no [Moip Labs](http://labs.moip.com.br/).

### A compra é cancelada após alguns minutos, mesmo com o pedido sendo pago, como resolvo isso? ###

Para resolver este problema vá até "WooCommerce" > "Configurações" > "Inventário" e limpe (deixe em branco) o valor da opção **Manter Estoque (minutos)**.

### O pedido foi pago e ficou com o status de "processando" e não como "concluído", isto esta certo ? ###

Sim, esta certo e significa que o plugin esta trabalhando como deveria.

Todo gateway de pagamentos no WooCommerce deve mudar o status do pedido para "processando" no momento que é confirmado o pagamento e nunca deve ser alterado sozinho para "concluído", pois o pedido deve ir apenas para o status "concluído" após ele ter sido entregue.

Para produtos baixáveis a configuração padrão do WooCommerce é permitir o acesso apenas quando o pedido tem o status "concluído", entretanto nas configurações do WooCommerce na aba *Produtos* é possível ativar a opção **"Conceder acesso para download do produto após o pagamento"** e assim liberar o download quando o status do pedido esta como "processando".

### Mais dúvidas relacionadas ao funcionamento do plugin? ###

Entre em contato [clicando aqui](http://claudiosmweb.com/plugins/moip-para-woocommerce/).

## Screenshots ##

### 1. Settings page. ###
![1. Settings page.](http://ps.w.org/woocommerce-moip/assets/screenshot-1.png)

### 2. Checkout page. ###
![2. Checkout page.](http://ps.w.org/woocommerce-moip/assets/screenshot-2.png)

### 3. Transparente Checkout Page. ###
![3. Transparente Checkout Page.](http://ps.w.org/woocommerce-moip/assets/screenshot-3.png)


## Changelog ##

### 2.2.9 - 11/09/2014 ###

* Corrigido o parametro de repassar os juros para o cliente (obrigado [bercacula](http://wordpress.org/support/profile/bercacula)).

### 2.2.8 - 20/06/2014 ###

* Corrigido erros de ortografia.

### 2.2.7 - 24/03/2014 ###

* Alterada a ordem dos botões no checkout transparente e normal para melhorar a usabilidade.

### 2.2.6 - 05/03/2014 ###

* Correção de um erro que deixava o parcelamento em branco no checkout transparente.

### 2.2.5 - 25/02/2014 ###

* Correção das mensagens de erro retornadas pelo Moip durante o checkout transparente.

### 2.2.4 - 21/12/2013 ###

* Correção nas mensagens de log.

### 2.2.3 - 21/12/2013 ###

* Correção na tradução do plugin.

### 2.2.2 - 18/12/2013 ###

* Adicionado o gancho `woocommerce_moip_after_successful_request`.

### 2.2.1 - 16/12/2013 ###

* Corrigido links das notificações no admin.

### 2.2.0 - 15/12/2013 ###

* Corrigido padrões de código.
* Removida compatibilidade com versões 1.6.x ou inferiores do WooCommerce.
* Adicionada compatibilidade com WooCommerce 2.1 ou superior.
* Melhorado o checkout transparente.

### 2.1.2 - 16/08/2013 ###

* Correção nos scripts de checkout comum e transparente.

### 2.1.1 - 10/08/2013 ###

* Correção das URLs de pagamento do Moip.
* Correção do JavaScript do admin do plugin.

### 2.1.0 - 26/07/2013 ###

* Adicionado o filtro `woocommerce_moip_holder_data`.
* Adicionada compatibilidade com o [WooCommerce Extra Checkout Fields for Brazil](http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/)

### 2.0.0 - 24/07/2013 ###

* Adicionado Checkout Transparente.
* Melhoria nas mensagens de status do pedido.
* Melhorias no código.

### 1.5.0 - 19/07/2013 ###

* Adicionada a API de pagamentos em XML do Moip.
* Adicionadas opções para controle dos métodos de pagamento (apenas para API em XML).
* Adicionadas opções de parcelamento (apenas para API em XML).
* Adicionadas opções para customização do boleto bancário (apenas para API em XML).

### 1.4.0 - 18/07/2013 ###

* Melhoria no código.
* Adicionada compatibilidade com o WooCommerce 2.1 ou superior.

### 1.3.1 - 18/06/2013 ###

* Correção do retorno automático de dados para o status `Concluido`.

### 1.3.0 - 06/05/2013 ###

* Melhorado o retorno automático de dados.

### 1.2.1 - 06/05/2013 ###

* Adicionado o parametro `$order` no filtro `woocommerce_moip_args`.
* Melhoria na tradução.

### 1.1.2 - 06/03/2013 ###

* Corrigida a compatibilidade com WooCommerce 2.0.0 ou mais recente.

### 1.1.1 - 08/02/2013 ###

* Corrigido o hook responsavel por salvar as opções para a versão 2.0 RC do WooCommerce.

### 1.1 - 30/11/2012 ###

* Adicionada opção para logs de erro.
* Adicionada opção para utilizar o sandbox do Moip Labs.

### 1.0 ###

* Versão inicial.

## Upgrade Notice ##

### 2.2.9 ###

* Corrigido o parametro de repassar os juros para o cliente.

## License ##

WooCommerce Moip is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

WooCommerce Moip is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with WooCommerce Moip. If not, see <http://www.gnu.org/licenses/>.
