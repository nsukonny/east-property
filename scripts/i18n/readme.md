# Каталог интерфейса на новый язык

Два шага. Первый отправляет `languages/east-property.pot` в DeepL, второй
собирает `.po` из ответа и карты переопределений. Оба повторяемы: перевод
кэшируется в опции `ep_deepl_cache`, так что второй прогон почти бесплатен.

```bash
cd ~/Projects/eastproperty.com/app/public/wp-content/themes/east-property
OUT=/tmp/i18n-zh && mkdir -p "$OUT" && cp scripts/i18n/overrides.json "$OUT/"

EP_OUT="$OUT" EP_TARGET=ZH-HANS wp eval-file scripts/i18n/translate-pot.php
EP_OUT="$OUT" EP_LOCALE=zh_CN wp eval-file scripts/i18n/build-po.php

bash scripts/make-translations.sh
```

`overrides.json` — точные замены по msgid, они всегда побеждают ответ DeepL.
Ключ с контекстом пишется как `context\u0004msgid`.

Зачем они: на отраслевых терминах DeepL уверенно ошибается, и ошибка выглядит
правдоподобно. `Property` приходит как 属性 (свойство объекта в коде),
`Secondary` как 中学 (средняя школа), `Villa` как 村庄 (деревня), `Studio` как
研究 (исследование). При этом `Attributes` в ярлыках типов записей — именно
属性, поэтому замена идёт по целой строке, а не по слову.

После сборки обязательно сверить плейсхолдеры: `-%d%%` DeepL однажды вернул как
«百分比», то есть выкинул оба. Проверка — сравнить наборы `%s`, `%d`, `%%` в
msgid и msgstr по всему файлу.

Китайский — один plural (`nplurals=1; plural=0;`), это задаётся `EP_PLURAL`.
