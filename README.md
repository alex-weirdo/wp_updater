## WP_Updater
Решение для обновления плагинов WP

### Установка
* Скопировать в /wp-content/plugins/
* Туда же (/wp-content/plugins/) клонировать нужный плагин из репозитория
* В файл обновляемого плагина вставить код
```
if ( class_exists(AS_UPDATER::class) ) {
	$TARGET_DIR = __DIR__;
	$slug = "slug";
	$REMOTE_SSH = 'git@git.101m.ru:user/repository.git';
	$BRANCH = 'master';
	$TARGET_NAME = 'archive';
	new AS_UPDATER($TARGET_DIR, $slug, $REMOTE_SSH, $BRANCH, $TARGET_NAME);
}
```
* В панели администратора включить плагин The Updater
Рядом с обновляемыми плагинами появится информация о наличии/отсутствии обновлений