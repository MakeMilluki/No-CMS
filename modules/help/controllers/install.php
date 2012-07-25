<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of install
 *
 * @author gofrendi
 */
class Install extends CMS_Module_Installer{
	protected $DEPENDENCIES = array();
	protected $NAME = 'gofrendi.noCMS.help';
    //put your code here
    public function do_install(){
        $this->add_navigation('help', 'No-CMS User guide', $this->cms_module_path(), 1);
    }
    
    public function do_uninstall(){
        $this->remove_navigation('help');
    }
}

?>
