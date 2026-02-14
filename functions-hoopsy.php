<?php
/**
 * Hoopsy – child theme functions
 * Motyw: Salient / Nectar
 */

if (!defined('ABSPATH')) { exit; }

// Produkty objęte paskiem zaufania / wysyłki / FAQ / sticky ATC
define('HOOPSY_FREE_SHIPPING_ID', 13396);
define('HOOPSY_COD_BLOCKED_ID', 11245);
define('HOOPSY_HURRYSTOCK_PRODUCT_IDS', [13756, 15220]);
define('HOOPSY_HURRYSTOCK_SOURCE_ID', 7866);
define('HOOPSY_STICKY_ATC_PRODUCT_IDS', [15220]);
define('HOOPSY_FAQ_PRODUCT_IDS', [15220]);
define('HOOPSY_BRAND_COLOR', '#f84077');

// Zewnętrzne adresy URL (łatwa aktualizacja w jednym miejscu)
define('HOOPSY_LOGO_INPOST', 'https://inpost.pl/sites/default/files/2024-07/InPost_logotype_2024_white_bg.svg');
define('HOOPSY_LOGO_WYGODNE_ZWROTY', 'https://wygodnezwroty.pl/next-img/logo/pl.svg');
define('HOOPSY_LOGO_BLIK', 'https://www.blik.com/layout/default/dist/gfx/logo/logo.svg');
define('HOOPSY_LOGO_P24', 'https://www.przelewy24.pl/themes/przelewy24/assets/img/base/przelewy24_logo_2022.svg');
define('HOOPSY_ICON_VERIFIED', 'https://www.hoopsy.pl/wp-content/uploads/2025/11/success_4192775.svg');
define('HOOPSY_ICON_LOCK', 'https://www.hoopsy.pl/wp-content/uploads/2025/11/lock_17002091.svg');
define('HOOPSY_PROMO_IMAGE', 'https://www.hoopsy.pl/wp-content/uploads/2025/10/ZROB-SAM-OBRAZEK-CIOTO2.png');

// ====== STYLE DZIECKA ======
add_action('wp_enqueue_scripts', 'hoopsy_enqueue_styles', 100);
function hoopsy_enqueue_styles(): void
{
    $nectar_theme_version = function_exists('nectar_get_theme_version') ? nectar_get_theme_version() : null;
    wp_enqueue_style('salient-child-style', get_stylesheet_directory_uri() . '/style.css', [], $nectar_theme_version);

    if (is_rtl()) {
        wp_enqueue_style('salient-rtl', get_template_directory_uri() . '/rtl.css', [], '1');
    }
}

// ====== PRZYSPIESZENIE KASY ======
add_action('wp_enqueue_scripts', 'hoopsy_checkout_dequeue', 999);
function hoopsy_checkout_dequeue(): void {
    if (!is_checkout()) return;

    // CSS – zbędne na kasie
    wp_dequeue_style('dashicons');
    wp_dequeue_style('owl.carousel');
    wp_dequeue_style('owl.theme.default');
    wp_dequeue_style('contact-form-7');
    wp_dequeue_style('nectar-cf7');
    wp_dequeue_style('fancyBox');
    wp_dequeue_style('duplicate-post');
    wp_dequeue_style('nectar-ocm-core');
    wp_dequeue_style('nectar-ocm-simple');

    // JS – zbędne na kasie
    wp_dequeue_script('owl.carousel');
    wp_dequeue_script('hoverintent-js');
    wp_dequeue_script('hoverintent');
    wp_dequeue_script('contact-form-7');
    wp_dequeue_script('swv');
    wp_dequeue_script('jquery-easing');
    wp_dequeue_script('jquery-mousewheel');
    wp_dequeue_script('nectar-waypoints');
    wp_dequeue_script('imagesLoaded');
    wp_dequeue_script('nectar-transit');

    // Duplikat select2 (selectWoo wystarczy)
    wp_dequeue_script('wc-select2');
    wp_dequeue_style('select2');
}

/**
 * Uniwersalna logika wysyłki:
 * - do 18:30 -> Dziś
 * - po 18:30 -> Jutro
 */
function hoopsy_get_shipping_meta(int $product_id = 0): array {
    $tz  = wp_timezone();
    $now = new DateTimeImmutable('now', $tz);

    $cutoff   = $now->setTime(18, 30, 0);
    $is_today = ($now <= $cutoff);

    $ship_dt   = $is_today ? $now : $now->modify('+1 day');
    $day_word  = $is_today ? 'Dziś' : 'Jutro';
    $date_text = wp_date('j.m', $ship_dt->getTimestamp(), $tz);

    return [
        'left'  => $day_word . ' ' . $date_text,
        'right' => ($product_id === HOOPSY_FREE_SHIPPING_ID) ? 'Darmowa dostawa' : 'Dostawa w 24h',
    ];
}

// ====== WYŁĄCZ OBLICZENIA WYSYŁKI NA KOSZYKU ======
add_filter('woocommerce_cart_ready_to_calc_shipping', 'hoopsy_cart_hide_shipping', 99);
function hoopsy_cart_hide_shipping(bool $show_shipping): bool {
    return is_cart() ? false : $show_shipping;
}

// ====== AUTOMATYCZNE ZAAKCEPTOWANIE REGULAMINU ======
add_filter('woocommerce_checkout_posted_data', 'hoopsy_auto_accept_terms');
function hoopsy_auto_accept_terms(array $data): array
{
    $data['terms'] = 1;
    return $data;
}

// ====== BLOKADA COD DLA KONKRETNEGO PRODUKTU ======
add_filter('woocommerce_available_payment_gateways', 'hoopsy_disable_cod');
function hoopsy_disable_cod(array $available_gateways): array
{
    if (is_admin() || !is_checkout() || !WC()->cart) return $available_gateways;

    foreach (WC()->cart->get_cart_contents() as $item) {
        if ((int) $item['product_id'] === HOOPSY_COD_BLOCKED_ID) {
            unset($available_gateways['cod']);
            break;
        }
    }
    return $available_gateways;
}

// ====== WALIDACJA NUMERU DOMU W ADRESIE ======
add_action('woocommerce_checkout_process', 'hoopsy_validate_address_number');
function hoopsy_validate_address_number(): void
{
    $address = isset($_POST['billing_address_1']) ? sanitize_text_field($_POST['billing_address_1']) : '';
    if ($address !== '' && !preg_match('/\d/', $address)) {
        wc_add_notice('Podaj również numer domu/mieszkania w adresie.', 'error');
    }
}

// ====== AUTOAKTUALIZACJA KOSZYKA PO ZMIANIE ILOŚCI ======
add_action('wp_footer', 'hoopsy_cart_auto_update');
function hoopsy_cart_auto_update(): void {
    if (!is_cart()) return;

    echo '<script>
        jQuery(function($){
            $("div.woocommerce").on("change", "input.qty", function(){
                $("[name=\'update_cart\']").prop("disabled", false).trigger("click");
            });
        });
    </script>';
}

// ====== OBRAZEK POD KOSZYKIEM ======
add_action('woocommerce_after_cart', 'hoopsy_cart_promo_image');
function hoopsy_cart_promo_image(): void
{
    ?>
    <div class="mobile-only">
        <img src="<?= esc_url(HOOPSY_PROMO_IMAGE) ?>"
             alt="Hoopsy promo" />
    </div>
    <?php
}

// ====== WYRÓŻNIENIE FRAZY W TYTULE PRODUKTU ======
add_filter('the_title', 'hoopsy_highlight_phrase', 10, 2);
function hoopsy_highlight_phrase(string $title, int $id): string
{
    if (is_product() || is_shop() || is_product_category()) {
        $title = str_ireplace('+ gratis pokrowiec!', '<span class="custom-highlight">+ gratis pokrowiec!</span>', $title);
    }
    return $title;
}

/**
 * Zmień CTA na "Zamawiam i płacę"
 */
add_filter('woocommerce_order_button_text', 'hoopsy_order_button_text');
function hoopsy_order_button_text(): string {
    return 'Zamawiam i płacę';
}

/**
 * Wyłącz standardowy checkbox T&C
 */
add_filter('woocommerce_checkout_show_terms', '__return_false');
add_filter('woocommerce_get_terms_and_conditions_checkbox_html', '__return_empty_string');

/**
 * Własny komunikat zgody (jeden blok tekstu, mniejszy font)
 */
add_action('woocommerce_review_order_after_submit', 'hoopsy_legal_consent');
function hoopsy_legal_consent(): void {
    $terms_url   = home_url('/regulamin/');
    $privacy_url = home_url('/privacy-policy/');
?>
<div id="hoopsy-legal-consent">
    Klikając „Zamawiam i płacę" złożysz zamówienie. Kontynuując, dobrowolnie zgadzasz się z
    <a href="<?php echo esc_url($terms_url); ?>" target="_blank" rel="noopener">Regulaminem sklepu</a>
    oraz <a href="<?php echo esc_url($privacy_url); ?>" target="_blank" rel="noopener">Polityką prywatności</a>
    i wyrażasz zgodę na otrzymanie rachunku w formie elektronicznej na podany adres e-mail.
    <span id="hoopsy-payment-note">
        W przypadku płatności przez PayU zgadzasz się również z
        <a href="https://static.payu.com/sites/terms/files/payu_terms_of_service_single_transaction_pl_pl.pdf"
            target="_blank" rel="noopener">zasadami płatności PayU</a>.
    </span>
    <input type="hidden" name="hoopsy_terms_implied" value="1" />
</div>
<script>
    (function () {
        function updatePayUNote() {
            var checked = document.querySelector('input[name="payment_method"]:checked');
            var note = document.getElementById('hoopsy-payment-note');
            if (!checked || !note) return;
            note.style.display = /payu/i.test(checked.value || '') ? 'inline' : 'none';
        }
        document.addEventListener('change', function (e) {
            if (e.target && e.target.name === 'payment_method') { updatePayUNote(); }
        });
        updatePayUNote();

        var btn = document.getElementById('place_order');
        if (btn) { btn.setAttribute('aria-describedby', 'hoopsy-legal-consent'); }
    })();
</script>
<?php
}


/**
 * Shared shipping row (DRY – trust_bar & wysylka_box).
 */
function hoopsy_render_shipping_row(string $wysylka_info, string $prawy_tekst_wysylka): void {
    ?>
    <div class="pasek-zaufania pasek-zaufania-wysylka">
        <div class="pasek-zaufania-item">
            <span class="pasek-zaufania-icon">
                <span class="pasek-wysylka-dot"></span>
            </span>
            <span class="pasek-zaufania-text">
                Wysyłka: <?= esc_html($wysylka_info) ?>
            </span>
        </div>

        <div class="pasek-zaufania-item">
            <span class="pasek-zaufania-icon">
                <span class="pasek-flag-pl">
                    <span class="pasek-flag-pl-top"></span>
                    <span class="pasek-flag-pl-bottom"></span>
                </span>
            </span>
            <span class="pasek-zaufania-text"><?= esc_html($prawy_tekst_wysylka) ?></span>
        </div>
    </div>
    <?php
}

// ====== PASEK ZAUFANIA POD PRZYCISKIEM ATC ======
add_action('woocommerce_after_add_to_cart_button', 'hoopsy_trust_bar', 10);
function hoopsy_trust_bar(): void
{
    global $product;
    if (!$product instanceof WC_Product) return;

    $id = (int) $product->get_id();
    $meta = hoopsy_get_shipping_meta($id);
    ?>
    <div class="pasek-zaufania-salient pasek-zaufania-salient--atc">

        <?php hoopsy_render_shipping_row($meta['left'], $meta['right']); ?>

        <!-- SEKCJA IKONEK -->
        <div class="pasek-platnosci">
            <img class="pasek-logo pasek-logo-inpost" src="<?= esc_url(HOOPSY_LOGO_INPOST) ?>" alt="InPost">
            <span class="pasek-separator"></span>

            <img class="pasek-logo pasek-logo-wz" src="<?= esc_url(HOOPSY_LOGO_WYGODNE_ZWROTY) ?>" alt="Wygodne Zwroty">
            <span class="pasek-separator"></span>

            <img class="pasek-logo pasek-logo-blik" src="<?= esc_url(HOOPSY_LOGO_BLIK) ?>" alt="BLIK">
            <span class="pasek-separator"></span>

            <img class="pasek-logo pasek-logo-p24" src="<?= esc_url(HOOPSY_LOGO_P24) ?>" alt="Przelewy24">
        </div>

        <!-- SEKCJA ZAUFANIA -->
        <div class="pasek-zaufania">
            <div class="pasek-zaufania-item">
                <span class="pasek-zaufania-icon">
                    <img src="<?= esc_url(HOOPSY_ICON_VERIFIED) ?>"
                         alt="Zweryfikowany sprzedawca">
                </span>
                <span class="pasek-zaufania-text">Polska firma<br>NIP &amp; REGON</span>
            </div>

            <div class="pasek-zaufania-item">
                <span class="pasek-zaufania-icon">
                    <img src="<?= esc_url(HOOPSY_ICON_LOCK) ?>"
                         alt="Ochrona danych i prywatności">
                </span>
                <span class="pasek-zaufania-text">Ochrona danych i prywatności</span>
            </div>
        </div>
    </div>
    <?php
}

// ====== FAQ ACCORDION ======
add_action('woocommerce_after_add_to_cart_button', 'hoopsy_faq_accordion', 25);
function hoopsy_faq_accordion(): void {
    global $product;
    if (!$product instanceof WC_Product) return;

    $pid = (int) $product->get_id();
    if (!in_array($pid, HOOPSY_FAQ_PRODUCT_IDS, true)) return;

    $faq_items = [
        [
            'q' => 'Jaki jest rozmiar? Zmieszczę się w pasie?',
            'a' => '<strong>Pasuje do 130 cm w pasie</strong> - jeśli potrzebujesz więcej to napisz do nas, dołożymy dodatkowe elementy.<br><br>Rozmiar regulujesz do swojej talii dodając lub odejmując elementy koła.',
        ],
        [
            'q' => 'Czy łatwo się je zakłada i zdejmuje?',
            'a' => '<strong>Tak! Łączysz i odpinasz elementy w sekundę.</strong> Zakładasz, naciskasz segment i gotowe – bez użycia siły.',
        ],
        [
            'q' => 'Dam radę? Nigdy wcześniej nie kręciłam.',
            'a' => '<strong>Tak! Ciężarek sunie gładko po szynie</strong> – wystarczy nadać mu pęd. Uda się każdemu maksymalnie po kilku próbach.',
        ],
        [
            'q' => 'Czy hałasuje? Innych firm są głośne.',
            'a' => '<strong>Nie. Hoopsy™ ma metalowe łożyska, więc jest cichsze.</strong> Słychać tylko szum – spokojnie obejrzysz przy nim serial, nie będziesz przeszkadzać sobie lub innym.',
        ],
        [
            'q' => 'Kiedy zobaczę pierwsze efekty?',
            'a' => 'Większość naszych klientek czuje pierwszy „luz" w spodniach już po <strong>14 dniach</strong> regularnego kręcenia (min. 15-20 minut dziennie). Wyraźne wysmuklenie talii i ujędrnienie zazwyczaj pojawiają się po pełnym, <strong>30-dniowym cyklu z naszym Planem Ćwiczeń</strong>.',
        ],
    ];
    ?>
    <div class="hoopsy-faq-wrapper">
        <div class="hoopsy-faq-title">Najczęściej zadawane pytania o Hoopsy™</div>

        <?php foreach ($faq_items as $item) : ?>
            <details class="hoopsy-faq-item">
                <summary><?= esc_html($item['q']) ?><span class="hoopsy-faq-arrow">▼</span></summary>
                <div class="hoopsy-faq-answer"><span class="hoopsy-faq-dot"></span><?= wp_kses_post($item['a']) ?></div>
            </details>
        <?php endforeach; ?>
    </div>
    <?php
}

// ====== WYSYŁKA + METODY DOSTAWY NAD TYTUŁEM ======
add_action('woocommerce_single_product_summary', 'hoopsy_wysylka_box_only', 3);
function hoopsy_wysylka_box_only(): void
{
    if (!is_product()) return;

    global $product;
    if (!$product instanceof WC_Product) return;

    $id = (int) $product->get_id();
    $meta = hoopsy_get_shipping_meta($id);
    ?>
    <div class="pasek-zaufania-salient pasek-zaufania-salient--shipping">

        <?php hoopsy_render_shipping_row($meta['left'], $meta['right']); ?>

        <!-- METODY DOSTAWY -->
        <div class="hoopsy-metody-inline">
            <span class="hoopsy-metoda-label">Paczkomat 24/7</span>
            <span class="hoopsy-metoda-separator"></span>
            <span class="hoopsy-metoda-label">Kurier</span>
            <span class="hoopsy-metoda-separator"></span>
            <span class="hoopsy-metoda-label">Płatność przy odbiorze</span>
        </div>

    </div>
    <?php
}

// ====== WŁASNY BLOK OCEN ======
add_action('after_setup_theme', 'hoopsy_setup_rating');
function hoopsy_setup_rating(): void {
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 5);
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10);
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 15);
    add_action('woocommerce_single_product_summary', 'hoopsy_custom_product_rating', 5);
}

function hoopsy_custom_product_rating(): void
{
    if (!is_product()) return;

    global $product;
    if (!$product instanceof WC_Product) return;

    $average = (float) $product->get_average_rating();
    $count   = (int) $product->get_review_count();

    if ($count === 0 || $average <= 0) return;

    $stars_html = wc_get_rating_html($average, $count);
    if (!$stars_html) return;

    $avg_display = str_replace('.', ',', wc_format_decimal($average, 1));

    echo '<div class="hoopsy-rating-wrap">';
    echo '<a href="#tab-reviews" class="hoopsy-rating-panel">';
    echo $stars_html;
    echo '<span class="hoopsy-rating-text">'
        . '<span class="hoopsy-rating-value">' . esc_html($avg_display) . '/5</span>'
        . ' (' . esc_html($count) . ') – Uwielbiane przez klientki.'
        . '</span>';
    echo '</a>';
    echo '</div>';
}

// ====== HURRYSTOCK SHORTCODE ======
add_shortcode('dn_hurrystock_inline', 'hoopsy_hurrystock_shortcode');
function hoopsy_hurrystock_shortcode(array|string $atts): string {
    $parsed = shortcode_atts([
        'id'     => 0,
        'label'  => 'Pozostało',
        'suffix' => 'szt.',
        'class'  => '',
    ], $atts, 'dn_hurrystock_inline');

    $id = absint($parsed['id']);
    if (!$id) return '';

    $class = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $parsed['class']);

    ob_start();
    ?>
    <span class="dn-hurrystock-inline dn-hurrystock-sale <?= esc_attr($class) ?>" data-dn-hurrystock-id="<?= $id ?>"><span class="dn-hurrystock-label"><?= esc_html($parsed['label']) ?></span><span class="dn-hurrystock-num"><span class="dn-mini-loader" aria-label="Wczytywanie"></span></span><span class="dn-hurrystock-suffix"><?= esc_html($parsed['suffix']) ?></span></span>
    <?php
    return trim(ob_get_clean());
}

// ====== BADGE HURRYSTOCK OBOK CENY (action hook) ======
add_action('woocommerce_single_product_summary', 'hoopsy_hurrystock_badge', 11);
function hoopsy_hurrystock_badge(): void {
    if (!is_product()) return;

    global $product;
    if (!$product instanceof WC_Product) return;
    if (!in_array((int) $product->get_id(), HOOPSY_HURRYSTOCK_PRODUCT_IDS, true)) return;

    $hs_id = HOOPSY_HURRYSTOCK_SOURCE_ID;
    echo '<span class="dn-price-hs dn-price-hs--standalone" aria-hidden="true">'
        . do_shortcode("[dn_hurrystock_inline id=\"{$hs_id}\" label=\"Pozostało\" suffix=\"szt.\"]")
        . '</span>';
}

// ====== HURRYSTOCK JS (inline, wp_footer) ======
add_action('wp_footer', 'hoopsy_hurrystock_js', 99);
function hoopsy_hurrystock_js(): void {
    if (!is_product()) return;

    global $product;
    if (!$product instanceof WC_Product) return;
    if (!in_array((int) $product->get_id(), HOOPSY_HURRYSTOCK_PRODUCT_IDS, true)) return;
    ?>
    <script>
    (function(){
      var HS_ID = <?php echo (int) HOOPSY_HURRYSTOCK_SOURCE_ID; ?>;

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
}

// ====== STICKY ADD TO CART (MOBILE) ======
add_action('wp_footer', 'hoopsy_sticky_atc', 99);
function hoopsy_sticky_atc(): void {
  if (!is_product()) return;

  global $product;
  if (!$product instanceof WC_Product) return;

  $pid = (int) $product->get_id();
  if (!in_array($pid, HOOPSY_STICKY_ATC_PRODUCT_IDS, true)) return;

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

  <script>
  (function(){
    var bar = document.getElementById('kc-sticky-atc');
    if(!bar) return;

    var MQ = window.matchMedia('(max-width: 768px)');
    var formCart = document.querySelector('form.cart, form.variations_form');
    var scrollTarget = formCart || document.querySelector('.single_add_to_cart_button') || document.querySelector('form.cart');

    var mainBtn = document.querySelector('.single_add_to_cart_button');
    var ebookBox = document.querySelector('.hoopsy-gratis-box');

    function toggleSticky(){
      if(!MQ.matches){
        bar.classList.remove('is-visible');
        bar.setAttribute('aria-hidden','true');
        document.body.classList.remove('kc-sticky-atc-on');
        return;
      }

      var show = false;

      if(ebookBox){
        var ebookRect = ebookBox.getBoundingClientRect();
        if(ebookRect.top + 65 < window.innerHeight) show = true;
      }
      else if(mainBtn){
        var btnRect = mainBtn.getBoundingClientRect();
        if(btnRect.bottom < 0) show = true;
      }

      if(mainBtn && show){
        var btnRect2 = mainBtn.getBoundingClientRect();
        var inView = btnRect2.top < window.innerHeight && btnRect2.bottom > 0;
        if(inView) show = false;
      }

      if(show){
        bar.classList.add('is-visible');
        bar.setAttribute('aria-hidden','false');
        document.body.classList.add('kc-sticky-atc-on');
      } else {
        bar.classList.remove('is-visible');
        bar.setAttribute('aria-hidden','true');
        document.body.classList.remove('kc-sticky-atc-on');
      }
    }

    var addLink   = bar.querySelector('.kc-sticky-atc__btn--add');
    var scrollBtn = bar.querySelector('.kc-sticky-atc__btn--scroll');

    if(addLink){
      addLink.addEventListener('click', function(){
        var q = 1;
        var mainQty = document.querySelector('form.cart input.qty');
        if(mainQty){
          var v = parseInt(mainQty.value, 10);
          if(Number.isFinite(v) && v > 0) q = v;
        }
        var url = new URL(addLink.href, window.location.origin);
        url.searchParams.set('quantity', String(q));
        addLink.href = url.toString();
      });
    }

    if(scrollBtn){
      scrollBtn.addEventListener('click', function(){
        if(!scrollTarget) return;
        scrollTarget.scrollIntoView({behavior:'smooth', block:'center'});
        if(formCart){
          formCart.classList.add('kc-sticky-highlight');
          setTimeout(function(){ formCart.classList.remove('kc-sticky-highlight'); }, 900);
        }
      });
    }

    toggleSticky();
    window.addEventListener('scroll', toggleSticky, {passive:true});
    window.addEventListener('resize', toggleSticky);
  })();
  </script>
  <?php
}

// ====== CHECKOUT: CENA REGULARNA POD CENĄ PROMOCYJNĄ ======
add_filter('woocommerce_cart_item_subtotal', 'hoopsy_checkout_show_regular_price', 10, 3);
function hoopsy_checkout_show_regular_price(string $subtotal, array $cart_item, string $cart_item_key): string
{
    if (!is_checkout()) return $subtotal;

    $product = $cart_item['data'];
    if (!$product->is_on_sale()) return $subtotal;

    $qty = $cart_item['quantity'];
    $regular_total = (float) $product->get_regular_price() * $qty;

    $subtotal = '<span class="hoopsy-prices-wrap">'
        . $subtotal
        . '<span class="hoopsy-old-price">'
        . wc_price($regular_total)
        . '</span></span>';

    return $subtotal;
}

// ====== CHECKOUT: PLUS/MINUS PRZY ILOŚCI ======
add_filter('woocommerce_checkout_cart_item_quantity', 'hoopsy_checkout_qty_buttons', 10, 3);
function hoopsy_checkout_qty_buttons(string $quantity_html, array $cart_item, string $cart_item_key): string
{
    $qty = $cart_item['quantity'];
    ob_start();
    ?>
    <span class="hoopsy-qty-wrap" data-cart-key="<?= esc_attr($cart_item_key) ?>">
        <button type="button" class="hoopsy-qty-btn hoopsy-qty-minus" aria-label="Mniej">−</button>
        <span class="hoopsy-qty-val"><?= esc_html($qty) ?></span>
        <button type="button" class="hoopsy-qty-btn hoopsy-qty-plus" aria-label="Więcej">+</button>
    </span>
    <?php
    return trim(ob_get_clean());
}

// ====== AJAX: AKTUALIZACJA ILOŚCI Z CHECKOUT ======
add_action('wp_ajax_hoopsy_update_qty', 'hoopsy_ajax_update_qty');
add_action('wp_ajax_nopriv_hoopsy_update_qty', 'hoopsy_ajax_update_qty');
function hoopsy_ajax_update_qty(): void
{
    check_ajax_referer('hoopsy_qty_nonce', 'nonce');

    $key = sanitize_text_field($_POST['cart_key'] ?? '');
    $qty = absint($_POST['qty'] ?? 0);

    if (!$key) {
        wp_send_json_error(['message' => 'Missing cart key']);
    }

    if ($qty === 0) {
        WC()->cart->remove_cart_item($key);
    } else {
        WC()->cart->set_quantity($key, $qty, true);
    }

    wp_send_json_success();
}

// ====== ZMIANA "ZAWIERA" NA "W TYM" PRZY VAT ======
// Filtr rejestrowany warunkowo – zero narzutu na stronach produktów / sklepu
add_action('template_redirect', 'hoopsy_setup_vat_text_filter');
function hoopsy_setup_vat_text_filter(): void {
    if (is_checkout() || is_cart()) {
        add_filter('gettext', 'hoopsy_change_vat_text', 10, 3);
    }
}
function hoopsy_change_vat_text(string $translated, string $text, string $domain): string
{
    if ($domain !== 'woocommerce') return $translated;

    if (strpos($translated, 'zawiera') !== false) {
        $translated = str_replace('zawiera', 'w tym', $translated);
    }
    if ($text === 'Billing details') {
        return 'Dane do zamówienia';
    }
    return $translated;
}

// ====== CHECKOUT: SKRYPTY (inline, wp_footer) ======
add_action('wp_footer', 'hoopsy_checkout_js', 999);
function hoopsy_checkout_js(): void {
    if (!is_checkout()) return;
?>
<script>
    var hoopsy_vars = { qty_nonce: '<?php echo wp_create_nonce("hoopsy_qty_nonce"); ?>' };

    /* Domyślna metoda płatności - PayU */
    document.addEventListener("DOMContentLoaded", function() {
        var payu = document.querySelector("#payment_method_payulistbanks");
        if (payu) { payu.checked = true; }
    });

    /* Opisy czasu dostawy pod metodami wysyłki */
    function addShippingDescriptions() {
      document.querySelectorAll('ul.woocommerce-shipping-methods li label').forEach(function(label) {
        if (!label.parentElement.querySelector('.shipping-method-description')) {
          var p = document.createElement('p');
          p.className = 'shipping-method-description';
          p.innerText = 'Czas dostawy - 1 dzień roboczy';
          label.insertAdjacentElement('afterend', p);
        }
      });
    }
    document.addEventListener('DOMContentLoaded', addShippingDescriptions);

    /* Walidacja adresu (numer domu) */
    (function(){
        var field, warn, row;
        function init(){
            field = document.getElementById('billing_address_1');
            if(!field) return;
            row = document.getElementById('billing_address_1_field');
            warn = document.createElement('div');
            warn.className = 'hoopsy-addr-warning';
            warn.textContent = 'Podaj również numer domu/mieszkania! (np. Przyjaźni 24/8)';
            field.parentNode.appendChild(warn);
            field.addEventListener('blur', function(){ checkField(false); });
            field.addEventListener('input', function(){ if(/\d/.test(field.value)) clearError(); });
        }
        function setError(scroll){
            field.classList.remove('hoopsy-addr-blink');
            void field.offsetWidth;
            field.classList.add('hoopsy-addr-blink', 'hoopsy-addr-error');
            if(row) row.classList.add('hoopsy-row-error');
            warn.style.display = 'block';
            if(scroll){
                var rect = field.getBoundingClientRect();
                var scrollTo = window.pageYOffset + rect.top - (window.innerHeight / 2) + (rect.height / 2);
                window.scrollTo({ top: scrollTo, behavior: 'smooth' });
            }
        }
        function clearError(){
            field.classList.remove('hoopsy-addr-blink', 'hoopsy-addr-error');
            if(row) row.classList.remove('hoopsy-row-error');
            warn.style.display = 'none';
        }
        function checkField(scroll){
            if(!field) return false;
            var val = field.value.trim();
            if(val.length > 0 && !/\d/.test(val)){ setError(scroll); return false; }
            return true;
        }
        document.addEventListener('DOMContentLoaded', function(){
            init();
            document.addEventListener('click', function(e){
                if(e.target && e.target.id === 'place_order') checkField(true);
            });
        });
        if(typeof jQuery !== 'undefined'){
            jQuery(document.body).on('checkout_error', function(){
                if(!field) return;
                var val = field.value.trim();
                if(val.length > 0 && !/\d/.test(val)){
                    document.querySelectorAll('.woocommerce-error li').forEach(function(li){
                        if(/numer domu/i.test(li.textContent)) li.style.display = 'none';
                    });
                    var errorList = document.querySelector('.woocommerce-error');
                    if(errorList){
                        var visible = Array.from(errorList.querySelectorAll('li')).filter(function(li){ return li.style.display !== 'none'; });
                        if(visible.length === 0) errorList.style.display = 'none';
                    }
                    setTimeout(function(){ jQuery('html, body').stop(true); setError(true); }, 50);
                }
            });
        }
    })();

    /* Setup checkout boxów + reorder */
    jQuery(function ($) {
        function setupCheckoutBoxes() {
            var table = $('.woocommerce-checkout-review-order-table');
            if (!table.length) return;
            $('.hoopsy-products-box').remove();
            $('.hoopsy-delivery-header-el').remove();
            $('.hoopsy-payment-header').remove();
            var tbody = table.find('tbody');
            if (!tbody.length || !tbody.find('tr').length) return;

            var productsBox = $('<div class="hoopsy-products-box"></div>');
            productsBox.append('<div class="hoopsy-box-header"><span class="hoopsy-step-num">2</span> DOSTAWA I PODSUMOWANIE</div>');
            var productsTable = $('<table class="hoopsy-box-table"></table>');
            productsTable.append(tbody.detach());
            productsBox.append(productsTable);

            var deliveryHeader = $('<div class="hoopsy-delivery-header-el hoopsy-box-header"><span class="hoopsy-step-num">1</span> KOSZYK Z PRODUKTAMI</div>');
            table.before(deliveryHeader);
            table.after(productsBox);
            table.find('thead').hide();

            var payment = $('#payment');
            if (payment.length && !payment.prev('.hoopsy-payment-header').length) {
                payment.before('<div class="hoopsy-payment-header hoopsy-box-header"><span class="hoopsy-step-num">4</span> METODA PŁATNOŚCI</div>');
            }
        }

        setupCheckoutBoxes();

        var billingH3 = $('.woocommerce-billing-fields h3');
        if (billingH3.length && !billingH3.find('.hoopsy-step-num').length) {
            billingH3.prepend('<span class="hoopsy-step-num">3</span> ');
        }

        mobileReorder();

        var savedScroll = null;
        $(document.body).on('update_checkout', function () { savedScroll = window.pageYOffset; });
        $(document.body).on('updated_checkout', function () {
            setupCheckoutBoxes();
            mobileReorder();
            addShippingDescriptions();
            if (savedScroll !== null) { window.scrollTo(0, savedScroll); savedScroll = null; }
        });

        var currentLayout = 'desktop';
        function mobileReorder() {
            if (window.innerWidth > 768) { if (currentLayout === 'mobile') desktopReorder(); return; }
            if (currentLayout === 'mobile') return;
            currentLayout = 'mobile';
            var customerDetails = $('#customer_details');
            var col1 = customerDetails.children('.col-1');
            if (!col1.length) return;
            var paymentHeader = $('.hoopsy-payment-header');
            var payment = $('#payment');
            customerDetails.append(paymentHeader);
            customerDetails.append(payment);
        }

        function desktopReorder() {
            currentLayout = 'desktop';
            var orderReview = $('#order_review');
            var paymentHeader = $('.hoopsy-payment-header');
            var payment = $('#payment');
            orderReview.append(paymentHeader);
            orderReview.append(payment);
        }

        $(window).on('resize', function () { mobileReorder(); });

        $(document).on('click', '.hoopsy-qty-btn', function () {
            var wrap = $(this).closest('.hoopsy-qty-wrap');
            var valEl = wrap.find('.hoopsy-qty-val');
            var qty = parseInt(valEl.text(), 10) || 1;
            var cartKey = wrap.data('cart-key');
            if ($(this).hasClass('hoopsy-qty-plus')) { qty++; }
            else { qty = Math.max(0, qty - 1); }
            valEl.text(qty);
            wrap.css('opacity', '0.5');
            $.post(wc_checkout_params.ajax_url, {
                action: 'hoopsy_update_qty',
                cart_key: cartKey,
                qty: qty,
                nonce: hoopsy_vars.qty_nonce
            }, function () { $(document.body).trigger('update_checkout'); });
        });
    });
</script>
<?php
}
