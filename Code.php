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
