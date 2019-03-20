<?php
/*
Plugin Name: The Updater
Description: Update plugins from git repository
Version: 1.0
Author: AS
*/



class AS_UPDATER
{

	public $REMOTE_SSH;
	//const DEPLOYMENT_KEY = '';
	public $TARGET_DIR;
	public $TARGET_NAME;
	public $BRANCH;
	public $slug;
	public $has_update = false;
	public $name;
	public $ERROR;


	public function __construct($TARGET_DIR, $slug, $REMOTE_SSH, $BRANCH, $TARGET_NAME)
	{
		$this->TARGET_DIR = $TARGET_DIR;
		$this->slug = $slug;
		$this->REMOTE_SSH = $REMOTE_SSH;
		$this->BRANCH = $BRANCH;
		$this->TARGET_NAME = $TARGET_NAME;

		add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
		add_action('admin_init', array($this, 'onAdminInit'));
		add_action('wp_ajax_plugin_update', [$this, 'pull']);
	}

	function enqueue_scripts($hook)
	{
		if ('plugins.php' == $hook) {
			wp_enqueue_script('updater_script', plugins_url('js/script.js', __FILE__), array('jquery'), '1.0.7');
			wp_enqueue_style('updater_style', plugins_url('css/style.css', __FILE__));
		}
	}

	/**
	 * checkRemote - проверяет наличие обновлений.
	 * Возвращает true при наличии и false при отсутствии
	 * @return bool
	 */
	public function checkRemote(){
		if (!is_dir($this->TARGET_DIR . '/.git')) {
			$this->ERROR = "Локальный репозиторий не найден";
			return false;
		}

		$local_hash = str_ireplace("'", '', exec("cd $this->TARGET_DIR && git log --pretty=format:'%H' -n 1"));
		$remote_hash = explode("\t", exec("cd $this->TARGET_DIR && git ls-remote $this->REMOTE_SSH"))[0];

		if (!$remote_hash) {
			$this->ERROR = "Ошибка соединения с удаленным репозиторием";
			return false;
		}

		$this->ERROR = null;

		if ($local_hash !== $remote_hash) {
			$this->has_update = true;
		} else {
			$this->has_update = false;
		}
		return $this->has_update;
	}


	public function pull(){
		$this->name = $_POST['namePluginForUpdate'];
		if (!$this->name) return false;

		$this->TARGET_DIR = str_ireplace(["/", "\\"], DIRECTORY_SEPARATOR, plugin_dir_path(__FILE__) . '..\\' . pathinfo($this->name)['dirname']);
		if ( $this->checkRemote() ) {
			$this->getArchive();
			exec("cd $this->TARGET_DIR && git stash && git pull");
		}
		return false;
	}

	public function getArchive(){
		$this->TARGET_DIR;
		exec("cd $this->TARGET_DIR && git archive --remote=$this->REMOTE_SSH $this->BRANCH --output=$this->TARGET_NAME-$this->BRANCH.tar");
	}


	public function onAdminInit() {
			add_filter('plugin_row_meta', array($this, 'addCheckForUpdatesLink'), 10, 2);
	}

	public function addCheckForUpdatesLink($pluginMeta, $pluginFile) {
		$file_name_1 = explode(DIRECTORY_SEPARATOR, str_ireplace(['/', '\\'], DIRECTORY_SEPARATOR, $pluginFile))[0] . '<br>';
		$file_name_2 = explode(DIRECTORY_SEPARATOR, str_ireplace(['/', '\\'], DIRECTORY_SEPARATOR, $this->TARGET_DIR));
		$file_name_2 = $file_name_2[count($file_name_2)-1];
		if ( false === strpos($file_name_1 , $file_name_2) ) return $pluginMeta;

			$linkText = ($this->checkRemote()) ? "Доступно обновление" : 'У вас актуальная версия';
			if ( !empty($linkText) ) {
				/** @noinspection HtmlUnknownTarget */
				$pluginMeta[] = sprintf('<a id="updatePlugin" class="%s" name="%s" style="%s"><strong>%s</strong></a>',
					(!$this->ERROR) ? 'ok' : '',
					$pluginFile,
					$style = ($this->has_update) ? 'color:#07c907' : (($this->ERROR) ? 'color:#ff0202' : '') ,
					$text = ($this->ERROR) ? $this->ERROR : $linkText);
			}
			return $pluginMeta;
	}

	public function removeHooks() {
		remove_action('admin_init', array($this, 'onAdminInit'));
		remove_filter('plugin_row_meta', array($this, 'addCheckForUpdatesLink'), 10);
	}

}
