<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Twigテンプレートライブラリ
 *
 */
class Twig {
    /**
     * config
     */
    const TWIG_CONFIG_FILE = 'twig';

    /**
     * テンプレート格納ディレクトリ
     */
    protected $template_dir;

    /**
     * キャッシュディレクトリ
     */
    protected $cache_dir;

    /**
     * 拡張子
     */
    protected $extension;

    /**
     * コントローラのインスタンス
     * @var CI_Controller
     */
    private $_ci;

    /**
     * 環境クラス
     * @var Twig_Environment
     */
    private $_env;

    /**
     * constructor
     */
    public function __construct()
    {
        $this->_ci =& get_instance();

        $this->_ci->config->load(self::TWIG_CONFIG_FILE, TRUE);
        $this->template_dir = $this->_ci->config->item('template_dir', self::TWIG_CONFIG_FILE);
        $this->cache_dir = $this->_ci->config->item('cache_dir', self::TWIG_CONFIG_FILE);
        $this->extension = $this->_ci->config->item('extension', self::TWIG_CONFIG_FILE);

        // 環境クラス
        $loader = new Twig_Loader_Filesystem($this->template_dir, $this->cache_dir);
        $this->_env = new Twig_Environment($loader, [
            'cache' => $this->cache_dir,
            'auto_reload' => TRUE
        ]);
    }

    /**
     * テンプレート展開
     * @param string $view ビュー名
     * @param array $vars 変数の連想配列
     * @param bool $return ロードされたビューを返すかどうか
     * @return  $return を TRUE に設定した場合は表示内容の文字列
     */
    public function view(string $view, array $vars = [], bool $return = FALSE)
    {
        // テンプレートをロード
        $ext = pathinfo($this->template_dir.$view, PATHINFO_EXTENSION);
        $template_path = ($ext === '') ? $view.$this->extension : $view;
        $template = $this->_env->loadTemplate($template_path);

        // フラグにより内容を返すか、描画するか判定
        if ($return) {
            return $template->render($vars);
        } else {
            $this->_ci->output->set_output($template->render($vars));
            return $this;
        }
    }
}
