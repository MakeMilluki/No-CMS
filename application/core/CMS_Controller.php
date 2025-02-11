<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * CMS_Controller class.
 *
 * @author gofrendi
 */
class CMS_Controller extends MX_Controller
{
    // going to be deprecated
    public $PRIV_EVERYONE = PRIV_EVERYONE;
    public $PRIV_NOT_AUTHENTICATED = PRIV_NOT_AUTHENTICATED;
    public $PRIV_AUTHENTICATED = PRIV_AUTHENTICATED;
    public $PRIV_AUTHORIZED = PRIV_AUTHORIZED;
    public $PRIV_EXCLUSIVE_AUTHORIZED = PRIV_EXCLUSIVE_AUTHORIZED;

    protected $__cms_dynamic_widget = false;
    private $__cms_widgets = null;
    private $__cms_navigations = null;
    private $__cms_navigation_path = null;
    private $__cms_navigation_name = null;
    private $__cms_quicklinks = null;
    protected $__cms_base_model_name = 'no_cms_autoupdate_model';

    protected function _guard_controller()
    {
        $module_path = $this->cms_module_path();
        // in case of module is not installed, but the naughty user add navigation manually, show module not installed message
        // however if description.txt doesn't exists, than it has nothing todo with the module
        if ($module_path != 'main' && $module_path != '' && file_exists(FCPATH.'modules/'.$module_path.'/description.txt')) {
            if ($this->cms_module_name($module_path) == '') {
                die('<pre>ERROR : Module '.$module_path.' is not installed</pre>');
            }
        }
    }

    public function __construct()
    {
        parent::__construct();

        // for the first-time installation, it might not load main configuration even if the main configuration
        // is already exists. Thus we need to explicitly code it
        if(CMS_SUBSITE == '' && ENVIRONMENT == 'first-time' && file_exists(APPPATH.'config/main/database.php')){
            unset($db);
            include(APPPATH.'config/main/database.php');
            $this->load->database($db['default']);
        }else{
            $this->load->database();
        }

        // load helpers and libraries
        $this->load->helper('url');
        $this->load->helper('html');
        $this->load->helper('form');
        $this->load->helper('string');
        $this->load->helper('cms_helper');
        $this->load->library('form_validation');
        $this->form_validation->CI = &$this;
        $this->load->driver('session');

        $this->JQUERY_PATH = base_url('assets/grocery_crud/js/jquery-1.11.1.min.js');

        $module_path = $this->cms_module_path();
        $this->load->model($this->__cms_base_model_name);
        $this->{$this->__cms_base_model_name}->__controller_module_path = $module_path;

        // unpublished modules should never be accessed.
        if (CMS_SUBSITE != '' && $module_path != 'main' && $module_path != '') {
            $subsite_auth_file = FCPATH.'modules/'.$module_path.'/subsite_auth.php';
            if (file_exists($subsite_auth_file)) {
                unset($public);
                unset($subsite_allowed);
                include $subsite_auth_file;
                if (isset($public) && is_bool($public) && !$public) {
                    if (!isset($subsite_allowed) || (is_array($subsite_allowed) && !in_array(CMS_SUBSITE, $subsite_allowed))) {
                        die('Module is not accessible for '.CMS_SUBSITE.' subsite');
                    }
                }
            }
        }

        $this->_guard_controller();

        if (isset($_REQUEST['__cms_dynamic_widget'])) {
            $this->__cms_dynamic_widget = true;
        }

        if (!$this->__cms_dynamic_widget) {
            // if there is old_url, then save it
            $old_url = $this->session->userdata('cms_old_url');
        }

        if (!isset($_COOKIE['__sso_login'])) {
            // just use for temporary fix
            $_COOKIE['__sso_login'] = false;
        }
        if (!$this->input->is_ajax_request() && !$this->__cms_dynamic_widget && !$_COOKIE['__sso_login'] && $this->__cms_base_model_name == 'no_cms_autoupdate_model' && CMS_SUBSITE != '' && USE_SUBDOMAIN && $this->cms_user_id() <= 0) {
            setcookie('__sso_login', true, time() + 600, '/');
            if ($this->input->get('__origin') == null || $this->input->get('__token') == null) {
                include BASEPATH.'../hostname.php';
                $url = current_url();
                $ssl = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? true : false;
                $sp = strtolower($_SERVER['SERVER_PROTOCOL']);
                $protocol = substr($sp, 0, strpos($sp, '/')).(($ssl) ? 's' : '');
                $port = $_SERVER['SERVER_PORT'];
                $port = ((!$ssl && $port == '80') || ($ssl && $port == '443')) ? '' : ':'.$port;
                $host = isset($hostname) ? $hostname : $_SERVER['SERVER_NAME'].$port;
                $redirection = $protocol.'://'.$host.'/index.php/main/check_login?__origin='.urlencode($url).'&__server_name='.$_SERVER['SERVER_NAME'];
                redirect($redirection);
            }
        }

        /*
        if(!$this->input->is_ajax_request()){
            $this->output->enable_profiler(1);
        }*/
    }

    protected function cms_unique_field_name($field_name)
    {
        return $this->{$this->__cms_base_model_name}->cms_unique_field_name($field_name);
    }

    protected function cms_random_string($length = 10)
    {
        return $this->{$this->__cms_base_model_name}->cms_random_string($length);
    }

    /*
    * @ usage $this->t('purchase')
    */
    protected function t($table_name, $alias = null)
    {
        return $this->{$this->__cms_base_model_name}->t($table_name, $alias);
    }

    protected function n($navigation_name)
    {
        return $this->{$this->__cms_base_model_name}->n($navigation_name);
    }

    /**
     * @author goFrendiAsgard
     * @desc   get default_controller
     */
    protected function cms_get_default_controller()
    {
        return $this->{$this->__cms_base_model_name}->cms_get_default_controller();
    }

    /**
     * @author goFrendiAsgard
     *
     * @param string $value
     * @desc   set default_controller to value
     */
    protected function cms_set_default_controller($value)
    {
        $this->{$this->__cms_base_model_name}->cms_set_default_controller($value);
    }

    /**
     * @author goFrendiAsgard
     *
     * @param string $hostname
     * @param int    $port
     * @desc   is it able to go to some site?
     */
    protected function cms_is_connect($hostname = null, $port = 80)
    {
        return $this->{$this->__cms_base_model_name}->cms_is_connect($hostname, $port);
    }

    /**
     * @author goFrendiAsgard
     *
     * @return Grocery_CRUD
     * @desc   return Grocery_CRUD
     */
    protected function new_crud()
    {
        $this->load->library('Extended_grocery_crud');
        $crud = new Extended_grocery_crud();
        $crud->set_theme('no-flexigrid');

        return $crud;
    }

    /**
     * @author goFrendiAsgard
     *
     * @param string $table_name
     *
     * @return string
     * @desc   return complete table name
     */
    protected function cms_complete_table_name($table_name, $module_name = null)
    {
        return $this->{$this->__cms_base_model_name}->cms_complete_table_name($table_name, $module_name);
    }

    /**
     * @author goFrendiAsgard
     *
     * @param string $navigation_name
     *
     * @return string
     * @desc   return complete navigation name
     */
    protected function cms_complete_navigation_name($navigation_name, $module_name = null)
    {
        return $this->{$this->__cms_base_model_name}->cms_complete_navigation_name($navigation_name, $module_name);
    }

    /**
     * @author goFrendiAsgard
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return mixed
     * @desc   if value specified, this will set CI_Session["key"], else it will return CI_session["key"]
     */
    protected function cms_ci_session($key, $value = null)
    {
        return $this->{$this->__cms_base_model_name}->cms_ci_session($key, $value);
    }

    /**
     * @author goFrendiAsgard
     *
     * @param string $key
     * @desc   unset CI_session["key"]
     */
    protected function cms_unset_ci_session($key)
    {
        return $this->{$this->__cms_base_model_name}->cms_unset_ci_session($key);
    }

    /**
     * @author goFrendiAsgard
     *
     * @param string $user_name
     *
     * @return mixed
     * @desc   set or get CI_Session["cms_user_name"]
     */
    protected function cms_user_name($user_name = null)
    {
        return $this->{$this->__cms_base_model_name}->cms_user_name($user_name);
    }

    /**
     * @author goFrendiAsgard
     *
     * @param string $real_name
     *
     * @return mixed
     * @desc   set or get CI_Session["cms_user_real_name"]
     */
    protected function cms_user_real_name($real_name = null)
    {
        return $this->{$this->__cms_base_model_name}->cms_user_real_name($real_name);
    }

    /**
     * @author goFrendiAsgard
     *
     * @param string $email
     *
     * @return mixed
     * @desc   set or get CI_Session["cms_user_email"]
     */
    protected function cms_user_email($email = null)
    {
        return $this->{$this->__cms_base_model_name}->cms_user_email($email);
    }

    /**
     * @author goFrendiAsgard
     *
     * @param int $user_id
     * @desc   set or get CI_Session["cms_user_id"]
     */
    protected function cms_user_id($user_id = null)
    {
        return $this->{$this->__cms_base_model_name}->cms_user_id($user_id);
    }

    /**
     * @author goFrendiAsgard
     *
     * @return array
     */
    protected function cms_user_group()
    {
        return $this->{$this->__cms_base_model_name}->cms_user_group();
    }

    /**
     * @author goFrendiAsgard
     *
     * @return array
     */
    protected function cms_user_group_id()
    {
        return $this->{$this->__cms_base_model_name}->cms_user_group_id();
    }

    /**
     * @author goFrendiAsgard
     *
     * @return bool
     * @desc   TRUE if current user is super admin, FALSE otherwise
     */
    protected function cms_user_is_super_admin()
    {
        return $this->{$this->__cms_base_model_name}->cms_user_is_super_admin();
    }

    protected function cms_do_move_widget_after($src_widget_id, $dst_widget_id)
    {
        $this->{$this->__cms_base_model_name}->cms_do_move_widget_after($src_widget_id, $dst_widget_id);
    }

    protected function cms_do_move_widget_before($src_widget_id, $dst_widget_id)
    {
        $this->{$this->__cms_base_model_name}->cms_do_move_widget_before($src_widget_id, $dst_widget_id);
    }

    protected function cms_do_move_quicklink_after($src_quicklink_id, $dst_quicklink_id)
    {
        $this->{$this->__cms_base_model_name}->cms_do_move_quicklink_after($src_quicklink_id, $dst_quicklink_id);
    }

    protected function cms_do_move_quicklink_before($src_quicklink_id, $dst_quicklink_id)
    {
        $this->{$this->__cms_base_model_name}->cms_do_move_quicklink_before($src_quicklink_id, $dst_quicklink_id);
    }

    protected function cms_do_move_navigation_before($src_navigation_id, $dst_navigation_id)
    {
        $this->{$this->__cms_base_model_name}->cms_do_move_navigation_before($src_navigation_id, $dst_navigation_id);
    }

    protected function cms_do_move_navigation_after($src_navigation_id, $dst_navigation_id)
    {
        $this->{$this->__cms_base_model_name}->cms_do_move_navigation_after($src_navigation_id, $dst_navigation_id);
    }

    protected function cms_do_move_navigation_into($src_navigation_id, $dst_navigation_id)
    {
        $this->{$this->__cms_base_model_name}->cms_do_move_navigation_into($src_navigation_id, $dst_navigation_id);
    }

    /**
     * @author  goFrendiAsgard
     *
     * @param   string navigation_name
     * @desc    move navigation up
     */
    protected function cms_do_move_up_navigation($navigation_name)
    {
        $this->{$this->__cms_base_model_name}->cms_do_move_up_navigation($navigation_name);
    }

    /**
     * @author  goFrendiAsgard
     *
     * @param   string navigation_name
     * @desc    move navigation down
     */
    protected function cms_do_move_down_navigation($navigation_name)
    {
        $this->{$this->__cms_base_model_name}->cms_do_move_down_navigation($navigation_name);
    }

    /**
     * @author  goFrendiAsgard
     *
     * @param   string widget_name
     * @desc    move widget up
     */
    protected function cms_do_move_up_widget($widget_name)
    {
        $this->{$this->__cms_base_model_name}->cms_do_move_up_widget($widget_name);
    }

    /**
     * @author  goFrendiAsgard
     *
     * @param   string widget_name
     * @desc    move widget down
     */
    protected function cms_do_move_down_widget($widget_name)
    {
        $this->{$this->__cms_base_model_name}->cms_do_move_down_widget($widget_name);
    }

    /**
     * @author  goFrendiAsgard
     *
     * @param   int quicklink id
     * @desc    move quicklink up
     */
    protected function cms_do_move_up_quicklink($quicklink_id)
    {
        $this->{$this->__cms_base_model_name}->cms_do_move_up_quicklink($quicklink_id);
    }

    /**
     * @author  goFrendiAsgard
     *
     * @param   int quicklink id
     * @desc    move quicklink down
     */
    protected function cms_do_move_down_quicklink($quicklink_id)
    {
        $this->{$this->__cms_base_model_name}->cms_do_move_down_quicklink($quicklink_id);
    }

    protected function cms_user_table_name()
    {
        return $this->{$this->__cms_base_model_name}->cms_user_table_name();
    }

    protected function cms_chipper()
    {
        return $this->{$this->__cms_base_model_name}->cms_chipper();
    }

    /**
     * @author  goFrendiAsgard
     *
     * @param   int parent_id
     * @param   int max_menu_depth
     * @desc    return navigation child if parent_id specified, else it will return root navigation
     *           the max depth of menu is depended on max_menud_depth
     */
    protected function cms_navigations($parent_id = null, $max_menu_depth = null)
    {
        return $this->{$this->__cms_base_model_name}->cms_navigations($parent_id, $max_menu_depth);
    }

    /**
     * @author goFrendiAsgard
     *
     * @return mixed
     * @desc   return quick links
     */
    protected function cms_quicklinks()
    {
        return $this->{$this->__cms_base_model_name}->cms_quicklinks();
    }

    /**
     * @author  goFrendiAsgard
     *
     * @param   slug
     * @param   widget_name
     *
     * @return mixed
     * @desc    return widgets
     */
    protected function cms_widgets($slug = null, $widget_name = null)
    {
        return $this->{$this->__cms_base_model_name}->cms_widgets($slug, $widget_name);
    }

    /**
     * @author  goFrendiAsgard
     *
     * @param   string navigation_name
     *
     * @return string
     * @desc    return url of navigation
     */
    protected function cms_navigation_url($navigation_name)
    {
        return $this->{$this->__cms_base_model_name}->cms_navigation_url($navigation_name);
    }

    /**
     * @author  goFrendiAsgard
     *
     * @param   string navigation_name
     *
     * @return string
     * @desc    return submenu screen
     */
    /**
     * @author  goFrendiAsgard
     *
     * @param   string navigation_name
     *
     * @return string
     * @desc    return submenu screen
     */
    protected function cms_submenu_screen($navigation_name)
    {
        $submenus = array();
        if (!isset($navigation_name)) {
            $submenus = $this->cms_navigations(null, 1);
        } else {
            $query = $this->db->select('navigation_id')
                ->from(cms_table_name('main_navigation'))
                ->where('navigation_name', $navigation_name)
                ->get();
            if ($query->num_rows() > 0) {
                $row = $query->row();
                $navigation_id = $row->navigation_id;
                $submenus = $this->cms_navigations($navigation_id, 1);
            } else {
                return '';
            }
        }

        $html = '
        <script type="text/javascript">
            function __adjust_component(identifier){
                var max_height = 0;
                $(identifier).each(function(){
                    $(this).css("margin-bottom", 0);
                    if($(this).height()>max_height){
                        max_height = $(this).height();
                    }
                });
                $(identifier).each(function(){
                    $(this).height(max_height);
                    var margin_bottom = 0;
                    if($(this).height()<max_height){
                        margin_bottom = max_height - $(this).height();
                    }
                    margin_bottom += 10;
                    $(this).css("margin-bottom", margin_bottom);
                });
            }
            function __adjust_thumbnail_submenu(){
                __adjust_component(".thumbnail_submenu img");
                __adjust_component(".thumbnail_submenu div.caption");
                __adjust_component(".thumbnail_submenu");
            }
            $(window).load(function(){
                __adjust_thumbnail_submenu();
                // resize
                $(window).resize(function(){
                    __adjust_thumbnail_submenu();
                });
            });
        </script>';

        $html .= '<div class="row">';
        $module_path = $this->cms_module_path();
        $image_directories = array();
        if ($module_path != '') {
            $image_directories[] = "modules/$module_path/assets/navigation_icon";
        }
        $image_directories[] = 'assets/nocms/navigation_icon';
        foreach ($this->cms_get_module_list() as $module_list) {
            $other_module_path = $module_list['module_path'];
            $image_directories[] = "modules/$other_module_path/assets/navigation_icon";
        }
        $submenu_count = count($submenus);
        foreach ($submenus as $submenu) {
            $navigation_id = $submenu['navigation_id'];
            $navigation_name = $submenu['navigation_name'];
            $title = $submenu['title'];
            $url = $submenu['url'];
            $description = $submenu['description'];
            $allowed = $submenu['allowed'];
            $notif_url = $submenu['notif_url'];
            if (!$allowed) {
                continue;
            }

            // check image in current module

            $image_file_names = array();
            $image_file_names[] = $navigation_name.'.png';
            if ($module_path !== '' && $module_path !== 'main') {
                $module_prefix = cms_module_prefix($this->cms_module_path());
                if (substr($navigation_name, 0, strlen($module_prefix)) == $module_prefix) {
                    $image_file_names[] = substr($navigation_name, strlen($module_prefix) + 1).'.png';
                }
            }
            $image_file_path = '';
            foreach ($image_directories as $image_directory) {
                foreach ($image_file_names as $image_file_name) {
                    $image_file_path = $image_directory.'/'.$image_file_name;
                    if (!file_exists(FCPATH.$image_file_path)) {
                        $image_file_path = '';
                    }
                    if ($image_file_path !== '') {
                        break;
                    }
                }
                if ($image_file_path !== '') {
                    break;
                }
            }

            $badge = '';
            if ($notif_url != '') {
                $badge_id = '__cms_notif_submenu_screen_'.$navigation_id;
                $badge = '&nbsp;<span id="'.$badge_id.'" class="badge"></span>';
                $badge .= '<script type="text/javascript">
                        $(window).load(function(){
                            setInterval(function(){
                                $.ajax({
                                    dataType:"json",
                                    url: "'.addslashes($notif_url).'",
                                    success: function(response){
                                        if(response.success){
                                            $("#'.$badge_id.'").html(response.notif);
                                        }
                                        __adjust_thumbnail_submenu();
                                    }
                                });
                            }, 300000);
                        });
                    </script>
                ';
            }

            // default icon
            if ($image_file_path == '') {
                $image_file_path = 'assets/nocms/images/icons/package.png';
            }
            $html .= '<a href="'.$url.'" style="text-decoration:none;">';
            if ($submenu_count <= 2) {
                $html .= '<div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">';
            } elseif ($submenu_count % 3 == 0) {
                $html .= '<div class="col-xs-12 col-sm-4 col-md-4 col-lg-4">';
            } else {
                $html .= '<div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">';
            }
            $html .= '<div class="thumbnail thumbnail_submenu">';

            if ($image_file_path != '') {
                $html .= '<img style="margin-top:10px; max-height:60px;" src="'.base_url($image_file_path).'" />';
            }

            $html .= '<div class="caption">';
            $html .= '<h4>'.$title.$badge.'</h4>';
            $html .= '<p>'.$description.'</p>';
            $html .= '</div>'; // end of div.caption
            $html .= '</div>'; // end of div.thumbnail
            $html .= '</div>'; // end of div.col-xs-6 col-sm-4 col-md-3
            $html .= '</a>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * @author  goFrendiAsgard
     *
     * @param   string navigation_name
     *
     * @return mixed
     * @desc    return navigation path, used for layout
     **/
    protected function cms_get_navigation_path($navigation_name = null)
    {
        return $this->{$this->__cms_base_model_name}->cms_get_navigation_path($navigation_name);
    }

    /**
     * @author  goFrendiAsgard
     *
     * @return mixed
     * @desc    return privileges of current user
     */
    protected function cms_privileges()
    {
        return $this->{$this->__cms_base_model_name}->cms_privileges();
    }

    /**
     * @author  goFrendiAsgard
     *
     * @param   string navigation_name
     *
     * @return bool
     * @desc    check if user authorized to navigate into a page specified in parameter
     */
    protected function cms_allow_navigate($navigation_name)
    {
        return $this->{$this->__cms_base_model_name}->cms_allow_navigate($navigation_name);
    }

    /**
     * @author  goFrendiAsgard
     *
     * @param   string privilege_name
     *
     * @return bool
     * @desc    check if user have privilege specified in parameter
     */
    protected function cms_have_privilege($privilege_name)
    {
        return $this->{$this->__cms_base_model_name}->cms_have_privilege($privilege_name);
    }

    protected function cms_route_key_exists($route_key)
    {
        return $this->{$this->__cms_base_model_name}->cms_route_key_exists($route_key);
    }

    /**
     * @author  goFrendiAsgard
     *
     * @param   string identity
     * @param   string password
     *
     * @return bool
     * @desc    login with identity and password. Identity can be user_name or e-mail
     */
    protected function cms_do_login($identity, $password)
    {
        return $this->{$this->__cms_base_model_name}->cms_do_login($identity, $password);
    }

    /**
     * @author  goFrendiAsgard
     * @desc    logout
     */
    protected function cms_do_logout()
    {
        $this->{$this->__cms_base_model_name}->cms_do_logout();
    }

    /**
     * @author  goFrendiAsgard
     *
     * @param   string user_name
     * @param   string email
     * @param   string real_name
     * @param   string password
     * @desc    register new user
     */
    protected function cms_do_register($user_name, $email, $real_name, $password)
    {
        return $this->{$this->__cms_base_model_name}->cms_do_register($user_name, $email, $real_name, $password);
    }

    /**
     * @author  goFrendiAsgard
     *
     * @param   string user_name
     * @param   string email
     * @param   string real_name
     * @param   string password
     * @desc    change current profile (user_name, email, real_name and password)
     */
    protected function cms_do_change_profile($email, $real_name, $password = null, $user_id = null)
    {
        return $this->{$this->__cms_base_model_name}->cms_do_change_profile($email, $real_name, $password, $user_id);
    }

    /**
     * @author  goFrendiAsgard
     *
     * @param   string module_name
     *
     * @return bool
     * @desc    checked if module installed
     */
    protected function cms_is_module_active($module_name)
    {
        return $this->{$this->__cms_base_model_name}->cms_is_module_active($module_name);
    }

    /**
     * @author  goFrendiAsgard
     *
     * @return mixed
     * @desc    get module list
     */
    protected function cms_get_module_list($keyword = null)
    {
        return $this->{$this->__cms_base_model_name}->cms_get_module_list($keyword);
    }

    protected function cms_module_version($module_name = null)
    {
        return $this->{$this->__cms_base_model_name}->cms_module_version();
    }

    /**
     * @author  goFrendiAsgard
     *
     * @param   string module_name
     *
     * @return string
     * @desc    get module_path (folder name) of specified module_name (name space)
     */
    protected function cms_module_path($module_name = null)
    {
        if ($module_name === null) {
            $module_path = '';
            $reflector = new ReflectionObject($this);
            $file_name = $reflector->getFilename();
            if (strpos($file_name, FCPATH.'modules') === 0) {
                $file_name = trim(str_replace(FCPATH.'modules', '', $file_name), DIRECTORY_SEPARATOR);
                $file_name_part = explode(DIRECTORY_SEPARATOR, $file_name);
                if (count($file_name_part) >= 2) {
                    $module_path = $file_name_part[0];
                }
            }

            return $module_path;
        } else {
            return $this->{$this->__cms_base_model_name}->cms_module_path($module_name);
        }
    }

    /**
     * @author  goFrendiAsgard
     *
     * @param   string module_path
     *
     * @return string
     * @desc    get module_name (name space) of specified module_path (folder name)
     */
    protected function cms_module_name($path = null)
    {
        return $this->{$this->__cms_base_model_name}->cms_module_name($path);
    }

    /**
     * @author  goFrendiAsgard
     *
     * @return mixed
     * @desc    get layout list
     */
    protected function cms_get_theme_list($keyword = null)
    {
        return $this->{$this->__cms_base_model_name}->cms_get_theme_list($keyword);
    }

    /**
     * @author  goFrendiAsgard
     *
     * @param   string identity
     * @param	bool send_mail
     * @param   string reason (FORGOT, SIGNUP)
     *
     * @return bool
     * @desc    generate activation code, and send email to applicant
     */
    protected function cms_generate_activation_code($identity, $send_mail = false, $reason = 'FORGOT')
    {
        return $this->{$this->__cms_base_model_name}->cms_generate_activation_code($identity, $send_mail, $reason);
    }

    /**
     * @author  goFrendiAsgard
     *
     * @param   string activation_code
     * @param   string new_password
     *
     * @return bool success
     * @desc    activate user
     */
    protected function cms_activate_account($activation_code, $new_password = null)
    {
        return $this->{$this->__cms_base_model_name}->cms_activate_account($activation_code, $new_password);
    }

    protected function _cms_set_user_subsite_activation($user_id, $active)
    {
        return $this->{$this->__cms_base_model_name}->_cms_set_user_subsite_activation($user_id, $active);
    }

    /**
     * @author  goFrendiAsgard
     *
     * @param   string from_address
     * @param   string from_name
     * @param   string to_address
     * @param   string subject
     * @param   string message
     * @desc    send email
     */
    protected function cms_send_email($from_address, $from_name, $to_address, $subject, $message)
    {
        return $this->{$this->__cms_base_model_name}->cms_send_email($from_address, $from_name, $to_address, $subject, $message);
    }

    protected function cms_resize_image($file_name, $nWidth, $nHeight)
    {
        $this->{$this->__cms_base_model_name}->cms_resize_image($file_name, $nWidth, $nHeight);
    }

    /**
     * @author  goFrendiAsgard
     *
     * @param   string activation_code
     *
     * @return bool
     * @desc    validate activation_code
     */
    protected function cms_valid_activation_code($activation_code)
    {
        return $this->{$this->__cms_base_model_name}->cms_valid_activation_code($activation_code);
    }

    /**
     * @author  goFrendiAsgard
     *
     * @param   string name
     * @param   string value
     * @param   string description
     * @desc    set config variable
     */
    protected function cms_set_config($name, $value, $description = null)
    {
        return $this->{$this->__cms_base_model_name}->cms_set_config($name, $value, $description);
    }

    /**
     * @author  goFrendiAsgard
     *
     * @param   string name
     * @desc    unset configuration variable
     */
    protected function cms_unset_config($name)
    {
        return $this->{$this->__cms_base_model_name}->cms_unset_config($name);
    }

    /**
     * @author  goFrendiAsgard
     *
     * @param   string name, bool raw
     *
     * @return string
     * @desc    get configuration variable
     */
    protected function cms_get_config($name, $raw = false)
    {
        return $this->{$this->__cms_base_model_name}->cms_get_config($name, $raw);
    }

    /**
     * @author	goFrendiAsgard
     *
     * @param	string language
     *
     * @return string language
     * @desc	set language for this session only
     */
    protected function cms_language($language = null)
    {
        return $this->{$this->__cms_base_model_name}->cms_language($language);
    }

    /**
     * @author	goFrendiAsgard
     *
     * @return array list of available languages
     * @desc	get available languages
     */
    protected function cms_language_list()
    {
        return $this->{$this->__cms_base_model_name}->cms_language_list();
    }

    /**
     * @author  goFrendiAsgard
     *
     * @param   string key
     *
     * @return string
     * @desc    get translation of key in site_language
     */
    protected function cms_lang($key, $module = null)
    {
        return $this->{$this->__cms_base_model_name}->cms_lang($key, $module);
    }

    /**
     * @author goFrendiAsgard
     *
     * @param  string value
     *
     * @return string
     * @desc   parse keyword like @site_url and @base_url
     */
    protected function cms_parse_keyword($value)
    {
        return $this->{$this->__cms_base_model_name}->cms_parse_keyword($value);
    }

    /**
     * @author goFrendiAsgard
     *
     * @param  string user_name
     *
     * @return bool
     * @desc   check if user already exists
     */
    protected function cms_is_user_exists($identity, $exception_user_id = 0)
    {
        return $this->{$this->__cms_base_model_name}->cms_is_user_exists($identity, $exception_user_id);
    }

    /**
     * @author goFrendiAsgard
     *
     * @param  string url_string
     *
     * @return bool
     * @desc   guess the navigation name of an url
     */
    protected function cms_navigation_name($url_string = null)
    {
        if (!isset($url_string)) {
            $url_string = $this->uri->uri_string();
        }

        if ($this->db->platform() == 'pdo' && $this->db->subdriver == 'sqlite') {
            $url_pattern = "url || '%'";
        } else {
            $url_pattern = "CONCAT(url, '%')";
        }
        $SQL = 'SELECT navigation_name
        	FROM '.cms_table_name('main_navigation')."
        	WHERE '".addslashes($url_string)."' LIKE ".$url_pattern."
        		OR '/".addslashes($url_string)."/' LIKE ".$url_pattern."
        		OR '/".addslashes($url_string)."' LIKE ".$url_pattern."
        		OR '".addslashes($url_string)."/' LIKE ".$url_pattern.'
        	ORDER BY LENGTH(url) DESC';
        $query = $this->db->query($SQL);

        $navigation_name = null;
        if ($query->num_rows() > 0) {
            $row = $query->row();
            $navigation_name = stripslashes($row->navigation_name);
        }

        return $navigation_name;
    }

    /**
     * @author goFrendiAsgard
     * @desc   redirect to main/login page
     */
    protected function cms_redirect()
    {
        $uriString = $this->uri->uri_string();
        $old_url = $this->session->userdata('old_url');
        if (!isset($old_url)) {
            // AJAX request should not be used for redirection
            if (!$this->input->is_ajax_request()) {
                $this->session->set_userdata('cms_old_url', $uriString);
            }
        }

        $login_url = $this->cms_navigation_url('main_login');
        if ($this->cms_allow_navigate('main_login') && ($uriString != $login_url)) {
            redirect($login_url, 'refresh');
        } else {
            $navigation_name = $this->cms_navigation_name($this->router->default_controller);
            if (!isset($navigation_name)) {
                $navigation_name = $this->cms_navigation_name($this->router->default_controller.'/index');
            }
            // redirect to default controller
            if (isset($navigation_name) && $this->cms_allow_navigate($navigation_name) &&
            ($uriString != '') && ($uriString != $this->router->default_controller) &&
            ($uriString != $this->router->default_controller.'/index')) {
                redirect('', 'refresh');
            } else {
                show_404();
            }
        }
    }

    /**
     * @author goFrendiAsgard
     *
     * @param string navigation_name
     * @param string or array privilege_required
     * @desc guard a page from unauthorized access
     */
    protected function cms_guard_page($navigation_name = null, $privilege_required = null)
    {
        $privilege_required = isset($privilege_required) ? $privilege_required : array();
        // check if allowed
        if (!isset($navigation_name) || $this->cms_allow_navigate($navigation_name)) {
            if (!isset($privilege_required)) {
                $allowed = true;
            } elseif (is_array($privilege_required)) {
                // privilege_required is array
                $allowed = true;
                foreach ($privilege_required as $privilege) {
                    $allowed = $allowed && $this->cms_have_privilege($privilege);
                    if (!$allowed) {
                        break;
                    }
                }
            } else { // privilege_required is string
                $allowed = $this->cms_have_privilege($privilege_required);
            }
        } else {
            $allowed = false;
        }
        // if not allowed then redirect
        if (!$allowed) {
            $this->cms_redirect();
        }
    }

    /**
     * @author  goFrendiAsgard
     *
     * @param   string content
     * @desc    flash content to be served as metadata on next call of $this->view in controller
     */
    protected function cms_flash_metadata($content)
    {
        $this->{$this->__cms_base_model_name}->cms_flash_metadata($content);
    }

    private function cms_ck_adjust_script()
    {
        $base_url = base_url();
        $save_base_url = str_replace('/', '\\/', $base_url);
        $ck_editor_adjust_script = '
            $(document).ready(function(){
                if (typeof(CKEDITOR) != "undefined"){
                    function __adjust_ck_editor(){
                        for (instance in CKEDITOR.instances) {
                            /* ck_instance */
                            ck_instance = CKEDITOR.instances[instance];
                            var name = CKEDITOR.instances[instance].name;
                            var $ck_textarea = $("#cke_"+name+" textarea");
                            var $ck_iframe = $("#cke_"+name+" iframe");
                            var data = ck_instance.getData();
                            if($ck_textarea.length > 0){
                                content = data.replace(
                                    /(src=".*?)('.$save_base_url.')(.*?")/gi,
                                    "$1{"+"{ base_url }}$3"
                                );
                                ck_instance.setData(content);
                            }else if ($ck_iframe.length > 0){
                                var re = new RegExp(\'(src=".*?)({\'+\'{ base_url }})(.*?")\',"gi");
                                content = data.replace(
                                    re,
                                    "$1'.$base_url.'$3"
                                );
                                ck_instance.setData(content);
                            }
                            ck_instance.updateElement();
                        }
                    }

                    /* when instance ready & form submit, adjust ck editor */
                    CKEDITOR.on("instanceReady", function(){
                        __adjust_ck_editor();
                        for (instance in CKEDITOR.instances) {
                            /* ck_instance */
                            ck_instance = CKEDITOR.instances[instance];
                            ck_instance.on("mode", function(){
                                __adjust_ck_editor();
                            });
                        }
                    });

                    /* when form submit, adjust ck editor */
                    $(document).ajaxSend(function(event, xhr, settings) {
                        if(settings.url == $("#crudForm").attr("action")){
                            for (instance in CKEDITOR.instances) {
                                /* ck_instance */
                                ck_instance = CKEDITOR.instances[instance];
                                var name = CKEDITOR.instances[instance].name;
                                var $original_textarea = $("textarea#"+name);
                                var data = ck_instance.getData();
                                content = data.replace(
                                    /(src=".*?)('.$save_base_url.')(.*?")/gi,
                                    "$1{"+"{ base_url }}$3"
                                );
                                ck_instance.setData(content);
                            }
                        }
                    });

                    $(document).ajaxComplete(function(event, xhr, settings){
                        if(settings.url == $("#crudForm").attr("action")){
                            __adjust_ck_editor();
                        }
                    });
                }
            });
        ';

        return $ck_editor_adjust_script;
    }

    /**
     * @author  goFrendiAsgard
     *
     * @param   string view_url
     * @param   string data
     * @param   string navigation_name
     * @param   array config
     * @param   bool return_as_string
     *
     * @return string or null
     * @desc    replace $this->load->view. This method will also load header, menu etc except there is _only_content parameter via GET or POST
     */
    public function view($view_url, $data = null, $navigation_name = null, $config = null, $return_as_string = false)
    {
        $this->load->library('template');
        $result = null;
        $view_url = $this->cms_parse_keyword($view_url);

        /*
         * PREPARE PARAMETERS *********************************************************************************************
         */
        // get dynamic widget status
        // (this is necessary since sometime the function called directly without run the constructor, i.e: when using Modules::run)

        if (isset($_REQUEST['__cms_dynamic_widget'])) {
            $this->__cms_dynamic_widget = true;
        }

        /*
         * PREPARE PARAMETERS *********************************************************************************************
         */

        // this method can be called as $this->view('view_path', $data, true);
        // or $this->view('view_path', $data, $navigation_name, true);
        if (is_bool($navigation_name) && count($config) == 0) {
            $return_as_string = $navigation_name;
            $navigation_name = null;
            $config = null;
        } elseif (is_bool($config)) {
            $return_as_string = $config;
            $config = null;
        }

        if (!isset($return_as_string)) {
            $return_as_string = false;
        }
        if (!isset($config)) {
            $config = array();
        }

        $privilege_required = isset($config['privileges']) ? $config['privileges'] : array();
        $custom_theme = isset($config['theme']) ? $config['theme'] : null;
        $custom_layout = isset($config['layout']) ? $config['layout'] : null;
        $custom_title = isset($config['title']) ? $config['title'] : null;
        $custom_metadata = isset($config['metadata']) ? $config['metadata'] : array();
        $custom_partial = isset($config['partials']) ? $config['partials'] : null;
        $custom_keyword = isset($config['keyword']) ? $config['keyword'] : null;
        $custom_description = isset($config['description']) ? $config['description'] : null;
        $custom_author = isset($config['author']) ? $config['author'] : null;
        $only_content = isset($config['only_content']) ? $config['only_content'] : false;
        $always_allow = isset($config['always_allow']) ? $config['always_allow'] : false;
        $layout_suffix = isset($config['layout_suffix']) ? $config['layout_suffix'] : '';
        $custom_css = isset($config['css']) ? $config['css'] : '';
        $custom_js = isset($config['js']) ? $config['js'] : '';

        /*
         * GUESS $navigation_name THROUGH ITS URL  ***********************************************************************
         */
        $navigation_name_provided = true;
        if (!isset($navigation_name) && !$this->__cms_dynamic_widget) {
            $navigation_name = $this->cms_navigation_name();
            if (!$navigation_name) {
                $navigation_name_provided = false;
            }
        }

        /*
         * CHECK IF THE CURRENT NAVIGATION IS ACCESSIBLE  *****************************************************************
         */
        if (!$always_allow) {
            $this->cms_guard_page($navigation_name, $privilege_required);
        }
        // privilege is absolute
        $this->cms_guard_page(null, $privilege_required);

        /*
         * CHECK IF THE PAGE IS STATIC  **********************************************************************************
         */
        $data = (array) $data;
        $row_navigation = null;
        if ($navigation_name != null) {
            $query = $this->db->select('navigation_id, title, page_title, page_keyword, description, default_theme, default_layout, only_content, is_static, static_content')
                ->from(cms_table_name('main_navigation'))
                ->where(array('navigation_name' => $navigation_name))
                ->get();
            if ($query->num_rows() > 0) {
                $row_navigation = $query->row();
            }
        }
        if ($navigation_name_provided && !isset($data['_content']) && $row_navigation != null) {
            if ($row_navigation->is_static == 1) {
                $static_content = $row_navigation->static_content;
                // static_content should contains string
                if (!$static_content) {
                    $static_content = '';
                }
                if ($this->cms_editing_mode() && $this->cms_allow_navigate('main_navigation_management')) {
                    $static_content = '<div class="row" style="padding-top:10px; padding-bottom:10px;"><a class="btn btn-primary pull-right" href="{{ SITE_URL }}main/navigation/edit/'.$row_navigation->navigation_id.'">'.
                        '<i class="glyphicon glyphicon-pencil"></i> Edit Page'.
                        '</a></div>'.$static_content;
                }
                $data['cms_content'] = $static_content;
                $view_url = 'CMS_View';
            }
        }

        /*
         * SHOW THE PAGE IF IT IS ACCESSIBLE  *****************************************************************************
         */

        // GET THE THEME, TITLE & ONLY_CONTENT FROM DATABASE
        $theme = '';
        $title = '';
        $keyword = '';
        $default_theme = null;
        $default_layout = null;
        $page_title = null;
        $page_keyword = null;
        $page_description = null;
        $page_author = null;
        if ($navigation_name_provided && $row_navigation != null) {
            $default_theme = $row_navigation->default_theme;
            $default_layout = $row_navigation->default_layout;
            // title
            if (isset($row_navigation->page_title) && ($row_navigation->page_title !== null) && $row_navigation->page_title != '') {
                $page_title = $row_navigation->page_title;
            } elseif (isset($row_navigation->title) && ($row_navigation->title !== null) && $row_navigation->title != '') {
                $page_title = $row_navigation->title;
            }
            $page_title = isset($page_title) && $page_title !== null ? $page_title : '';
            // keyword
            $page_keyword = isset($row_navigation->page_keyword) && $row_navigation->page_keyword !== null ? $row_navigation->page_keyword : '';
            // keyword
            $page_description = isset($row_navigation->description) && $row_navigation->description !== null ? $row_navigation->description : '';
            // only content
            if (!isset($only_content)) {
                $only_content = ($row_navigation->only_content == 1);
            }
        }

        // ASSIGN THEME
        if (isset($custom_theme) && $custom_theme !== null && $custom_theme != '') {
            $theme = $custom_theme;
        } elseif (isset($default_theme) && $default_theme != null && $default_theme != '') {
            $themes = $this->cms_get_theme_list();
            $theme_path = array();
            foreach ($themes as $theme) {
                $theme_path[] = $theme['path'];
            }
            if (in_array($default_theme, $theme_path)) {
                $theme = $default_theme;
            }
        } else {
            $theme = $this->cms_get_config('site_theme');
        }

        // ASSIGN TITLE
        $title = '';
        if (isset($custom_title) && $custom_title !== null && $custom_title != '') {
            $title = $this->cms_get_config('site_name').' - '.$custom_title;
        } elseif (isset($page_title) && $page_title !== null && $page_title != '') {
            $title = $this->cms_get_config('site_name').' - '.$page_title;
        } else {
            $title = $this->cms_get_config('site_name');
        }

        // ASSIGN KEYWORD
        if (isset($custom_keyword) && $custom_keyword != null && $custom_keyword != '') {
            $keyword = $custom_keyword;
        } elseif (isset($page_keyword) && $page_keyword !== null && $page_keyword != '') {
            $keyword = $page_keyword;
            if ($custom_keyword != '') {
                $keyword .= ', '.$custom_keyword;
            }
        } else {
            $keyword = '';
        }

        // ASSIGN DESCRIPTION
        if (isset($custom_description) && $custom_description != null && $custom_description != '') {
            $description = $custom_description;
        } elseif (isset($page_description) && $page_description !== null && $page_description != '') {
            $description = $page_description;
            if ($custom_description != '') {
                $description .= ', '.$custom_description;
            }
        } else {
            $description = '';
        }

        // ASSIGN AUTHOR
        if (isset($custom_author) && $custom_author != null && $custom_author != '') {
            $author = $custom_author;
        } else {
            $author = $this->{$this->__cms_base_model_name}->cms_get_super_admin()->real_name;
        }

        // GET THE LAYOUT
        if (isset($custom_layout)) {
            $layout = $custom_layout;
        } elseif (isset($default_layout) && $default_layout != '') {
            $layout = $default_layout;
        } else {
            $this->load->library('user_agent');
            $layout = $this->agent->is_mobile() ? 'mobile' : $this->cms_get_config('site_layout');
        }

        // ADJUST THEME AND LAYOUT
        if (!$this->cms_layout_exists($theme, $layout)) {
            // ASSIGN LAYOUT
            if (!file_exists(FCPATH.'themes/'.$theme) || !is_dir(FCPATH.'themes/'.$theme)) {
                $theme = 'neutral';
            }
            if (!file_exists(FCPATH.'themes/'.$theme.'/views/layouts/'.$layout.'.php')) {
                $layout = 'default';
                if (!file_exists(FCPATH.'themes/'.$theme.'/views/layouts/default.php')) {
                    $theme = 'neutral';
                }
            }
        }
        // save used_theme
        $this->session->set_userdata('__cms_used_theme', $theme);

        // ADD AUTHENTICATED SUFFIX (in case of user has logged in)
        $cms_user_id = $this->cms_user_id();
        if ($layout_suffix == '' && isset($cms_user_id) && $cms_user_id) {
            $layout_suffix = 'authenticated';
        }

        if ($this->cms_layout_exists($theme, $layout.'_'.$layout_suffix)) {
            $layout = $layout.'_'.$layout_suffix;
        }

        $data['__is_bootstrap_cdn_connected'] = false;

        // IT'S SHOW TIME
        if ($only_content || $this->__cms_dynamic_widget || (isset($_REQUEST['_only_content'])) || $this->input->is_ajax_request()) {
            $result = $this->load->view($view_url, $data, true);
            $result = $custom_css.$custom_js.$result;
        } else {
            // save navigation name
            $this->cms_ci_session('__cms_navigation_name', $navigation_name);
            // set theme, layout and title
            $this->template->title($title);
            $this->template->set_theme($theme);
            $this->template->set_layout($layout);

            // set keyword metadata
            if ($keyword != '') {
                $keyword_metadata = '<meta name="keyword" content="'.$keyword.'">';
                $this->template->append_metadata($keyword_metadata);
            }
            // set description metadata
            if ($description != '') {
                $description_metadata = '<meta name="description" content="'.$description.'">';
                $this->template->append_metadata($description_metadata);
            }
            // set author metadata
            if ($author != '') {
                $author_metadata = '<meta name="author" content="'.$author.'">';
                $this->template->append_metadata($author_metadata);
            }

            // add IE compatibility
            $this->template->append_metadata('<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">');
            // add width
            $this->template->append_metadata('<meta name="viewport" content="width=device-width, initial-scale=1.0">');

            $asset = new Cms_asset();
            $asset->add_js($this->JQUERY_PATH);

            // ckeditor adjustment thing
            $asset->add_internal_js($this->cms_ck_adjust_script());

            // add javascript base_url for ckeditor
            $asset->add_internal_js('var __cms_base_url = "'.base_url().'";');

            // check login status
            //$login_code = '<script type="text/javascript">';
            $login_code = '';
            if ($this->cms_user_id() > 0) {
                $login_code .= 'var __cms_is_login = true;';
            } else {
                $login_code .= 'var __cms_is_login = false;';
            }
            $login_code .= 'setInterval(function(){
                $.ajax({
                    url : "{{ site_url }}main/json_login_info",
                    dataType: "json",
                    success: function(response){
                        if(response.is_login != __cms_is_login){
                            window.location = $(location).attr("href");
                        }
                    }
                });
            },300000);';
            $asset->add_internal_js($login_code);

            // google analytic
            $analytic_property_id = $this->cms_get_config('cms_google_analytic_property_id');
            if (trim($analytic_property_id) != '') {
                if ($this->cms_is_connect('google-analytics.com')) {
                    // create analytic code
                    $analytic_code = '';
                    $analytic_code .= 'var _gaq = _gaq || []; ';
                    $analytic_code .= '_gaq.push([\'_setAccount\', \''.$analytic_property_id.'\']); ';
                    $analytic_code .= '_gaq.push([\'_trackPageview\']); ';
                    $analytic_code .= '(function() { ';
                    $analytic_code .= 'var ga = document.createElement(\'script\'); ga.type = \'text/javascript\'; ga.async = true; ';
                    $analytic_code .= 'ga.src = (\'https:\' == document.location.protocol ? \'https://ssl\' : \'http://www\') + \'.google-analytics.com/ga.js\'; ';
                    $analytic_code .= 'var s = document.getElementsByTagName(\'script\')[0]; s.parentNode.insertBefore(ga, s); ';
                    $analytic_code .= '})(); ';
                    $asset->add_internal_js($analytic_code);
                }
            }

            // add hack if exists
            if (!isset($_SESSION)) {
                session_start();
            }
            if (isset($_SESSION['__cms_flash_metadata'])) {
                $this->template->append_metadata($_SESSION['__cms_flash_metadata']);
                unset($_SESSION['__cms_flash_metadata']);
            }

            // config metadata
            foreach ($custom_metadata as $metadata) {
                $this->template->append_metadata($metadata);
            }

            // append custom css & js
            $this->template->append_js($asset->compile_js());
            $this->template->append_css($asset->compile_css());
            $this->template->append_js($custom_js);
            $this->template->append_css($custom_css);

            $this->load->helper('directory');
            $partial_path = BASEPATH.'../themes/'.$theme.'/views/partials/'.$layout.'/';
            if (is_dir($partial_path)) {
                $partials = directory_map($partial_path, 1);
                foreach ($partials as $partial) {
                    // if is directory or is not php, then ignore it
                    if (is_dir($partial)) {
                        continue;
                    }
                    $partial_extension = pathinfo($partial_path.$partial, PATHINFO_EXTENSION);
                    if (strtoupper($partial_extension) != 'PHP') {
                        continue;
                    }

                    // add partial to template
                    $partial_name = pathinfo($partial_path.$partial, PATHINFO_FILENAME);
                    if (isset($custom_partial[$partial_name])) {
                        $this->template->inject_partial($partial_name, $custom_partial[$partial_name]);
                    } else {
                        $this->template->set_partial($partial_name, 'partials/'.$layout.'/'.$partial, $data);
                    }
                }
            }
            $result = $this->template->build($view_url, $data, true);
        }

        // parse keyword
        $result = $this->cms_parse_keyword($result);

        // parse widgets used_theme & navigation_path
        $result = $this->__cms_parse_widget_theme_path($result, $theme, $layout, $navigation_name);
        $this->load->library('cms_asset');
        $asset = new Cms_asset();
        $result = $asset->minify($result, 'js');

        if ($return_as_string) {
            return $result;
        } else {
            $this->cms_show_html($result);
        }
    }

    private function __cms_parse_widget_theme_path($html, $theme, $layout, $navigation_name, $recursive_level = 5)
    {
        if (strpos($html, '{{ ') !== false) {
            $html = $this->{$this->__cms_base_model_name}->cms_escape_template($html);

            // parse widget
            if (strpos($html, '{{ ') !== false) {
                $pattern = '/\{\{ widget([a-zA-Z0-9-_]*?):(.*?) \}\}/si';
                // execute regex
                $html = preg_replace_callback($pattern, array(
                    $this,
                    '__cms_preg_replace_callback_widget',
                ), $html);
            }

            // prepare pattern and replacement for theme and path
            if (strpos($html, '{{ ') !== false) {
                $pattern = array();
                $replacement = array();

                // theme
                $pattern[] = "/\{\{ used_theme \}\}/si";
                $replacement[] = $theme;
                $nav_path = $this->__cms_build_nav_path($navigation_name);
                $pattern[] = "/\{\{ navigation_path \}\}/si";
                $replacement[] = $nav_path;

                $html = preg_replace($pattern, $replacement, $html);

                $html = $this->{$this->__cms_base_model_name}->cms_unescape_template($html);
            }

            --$recursive_level;
            // recursively search widget inside widget
            if (strpos($html, '{{ ') !== false && $recursive_level > 0) {
                $html = $this->__cms_parse_widget_theme_path($html, $theme, $layout, $navigation_name, $recursive_level);
            }
        }

        return $html;
    }

    private function __cms_build_left_nav($navigations = null, $first = true)
    {
        if (!isset($navigations)) {
            if (!isset($this->__cms_navigations)) {
                $navigations = $this->cms_navigations();
                $this->__cms_navigations = $navigations;
            } else {
                $navigations = $this->__cms_navigations;
            }
        }
        if (count($navigations) == 0) {
            return '';
        }

        if ($first) {
            $style = 'display: block; position: static; border:none; margin:0px; background-color:light-gray;';
        } else {
            $style = 'background-color:light-gray;';
        }
        $result = '<ul  class="dropdown-menu nav nav-pills nav-stacked" style="'.$style.'">';
        foreach ($navigations as $navigation) {
            if (($navigation['allowed'] && $navigation['active']) || $navigation['have_allowed_children']) {
                // make text
                if ($navigation['allowed'] && $navigation['active']) {
                    $text = '<a class="dropdown-toggle" href="'.$navigation['url'].'">'.$navigation['title'].'</a>';
                } else {
                    $text = $navigation['title'];
                }

                if (count($navigation['child']) > 0 && $navigation['have_allowed_children']) {
                    $result .= '<li class="dropdown-submenu">'.$text.$this->__cms_build_left_nav($navigation['child'], false).'</li>';
                } else {
                    $result .= '<li>'.$text.'</li>';
                }
            }
        }
        $result .= '</ul>';

        return $result;
    }

    private function __cms_build_top_nav_btn($navigations = null, $caption = 'Complete Menu', $first = true)
    {
        if (!isset($navigations)) {
            if (!isset($this->__cms_navigations)) {
                $navigations = $this->cms_navigations();
                $this->__cms_navigations = $navigations;
            } else {
                $navigations = $this->__cms_navigations;
            }
        }
        if (count($navigations) == 0) {
            return '';
        }

        $result = '';
        $result .= '<ul class="dropdown-menu">';
        foreach ($navigations as $navigation) {
            if (($navigation['allowed'] && $navigation['active']) || $navigation['have_allowed_children']) {
                // make text
                if ($navigation['allowed'] && $navigation['active']) {
                    $text = '<a href="'.$navigation['url'].'">'.$navigation['title'].'</a>';
                } else {
                    $text = '<a href="#">'.$navigation['title'].'</a>';
                }

                if (count($navigation['child']) > 0 && $navigation['have_allowed_children']) {
                    $result .= '<li class="dropdown-submenu">'.$text.$this->__cms_build_top_nav_btn($navigation['child'], $caption, false).'</li>';
                } else {
                    $result .= '<li>'.$text.'</li>';
                }
            }
        }
        $result .= '</ul>';
        if ($first) {
            $result = '<ul class="nav"><li class="dropdown">'.
                '<a class="dropdown-toggle" data-toggle="dropdown" href="#">'.$caption.' <span class="caret"></span></a>'.
                $result.
                '</li></ul>';
        }

        return $result;
    }

    private function __cms_build_quicklink()
    {
        if (isset($this->__cms_quicklinks)) {
            $quicklinks = $this->__cms_quicklinks;
        } else {
            $quicklinks = $this->cms_quicklinks();
        }
        if (count($quicklinks) == 0) {
            return '';
        }
        $html = '<ul class="nav">';
        foreach ($quicklinks as $quicklink) {
            $html .= '<li>';
            $html .= anchor($quicklink['url'], $quicklink['title']);
            $html .= '</li>';
        }
        $html .= '</ul>';

        return $html;
    }

    private function __cms_build_widget($slug = null, $widget_name = null)
    {
        $widgets = $this->cms_widgets($slug, $widget_name);
        $html = '';
        if (isset($widget_name)) {
            foreach ($widgets as $slug_widgets) {
                if (count($slug_widgets) > 0) {
                    $widget = $slug_widgets[0];
                    $html = $widget['content'];
                    break;
                }
            }
        } elseif (isset($slug) && isset($widgets[$slug])) {
            $html = '<div class="cms-widget-slug-'.$slug.'">';
            foreach ($widgets[$slug] as $widget) {
                $html .= '<div class="cms-widget-container">';
                $html .= '<h3>'.$widget['title'].'</h3>';
                $html .= '<div class="cms-widget-content" style="margin-bottom:20px;">'.$widget['content'].'</div>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }

        return $html;
    }

    private function __cms_build_nav_path($navigation_name)
    {
        $path = $this->cms_get_navigation_path($navigation_name);
        $html = '<ol class="breadcrumb">';
        for ($i = 0; $i < count($path); ++$i) {
            $current_path = $path[$i];
            $html .= '<li>'.anchor($current_path['url'], $current_path['title']).'</li>';
        }
        $html .= '</ol>';

        return $html;
    }

    private function __cms_preg_replace_callback_widget($arr)
    {
        $html = '';
        if (count($arr) > 2) {
            $option = $arr[1];
            $slug = null;
            $widget_name = null;
            if ($option == '' || $option == '_slug') {
                $slug = $arr[2];
            } elseif ($option == '_name' || $option == '_code') {
                $widget_name = $arr[2];
            }
            //$slug = $arr[1];
            //var_dump(array($slug, $widget_name));
            $html = $this->__cms_build_widget($slug, $widget_name);
        }

        return $html;
    }

    protected function cms_layout_exists($theme, $layout)
    {
        if (CMS_SUBSITE != '') {
            $subsite_auth_file = FCPATH.'themes/'.$theme.'/subsite_auth.php';
            if (file_exists($subsite_auth_file)) {
                unset($public);
                unset($subsite_allowed);
                include $subsite_auth_file;
                if (isset($public) && is_bool($public) && !$public) {
                    if (isset($subsite_allowed) && is_array($subsite_allowed) && !in_array(CMS_SUBSITE, $subsite_allowed)) {
                        return false;
                    }
                }
            }
        }

        return is_file(FCPATH.'themes/'.$theme.'/views/layouts/'.$layout.'.php');
    }

    private function __cms_cache($time = 5)
    {
        // cache
        $this->load->driver('cache');
        $this->output->cache($time);
    }

    /**
     * @author  goFrendiAsgard
     *
     * @param   mixed variable
     * @param   int options
     * @desc    show variable in json encoded form
     */
    protected function cms_show_json($variable, $options = 0)
    {
        $result = '';
        // php 5.3.0 accepts 2 parameters, while lower version only accepts 1 parameter
        if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
            $result = json_encode($variable, $options);
        } else {
            $result = json_encode($variable);
        }
        // show the json
        $this->output->set_content_type('application/json')->set_output($result);
    }

    /**
     * @author  goFrendiAsgard
     *
     * @param   mixed variable
     * @desc    show variable for debugging purpose
     */
    protected function cms_show_variable($variable)
    {
        $data = array(
            'cms_content' => '<pre>'.print_r($variable, true).'</pre>',
        );
        $this->load->view('CMS_View', $data);
    }

    /**
     * @author  goFrendiAsgard
     *
     * @param   string html
     * @desc    you are encouraged to use this instead of echo $html
     */
    protected function cms_show_html($html)
    {
        $data = array(
            'cms_content' => $html,
        );
        $this->load->view('CMS_View', $data);
    }

    /**
     * @author goFrendiAsgard
     *
     * @return array providers
     */
    protected function cms_third_party_providers()
    {
        return $this->{$this->__cms_base_model_name}->cms_third_party_providers();
    }

    /**
     * @author goFrendiAsgard
     *
     * @return array status
     * @desc return all status from third-party provider
     */
    protected function cms_third_party_status()
    {
        return $this->{$this->__cms_base_model_name}->cms_third_party_status();
    }

    /**
     * @author goFrendiAsgard
     *
     * @return bool success
     * @desc login/register by using third-party provider
     */
    protected function cms_third_party_login($provider, $email = null)
    {
        return $this->{$this->__cms_base_model_name}->cms_third_party_login($provider, $email);
    }

    final protected function cms_add_navigation($navigation_name, $title, $url, $authorization_id = 1,
        $parent_name = null, $index = null, $description = null, $bootstrap_glyph = null,
        $default_theme = null, $default_layout = null, $notif_url = null, $hidden = 0, $static_content = '')
    {
        $this->{$this->__cms_base_model_name}->cms_add_navigation($navigation_name, $title, $url, $authorization_id,
            $parent_name, $index, $description, $bootstrap_glyph,
            $default_theme, $default_layout, $notif_url, $hidden, $static_content);
    }

    final protected function cms_remove_navigation($navigation_name)
    {
        $this->{$this->__cms_base_model_name}->cms_remove_navigation($navigation_name);
    }

    final protected function cms_add_privilege($privilege_name, $title, $authorization_id = 1, $description = null)
    {
        $this->{$this->__cms_base_model_name}->cms_add_privilege($privilege_name, $title, $authorization_id, $description);
    }
    final protected function cms_remove_privilege($privilege_name)
    {
        $this->{$this->__cms_base_model_name}->cms_remove_privilege($privilege_name);
    }

    protected function cms_add_group($group_name, $description)
    {
        $this->{$this->__cms_base_model_name}->cms_add_group($group_name, $description);
    }

    protected function cms_remove_group($group_name)
    {
        $this->{$this->__cms_base_model_name}->cms_remove_group($group_name);
    }

    protected function cms_add_widget($widget_name, $title = null, $authorization_id = 1, $url = null, $slug = null,
        $index = null, $description = null)
    {
        $this->{$this->__cms_base_model_name}->cms_add_widget($widget_name, $title, $authorization_id, $url, $slug, $index,
            $description);
    }

    protected function cms_remove_widget($widget_name)
    {
        $this->{$this->__cms_base_model_name}->cms_remove_widget($widget_name);
    }

    protected function cms_add_quicklink($navigation_name)
    {
        $this->{$this->__cms_base_model_name}->cms_add_quicklink($navigation_name);
    }

    protected function cms_remove_quicklink($navigation_name)
    {
        $this->{$this->__cms_base_model_name}->cms_remove_quicklink($navigation_name);
    }

    final public function cms_reconfig_route()
    {
        $this->{$this->__cms_base_model_name}->cms_reconfig_route();
    }

    final public function cms_add_route($key, $value, $description = '')
    {
        $this->{$this->__cms_base_model_name}->cms_add_route($key, $value, $description);
    }

    final public function cms_remove_route($key)
    {
        $this->{$this->__cms_base_model_name}->cms_remove_route($key);
    }

    final public function cms_add_config($config_name, $value, $description = null)
    {
        $this->{$this->__cms_base_model_name}->cms_add_config($config_name, $value, $description);
    }

    final public function cms_remove_config($config_name)
    {
        $this->{$this->__cms_base_model_name}->cms_remove_config($config_name);
    }

    protected function cms_assign_navigation($navigation_name, $group_name)
    {
        $this->{$this->__cms_base_model_name}->cms_assign_navigation($navigation_name, $group_name);
    }
    protected function cms_assign_privilege($privilege_name, $group_name)
    {
        $this->{$this->__cms_base_model_name}->cms_assign_privilege($privilege_name, $group_name);
    }
    protected function cms_assign_widget($widget_name, $group_name)
    {
        $this->{$this->__cms_base_model_name}->cms_assign_widget($widget_name, $group_name);
    }

    protected function cms_execute_sql($SQL, $separator)
    {
        $this->{$this->__cms_base_model_name}->cms_execute_sql($SQL, $separator);
    }

    protected function cms_set_editing_mode()
    {
        $this->{$this->__cms_base_model_name}->cms_set_editing_mode();
    }

    protected function cms_unset_editing_mode()
    {
        $this->{$this->__cms_base_model_name}->cms_unset_editing_mode();
    }

    protected function cms_editing_mode()
    {
        if ($this->cms_user_is_super_admin()) {
            return $this->{$this->__cms_base_model_name}->cms_editing_mode();
        } else {
            return false;
        }
    }
}
