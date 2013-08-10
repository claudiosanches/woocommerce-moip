=== WooCommerce Moip ===
Contributors: claudiosanches
Donate link: http://claudiosmweb.com/doacoes/
Tags: woocommerce, checkout, moip
Requires at least: 3.0
Tested up to: 3.6
Stable tag: 2.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds Moip gateway to the WooCommerce plugin

== Description ==

### Add Moip gateway to WooCommerce ###

This plugin adds **Moip** (available **Transparent Checkout**) gateway to WooCommerce.

Please notice that WooCommerce must be installed and active.

= Contribute =

You can contribute to the source code in our [GitHub](https://github.com/claudiosmweb/woocommerce-moip) page.

### Descrição em Português: ###

Adicione o **Moip** (disponível **Checkout Transparente**) como método de pagamento em sua loja WooCommerce.

[Moip](http://site.moip.com.br/) é um método de pagamento brasileiro desenvolvido pela IG.

O plugin WooCommerce Moip foi desenvolvido sem nenhum incentivo do Moip ou IG. Nenhum dos desenvolvedores deste plugin possuem vínculos com estas duas empresas.

Este plugin foi feito baseado na [documentação oficial do Moip](http://labs.moip.com.br/).

= Instalação: =

Confira o nosso guia de instalação e configuração do WooCommerce Moip na aba [Installation](http://wordpress.org/extend/plugins/woocommerce-moip/installation/).

= Dúvidas? =

Você pode esclarecer suas dúvidas usando:

* A nossa sessão de [FAQ](http://wordpress.org/extend/plugins/woocommerce-moip/faq/).
* Criando um tópico no [fórum de ajuda do WordPress](http://wordpress.org/support/plugin/woocommerce-moip) (apenas em inglês).
* Criando um tópico no [GitHub](https://github.com/claudiosmweb/woocommerce-moip/issues).
* Ou entre em contato com os desenvolvedores do plugin em nossa [página](http://claudiosmweb.com/plugins/moip-para-woocommerce/).

= Coloborar =

Você pode contribuir com código-fonte em nossa página no [GitHub](https://github.com/claudiosmweb/woocommerce-moip).

== Installation ==

* Upload plugin files to your plugins folder, or install using WordPress built-in Add New Plugin installer;
* Activate the plugin;
* Navigate to WooCommerce -> Settings -> Payment Gateways, choose Moip and fill the options.

### Instalação e configuração em Português: ###

= Instalação do plugin: =

* Envie os arquivos do plugin para a pasta wp-content/plugins ou usando o instalador de plugins do WordPress;
* Ative o plugin.

= Requerimentos: =

É necessário possuir uma conta no [Moip](http://site.moip.com.br/) e instalar a última versão do [WooCommerce](http://wordpress.org/extend/plugins/woocommerce/).

= Configurações no Moip: =

No Moip você precisa validar sua conta e configurar ela para receber pagamentos.

Para que seja possível receber notificações sobre as transações direto no seu WooCommerce você deve ativar a opção "**Notificação de Alteração de Status de Pagamento**" em `Menus Dados > Preferências > Notificação das transações` e preencher a opção "**URL de notificação**" da seguinte forma:

    http://seusite.com/?wc-api=WC_MOIP_Gateway

= Configurações do Plugin: =

Com o plugin instalado acesse o admin do WordPress e entre em `WooCommerce > Configurações > Portais de pagamento > Moip`.

Você tem três opções de API de pagamento:

1. **HTML** - padrão e menos segura
2. **XML** - segura e mais flexível
3. **Checkout Transparente** - seguro, flexível e funciona sem precisar levar o cliente para o site do Moip.

Para a versão em **HTML** basta adicionar o seu nome de usuário ou e-mail em **Moip Login** para habilitar.

Já as versões em **XML** e **Checkout Transparente** é necessário configurar o **Token de Acesso** e a **Chave de Acesso**. Você pode obter estas informações utilizando o seguinte tutorial: [Pergunta do usuário: Como obter o token e a chave de acesso da API do Moip?](https://labs.moip.com.br/blog/pergunta-do-usuario-como-obter-o-token-e-a-chave-de-acesso-da-api-do-moip/).

= Configurações no WooCommerce =

No WooCommerce 2.0 ou superior existe uma opção para cancelar a compra e liberar o estoque depois de alguns minutos.

Esta opção não funciona muito bem com o Moip, pois pagamentos por boleto bancário pode demorar até 48 horas para serem validados.

Para corrigir isso é necessário ir em "WooCommerce" > "Configurações" > "Inventário" e limpar (deixe em branco) o valor da opção **Manter Estoque (minutos)**.

== Frequently Asked Questions ==

= What is the plugin license? =

This plugin is released under a GPL license.

= What is needed to use this plugin? =

* WooCommerce installed and active
* Only one account on [Moip](http://site.moip.com.br/ "Moip").

### FAQ em Português: ###

= Qual é a licença do plugin? =

Este plugin esta licenciado como GPL.

= O que eu preciso para utilizar este plugin? =

* Ter instalado o plugin WooCommerce.
* Possuir uma conta no Moip.

= O que eu preciso para utilizar o Checkout Transparente? =

Você vai precisar do Token e Chave de acesso.

Veja mais a baixo como conseguir estas informações.

= Como consigo o Token e a Chave de acesso do Moip? =

Tutorial de como conseguir o Token e a Chave de acesso: [Pergunta do usuário: Como obter o token e a chave de acesso da API do Moip?](https://labs.moip.com.br/blog/pergunta-do-usuario-como-obter-o-token-e-a-chave-de-acesso-da-api-do-moip/)

= Como funciona o Moip? =

Saiba mais em: [O que é - Moip](http://site.moip.com.br/o-que-e/).

= Quais são os meios de pagamento que o plugin aceita? =

São aceitos todos os meios de pagamentos que o Moip disponibiliza.
Entretanto você precisa ativa-los na sua conta no Moip.

= Quais são as taxas de transações que o Moip cobra? =

Consulte a página: [Quanto custa - Moip](http://site.moip.com.br/quanto-custa/).

= Como que plugin faz integração com Moip? =

Fazemos a integração baseada na documentação oficial do Moip que pode ser encontrada no [Moip Labs](http://labs.moip.com.br/).

= A compra é cancelada após alguns minutos, mesmo com o pedido sendo pago, como resolvo isso? =

Para resolver este problema vá até "WooCommerce" > "Configurações" > "Inventário" e limpe (deixe em branco) o valor da opção **Manter Estoque (minutos)**.

= Mais dúvidas relacionadas ao funcionamento do plugin? =

Entre em contato [clicando aqui](http://claudiosmweb.com/plugins/moip-para-woocommerce/).

== Screenshots ==

1. Settings page.
2. Checkout page.
3. Transparente Checkout Page.

== Changelog ==

= 2.1.1 - 10/08/2013 =

* Correção das URLs de pagamento do Moip.
* Correção do JavaScript do admin do plugin.

= 2.1.0 - 26/07/2013 =

* Adicionado o filtro `woocommerce_moip_holder_data`.
* Adicionada compatibilidade com o [WooCommerce Extra Checkout Fields for Brazil](http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/)

= 2.0.0 - 24/07/2013 =

* Adicionado Checkout Transparente.
* Melhoria nas mensagens de status do pedido.
* Melhorias no código.

= 1.5.0 - 19/07/2013 =

* Adicionada a API de pagamentos em XML do Moip.
* Adicionadas opções para controle dos métodos de pagamento (apenas para API em XML).
* Adicionadas opções de parcelamento (apenas para API em XML).
* Adicionadas opções para customização do boleto bancário (apenas para API em XML).

= 1.4.0 - 18/07/2013 =

* Melhoria no código.
* Adicionada compatibilidade com o WooCommerce 2.1 ou superior.

= 1.3.1 - 18/06/2013 =

* Correção do retorno automático de dados para o status `Concluido`.

= 1.3.0 - 06/05/2013 =

* Melhorado o retorno automático de dados.

= 1.2.1 - 06/05/2013 =

* Adicionado o parametro `$order` no filtro `woocommerce_moip_args`.
* Melhoria na tradução.

= 1.1.2 - 06/03/2013 =

* Corrigida a compatibilidade com WooCommerce 2.0.0 ou mais recente.

= 1.1.1 - 08/02/2013 =

* Corrigido o hook responsavel por salvar as opções para a versão 2.0 RC do WooCommerce.

= 1.1 - 30/11/2012 =

* Adicionada opção para logs de erro.
* Adicionada opção para utilizar o sandbox do Moip Labs.

= 1.0 =

* Versão inicial.

== Upgrade Notice ==

= 2.1.0 =

* Adicionado Checkout Transparente.
* Melhoria nas mensagens de status do pedido.
* Melhorias no código.
* Adicionado o filtro `woocommerce_moip_holder_data`.
* Adicionada compatibilidade com o [WooCommerce Extra Checkout Fields for Brazil](http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/)

== License ==

WooCommerce Moip is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

WooCommerce Moip is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with WooCommerce Moip. If not, see <http://www.gnu.org/licenses/>.
