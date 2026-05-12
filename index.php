<?php

declare(strict_types=1);

session_start();

$config = require __DIR__ . '/config.php';
date_default_timezone_set($config['site']['timezone'] ?? 'Europe/Moscow');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$site = $config['site'];
$siteUrl = rtrim((string) $site['site_url'], '/');
$canonical = $siteUrl . '/';
$phoneHref = 'tel:+' . preg_replace('/\D+/', '', (string) $site['phone_href']);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function svg_icon(string $name): string
{
    $icons = [
        'phone' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 10.8a15.5 15.5 0 0 0 6.6 6.6l2.2-2.2a1.4 1.4 0 0 1 1.4-.35c1.5.5 3.1.75 4.7.75.8 0 1.5.7 1.5 1.5v3.5c0 .8-.7 1.5-1.5 1.5C10.7 22.6 1.4 13.3 1.4 1.5 1.4.7 2.1 0 2.9 0h3.5c.8 0 1.5.7 1.5 1.5 0 1.6.25 3.2.75 4.7.15.5 0 1-.35 1.4l-2.2 2.2Z" transform="translate(.2 .7) scale(.92)"/></svg>',
        'calendar' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2v3M17 2v3M3.5 9.5h17M5.5 4h13A2.5 2.5 0 0 1 21 6.5v12A2.5 2.5 0 0 1 18.5 21h-13A2.5 2.5 0 0 1 3 18.5v-12A2.5 2.5 0 0 1 5.5 4Z"/></svg>',
        'arrow' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>',
        'check' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>',
        'map' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 18 3 21V6l6-3 6 3 6-3v15l-6 3-6-3Zm0 0V3m6 18V6"/></svg>',
        'bolt' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13 2 4 14h7l-1 8 10-13h-7l0-7Z"/></svg>',
        'shield' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/><path d="m8.5 11.5 2.3 2.3 4.9-5"/></svg>',
        'wrench' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14.7 6.3a4.7 4.7 0 0 0 5.1 6.5l-7.2 7.2a2.4 2.4 0 0 1-3.4-3.4l7.2-7.2a4.7 4.7 0 0 0-1.7-3.1Z"/><path d="M6.5 17.5 2 13l2.5-2.5L9 15"/></svg>',
    ];

    return $icons[$name] ?? $icons['check'];
}

$serviceOptions = [
    'Диагностика',
    'Замена масла / ТО',
    'Электрика / сигнализация',
    'Кондиционер',
    'АКПП',
    'Выхлопная система',
    'Подбор запчастей',
    'ТО китайского автомобиля',
    'Другое',
];

$trustChips = [
    'Рейтинг 5,0',
    '340 оценок',
    'Хорошее место 2026',
    'Гарантия на работы',
    'Запчасти под заказ',
    'Предварительная запись',
];

$problemCards = [
    ['title' => 'Загорелся чек', 'service' => 'Диагностика', 'hint' => 'Начнём с компьютерной диагностики и объясним ошибки понятным языком.'],
    ['title' => 'Нужно ТО или замена масла', 'service' => 'Замена масла / ТО', 'hint' => 'Подберём расходники и согласуем время обслуживания.'],
    ['title' => 'Проблема с электрикой', 'service' => 'Электрика / сигнализация', 'hint' => 'Опишите симптомы: не заводится, пропадает питание, ошибки, свет или оборудование.'],
    ['title' => 'Не работает кондиционер', 'service' => 'Кондиционер', 'hint' => 'Проверим систему и уточним, нужна ли заправка или ремонт.'],
    ['title' => 'Нужна сигнализация с автозапуском', 'service' => 'Электрика / сигнализация', 'hint' => 'Подскажем варианты после уточнения автомобиля и желаемых функций.'],
    ['title' => 'Проблема с выхлопом', 'service' => 'Выхлопная система', 'hint' => 'Осмотрим выхлоп, гофру, глушитель и согласуем объём работ.'],
    ['title' => 'Плохо греет печка', 'service' => 'Промывка радиатора печки', 'hint' => 'Часто помогает диагностика системы охлаждения и промывка радиатора отопителя.'],
    ['title' => 'Нужны запчасти', 'service' => 'Подбор запчастей', 'hint' => 'Можем подобрать детали под автомобиль и согласовать стоимость до ремонта.'],
    ['title' => 'Китайский автомобиль', 'service' => 'ТО китайского автомобиля', 'hint' => 'Обслуживаем Geely, Chery, Haval, Great Wall, Hover и другие марки.'],
    ['title' => 'Нужна диагностика перед ремонтом', 'service' => 'Диагностика', 'hint' => 'Это нормальный первый шаг, если причина неисправности не очевидна.'],
];

$categoryFilters = [
    'all' => 'Все',
    'maintenance' => 'ТО и масло',
    'diagnostics' => 'Диагностика',
    'transmission' => 'АКПП',
    'climate' => 'Кондиционер',
    'electric' => 'Электрика и сигнализации',
    'exhaust' => 'Выхлоп',
    'cooling' => 'Охлаждение и печка',
    'parts' => 'Запчасти',
    'china' => 'Китайские автомобили',
];

$services = [
    ['title' => 'Замена масла ДВС', 'category' => 'maintenance', 'price' => 'от 1200 ₽', 'description' => 'Замена масла в двигателе с подбором подходящих расходников под автомобиль.', 'service' => 'Замена масла / ТО'],
    ['title' => 'Комплексная диагностика', 'category' => 'diagnostics', 'price' => 'от 1000 ₽', 'description' => 'Проверка основных систем, чтобы понять причину неисправности до ремонта.', 'service' => 'Диагностика'],
    ['title' => 'Компьютерная диагностика', 'category' => 'diagnostics electric', 'price' => 'от 1000 ₽', 'description' => 'Считываем ошибки, смотрим параметры и объясняем, что они значат.', 'service' => 'Диагностика'],
    ['title' => 'Подбор и заказ автозапчастей', 'category' => 'parts', 'price' => 'от 100 ₽', 'description' => 'Подбираем и заказываем запасные части с предоставлением гарантии.', 'service' => 'Подбор запчастей'],
    ['title' => 'Замена масла в АКПП', 'category' => 'transmission maintenance', 'price' => 'от 2500 ₽', 'description' => 'Обслуживание автоматической коробки после осмотра и согласования расходников.', 'service' => 'АКПП'],
    ['title' => 'Заправка кондиционера', 'category' => 'climate', 'price' => 'от 3500 ₽', 'description' => 'Проверим работу системы и заправим кондиционер при исправном контуре.', 'service' => 'Кондиционер'],
    ['title' => 'Регулировка света фар', 'category' => 'electric diagnostics', 'price' => 'от 1000 ₽', 'description' => 'Настраиваем свет фар, чтобы дорога была видна, а встречный поток не слепило.', 'service' => 'Электрика / сигнализация'],
    ['title' => 'Установка сигнализации с автозапуском', 'category' => 'electric', 'price' => 'от 9000 ₽', 'description' => 'Устанавливаем сигнализации с автозапуском и согласуем решение под автомобиль.', 'service' => 'Электрика / сигнализация'],
    ['title' => 'Ремонт глушителя', 'category' => 'exhaust', 'price' => 'от 2500 ₽', 'description' => 'Ремонт выхлопной системы, замена гофры, пламегаситель, удаление катализатора.', 'service' => 'Выхлопная система'],
    ['title' => 'Промывка радиатора печки', 'category' => 'cooling climate', 'price' => 'от 5000 ₽', 'description' => 'Аппаратная промывка радиатора отопителя, если печка греет слабо.', 'service' => 'Промывка радиатора печки'],
    ['title' => 'ТО китайских автомобилей', 'category' => 'china maintenance transmission', 'price' => 'от 1500 ₽', 'description' => 'ТО китайских авто: двигатель, АКПП, раздатка, мосты, подбор расходников.', 'service' => 'ТО китайского автомобиля'],
    ['title' => 'Нормочас', 'category' => 'maintenance diagnostics electric', 'price' => '1000–2000 ₽', 'description' => 'Стоимость нормочаса зависит от задачи, автомобиля и объёма работ.', 'service' => 'Другое'],
];

$trustCards = [
    ['title' => 'Сначала диагностика — потом ремонт', 'text' => 'Объясняем, что нашли, какие есть варианты и что действительно нужно делать.', 'icon' => 'wrench'],
    ['title' => 'Запчасти можно заказать через сервис', 'text' => 'Подберём детали под автомобиль и согласуем стоимость до ремонта.', 'icon' => 'check'],
    ['title' => 'Сильная сторона — электрика и сигнализации', 'text' => 'Диагностика электрики, установка сигнализаций, работа с автозапуском и дополнительным оборудованием.', 'icon' => 'bolt'],
    ['title' => 'Понятная предварительная запись', 'text' => 'Лучше записаться заранее: так проще спланировать время и подготовить нужные детали.', 'icon' => 'calendar'],
    ['title' => 'Гарантия на работы', 'text' => 'Фиксируем выполненные работы и даём гарантию в рамках условий сервиса.', 'icon' => 'shield'],
    ['title' => 'Удобно ждать', 'text' => 'Есть Wi-Fi, туалет, парковка и зона ожидания.', 'icon' => 'check'],
];

$reviews = [
    'Обслуживают не первую машину, доверяют мастерам и отмечают понятные цены.',
    'Хвалят диагностику: находят причину, объясняют варианты ремонта и дают рекомендации.',
    'Отмечают работу с электрикой, сигнализациями и автозапуском.',
    'Пишут, что можно заказать запчасти через сервис и приехать уже на ремонт.',
    'Отмечают чистые боксы, вежливый персонал и нормальную зону ожидания.',
    'Рекомендуют записываться заранее — сервис востребован.',
];

$faqs = [
    ['q' => 'Нужно ли записываться заранее?', 'a' => 'Да, лучше заранее. Так можно согласовать время и подготовиться к работе.'],
    ['q' => 'Можно ли заказать запчасти через сервис?', 'a' => 'Да, сервис подбирает и заказывает запчасти с предоставлением гарантии.'],
    ['q' => 'Можно ли приехать только на диагностику?', 'a' => 'Да, можно начать с диагностики и после этого решить, какие работы делать.'],
    ['q' => 'Сколько стоит ремонт?', 'a' => 'Стоимость зависит от автомобиля и объёма работ. На сайте указаны ориентировочные цены по основным услугам.'],
    ['q' => 'Делаете ли китайские автомобили?', 'a' => 'Да, есть техническое обслуживание китайских автомобилей.'],
    ['q' => 'Можно ли оплатить картой?', 'a' => 'Да, доступны карта, наличные, безналичная оплата и банковский перевод.'],
    ['q' => 'Есть ли гарантия?', 'a' => 'Да, гарантия предоставляется на выполненные работы в рамках условий сервиса.'],
];

$jsonLdOffers = array_map(static fn (array $service): array => [
    '@type' => 'Offer',
    'name' => $service['title'],
    'description' => $service['description'],
    'priceCurrency' => 'RUB',
    'availability' => 'https://schema.org/InStock',
], $services);

$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'AutoRepair',
    '@id' => $canonical . '#autorepair',
    'name' => 'Смарт',
    'url' => $canonical,
    'telephone' => '+7 925 670-94-35',
    'priceRange' => '₽₽',
    'address' => [
        '@type' => 'PostalAddress',
        'addressLocality' => 'Ногинск',
        'streetAddress' => 'ул. 8 Марта, 3',
        'addressRegion' => 'Московская область',
        'addressCountry' => 'RU',
    ],
    'areaServed' => [
        'Ногинск',
        'Богородский городской округ',
        'Московская область',
    ],
    'aggregateRating' => [
        '@type' => 'AggregateRating',
        'ratingValue' => '5.0',
        'reviewCount' => '340',
    ],
    'makesOffer' => $jsonLdOffers,
];

function render_form(string $idPrefix, array $serviceOptions, string $csrfToken, string $canonical, bool $withCar = false, string $title = 'Быстрая заявка'): void
{
    ?>
    <form class="lead-form" id="<?= e($idPrefix) ?>-form" action="/submit.php" method="post" data-smart-form novalidate>
        <div class="lead-form__head">
            <h3><?= e($title) ?></h3>
            <p>Заявка не обязывает к ремонту — сначала уточним проблему и удобное время.</p>
        </div>

        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        <input type="hidden" name="page_url" value="<?= e($canonical) ?>">
        <input type="hidden" name="utm_source" value="">
        <input type="hidden" name="utm_medium" value="">
        <input type="hidden" name="utm_campaign" value="">
        <input type="hidden" name="utm_content" value="">
        <input type="hidden" name="utm_term" value="">
        <label class="hp-field" aria-hidden="true">
            Сайт компании
            <input type="text" name="company_website" tabindex="-1" autocomplete="off">
        </label>

        <div class="field">
            <label for="<?= e($idPrefix) ?>-name">Имя <span aria-hidden="true">*</span></label>
            <input id="<?= e($idPrefix) ?>-name" name="name" type="text" autocomplete="name" placeholder="Например, Андрей" minlength="2" required>
            <span class="field-error" data-error-for="name"></span>
        </div>

        <div class="field">
            <label for="<?= e($idPrefix) ?>-phone">Телефон <span aria-hidden="true">*</span></label>
            <input id="<?= e($idPrefix) ?>-phone" name="phone" type="tel" autocomplete="tel" inputmode="tel" placeholder="+7 (___) ___-__-__" required>
            <span class="field-error" data-error-for="phone"></span>
        </div>

        <?php if ($withCar): ?>
            <div class="field">
                <label for="<?= e($idPrefix) ?>-car">Марка и модель автомобиля</label>
                <input id="<?= e($idPrefix) ?>-car" name="car" type="text" autocomplete="off" placeholder="Например, Haval Jolion">
                <span class="field-error" data-error-for="car"></span>
            </div>
        <?php else: ?>
            <input type="hidden" name="car" value="">
        <?php endif; ?>

        <div class="field">
            <label for="<?= e($idPrefix) ?>-service">Что нужно сделать?</label>
            <select id="<?= e($idPrefix) ?>-service" name="service" data-service-select>
                <option value="Не выбрано">Не выбрано</option>
                <?php foreach ($serviceOptions as $option): ?>
                    <option value="<?= e($option) ?>"><?= e($option) ?></option>
                <?php endforeach; ?>
            </select>
            <span class="field-hint" data-service-hint>Можно выбрать позже, если причина пока непонятна.</span>
            <span class="field-error" data-error-for="service"></span>
        </div>

        <div class="field">
            <label for="<?= e($idPrefix) ?>-message">Комментарий</label>
            <textarea id="<?= e($idPrefix) ?>-message" name="message" rows="4" placeholder="Опишите симптомы, марку авто или удобное время"></textarea>
            <span class="field-error" data-error-for="message"></span>
        </div>

        <div class="form-consents">
            <label class="checkbox-line">
                <input type="checkbox" name="consent_personal_data" value="1" required>
                <span>Даю согласие на обработку персональных данных для обработки моей заявки и обратной связи. <a href="/docs/personal-data-consent.html" target="_blank" rel="noopener">Открыть согласие</a></span>
            </label>
            <span class="field-error" data-error-for="consent_personal_data"></span>

            <label class="checkbox-line">
                <input type="checkbox" name="consent_policy" value="1" required>
                <span>Подтверждаю, что ознакомлен(а) с Политикой обработки персональных данных. <a href="/docs/privacy-policy.html" target="_blank" rel="noopener">Открыть политику</a></span>
            </label>
            <span class="field-error" data-error-for="consent_policy"></span>
        </div>

        <button class="btn btn--primary btn--full" type="submit">
            <span>Отправить заявку</span>
            <?= svg_icon('arrow') ?>
        </button>

        <p class="form-note">Ответим и уточним удобное время для записи. Стоимость зависит от автомобиля и объёма работ.</p>
        <div class="form-status" role="status" aria-live="polite"></div>
    </form>
    <?php
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Автосервис Смарт в Ногинске — диагностика, ТО, электрика и ремонт авто</title>
    <meta name="description" content="Автосервис “Смарт” в Ногинске на ул. 8 Марта, 3: диагностика, ТО, замена масла, автоэлектрика, сигнализации, кондиционер, запчасти под заказ. Рейтинг 5,0, предварительная запись.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= e($canonical) ?>">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="ru_RU">
    <meta property="og:title" content="Автосервис Смарт в Ногинске — диагностика, ТО, электрика и ремонт авто">
    <meta property="og:description" content="Понятная диагностика, согласование работ, запчасти под заказ и предварительная запись на ул. 8 Марта, 3 в Ногинске.">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <meta property="og:image" content="<?= e($siteUrl) ?>/assets/img/service-map.svg">
    <meta name="theme-color" content="#0F1720">
    <script>document.documentElement.classList.add('js');</script>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?></script>
</head>
<body>
    <a class="skip-link" href="#main">Перейти к содержанию</a>

    <header class="site-header" data-header>
        <div class="container header-grid">
            <a class="brand" href="#top" aria-label="Смарт, автосервис в Ногинске">
                <span class="brand__mark">С</span>
                <span>
                    <strong>Смарт</strong>
                    <small>автосервис в Ногинске</small>
                </span>
            </a>

            <nav class="nav" id="site-nav" aria-label="Основная навигация">
                <a href="#services">Услуги</a>
                <a href="#prices">Цены</a>
                <a href="#process">Как работаем</a>
                <a href="#reviews">Отзывы</a>
                <a href="#contacts">Контакты</a>
            </nav>

            <div class="header-actions">
                <a class="header-phone" href="<?= e($phoneHref) ?>"><?= e($site['phone']) ?></a>
                <a class="btn btn--small btn--primary" href="#lead-form">Записаться</a>
                <button class="burger" type="button" aria-label="Открыть меню" aria-controls="site-nav" aria-expanded="false" data-menu-toggle>
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
    </header>

    <main id="main">
        <section class="hero section-dark" id="top">
            <div class="container hero-grid">
                <div class="hero-copy reveal">
                    <div class="eyebrow">Ногинск, ул. 8 Марта, 3</div>
                    <h1>Автосервис “Смарт” в Ногинске: диагностика, ТО, электрика и ремонт без лишних работ</h1>
                    <p class="hero-lead">Разберёмся с причиной неисправности, подберём запчасти, согласуем работы и запишем на удобное время. Адрес: ул. 8 Марта, 3.</p>

                    <div class="trust-chips" aria-label="Преимущества автосервиса">
                        <?php foreach ($trustChips as $chip): ?>
                            <span><?= e($chip) ?></span>
                        <?php endforeach; ?>
                    </div>

                    <div class="hero-actions">
                        <a class="btn btn--primary" href="#lead-form">Записаться на диагностику</a>
                        <a class="btn btn--secondary" href="#prices">Посмотреть услуги и цены</a>
                    </div>

                    <div class="service-map" aria-label="Сервисная карта автомобиля">
                        <img src="/assets/img/service-map.svg" width="720" height="420" alt="Сервисная карта автомобиля: диагностика, согласование, ремонт и рекомендации в автосервисе Смарт">
                    </div>
                </div>

                <aside class="hero-form reveal" id="lead-form">
                    <?php render_form('hero', $serviceOptions, (string) $_SESSION['csrf_token'], $canonical, false, 'Быстрая заявка'); ?>
                </aside>
            </div>
        </section>

        <section class="section problem-picker" id="services">
            <div class="container">
                <div class="section-head">
                    <span class="eyebrow">Быстрый выбор</span>
                    <h2>Что нужно сделать с автомобилем?</h2>
                    <p>Выберите ближайшую ситуацию — мы подставим услугу в заявку и подскажем, с чего начать.</p>
                </div>

                <div class="problem-grid">
                    <?php foreach ($problemCards as $card): ?>
                        <button class="problem-card reveal" type="button" data-select-service="<?= e($card['service']) ?>" data-service-hint="<?= e($card['hint']) ?>">
                            <span><?= e($card['title']) ?></span>
                            <?= svg_icon('arrow') ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="section section-soft">
            <div class="container">
                <div class="section-head">
                    <span class="eyebrow">Доверие</span>
                    <h2>Почему в “Смарт” возвращаются на обслуживание</h2>
                </div>

                <div class="trust-grid">
                    <?php foreach ($trustCards as $card): ?>
                        <article class="info-card reveal">
                            <span class="info-card__icon"><?= svg_icon($card['icon']) ?></span>
                            <h3><?= e($card['title']) ?></h3>
                            <p><?= e($card['text']) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="section" id="prices">
            <div class="container">
                <div class="section-head section-head--wide">
                    <div>
                        <span class="eyebrow">Услуги и цены</span>
                        <h2>Основные услуги и ориентировочные цены</h2>
                    </div>
                    <p>Финальная стоимость зависит от марки автомобиля, состояния узлов и объёма работ. Точную цену уточним после диагностики или осмотра.</p>
                </div>

                <div class="filter-row" role="tablist" aria-label="Фильтр услуг">
                    <?php foreach ($categoryFilters as $slug => $label): ?>
                        <button class="filter-btn<?= $slug === 'all' ? ' is-active' : '' ?>" type="button" data-filter="<?= e($slug) ?>"><?= e($label) ?></button>
                    <?php endforeach; ?>
                </div>

                <div class="service-grid">
                    <?php foreach ($services as $service): ?>
                        <article class="service-card reveal" data-category="<?= e($service['category']) ?>">
                            <div>
                                <h3><?= e($service['title']) ?></h3>
                                <p><?= e($service['description']) ?></p>
                            </div>
                            <div class="service-card__footer">
                                <strong><?= e($service['price']) ?></strong>
                                <button class="text-action" type="button" data-select-service="<?= e($service['service']) ?>" data-service-hint="<?= e('Выбрана услуга: ' . $service['title'] . '. Оставьте телефон, и мы уточним детали.') ?>">
                                    Записаться <?= svg_icon('arrow') ?>
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="section diagnostics section-dark">
            <div class="container split-grid">
                <div class="reveal">
                    <span class="eyebrow">Диагностика без гадания</span>
                    <h2>Не меняем всё подряд — сначала ищем причину</h2>
                    <p>Если машина ведёт себя странно, загорелся чек, появились ошибки, проблемы с запуском, электрикой, кондиционером или ходовой — начните с диагностики. Так проще не тратить деньги на лишние детали и понять реальный объём ремонта.</p>
                    <button class="btn btn--primary" type="button" data-select-service="Диагностика" data-service-hint="Диагностика — хороший первый шаг, если причина неисправности пока не ясна.">Записаться на диагностику</button>
                </div>

                <ol class="route-steps reveal">
                    <li><span>1</span><p>Вы описываете проблему</p></li>
                    <li><span>2</span><p>Мы проводим диагностику</p></li>
                    <li><span>3</span><p>Объясняем варианты ремонта</p></li>
                    <li><span>4</span><p>Согласовываем работы и стоимость</p></li>
                </ol>
            </div>
        </section>

        <section class="section electric-block">
            <div class="container split-grid">
                <div class="accent-panel reveal">
                    <span class="panel-icon"><?= svg_icon('bolt') ?></span>
                    <h2>Автоэлектрика, сигнализации и автозапуск</h2>
                    <p>Берёмся за диагностику электрических неисправностей и согласовываем решение после проверки. Без обещаний наугад и без лишних работ в смете.</p>
                    <button class="btn btn--primary" type="button" data-select-service="Электрика / сигнализация" data-service-hint="Опишите, что происходит с электрикой или оборудованием. Мастер уточнит детали перед записью.">Уточнить стоимость ремонта</button>
                </div>

                <div class="tag-list reveal" aria-label="Направления автоэлектрики">
                    <span>установка сигнализации с автозапуском</span>
                    <span>диагностика электрики</span>
                    <span>ремонт стартеров и генераторов</span>
                    <span>установка парктроников</span>
                    <span>установка фаркопа</span>
                    <span>дополнительное оборудование</span>
                    <span>ремонт подушек безопасности</span>
                    <span>поиск сложных электрических неисправностей</span>
                </div>
            </div>
        </section>

        <section class="section section-soft china-block">
            <div class="container split-grid">
                <div class="reveal">
                    <span class="eyebrow">Растущий спрос</span>
                    <h2>ТО китайских автомобилей в Ногинске</h2>
                    <p>Проводим техническое обслуживание китайских автомобилей: замена масла в двигателе, АКПП, раздатке, переднем и заднем мосту. Подберём расходники и запчасти под конкретную модель.</p>
                    <p class="muted">Работаем с Geely, Chery, Haval, Great Wall, Hover и другими китайскими автомобилями.</p>
                    <button class="btn btn--primary" type="button" data-select-service="ТО китайского автомобиля" data-service-hint="Для ТО китайского автомобиля укажите марку, модель и пробег в комментарии.">Записаться на ТО</button>
                </div>
                <div class="brand-cloud reveal" aria-label="Марки китайских автомобилей">
                    <span>Geely</span><span>Chery</span><span>Haval</span><span>Great Wall</span><span>Hover</span><span>Exeed</span>
                </div>
            </div>
        </section>

        <section class="section" id="process">
            <div class="container">
                <div class="section-head">
                    <span class="eyebrow">Маршрут ремонта</span>
                    <h2>Как проходит запись и ремонт</h2>
                    <p>Спокойный порядок помогает заранее понимать следующий шаг и не принимать решения в спешке.</p>
                </div>

                <ol class="process-list">
                    <li class="reveal"><span>01</span><p>Оставляете заявку или звоните</p></li>
                    <li class="reveal"><span>02</span><p>Описываете проблему и марку автомобиля</p></li>
                    <li class="reveal"><span>03</span><p>Согласуем удобное время</p></li>
                    <li class="reveal"><span>04</span><p>Проводим диагностику или осмотр</p></li>
                    <li class="reveal"><span>05</span><p>Называем объём работ и ориентировочную стоимость</p></li>
                    <li class="reveal"><span>06</span><p>После согласования выполняем ремонт</p></li>
                    <li class="reveal"><span>07</span><p>Передаём автомобиль и рекомендации</p></li>
                </ol>
            </div>
        </section>

        <section class="section section-dark" id="reviews">
            <div class="container reviews-grid">
                <div class="reveal">
                    <span class="eyebrow">Отзывы</span>
                    <h2>Клиенты отмечают качество, честность и понятные объяснения</h2>
                    <p>Коротко пересказываем частые смыслы из отзывов. Длинные тексты не копируем дословно.</p>
                    <p class="source-note">по данным карточки на Яндекс Картах</p>
                </div>

                <div class="rating-panel reveal" aria-label="Показатели доверия">
                    <strong>5,0</strong><span>рейтинг</span>
                    <strong>340</strong><span>оценок</span>
                    <strong>176</strong><span>отзывов</span>
                    <strong>2026</strong><span>Хорошее место</span>
                </div>

                <div class="review-cards">
                    <?php foreach ($reviews as $review): ?>
                        <article class="review-card reveal">
                            <p><?= e($review) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="section faq">
            <div class="container">
                <div class="section-head">
                    <span class="eyebrow">FAQ</span>
                    <h2>Частые вопросы</h2>
                </div>

                <div class="faq-list">
                    <?php foreach ($faqs as $faq): ?>
                        <details class="faq-item reveal">
                            <summary><?= e($faq['q']) ?></summary>
                            <p><?= e($faq['a']) ?></p>
                        </details>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="section contacts section-soft" id="contacts">
            <div class="container contacts-grid">
                <div class="reveal">
                    <span class="eyebrow">Контакты</span>
                    <h2>Приезжайте в “Смарт” на ул. 8 Марта, 3</h2>
                    <dl class="contact-list">
                        <div><dt>Сервис</dt><dd>Смарт</dd></div>
                        <div><dt>Адрес</dt><dd>Ногинск, ул. 8 Марта, 3</dd></div>
                        <div><dt>Телефон</dt><dd><a href="<?= e($phoneHref) ?>"><?= e($site['phone']) ?></a></dd></div>
                        <div><dt>Формат</dt><dd>предварительная запись, парковка, Wi-Fi, туалет, оплата картой</dd></div>
                        <div><dt>Оплата</dt><dd>наличные, банковская карта, банковский перевод, безналичная оплата, онлайн</dd></div>
                        <div><dt>Доступность</dt><dd>есть парковка для людей с инвалидностью и доступный вход; помещение недоступно на инвалидной коляске</dd></div>
                        <div><dt>Кешбэк</dt><dd>5% на все покупки</dd></div>
                    </dl>
                    <div class="contact-actions">
                        <a class="btn btn--primary" href="<?= e($phoneHref) ?>"><?= svg_icon('phone') ?> Позвонить</a>
                        <a class="btn btn--secondary" href="https://yandex.ru/maps/?text=<?= rawurlencode('Смарт автосервис Ногинск ул. 8 Марта, 3') ?>" target="_blank" rel="noopener"><?= svg_icon('map') ?> Построить маршрут</a>
                        <a class="btn btn--ghost" href="#final-form">Оставить заявку</a>
                    </div>
                </div>

                <div class="map-card map-card--live reveal">
                    <iframe title="Яндекс Карта: автосервис Смарт, Ногинск, ул. 8 Марта, 3" loading="lazy" src="https://yandex.ru/map-widget/v1/?text=<?= rawurlencode('Смарт автосервис Ногинск ул. 8 Марта, 3') ?>&z=16" allowfullscreen></iframe>
                    <a class="map-card__route" href="https://yandex.ru/maps/?text=<?= rawurlencode('Смарт автосервис Ногинск ул. 8 Марта, 3') ?>" target="_blank" rel="noopener">Открыть в Яндекс Картах</a>
                </div>
            </div>
        </section>

        <section class="section final-cta section-dark" id="final-form">
            <div class="container final-grid">
                <div class="reveal">
                    <span class="eyebrow">Предварительная запись</span>
                    <h2>Опишите проблему — подскажем, с чего начать</h2>
                    <p>Если проблема не очевидна, не нужно гадать по форумам и менять детали наугад. Оставьте заявку, мастер уточнит симптомы и предложит следующий шаг.</p>
                    <ul class="check-list">
                        <li><?= svg_icon('check') ?> диагностика и объяснение причины</li>
                        <li><?= svg_icon('check') ?> согласование работ до ремонта</li>
                        <li><?= svg_icon('check') ?> подбор запчастей под автомобиль</li>
                    </ul>
                </div>

                <div class="reveal">
                    <?php render_form('final', $serviceOptions, (string) $_SESSION['csrf_token'], $canonical, true, 'Заявка на запись'); ?>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container footer-grid">
            <div>
                <strong>Смарт</strong>
                <p>Автосервис в Ногинске: диагностика автомобиля, ТО, замена масла, автоэлектрик, сигнализации, кондиционер, ремонт глушителя, ТО китайских автомобилей и подбор автозапчастей.</p>
            </div>
            <div>
                <a href="/docs/privacy-policy.html">Политика обработки персональных данных</a>
                <a href="/docs/personal-data-consent.html">Согласие на обработку персональных данных</a>
            </div>
            <div>
                <a href="<?= e($phoneHref) ?>"><?= e($site['phone']) ?></a>
                <span>Ногинск, ул. 8 Марта, 3</span>
            </div>
        </div>
    </footer>

    <div class="mobile-cta" aria-label="Быстрые действия">
        <a href="<?= e($phoneHref) ?>"><?= svg_icon('phone') ?> Позвонить</a>
        <a href="#lead-form"><?= svg_icon('calendar') ?> Записаться</a>
    </div>

    <script src="/assets/js/main.js" defer></script>
</body>
</html>
