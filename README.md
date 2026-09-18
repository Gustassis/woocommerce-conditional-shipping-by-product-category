# WooCommerce: Conditional Shipping Method by Product Category

## O problema

O WooCommerce não oferece, nativamente, nenhum mecanismo para condicionar a exibição de um método de envio à categoria dos produtos presentes no carrinho. As zonas de envio operam exclusivamente com base em endereço de entrega — CEP, estado, país. Não há filtro por tipo de produto.

O que isso significa na prática: se um método de envio está habilitado em uma zona, ele aparece para qualquer carrinho dentro daquela zona, independente do que está sendo comprado.

## O cenário

Uma loja com dois tipos de produto, **Categoria A** e **Categoria B**, precisava que um determinado método de envio fosse exibido **somente quando houvesse ao menos um produto da Categoria B no carrinho**.

Carrinho com Categoria A? Método oculto. Carrinho com Categoria B, mesmo que misturado com A? Método visível.

Instalar um plugin apenas para isso seria criar uma dependência desnecessária para uma regra de negócio pontual.

## A solução

O WooCommerce expõe o filter hook `woocommerce_package_rates`, que é executado no momento em que os métodos de envio de um pacote são resolvidos, antes de qualquer renderização no checkout. Ele recebe a lista de rates calculados e o pacote completo com os itens do carrinho — o suficiente para implementar qualquer lógica de filtragem sem precisar de plugin.

## O código

```php
add_filter('woocommerce_package_rates', 'remover_metodo_sem_categoria_b', 10, 2);

function remover_metodo_sem_categoria_b($rates, $package) {
    $tem_categoria_b = false;

    foreach ($package['contents'] as $item) {
        if (has_term('slug-da-categoria-b', 'product_cat', $item['product_id'])) {
            $tem_categoria_b = true;
            break;
        }
    }

    if (!$tem_categoria_b) {
        foreach ($rates as $rate_id => $rate) {
            if (strpos(strtolower($rate->label), 'nome-do-metodo') !== false) {
                unset($rates[$rate_id]);
            }
        }
    }

    return $rates;
}
```

## Por dentro do código

`woocommerce_package_rates` é um filter hook, ou seja, recebe dados, espera que você os modifique e os devolva. Os dois parâmetros relevantes aqui são `$rates` — um array associativo onde cada chave é o `rate_id` e o valor é um objeto `WC_Shipping_Rate` — e `$package['contents']`, que é o array de itens do carrinho naquele pacote de envio.

A primeira iteração usa `has_term()`, função nativa do WordPress que consulta a taxonomia `product_cat` diretamente no banco, sem instanciar o objeto do produto. É mais performático do que usar `wc_get_product()` seguido de `get_category_ids()` quando o único objetivo é checar pertencimento a uma taxonomia. O `break` encerra o loop assim que o primeiro item elegível é encontrado, evitando iterações desnecessárias.

A segunda iteração, executada apenas se nenhum item da Categoria B foi encontrado, usa `strpos` com `strtolower` para comparação case-insensitive contra o label do método. Isso evita depender do `rate_id` — que varia conforme o tipo de método (`flat_rate:16`, `local_pickup:3`) e pode mudar se a zona for reconfigurada — e amarra a lógica ao nome visível do método, que tende a ser mais estável.

O array `$rates` é passado por valor, então o `unset` opera na cópia local. O retorno da função é o que o WooCommerce de fato usa.

## Resultado

| Composição do carrinho | Método restrito aparece? |
|---|---|
| Apenas Categoria A | Não |
| Apenas Categoria B | Sim |
| Categoria A + Categoria B | Sim |

## Observações

- Substitua `slug-da-categoria-b` pelo slug real da categoria no WooCommerce (`Produtos > Categorias > Slug`).
- Substitua `nome-do-metodo` por parte do nome do método de envio cadastrado na zona (`WooCommerce > Configurações > Envio`).
- O snippet foi aplicado via WPCode para manter o `functions.php` do tema intocado.
- Após ativar, limpe o cache do servidor para garantir que o checkout reflita a mudança imediatamente.
