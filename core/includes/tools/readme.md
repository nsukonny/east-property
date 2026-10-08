## Переводы

Переводим при помощи Deepl все unit и property у которых указан need_translate

```bash
wp tools translate-units --post-type=unit --limit=10 --dry-run --allow-root 
wp tools translate-units --post-type=property --limit=10 --dry-run --allow-root 
wp tools translate-units --limit=10 --allow-root        # оба типа, unit и property
wp tools translate-units --post-id=33067 --allow-root   # тип определяется сам
```

Перевод локаций. Переводы описаний находятся в мета description_<slug>, берется список тех у которых пусто и создается
перевод из английской версии

```bash
wp tools translate-locations --limit=10 --dry-run --allow-root
wp tools translate-locations --language=ru --limit=10 --allow-root
wp tools translate-locations --term-id=141 --force --allow-root
```
