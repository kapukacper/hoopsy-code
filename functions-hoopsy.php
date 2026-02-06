<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * =========================================================
 * HOOPSY / STYLE DZIECKA - PEŁNY KOD DO functions.php
 * Jedna logika wysyłki:
 * - do 18:30 -> Dziś
 * - po 18:30 -> Jutro
 * =========================================================
 */

/* =========================================================
 * 1) STYLE DZIECKA + HELPERY
 * =======================================================*/

// 🎨 STYLE DZIECKA: ładowanie stylów motywu child
add_action('wp_enqueue_scripts', 'salient_child_enqueue_styles', 100);
function salient_child_enqueue_styles() {
    $nectar_theme_version = function_exists('nectar_get_theme_version') ? nectar_get_theme_version() : null;
    wp_enqueue_style('salient-child-style', get_stylesheet_directory_uri() . '/style.css', [], $nectar_theme_version);

    if (is_rtl()) {
        wp_enqueue_style('salient-rtl', get_template_directory_uri() . '/rtl.css', [], '1');
    }
}


function hoopsy_is_trust_product($product = null) {
    if (!$product instanceof WC_Product) {
        return false;
    }
    return true;
}

/**
 * Uniwersalna logika wysyłki:
 * - do 18:30 -> Dziś
 * - po 18:30 -> Jutro
 */
function hoopsy_get_shipping_meta($product_id = 0) {
    $tz  = wp_timezone();
    $now = new DateTimeImmutable('now', $tz);

    $cutoff   = $now->setTime(18, 30, 0);
    $is_today = ($now <= $cutoff);

    $ship_dt   = $is_today ? $now : $now->modify('+1 day');
    $day_word  = $is_today ? 'Dziś' : 'Jutro';
    $date_text = wp_date('j.m', $ship_dt->getTimestamp(), $tz);

    return [
        'left'  => $day_word . ' ' . $date_text,
        'right' => ((int) $product_id === 13396) ? 'Darmowa dostawa' : 'Dostawa w 24h',
    ];
}

/* =========================================================
 * 2) GLOBALNE STYLE (KOSZYK / OBRAZKI / PASKI)
 * =======================================================*/

add_action('wp_head', function() {
    if (is_admin()) {
        return;
    }

    echo '<style>
    /* --- koszyk: ukrycie wybranych elementów --- */
    .cart-subtotal,
    .woocommerce-form-coupon-toggle,
    .woocommerce-cart-form .coupon,
    .cart_totals h2,
    .woocommerce-message {
        display: none !important;
    }

    .woocommerce-cart-form .actions button[name="update_cart"],
    .woocommerce-cart-form .actions .button {
        display: none !important;
    }

    .woocommerce-cart-form .product-thumbnail img {
        width: 20% !important;
        height: auto !important;
    }

    @media screen and (max-width: 768px) {
        .checkout-button { margin-top: 0 !important; }
        .woocommerce-cart-form { margin-top: -30px !important; }
    }

    /* --- stabilne proporcje obrazków --- */
    .mobile-only img,
    .woocommerce-after-cart img,
    .checkout-promo-image img,
    .woocommerce img[alt="Hoopsy promo"],
    .woocommerce img[alt="Dostawa w 24h"] {
        width: 100% !important;
        max-width: 420px !important;
        height: auto !important;
        object-fit: contain !important;
        border-radius: 15px !important;
        display: block !important;
        margin: 0 auto 10px auto !important;
    }

    .mobile-only,
    .checkout-promo-image {
        text-align: center !important;
        width: 100% !important;
        max-width: 100% !important;
    }

    @media (max-width: 480px) {
        .mobile-only img,
        .checkout-promo-image img {
            max-width: 100% !important;
        }
    }

    /* --- wspólny styl paska wysyłki/zaufania --- */
    .pasek-zaufania-salient .pasek-wysylka-dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        background: #1ec13a;
        box-shadow: 0 0 0 0 rgba(30, 193, 58, .7);
        animation: pasek-dot-blink 1.4s infinite ease-out;
        flex: 0 0 9px;
        display: inline-block;
    }

    @keyframes pasek-dot-blink {
        0% { box-shadow: 0 0 0 0 rgba(30, 193, 58, .7); opacity: 1; }
        60% { box-shadow: 0 0 0 8px rgba(30, 193, 58, 0); opacity: .5; }
        100% { box-shadow: 0 0 0 0 rgba(30, 193, 58, 0); opacity: 1; }
    }

    .pasek-zaufania-salient .pasek-flag-pl {
        width: 18px;
        height: 12px;
        border-radius: 2px;
        overflow: hidden;
        display: inline-flex;
        flex-direction: column;
        box-shadow: 0 0 0 1px rgba(0, 0, 0, .06);
        flex: 0 0 18px;
    }

    .pasek-zaufania-salient .pasek-flag-pl-top { flex: 1; background: #fff; }
    .pasek-zaufania-salient .pasek-flag-pl-bottom { flex: 1; background: #d81b3f; }

    .pasek-zaufania-salient .pasek-zaufania {
        padding: 10px 14px;
        background: #f5f5f5;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        text-align: left;
        font-size: 15px;
        font-weight: 500;
        box-sizing: border-box;
    }

    .pasek-zaufania-salient .pasek-zaufania-item {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
        flex: 1 1 50%;
    }

    .pasek-zaufania-salient .pasek-zaufania-icon {
        flex: 0 0 26px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .pasek-zaufania-salient .pasek-zaufania-text {
        flex: 1 1 auto;
        line-height: 1.3;
        white-space: normal;
    }

    .pasek-zaufania-salient .pasek-zaufania-wysylka .pasek-zaufania-item:first-child { gap: 4px; }
    .pasek-zaufania-salient .pasek-zaufania-wysylka .pasek-zaufania-item:first-child .pasek-zaufania-text { white-space: nowrap; }

    .pasek-zaufania-salient .pasek-platnosci {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        padding: 10px 4px;
        margin-top: 4px;
        border-radius: 12px;
        border: 1px solid #f5f5f5;
        box-sizing: border-box;
        flex-wrap: nowrap;
        white-space: nowrap;
        gap: 0;
    }

    .pasek-zaufania-salient .pasek-platnosci .pasek-logo { height: 32px; display: block; }
    .pasek-zaufania-salient .pasek-platnosci .pasek-logo-wz { height: 29px; }
    .pasek-zaufania-salient .pasek-platnosci .pasek-logo-inpost,
    .pasek-zaufania-salient .pasek-platnosci .pasek-logo-p24 { height: 37px; }

    .pasek-zaufania-salient .pasek-platnosci .pasek-separator {
        width: 1px;
        height: 30px;
        background: #ddd;
        display: block;
        flex: 0 0 auto;
    }

    .pasek-zaufania-salient .hoopsy-metody-inline {
        margin-top: 4px;
        font-size: 13px;
        font-weight: 600;
        color: #444;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        white-space: nowrap;
    }

    .pasek-zaufania-salient .hoopsy-metoda-separator {
        width: 1px;
        height: 16px;
        background: #c6c6c6;
        display: inline-block;
    }

    @media (max-width: 480px) {
        .pasek-zaufania-salient .pasek-zaufania { padding: 8px 10px; font-size: 13px; gap: 10px; }
        .pasek-zaufania-salient .pasek-platnosci { padding: 8px 4px; }
        .pasek-zaufania-salient .pasek-platnosci .pasek-logo { height: 22px; }
        .pasek-zaufania-salient .pasek-platnosci .pasek-logo-wz { height: 20px; }
        .pasek-zaufania-salient .pasek-platnosci .pasek-logo-inpost,
        .pasek-zaufania-salient .pasek-platnosci .pasek-logo-p24 { height: 25px; }
        .pasek-zaufania-salient .pasek-platnosci .pasek-separator { height: 24px; }
        .pasek-zaufania-salient .hoopsy-metody-inline { font-size: 12px; gap: 8px; }
    }

    @media screen and (min-width: 768px) {
        .dn-mobile-only, .mobile-only { display: none !important; }
    }
    </style>';
}, 20);

/* =========================================================
 * 3) KOSZYK / CHECKOUT
 * =======================================================*/

// 🚚 Wyłączenie obliczania wysyłki na koszyku
add_filter('woocommerce_cart_ready_to_calc_shipping', function($show_shipping) {
    return is_cart() ? false : $show_shipping;
}, 99);

// ✅ Automatyczna akceptacja regulaminu
add_action('woocommerce_checkout_process', function() {
    if (!isset($_POST['terms'])) {
        $_POST['terms'] = 1;
    }
});

// 📱 Shortcode nad checkoutem (mobile)
add_action('woocommerce_before_checkout_form', function() {
    if (function_exists('is_checkout') && is_checkout() && !is_wc_endpoint_url()) {
        echo '<div class="dn-mobile-only" style="text-align:center; margin-top:-20px; margin-bottom:10px;">'
            . do_shortcode('[DropNinja_HurryUp id="12476"]')
            . '</div>';
    }
}, 1);

// 💳 Domyślna metoda płatności - PayU
add_action('wp_footer', function() {
    if (!is_checkout()) {
        return;
    }

    echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
        var payu = document.querySelector("#payment_method_payulistbanks");
        if (payu) { payu.checked = true; }
    });
    </script>';
});

// 🚫 Blokada COD dla produktu 11245
add_filter('woocommerce_available_payment_gateways', function($available_gateways) {
    if (is_admin() || !is_checkout() || !WC()->cart) {
        return $available_gateways;
    }

    $blocked_product_id = 11245;

    foreach (WC()->cart->get_cart_contents() as $item) {
        if ((int) $item['product_id'] === $blocked_product_id) {
            unset($available_gateways['cod']);
            break;
        }
    }

    return $available_gateways;
});

// 🔄 Autoaktualizacja koszyka po zmianie ilości
add_action('wp_footer', function() {
    if (!is_cart()) {
        return;
    }

    echo '<script>
    jQuery(function($){
        $("div.woocommerce").on("change", "input.qty", function(){
            $("[name=\'update_cart\']").prop("disabled", false).trigger("click");
        });
    });
    </script>';
});

// 🖼️ Obrazek pod koszykiem (mobile)
add_action('woocommerce_after_cart', function() {
    echo '<div class="mobile-only" style="text-align:center; margin:0;">
        <img style="border-radius:15px; width:110%;"
             src="https://www.hoopsy.pl/wp-content/uploads/2025/10/ZROB-SAM-OBRAZEK-CIOTO2.png"
             alt="Hoopsy promo" />
    </div>';
});

// 🕒 Opis czasu dostawy pod każdą metodą wysyłki
add_action('wp_footer', function() {
    if (!is_checkout()) {
        return;
    }
    ?>
    <script>
    function addShippingDescriptions() {
      document.querySelectorAll('ul.woocommerce-shipping-methods li label').forEach(function(label) {
        if (!label.parentElement.querySelector('.shipping-method-description')) {
          const p = document.createElement('p');
          p.className = 'shipping-method-description';
          p.innerText = 'Czas dostawy - 1 dzień roboczy';
          label.insertAdjacentElement('afterend', p);
        }
      });
    }

    document.addEventListener('DOMContentLoaded', addShippingDescriptions);
    jQuery(document.body).on('updated_checkout', addShippingDescriptions);
    </script>
    <style>
    .shipping-method-description {
        font-size: 11px;
        text-transform: uppercase;
        font-weight: 600;
        background: #ededed;
        padding: 2px 10px 5px 10px;
        border-radius: 5px;
        margin: 4px 0 20px 0 !important;
    }
    </style>
    <?php
});

// 🛍️ Zmiana CTA
add_filter('woocommerce_order_button_text', function() {
    return 'Kupuję i płacę';
});

// 📜 Wyłączenie domyślnego checkboxa regulaminu
add_filter('woocommerce_checkout_show_terms', '__return_false');
add_filter('woocommerce_get_terms_and_conditions_checkbox_html', '__return_empty_string');

// 🧾 Własny blok zgody
add_action('woocommerce_review_order_after_submit', function () {
    $terms_url   = home_url('/regulamin/');
    $privacy_url = home_url('/privacy-policy/');
    ?>
    <div id="hoopsy-legal-consent" style="margin-top:12px; font-size:11px; line-height:1.5; color:#444;">
        Klikając „Kupuję i płacę” złożysz zamówienie. Kontynuując, dobrowolnie zgadzasz się z
        <a href="<?php echo esc_url($terms_url); ?>" target="_blank" rel="noopener">Regulaminem sklepu</a>
        oraz <a href="<?php echo esc_url($privacy_url); ?>" target="_blank" rel="noopener">Polityką prywatności</a>
        i wyrażasz zgodę na otrzymanie rachunku w formie elektronicznej na podany adres e-mail.
        <span id="hoopsy-payment-note" style="display:none;">
            W przypadku płatności przez PayU zgadzasz się również z
            <a href="https://static.payu.com/sites/terms/files/payu_terms_of_service_single_transaction_pl_pl.pdf" target="_blank" rel="noopener">zasadami płatności PayU</a>.
        </span>
        <input type="hidden" name="hoopsy_terms_implied" value="1" />
    </div>
    <script>
    (function(){
      function updatePayUNote() {
        var checked = document.querySelector('input[name="payment_method"]:checked');
        var note = document.getElementById('hoopsy-payment-note');
        if (!checked || !note) return;
        note.style.display = /payu/i.test(checked.value || '') ? 'inline' : 'none';
      }

      document.addEventListener('change', function(e) {
        if (e.target && e.target.name === 'payment_method') {
          updatePayUNote();
        }
      });

      updatePayUNote();

      var btn = document.getElementById('place_order');
      if (btn) {
        btn.setAttribute('aria-describedby', 'hoopsy-legal-consent');
      }
    })();
    </script>
    <?php
});

/* =========================================================
 * 4) PRODUKT: TYTUŁ / RATING / PASKI WYSYŁKI-ZAUFANIA
 * =======================================================*/

// 🪄 Wyróżnienie frazy w tytule
add_filter('the_title', function($title, $id) {
    if (is_product() || is_shop() || is_product_category()) {
        $title = str_ireplace('+ gratis pokrowiec!', '<span class="custom-highlight">+ gratis pokrowiec!</span>', $title);
    }
    return $title;
}, 10, 2);

// 🚚 Wysyłka + metody dostawy nad tytułem produktu
add_action('woocommerce_single_product_summary', 'hoopsy_wysylka_box_only', 3);
function hoopsy_wysylka_box_only() {
    if (!is_product()) return;

    global $product;
    if (!hoopsy_is_trust_product($product)) return;

    $meta = hoopsy_get_shipping_meta($product->get_id());

    echo '<div class="pasek-zaufania-salient" style="margin:0 0 6px 0; background:#fff; font-size:13px; color:#555; text-align:center; width:100%; box-sizing:border-box;">
      <div class="pasek-zaufania pasek-zaufania-wysylka" style="margin:0;">
        <div class="pasek-zaufania-item">
          <span class="pasek-zaufania-icon"><span class="pasek-wysylka-dot"></span></span>
          <span class="pasek-zaufania-text">Wysyłka: ' . esc_html($meta['left']) . '</span>
        </div>
        <div class="pasek-zaufania-item">
          <span class="pasek-zaufania-icon">
            <span class="pasek-flag-pl"><span class="pasek-flag-pl-top"></span><span class="pasek-flag-pl-bottom"></span></span>
          </span>
          <span class="pasek-zaufania-text">' . esc_html($meta['right']) . '</span>
        </div>
      </div>

      <div class="hoopsy-metody-inline">
        <span>Paczkomat 24/7</span>
        <span class="hoopsy-metoda-separator"></span>
        <span>Kurier</span>
        <span class="hoopsy-metoda-separator"></span>
        <span>Płatność przy odbiorze</span>
      </div>
    </div>';
}

// 🛡️ Pasek zaufania pod przyciskiem ATC
add_action('woocommerce_after_add_to_cart_button', 'pasek_zaufania_perfect_salient', 10);
function pasek_zaufania_perfect_salient() {
    global $product;
    if (!hoopsy_is_trust_product($product)) return;

    $meta = hoopsy_get_shipping_meta($product->get_id());

    echo '<div class="pasek-zaufania-salient" style="margin:20px 0; background:#fff; font-size:13px; color:#555; text-align:center; width:100%; box-sizing:border-box;">
      <div class="pasek-zaufania pasek-zaufania-wysylka" style="margin:0 0 8px 0;">
        <div class="pasek-zaufania-item">
          <span class="pasek-zaufania-icon"><span class="pasek-wysylka-dot"></span></span>
          <span class="pasek-zaufania-text">Wysyłka: ' . esc_html($meta['left']) . '</span>
        </div>
        <div class="pasek-zaufania-item">
          <span class="pasek-zaufania-icon">
            <span class="pasek-flag-pl"><span class="pasek-flag-pl-top"></span><span class="pasek-flag-pl-bottom"></span></span>
          </span>
          <span class="pasek-zaufania-text">' . esc_html($meta['right']) . '</span>
        </div>
      </div>

      <div class="pasek-platnosci">
        <img class="pasek-logo pasek-logo-inpost" src="https://inpost.pl/sites/default/files/2024-07/InPost_logotype_2024_white_bg.svg" alt="InPost">
        <span class="pasek-separator"></span>
        <img class="pasek-logo pasek-logo-wz" src="https://wygodnezwroty.pl/next-img/logo/pl.svg" alt="Wygodne Zwroty">
        <span class="pasek-separator"></span>
        <img class="pasek-logo pasek-logo-blik" src="https://www.blik.com/layout/default/dist/gfx/logo/logo.svg" alt="BLIK">
        <span class="pasek-separator"></span>
        <img class="pasek-logo pasek-logo-p24" src="https://www.przelewy24.pl/themes/przelewy24/assets/img/base/przelewy24_logo_2022.svg" alt="Przelewy24">
      </div>

      <div class="pasek-zaufania" style="margin-top:8px;">
        <div class="pasek-zaufania-item">
          <span class="pasek-zaufania-icon">
            <img src="https://www.hoopsy.pl/wp-content/uploads/2025/11/success_4192775.svg" alt="Zweryfikowany sprzedawca" style="display:block; width:23px; height:23px;">
          </span>
          <span class="pasek-zaufania-text">Zweryfikowany sprzedawca</span>
        </div>
        <div class="pasek-zaufania-item">
          <span class="pasek-zaufania-icon">
            <img src="https://www.hoopsy.pl/wp-content/uploads/2025/11/lock_17002091.svg" alt="Ochrona danych i prywatności" style="display:block; width:23px; height:23px;">
          </span>
          <span class="pasek-zaufania-text">Ochrona danych i prywatności</span>
        </div>
      </div>
    </div>';
}

// ⭐ Własny blok ocen pod tytułem
add_action('after_setup_theme', function() {
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 5);
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10);
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 15);
    add_action('woocommerce_single_product_summary', 'hoopsy_custom_product_rating', 5);
});

function hoopsy_custom_product_rating() {
    if (!is_product()) return;

    global $product;
    if (!$product) return;

    $average = (float) $product->get_average_rating();
    $count   = (int) $product->get_review_count();

    if ($count === 0 || $average <= 0) return;

    $stars_html = wc_get_rating_html($average, $count);
    if (!$stars_html) return;

    $avg_display = str_replace('.', ',', wc_format_decimal($average, 1));

    echo '<div class="hoopsy-rating-wrap">';
    echo '<a href="#tab-reviews" class="hoopsy-rating-panel">';
    echo $stars_html;
    echo '<span class="hoopsy-rating-text"><span class="hoopsy-rating-value">' . esc_html($avg_display) . '/5</span> (' . esc_html($count) . ') – Uwielbiane przez klientki.</span>';
    echo '</a>';
    echo '</div>';
}

/* =========================================================
 * 5) STICKY ADD TO CART (MOBILE) - produkt 15220
 * =======================================================*/

add_action('wp_footer', function () {
  $KC_STICKY_ATC_PRODUCT_IDS = [15220];

  if (!function_exists('is_product') || !is_product()) return;

  global $product;
  if (!$product instanceof WC_Product) return;

  $pid = (int) $product->get_id();
  if (!in_array($pid, $KC_STICKY_ATC_PRODUCT_IDS, true)) return;

  if (!$product->is_purchasable() || !$product->is_in_stock()) return;

  $is_variable  = $product->is_type('variable');
  $price_html   = $product->get_price_html();
  $shipping     = hoopsy_get_shipping_meta($pid);
  $shipping_txt = $shipping['left'];

  $add_to_cart_base = esc_url(add_query_arg('add-to-cart', $pid, wc_get_cart_url()));
  ?>
  <div class="kc-sticky-atc" id="kc-sticky-atc" aria-hidden="true">
    <div class="kc-sticky-atc__inner">
      <div class="kc-sticky-atc__left">
        <div class="kc-sticky-atc__line1">
          <span class="kc-sticky-atc__price"><?php echo wp_kses_post($price_html); ?></span>
        </div>

        <div class="kc-sticky-atc__line2">
          <span class="pasek-wysylka-dot" aria-hidden="true"></span>
          <span>Wysyłka: <strong><?php echo esc_html($shipping_txt); ?></strong></span>
        </div>
      </div>

      <div class="kc-sticky-atc__right">
        <?php if ($is_variable): ?>
          <button type="button" class="kc-sticky-atc__btn kc-sticky-atc__btn--scroll">Wybierz wariant</button>
        <?php else: ?>
          <a href="<?php echo $add_to_cart_base; ?>" class="kc-sticky-atc__btn kc-sticky-atc__btn--add" rel="nofollow">Dodaj do koszyka</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <style>
    .kc-sticky-atc{ display:none; }

    @media (max-width: 768px){
      :root{ --kc-sticky-h: 66px; }

      body.kc-sticky-atc-on{
        padding-bottom: calc(var(--kc-sticky-h) + 16px + env(safe-area-inset-bottom)) !important;
      }

      .kc-sticky-atc{
        display:block;
        position: fixed;
        left: 10px; right: 10px; bottom: 10px;
        z-index: 999999;
        transform: translateY(120%);
        opacity: 0;
        transition: transform .22s ease, opacity .22s ease;
        pointer-events: none;
      }
      .kc-sticky-atc.is-visible{
        transform: translateY(0);
        opacity: 1;
        pointer-events: auto;
      }

      .kc-sticky-atc__inner{
        width: 100%;
        max-width: 560px;
        margin: 0 auto;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
        background: rgba(255,255,255,.98);
        border: 1px solid rgba(0,0,0,.10);
        box-shadow: 0 12px 26px rgba(0,0,0,.16);
        border-radius: 14px;
        padding: 10px 10px;
        backdrop-filter: blur(10px);
        min-height: var(--kc-sticky-h);
        box-sizing: border-box;
      }

      .kc-sticky-atc__left{
        display:flex;
        flex-direction:column;
        gap:4px;
        min-width:0;
        flex:1 1 auto;
      }

      .kc-sticky-atc__line1{
        display:flex;
        align-items:baseline;
        gap:8px;
        flex-wrap:nowrap;
        min-width:0;
      }

      .kc-sticky-atc__price{
        font-size: 14px;
        line-height:1.1;
        color: rgba(0,0,0,.85);
        white-space: nowrap;
      }
      .kc-sticky-atc__price del,
      .kc-sticky-atc__price ins{
        display:inline !important;
        white-space: nowrap !important;
      }
      .kc-sticky-atc__price ins{ text-decoration: none !important; }
      .kc-sticky-atc__price ins .amount,
      .kc-sticky-atc__price ins bdi{
        color: #2e7d32 !important;
        font-weight: 800 !important;
      }

      .kc-sticky-atc__line2{
        font-size: 13px;
        line-height:1.1;
        color: rgba(0,0,0,.68);
        white-space: nowrap;
        overflow: visible;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding-left: 4px;
      }
      .kc-sticky-atc__line2 > span:last-child{
        min-width: 0;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      .kc-sticky-atc__line2 strong{
        color: rgba(0,0,0,.86);
        font-weight: 900;
      }

      .kc-sticky-atc .pasek-wysylka-dot{
        width: 9px;
        height: 9px;
        border-radius: 50%;
        background: #1ec13a;
        box-shadow: 0 0 0 0 rgba(30,193,58,.7);
        animation: kc-sticky-dot-blink 1.4s infinite ease-out;
        flex: 0 0 9px;
        display: inline-block;
      }

      @keyframes kc-sticky-dot-blink{
        0%{ box-shadow: 0 0 0 0 rgba(30,193,58,.7); opacity: 1; }
        60%{ box-shadow: 0 0 0 8px rgba(30,193,58,0); opacity: .5; }
        100%{ box-shadow: 0 0 0 0 rgba(30,193,58,0); opacity: 1; }
      }

      .kc-sticky-atc__right{
        display:flex;
        align-items:center;
        justify-content:flex-end;
        flex: 0 0 44%;
        min-width: 170px;
      }

      .kc-sticky-atc__btn{
        height: 38px !important;
        width: 100%;
        min-width: 170px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        padding: 0 14px !important;
        border-radius: 12px;
        border:0 !important;
        cursor:pointer;
        text-decoration:none !important;
        background:#f84077 !important;
        color:#fff !important;
        font-size: 13px !important;
        font-weight: 900 !important;
        box-shadow: 0 10px 20px rgba(0,0,0,.14);
        white-space: nowrap;
      }

      @media (max-width: 390px){
        :root{ --kc-sticky-h: 64px; }
        .kc-sticky-atc__price{ font-size: 13px; }
        .kc-sticky-atc__line2{ font-size: 12px; }
      }

      .kc-sticky-highlight{
        outline: 3px solid rgba(248,64,119,.28);
        outline-offset: 6px;
        border-radius: 12px;
        transition: outline .2s ease;
      }
    }
  </style>

  <script>
    (function(){
      const bar = document.getElementById('kc-sticky-atc');
      if(!bar) return;

      const MQ = window.matchMedia('(max-width: 768px)');
      const formCart = document.querySelector('form.cart, form.variations_form');
      const scrollTarget = formCart || document.querySelector('.single_add_to_cart_button') || document.querySelector('form.cart');

      function toggleSticky(){
        if(!scrollTarget) return;

        if(!MQ.matches){
          bar.classList.remove('is-visible');
          bar.setAttribute('aria-hidden','true');
          document.body.classList.remove('kc-sticky-atc-on');
          return;
        }

        const rect = scrollTarget.getBoundingClientRect();
        const below = (rect.bottom < 0);

        if(below){
          bar.classList.add('is-visible');
          bar.setAttribute('aria-hidden','false');
          document.body.classList.add('kc-sticky-atc-on');
        } else {
          bar.classList.remove('is-visible');
          bar.setAttribute('aria-hidden','true');
          document.body.classList.remove('kc-sticky-atc-on');
        }
      }

      const addLink   = bar.querySelector('.kc-sticky-atc__btn--add');
      const scrollBtn = bar.querySelector('.kc-sticky-atc__btn--scroll');

      if(addLink){
        addLink.addEventListener('click', () => {
          let q = 1;
          const mainQty = document.querySelector('form.cart input.qty');
          if(mainQty){
            const v = parseInt(mainQty.value, 10);
            if(Number.isFinite(v) && v > 0) q = v;
          }
          const url = new URL(addLink.href, window.location.origin);
          url.searchParams.set('quantity', String(q));
          addLink.href = url.toString();
        });
      }

      if(scrollBtn){
        scrollBtn.addEventListener('click', () => {
          if(!scrollTarget) return;
          scrollTarget.scrollIntoView({behavior:'smooth', block:'center'});
          if(formCart){
            formCart.classList.add('kc-sticky-highlight');
            setTimeout(()=>formCart.classList.remove('kc-sticky-highlight'), 900);
          }
        });
      }

      toggleSticky();
      window.addEventListener('scroll', toggleSticky, {passive:true});
      window.addEventListener('resize', toggleSticky);
    })();
  </script>
  <?php
}, 99);

/* =========================================================
 * 6) HURRYSTOCK INLINE PRZY CENIE - produkt 13756
 * =======================================================*/

add_shortcode('dn_hurrystock_inline', function($atts) {
    $a = shortcode_atts([
        'id'     => 0,
        'label'  => 'Pozostało',
        'suffix' => 'szt.',
        'class'  => '',
    ], $atts, 'dn_hurrystock_inline');

    $id = absint($a['id']);
    if (!$id) return '';

    $class = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $a['class']);

    return '<span class="dn-hurrystock-inline dn-hurrystock-sale ' . $class . '" data-dn-hurrystock-id="' . $id . '">
      <span class="dn-hurrystock-label">' . esc_html($a['label']) . '</span>
      <span class="dn-hurrystock-num"><span class="dn-mini-loader" aria-label="Wczytywanie"></span></span>
      <span class="dn-hurrystock-suffix">' . esc_html($a['suffix']) . '</span>
    </span>';
});

add_action('woocommerce_single_product_summary', function() {
    if (!is_product()) return;

    global $product;
    if (!$product || !in_array((int) $product->get_id(), [13756, 15220], true)) return;

    echo '<span class="dn-price-hs dn-price-hs--standalone" aria-hidden="true">'
        . do_shortcode('[dn_hurrystock_inline id="7866" label="Pozostało" suffix="szt."]')
        . '</span>';
}, 11);

add_action('wp_footer', function() {
    if (!is_product()) return;

    global $product;
    if (!$product || !in_array((int) $product->get_id(), [13756, 15220], true)) return;
    ?>
    <script>
    (function(){
      var HS_ID = 7866;

      function extractNumber(txt){
        if(!txt) return null;
        txt = String(txt).replace(/\u00A0/g,' ');
        var matches = txt.match(/(\d[\d\s]*)/g);
        if(!matches || !matches.length) return null;
        var n = parseInt(matches[matches.length - 1].replace(/\s+/g,''), 10);
        return Number.isFinite(n) ? n : null;
      }

      function sourceSpan(){
        var src = document.getElementById('DropNinja_HurryStock_Shortcode_' + HS_ID);
        return src ? (src.querySelector('.boxes .box .count span') || null) : null;
      }

      function setOut(n){
        document.querySelectorAll('.dn-hurrystock-inline[data-dn-hurrystock-id="' + HS_ID + '"]').forEach(function(el){
          var out = el.querySelector('.dn-hurrystock-num');
          if (out) out.textContent = String(n);
        });
      }

      function syncFromSource(){
        var span = sourceSpan();
        if(!span) return null;
        var n = extractNumber(span.textContent || '');
        if(n !== null) setOut(n);
        return span;
      }

      function moveBadgeIntoPrice(){
        var price = document.querySelector('body.single-product .summary.entry-summary p.price');
        var badge = document.querySelector('body.single-product .summary.entry-summary .dn-price-hs--standalone');
        if(!price || !badge) return false;
        if(price.querySelector('.dn-price-hs--standalone')) return true;

        price.querySelectorAll('br').forEach(function(br){ br.remove(); });
        price.classList.add('dn-price-hs-row');
        price.appendChild(badge);
        badge.style.display = 'inline-flex';
        badge.setAttribute('aria-hidden','false');
        return true;
      }

      function boot(){
        moveBadgeIntoPrice();
        var span = syncFromSource();

        if(span){
          var obs = new MutationObserver(function(){
            var n = extractNumber(span.textContent || '');
            if(n !== null) setOut(n);
          });
          obs.observe(span, { characterData:true, childList:true, subtree:true });
        } else {
          var bodyObs = new MutationObserver(function(){
            var found = syncFromSource();
            if(found) bodyObs.disconnect();
          });
          bodyObs.observe(document.documentElement, { childList:true, subtree:true });
        }

        var t0 = performance.now();
        (function raf(){
          moveBadgeIntoPrice();
          syncFromSource();
          if(performance.now() - t0 < 1500) requestAnimationFrame(raf);
        })();
      }

      if(document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
      } else {
        boot();
      }
    })();
    </script>
    <?php
}, 99);

add_action('wp_head', function() {
    if (!is_product()) return;

    global $product;
    if (!$product || !in_array((int) $product->get_id(), [13756, 15220], true)) return;

    $sale_badge_img = 'https://medicane.pl/wp-content/uploads/2026/01/discount_10703149.png';
    ?>
    <style>
      body.single-product .summary.entry-summary .dn-price-hs--standalone { display:none; }

      body.single-product .summary.entry-summary p.price.dn-price-hs-row {
        display:flex !important;
        align-items:center !important;
        gap:10px;
        flex-wrap:nowrap !important;
        white-space:nowrap !important;
      }

      body.single-product .summary.entry-summary p.price.dn-price-hs-row del,
      body.single-product .summary.entry-summary p.price.dn-price-hs-row ins {
        display:inline-flex !important;
        align-items:center !important;
        white-space:nowrap !important;
      }

      body.single-product .summary.entry-summary p.price.dn-price-hs-row .dn-price-hs {
        margin-left:0 !important;
        display:inline-flex !important;
        align-self:center !important;
        vertical-align:middle !important;
        position:relative;
        top:0 !important;
        overflow:visible;
      }

      .dn-hurrystock-inline {
        display:inline-flex;
        align-items:center;
        gap:3px;
        background:#f4a5a5;
        color:#fff;
        border-radius:12px;
        padding:7px 14px;
        font-size:14px;
        line-height:1;
        white-space:nowrap;
        position:relative;
        overflow:visible;
      }

      .dn-hurrystock-label,
      .dn-hurrystock-suffix { opacity:.95; }

      .dn-hurrystock-num { font-weight:700; text-align:left; }

      @keyframes dnIconPulse {
        0%   { opacity:1;   transform:translateY(-50%) rotate(-10deg) scale(1); }
        50%  { opacity:.70; transform:translateY(-50%) rotate(-10deg) scale(1.04); }
        100% { opacity:1;   transform:translateY(-50%) rotate(-10deg) scale(1); }
      }

      .dn-hurrystock-inline.dn-hurrystock-sale::after {
        content:"";
        position:absolute;
        right:-22px;
        top:50%;
        width:29px;
        height:29px;
        transform:translateY(-50%) rotate(-10deg);
        transform-origin:50% 50%;
        background-image:url('<?php echo esc_url($sale_badge_img); ?>');
        background-size:contain;
        background-repeat:no-repeat;
        background-position:center;
        pointer-events:none;
        z-index:2;
        filter:drop-shadow(0 2px 2px rgba(0,0,0,.15));
        animation:dnIconPulse 2.2s ease-in-out infinite;
      }

      .dn-mini-loader { display:inline-block; width:18px; height:1em; vertical-align:baseline; position:relative; }
      .dn-mini-loader::before { content:"..."; letter-spacing:2px; opacity:.85; animation:dnDots 1s infinite steps(4,end); }

      @keyframes dnDots{
        0% { content:""; opacity:.55; }
        25% { content:"."; opacity:.70; }
        50% { content:".."; opacity:.85; }
        75% { content:"..."; opacity:1; }
        100% { content:""; opacity:.55; }
      }

      @media (max-width:480px){
        body.single-product .summary.entry-summary p.price.dn-price-hs-row { gap:8px; }

        .dn-hurrystock-inline {
          font-size:14px;
          padding:8px 12px;
          gap:2px;
          border-radius:8px;
        }

        .dn-hurrystock-inline.dn-hurrystock-sale::after {
          right:-20px;
          width:27px;
          height:27px;
        }
      }

      @media (max-width:360px){
        .dn-hurrystock-inline {
          font-size:12px;
          padding:7px 10px;
          gap:2px;
        }

        .dn-hurrystock-inline.dn-hurrystock-sale::after {
          width:25px;
          height:25px;
          right:-18px;
        }
      }

      @media (prefers-reduced-motion: reduce){
        .dn-hurrystock-inline.dn-hurrystock-sale::after {
          animation:none !important;
          opacity:1 !important;
        }
      }
    </style>
    <?php
});

/* =========================================================
 * 7) CHECKOUT MOBILE - MINI KOSZYK NA GÓRZE + +/- ILOŚĆ
 * =======================================================*/

add_filter('woocommerce_checkout_cart_item_quantity', function($quantity_html, $cart_item, $cart_item_key) {
    if (!is_checkout()) {
        return $quantity_html;
    }

    $qty = max(1, (int) $cart_item['quantity']);
    $key = esc_attr($cart_item_key);

    return '<span class="hoopsy-qty-desktop">&times;&nbsp;' . $qty . '</span>'
        . '<span class="hoopsy-qty-mobile" data-cart-key="' . $key . '">' 
        . '<button type="button" class="hoopsy-qty-btn" data-delta="-1" aria-label="Zmniejsz ilość">-</button>'
        . '<span class="hoopsy-qty-value">' . $qty . '</span>'
        . '<button type="button" class="hoopsy-qty-btn" data-delta="1" aria-label="Zwiększ ilość">+</button>'
        . '</span>';
}, 20, 3);

add_action('wp_ajax_hoopsy_update_checkout_qty', 'hoopsy_update_checkout_qty');
add_action('wp_ajax_nopriv_hoopsy_update_checkout_qty', 'hoopsy_update_checkout_qty');
function hoopsy_update_checkout_qty() {
    check_ajax_referer('hoopsy_checkout_qty', 'nonce');

    if (!WC()->cart) {
        wp_send_json_error(['message' => 'Brak koszyka.'], 400);
    }

    $key = isset($_POST['cart_item_key']) ? wc_clean(wp_unslash($_POST['cart_item_key'])) : '';
    $qty = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;
    $qty = max(1, $qty);

    $cart = WC()->cart->get_cart();
    if (!$key || !isset($cart[$key])) {
        wp_send_json_error(['message' => 'Nie znaleziono produktu w koszyku.'], 404);
    }

    WC()->cart->set_quantity($key, $qty, true);
    WC()->cart->calculate_totals();

    wp_send_json_success(['qty' => $qty]);
}

add_action('wp_footer', function() {
    if (!is_checkout() || is_wc_endpoint_url()) {
        return;
    }

    $nonce = wp_create_nonce('hoopsy_checkout_qty');
    ?>
    <style>
    @media (max-width: 768px) {
      #hoopsy-mobile-order-box {
        background: #f7f7f7;
        border: 1px solid #e9e9e9;
        border-radius: 16px;
        padding: 12px;
        margin: 0 0 14px 0;
      }

      #hoopsy-mobile-order-box #order_review_heading {
        margin: 0 0 8px 0 !important;
        font-size: 18px;
        line-height: 1.2;
      }

      #hoopsy-mobile-order-box #order_review {
        margin: 0 !important;
        padding: 0 !important;
        background: transparent !important;
        border: 0 !important;
      }

      #hoopsy-mobile-order-box .shop_table {
        margin: 0 !important;
        background: transparent !important;
      }

      #hoopsy-mobile-order-box .product-name,
      #hoopsy-mobile-order-box .product-total,
      #hoopsy-mobile-order-box .cart-subtotal,
      #hoopsy-mobile-order-box .order-total {
        font-size: 15px;
      }

      .hoopsy-qty-desktop {
        display: none !important;
      }

      .hoopsy-qty-mobile {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        margin-left: 8px;
        border: 1px solid #d9d9d9;
        border-radius: 10px;
        padding: 4px 8px;
        background: #fff;
        vertical-align: middle;
      }

      .hoopsy-qty-mobile.is-loading {
        opacity: 0.55;
        pointer-events: none;
      }

      .hoopsy-qty-btn {
        width: 26px;
        height: 26px;
        border: 0;
        border-radius: 7px;
        background: #f1f1f1;
        color: #111;
        font-size: 18px;
        line-height: 1;
        cursor: pointer;
      }

      .hoopsy-qty-value {
        min-width: 16px;
        text-align: center;
        font-weight: 700;
        font-size: 16px;
      }
    }

    @media (min-width: 769px) {
      .hoopsy-qty-mobile {
        display: none !important;
      }
    }
    </style>

    <script>
    (function($){
      const isMobile = window.matchMedia('(max-width: 768px)').matches;
      if (!isMobile) return;

      function mountMiniOrderBox() {
        const $form = $('form.checkout');
        const $heading = $('#order_review_heading');
        const $review = $('#order_review');

        if (!$form.length || !$heading.length || !$review.length) return;

        let $box = $('#hoopsy-mobile-order-box');
        if (!$box.length) {
          $box = $('<div id="hoopsy-mobile-order-box"></div>');
          $form.prepend($box);
        }

        $box.append($heading);
        $box.append($review);
      }

      function updateQty($control, nextQty) {
        const cartKey = $control.data('cartKey');
        if (!cartKey) return;

        $control.addClass('is-loading');

        $.ajax({
          url: wc_checkout_params.ajax_url,
          type: 'POST',
          dataType: 'json',
          data: {
            action: 'hoopsy_update_checkout_qty',
            nonce: '<?php echo esc_js($nonce); ?>',
            cart_item_key: cartKey,
            quantity: nextQty
          }
        }).done(function(resp){
          if (resp && resp.success && resp.data && resp.data.qty) {
            $control.find('.hoopsy-qty-value').text(resp.data.qty);
            $(document.body).trigger('update_checkout');
          }
        }).always(function(){
          $control.removeClass('is-loading');
        });
      }

      $(document).on('click', '.hoopsy-qty-mobile .hoopsy-qty-btn', function(){
        const $btn = $(this);
        const $control = $btn.closest('.hoopsy-qty-mobile');
        const $val = $control.find('.hoopsy-qty-value');

        const current = parseInt(($val.text() || '1').trim(), 10) || 1;
        const delta = parseInt($btn.data('delta'), 10) || 0;
        const next = Math.max(1, current + delta);

        if (next === current) return;
        updateQty($control, next);
      });

      $(document).ready(mountMiniOrderBox);
      $(document.body).on('updated_checkout', mountMiniOrderBox);
    })(jQuery);
    </script>
    <?php
}, 99);
