# Vindi Transparente para PrestaShop

Módulo da AGTI que integra o PrestaShop ao intermediador de pagamentos Vindi.

## Requisitos

- PrestaShop 1.7 ou superior
- PHP 7.3 ou superior
- Módulo `agcliente` instalado
- Extensão PHP `sysvsem` habilitada para os bloqueios de sincronização dos pedidos

## Instalação

1. Baixe o pacote da [última release](https://github.com/agtiengbr/agyapay/releases).
2. No Back Office, acesse **Módulos > Gerenciador de módulos** e envie o ZIP.
3. Instale o módulo **Vindi Transparente**.
4. Abra a configuração do módulo e informe as credenciais fornecidas pela Vindi.

## Funcionalidades

- Pagamento por cartão, boleto e Pix.
- Gestão de contas e transportadoras associadas.
- Consulta de transações e pedidos.

## Suporte

Abra uma issue neste repositório com a versão do módulo, do PrestaShop e os detalhes do erro.

## Correções na versão 2.7.5

- A finalização usa o ID retornado por `validateOrder()` para carregar o pedido correto.
- Os formulários para pagar pedidos existentes recebem valores numéricos; a formatação monetária fica na apresentação.
- O e-mail de Pix usa a assinatura de `Tools::displayDate()` correspondente ao PrestaShop 1.7 ou ao PrestaShop 8 e 9, incluindo o horário de vencimento.
- Os e-mails de Pix, boleto e pagamento pendente possuem templates HTML e texto em inglês para fallback, além de português brasileiro.
- Os lembretes de pagamento pendente validam os destinatários e só registram o envio após sucesso para todos eles. Envios desativados ou com falha continuam elegíveis para uma nova tentativa.
- Os campos de mapeamento são declarados para evitar propriedades dinâmicas no PHP 8.2 ou superior.

- A URL de notificação usa a conta vinculada ao transportador, em vez de interpretar seu token como ID.

## Testes de regressão

Execute na raiz do módulo:

```sh
php tests/regression.php 1.7.8.11
php tests/regression.php 8.2.8
php tests/regression.php 9.2.0
```

Esses testes usam dependências simuladas e as assinaturas das APIs de cada versão; não acessam banco, provedor de pagamentos ou SMTP. O código dos testes e das correções mantém sintaxe compatível com PHP 7.3.
