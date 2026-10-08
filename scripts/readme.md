## Деплой

`auto-deploy.sh` обычно запускает не человек, а GitHub Actions: пуш в `main` разворачивает прод, пуш в `dev` — stage
(`.github/workflows/deploy-main.yml` и `deploy-stage.yml`). Руками его зовут только когда workflow упал и надо
догнать вручную — из каталога темы на нужном сервере.

```bash
cd /var/www/eastproperty.com/public/wp-content/themes/east-property
bash ./scripts/auto-deploy.sh main

cd /var/www/stage.eastproperty.com/public/wp-content/themes/east-property
bash ./scripts/auto-deploy.sh dev
```

Ветка и путь сверяются между собой: `main` разворачивается только в `/var/www/eastproperty.com/public`, `dev` — только
в `/var/www/stage.eastproperty.com/public`. Ошибётесь каталогом — скрипт остановится, а не разложит stage на прод.

По шагам: обновляет репозиторий темы на указанную ветку с `reset --hard` и `clean -fd`, то же делает с `mu-plugins`
(и гоняет `composer install`, только если в них поменялись `composer.json`/`composer.lock`), затем `npm ci`, синхронизация
ACF JSON, сброс транзиентов и `rewrite flush --hard`, сборка ассетов `npm run build`, сброс объектного кэша и прогрев
`wp tools warm-cache`. Последние два шага не фатальны: если не вышли, деплой всё равно считается успешным.

Все локальные правки в теме и `mu-plugins` на сервере затираются — это не инструмент для хотфиксов на живом.

## Клон прода на stage

`tools.sh clone-to-stage` переносит базу прода на stage, переписывая домен. **Данные stage уничтожаются целиком.**
Запускать на сервере, где лежат обе установки.

```bash
cd /var/www/eastproperty.com/public/wp-content/themes/east-property
./scripts/tools.sh clone-to-stage -n          # только показать, что будет сделано
./scripts/tools.sh clone-to-stage
./scripts/tools.sh clone-to-stage -y          # без вопроса
./scripts/tools.sh clone-to-stage --keep-dump # оставить дамп после импорта
```

Перед первой записью идут проверки, и главная из них — имя базы: скрипт откажется работать, если у установки в
`STAGE_PATH` база называется не так, как ждёт `STAGE_DB`. Это то, что не даёт случайным запуском залить дамп обратно
на прод. Дополнительно сверяются пути (по реальным, с разыменованием симлинков), префиксы таблиц и доступность базы
stage.

Домен переписывается по схемам (`https://`, `http://`, `//`), а не подменой голого хоста, поэтому `info@eastproperty.com`
и `mail.eastproperty.com` остаются целыми; `guid` не трогается. В конце заново закрывается индексация, чистятся
транзиенты, объектный кэш и правила ссылок.

Дамп по умолчанию удаляется. С `--keep-dump` он остаётся в каталоге stage, то есть **под веб-рутом и доступен на
скачивание** — скрипт об этом предупреждает, файл надо унести или удалить.

## Закрыть stage от поисковиков

```bash
./scripts/tools.sh lock-stage
```

Ставит `blog_public = 0`. Нужно потому, что импорт дампа приносит опции прода и включает индексацию обратно, так что
`clone-to-stage` вызывает это сам в конце. Отдельно команда пригодится, если stage настраивали руками.

`robots.txt` намеренно оставлен открытым: `Disallow: /` помешал бы краулеру прочитать сам `noindex`.

В комментарии к скрипту упомянут `scripts/stage-nginx.conf` с заголовком `X-Robots-Tag` — **этого файла в репозитории
нет**, сниппет живёт только в конфиге nginx на сервере. Проверять так:

```bash
curl -sI https://stage.eastproperty.com/ | grep -i x-robots-tag
curl -s https://stage.eastproperty.com/ | grep -o "<meta name='robots'[^>]*>"
```

## Вернуть права после запуска от root

Любая команда `git`, `npm` или `composer`, выполненная на сервере от root, оставляет файлы, которые аккаунт деплоя
перезаписать не может, и следующий деплой встаёт на первом из них. Команда возвращает владельца и права и безопасна
для повторного запуска.

```bash
sudo ./scripts/tools.sh fix-permissions
sudo ./scripts/tools.sh fix-permissions -n    # показать, ничего не менять
```

Обходит обе установки, забирает `themes/east-property` и `mu-plugins` на `deploy:www-data`, ставит каталогам `2775`
(setgid, чтобы группа сохранялась у новых файлов) и файлам `664`, выключает reflog у клонов деплоя, делает
`auto-deploy.sh` и `tools.sh` исполняемыми, а `acf-json` — записываемым для веб-сервера: ACF пишет туда из браузера.
В конце проверяет результат, пробуя `git fetch --dry-run` от имени аккаунта деплоя.

Запускается только от root — иначе откажется, менять владельца всё равно нечем.

## Переводы темы

`make-translations.sh` пересобирает `.pot`, `.po`, `.mo` и JSON для JavaScript. Файл не исполняемый, поэтому через
`bash`. Запускать из каталога темы.

```bash
bash scripts/make-translations.sh

# на сервере
WP="wp --allow-root" bash scripts/make-translations.sh
```

Зачем скрипт, а не одна `wp i18n make-json`: тема отдаёт браузеру один бандл `assets/js/main.min.js`, а `make-json` по
умолчанию делает по JSON на каждый исходный js-файл и называет их хешем пути исходника — такие имена WordPress не ищет
и переводы не подхватывает. Скрипт строит карту «исходник → бандл» по факту из `src/js` и отдаёт её в `--use-map`,
получая ровно один файл с правильным именем.

Порядок шагов не случаен. `make-pot` пересобирает шаблон, затем **обязательный** `update-po`: `make-json` читает `.po`,
а не `.pot`, и если `.po` не синхронизирован, ссылок на js-файлы в нём нет и строки молча теряются. Дальше `make-mo`,
построение карты, удаление прежних `east-property-*.json` (файл с именем по handle затеняет свежий, потому что
`load_script_textdomain` ищет по handle раньше, чем по хешу пути) и `make-json`.

Последним шагом идёт проверка: скрипт собирает все строки, которые реально вызываются через `__`, `_x`, `_n`, `_nx` в
`src/js`, и сверяет с тем, что попало в JSON. Не хватает строк — выходит с ошибкой и печатает список. Это дешёвая
страховка и от забытого `update-po`, и от промаха карты.

Переопределяется через окружение: `WP`, `DOMAIN` (по умолчанию `east-property`), `BUNDLE`
(по умолчанию `assets/js/main.min.js`).

## Общее по tools.sh

```bash
./scripts/tools.sh help
```

Весь прогресс пишется в stderr, а не в stdout: значения читаются обратно через подстановку команд, и строка лога в
stdout попадала бы в значение. Поэтому прогон снимать `2>&1 | tee`, а не `>`.

Переопределяется через окружение: `PROD_PATH`, `STAGE_PATH`, `STAGE_DB`, `PROD_DOMAIN`, `STAGE_DOMAIN`, `DUMP_DIR`,
`DEPLOY_USER`, `WEB_GROUP`. Плюс `TOOLS_TRACE=1` — печатать каждый вызов wp-cli.

```bash
STAGE_DB=other_db ./scripts/tools.sh clone-to-stage
```
