# Прогрес Woo Starter

Оновлено після завершення завдання 06.

## Виконані завдання

| Завдання | Статус | Перевірюваний результат |
|---|---|---|
| 01 — Environment | Виконано | Коміт `efd9ab0` |
| 02 — Child theme | Виконано | Коміт `1b7416c` |
| 03 — Design tokens | Виконано | Коміт `4ad882a` |
| 04 — Presets | Виконано | Коміти `6e2c6a3`, `c15ee6b`; Service вибрано дефолтом |
| 05 — Catalog | Виконано | Коміт `bada1aa` |
| 06 — Personalization | Виконано | Модуль `inc/personalization.php` і перевірка `scripts/check-personalization.php` |
| 07 — Payments | PayPal підтверджено живим тестом; P24 відкладено | Замовлення #132 через PayPal sandbox → `processing`. P24 — ключами першого клієнта |

Завдання 08–12 не розпочинались.

## Результат завдання 06

- У даних товару є вкладка `Personalizacja` з увімкненням, типом поля, власною міткою, максимальною довжиною, обов’язковістю та доплатою.
- На сторінці товару показуються текстове поле, лічильник символів, додатковий вибір шрифту або кольору нитки та сума доплати.
- Сервер використовує `sanitize_text_field()` і обрізає текст до налаштованої максимальної довжини.
- Дані передаються через кошик у видимі meta позиції замовлення. Їх відображення в деталях замовлення та листах перевірено; exporter, який включає line-item meta, може прочитати їх через API позиції замовлення.
- Однаковий товар із різними текстами створює окремі позиції кошика.
- Демо-товар `Lniana koszula` має обов’язкову персоналізацію, вибір шрифту, ліміт 20 символів і доплату 20 zł.

## Перевірки завдання 06

Відтворювана команда:

```sh
docker compose --env-file docker/.env -f docker/docker-compose.yml -p woostarter \
  exec -T wpcli wp eval-file ../../../scripts/check-personalization.php
```

Результат: `Success: All seven personalization test cases passed.`

1. Текст видно в кошику — `PASS`.
2. Різні тексти створюють дві окремі позиції — `PASS`.
3. Текст є в згенерованому HTML листа клієнту — `PASS`.
4. Текст є в згенерованому HTML листа адміну — `PASS`.
5. Текст є у видимих meta позиції замовлення — `PASS`.
6. Польські літери та спеціальні символи після санітизації збережені — `PASS`.
7. Порожнє обов’язкове поле відхиляється сервером — `PASS`.

Додатково перевірено:

- доплата додається до ціни рівно один раз;
- багатобайтовий текст обрізається за кількістю символів, а не байтів;
- порожня необов’язкова персоналізація не блокує додавання товару;
- браузерний лічильник показав `14 / 20` для `Zażółć & Gęślą`;
- у реальному кошику одночасно відобразилися `Zażółć & Gęślą / Klasyczny` та `Anna / Nowoczesny`;
- `node --check theme/woostarter-child/assets/js/personalization.js` завершився без помилок;
- `node scripts/check-contrast.js` підтвердив усі обов’язкові пари WCAG AA;
- `git diff --check` завершився без помилок.

Окремий сторонній плагін CSV-експорту не встановлювався, тому його конкретний формат я не можу підтвердити. Самі видимі line-item meta збережені та доступні через WooCommerce API.

## Статус завдання 07

- Встановлено офіційний `woo-przelewy24` та `woocommerce-paypal-payments`.
- Додано розділені sandbox/live credentials через `.env` або `wp-config.php`.
- Статичні P24 і PayPal credentials не читаються з бази та блокуються перед записом у `wp_options`.
- Додано польську інструкцію `docs/platnosci.md`.
- Configuration checker пройшов на окремих фіктивних fixtures для sandbox і live; runtime clients обох плагінів використали правильний комплект без запису секретів у базу.
- Checkout без credentials відкривається без browser console errors і показує, що доступних методів оплати немає.
- Реальні оплата, серверна нотифікація та refund ще не підтверджені: для цього потрібні credentials двох sandbox-акаунтів і публічний HTTPS URL.
- Перевірено на практиці (2026-07-24): sandbox.przelewy24.pl вимагає спочатку **konto produkcyjne**, а воно потребує зареєстрованої działalność gospodarcza. Отримати sandbox без фірми не можна — припущення README про «sandbox без działalność» неактуальне.
- **Рішення:** 07 лишається «технічно готово». Живий P24 підтверджується ключами першого клієнта (у клієнта є фірма → є продакшн- і sandbox-ключі). Локальний end-to-end прогін checkout робимо через **PayPal sandbox** (безкоштовний, без фірми) — плагін PayPal уже встановлено.

## Живий тест PayPal sandbox (2026-07-24)

End-to-end оплата через PayPal sandbox пройдена й підтверджена з бекенду:

- Ключі PayPal sandbox (client_id / secret / merchant_id / merchant_email) внесені лише в `docker/.env` (gitignored), режим `sandbox`.
- `woostarter_payment_provider_is_configured('paypal')` = YES; `merchant_connected` / `use_manual_connection` = true; опція `woocommerce-ppcp-data-common` у БД **відсутня** — дані приходять тільки з env-фільтра.
- Живий токен PayPal з боку WP: HTTP 200.
- Тестове замовлення **#132**: статус `processing` (Przetwarzanie), `is_paid=yes`, total 138,00 PLN, метод `ppcp-gateway`, transaction ID `2VB97375UV9290240`, PayPal order `90N84173XN833942D`, intent CAPTURE, комісія записана (gross 138 / fee 4,80 / net 133,20).
- Секрет PayPal у `wp_options` **після** реальної транзакції: 0 збігів.
- Тимчасово додано метод доставки **Free shipping** у зону за замовчуванням, щоб checkout не блокувався (завдання 08 замінить це реальними InPost/Orlen). P24-гейтвеї лишаються без ключів — очікувано.
