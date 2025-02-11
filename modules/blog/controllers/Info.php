<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Installation script for new blog
 *
 * @author No-CMS Module Generator
 */
class Info extends CMS_Module {
    
    /////////////////////////////////////////////////////////////////////////////
    // Default Functions
    /////////////////////////////////////////////////////////////////////////////

    // ACTIVATION
    public function do_activate(){
        $this->remove_all();
        $this->build_all();
    }

    // DEACTIVATION
    public function do_deactivate(){
        $this->remove_all();
    }

    // UPGRADE
    public function do_upgrade($old_version){
        $module_path = $this->cms_module_path();
        // table : blog article
        $table_name = $this->cms_complete_table_name('article');
        $field_list = $this->db->list_fields($table_name);
        $missing_fields = array(
            'keyword' => $this->TYPE_VARCHAR_100_NULL,
            'description' => $this->TYPE_TEXT,
            'status' => array(
                'type'=>'enum',
                'constraint'=>array('draft','published','scheduled'), 
                'default' => 'draft',
                'null' => FALSE,
            ),
            'publish_date' => $this->TYPE_DATETIME_NULL,
        );
        $fields = array();
        foreach($missing_fields as $key=>$value){
            if(!in_array($key, $field_list)){
                $fields[$key] = $value;
            }
        }
        $this->dbforge->add_column($table_name, $fields);

        // table : blog comment
        $table_name = $this->cms_complete_table_name('comment');
        $field_list = $this->db->list_fields($table_name);
        $missing_fields = array(
            'parent_comment_id' => $this->TYPE_INT_UNSIGNED_NULL,
            'read' => array(
                'type' => 'INT',
                'constraint' => 20,
                'unsigned' => TRUE,
                'null' => FALSE,
                'default' => 0,
            )
        );
        $fields = array();
        foreach($missing_fields as $key=>$value){
            if(!in_array($key, $field_list)){
                $fields[$key] = $value;
            }
        }
        $this->dbforge->add_column($table_name, $fields);

        // navigation: blog_index
        $table_name = cms_table_name('main_navigation');
        $navigation_name = $this->cms_complete_navigation_name('index');
        $this->db->update($table_name,
            array('notif_url' => $this->cms_module_path($this->NAME).'/notif/new_comment'),
            array('navigation_name' => $navigation_name));
        // navigation: blog_article
        $navigation_name = $this->cms_complete_navigation_name('manage_article');
        $this->db->update($table_name,
            array('notif_url' => $this->cms_module_path($this->NAME).'/notif/new_comment'),
            array('navigation_name' => $navigation_name));

        // add widget archive
        $query = $this->db->select('widget_name')
            ->from(cms_table_name('main_widget'))
            ->where('widget_name', $this->cms_complete_navigation_name('archive'))
            ->get();
        if($query->num_rows()>0){
            $this->cms_add_widget($this->cms_complete_navigation_name('archive'), 'Archive',
                PRIV_EVERYONE, $this->cms_module_path().'/blog_widget/archive', 'sidebar');
        }

        // add widget blog
        $query = $this->db->select('widget_name')
            ->from(cms_table_name('main_widget'))
            ->where('widget_name', 'blog_content')
            ->get();
        if($query->num_rows() == 0){
            $this->cms_add_widget($this->cms_complete_navigation_name('content'), 'Blog Content',
                PRIV_EVERYONE, $this->cms_module_path());
        }

        $version_part = explode('.', $old_version);
        $major = $version_part[0];
        $minor = $version_part[1];
        $build = $version_part[2];
        if($major == 0 && $minor == 0 && $build <= 4){
            $this->db->update($this->cms_complete_table_name('article'),array('status'=>'published'),array('status'=>'draft'));
            $this->cms_add_group('Blog Editor', 'Can Add, Edit, Delete & Publish other\'s articles');
            $this->cms_add_group('Blog Author', 'Can Add, Edit, Delete & Publish his/her own articles');
            $this->cms_add_group('Blog Contributor', 'Can Add, Edit, and Delete his/her own articles');
            $this->cms_assign_navigation($this->cms_complete_navigation_name('manage_article'),'Blog Editor');
            $this->cms_assign_navigation($this->cms_complete_navigation_name('manage_article'),'Blog Author');
            $this->cms_assign_navigation($this->cms_complete_navigation_name('manage_article'),'Blog Contributor');
            $this->cms_assign_navigation($this->cms_complete_navigation_name('manage_category'),'Blog Editor');
        }
        if($major == 0 && $minor == 0 && $build <= 5){
            $fields = array('publish_date' => $this->TYPE_DATETIME_NULL);
            $table_name = $this->cms_complete_table_name('article');
            $this->dbforge->modify_column($table_name, $fields);
        }
        if($major == 0 && $minor == 0 && $build <=6){
            $fields = array(
                'article_id' => $this->TYPE_INT_UNSIGNED_AUTO_INCREMENT,
                'article_title' => $this->TYPE_TEXT,
                'article_url' => $this->TYPE_TEXT,
            );
            $table_name = $this->cms_complete_table_name('article');
            $this->dbforge->modify_column($table_name, $fields);
            $fields = array(
                'url' => $this->TYPE_TEXT,
            );
            $table_name = $this->cms_complete_table_name('photo');
            $this->dbforge->modify_column($table_name, $fields);
        }
        if($major == 0 && $minor == 0 && $build <=7){
            $fields = array(
                'visited' => $this->TYPE_INT_UNSIGNED_NULL,
                'featured' => $this->TYPE_INT_UNSIGNED_NULL,
            );
            $table_name = $this->cms_complete_table_name('article');
            $this->dbforge->add_column($table_name, $fields);
            // add popular and featured articles widgets
            $this->cms_add_widget($this->cms_complete_navigation_name('popular_article'), 'Popular Articles',
                PRIV_EVERYONE, $module_path.'/blog_widget/popular','sidebar');
            $this->cms_add_widget($this->cms_complete_navigation_name('featured_article'), 'Featured Articles',
                PRIV_EVERYONE, $module_path.'/blog_widget/featured','sidebar');
        }
        if($major == 0 && $minor == 0 && $build <= 8){
            $this->cms_add_navigation($this->cms_complete_navigation_name('setting'), 'Setting',
                $module_path.'/setting', PRIV_AUTHORIZED, $this->cms_complete_navigation_name('index'),
                NULL, 'Blog Setting', NULL, NULL, 'default-one-column'
            );

            // add configuration
            $this->cms_add_config($this->cms_complete_navigation_name('moderation'), 'FALSE', 'Is comment in blog need moderation?');

            // publication status
            $fields = array(
                    'status' => $this->TYPE_VARCHAR_50_NULL,
                );
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('status', TRUE);
            $this->dbforge->create_table($this->cms_complete_table_name('publication_status'));

            $table_name = $this->cms_complete_table_name('publication_status');
            $data = array('status'=>'draft');
            $this->db->insert($table_name, $data);
            $data = array('status'=>'published');
            $this->db->insert($table_name, $data);
            $data = array('status'=>'scheduled');
            $this->db->insert($table_name, $data);

            // comment
            $fields = array(
                'approved' => array('type' => 'INT', 'constraint' => 20, 'unsigned' => TRUE, 'null' => FALSE, 'default' => 0,),
            );
            $table_name = $this->cms_complete_table_name('comment');
            $this->dbforge->add_column($table_name, $fields);

            $this->db->update($this->cms_complete_table_name('comment'), array('approved' => 1));

            // photo
            $fields = array(
                'index'     => array('type' => 'INT', 'constraint' => 20, 'unsigned' => TRUE, 'null' => FALSE, 'default' => 0,),
                'caption'   => $this->TYPE_TEXT,
            );
            $table_name = $this->cms_complete_table_name('photo');
            $this->dbforge->add_column($table_name, $fields);

            $query = $this->db->select('article_id')
                ->from($this->cms_complete_table_name('article'))
                ->get();
            foreach($query->result() as $row){
                $article_id = $row->article_id;
                $query_photo = $this->db->select('photo_id')
                    ->from($this->cms_complete_table_name('photo'))
                    ->where('article_id', $article_id)
                    ->get();
                $index = 1;
                foreach($query_photo->result() as $row_photo){
                    $photo_id = $row_photo->photo_id;
                    $this->db->update($this->cms_complete_table_name('photo'),
                            array('index' => $index),
                            array('photo_id' => $photo_id)
                        );
                    $index++;
                }
            }

            // route
            if($module_path == 'blog'){
                $controller_path = 'blog';
            }else{
                $controller_path = $module_path.'/blog';
            }
            $this->cms_add_route($controller_path.'/(:any)\.html',    $controller_path.'/index/$1', 
                'Route to blog\'s article');
            $this->cms_add_route($controller_path.'/category/(:any)', $controller_path.'/index//$1', 
                'Route to blog\'s category');
            $this->cms_add_route($controller_path.'/archive/(:any)',  $controller_path.'/index///$1', 
                'Route to blog\'s archive');
            $this->cms_add_route($controller_path.'/category',  $controller_path.'/index/', 
                'Route to blog\'s category');
            $this->cms_add_route($controller_path.'/archive',  $controller_path.'/index/', 
                'Route to blog\'s archive');
        }
    }

    
    /////////////////////////////////////////////////////////////////////////////
    // Private Functions
    /////////////////////////////////////////////////////////////////////////////

    // REMOVE ALL NAVIGATIONS, WIDGETS, AND PRIVILEGES
    private function remove_all(){
        $module_path = $this->cms_module_path();

        // remove widgets
        $this->cms_remove_widget($this->cms_complete_navigation_name('newest_article'));
        $this->cms_remove_widget($this->cms_complete_navigation_name('popular_article'));
        $this->cms_remove_widget($this->cms_complete_navigation_name('featured_article'));
        $this->cms_remove_widget($this->cms_complete_navigation_name('article_category'));
        $this->cms_remove_widget($this->cms_complete_navigation_name('content'));
        $this->cms_remove_widget($this->cms_complete_navigation_name('archive'));

        // remove quicklinks
        $this->cms_remove_quicklink($this->cms_complete_navigation_name('index'));

        // remove navigations
        $this->cms_remove_navigation($this->cms_complete_navigation_name('manage_category'));
        $this->cms_remove_navigation($this->cms_complete_navigation_name('manage_article'));


        // remove parent of all navigations
        $this->cms_remove_navigation($this->cms_complete_navigation_name('index'));

        // import uninstall.sql
        $this->dbforge->drop_table($this->cms_complete_table_name('photo'), TRUE);
        $this->dbforge->drop_table($this->cms_complete_table_name('comment'), TRUE);
        $this->dbforge->drop_table($this->cms_complete_table_name('category_article'), TRUE);
        $this->dbforge->drop_table($this->cms_complete_table_name('category'), TRUE);
        $this->dbforge->drop_table($this->cms_complete_table_name('article'), TRUE);
        $this->dbforge->drop_table($this->cms_complete_table_name('publication_status'), TRUE);

    }

    // CREATE ALL NAVIGATIONS, WIDGETS, AND PRIVILEGES
    private function build_all(){
        $module_path = $this->cms_module_path();

        // parent of all navigations
        if($module_path == 'blog'){
            $parent_url = 'blog';
        }else{
            $parent_url = $module_path.'/blog';
        }
        $this->cms_add_navigation($this->cms_complete_navigation_name('index'), 'Blog',
            $parent_url, PRIV_EVERYONE, NULL, NULL, 'Blog', 'glyphicon-pencil', NULL, NULL,
            $this->cms_module_path().'/notif/new_comment'
        );

        // add navigations
        $this->cms_add_navigation($this->cms_complete_navigation_name('manage_article'), 'Manage Article',
            $module_path.'/manage_article', PRIV_AUTHORIZED, $this->cms_complete_navigation_name('index'),
            NULL, 'Add, edit, and delete blog articles', NULL, NULL, 'default-one-column',
            $this->cms_module_path().'/notif/new_comment'
        );
        $this->cms_add_navigation($this->cms_complete_navigation_name('manage_category'), 'Manage Category',
            $module_path.'/manage_category', PRIV_AUTHORIZED, $this->cms_complete_navigation_name('index'),
            NULL, 'Add, edit, and delete categories. Each article can has one or more categories', NULL, NULL, 'default-one-column'
        );
        $this->cms_add_navigation($this->cms_complete_navigation_name('setting'), 'Setting',
            $module_path.'/setting', PRIV_AUTHORIZED, $this->cms_complete_navigation_name('index'),
            NULL, 'Blog Setting', NULL, NULL, 'default-one-column'
        );

        $this->cms_add_quicklink($this->cms_complete_navigation_name('index'));

        $this->cms_add_widget($this->cms_complete_navigation_name('newest_article'), 'Newest Articles',
            PRIV_EVERYONE, $module_path.'/blog_widget/newest','sidebar');
        $this->cms_add_widget($this->cms_complete_navigation_name('popular_article'), 'Popular Articles',
            PRIV_EVERYONE, $module_path.'/blog_widget/popular','sidebar');
        $this->cms_add_widget($this->cms_complete_navigation_name('featured_article'), 'Featured Articles',
            PRIV_EVERYONE, $module_path.'/blog_widget/featured','sidebar');
        $this->cms_add_widget($this->cms_complete_navigation_name('article_category'), 'Article Categories',
            PRIV_EVERYONE, $module_path.'/blog_widget/category','sidebar');
        $this->cms_add_widget($this->cms_complete_navigation_name('content'), 'Blog Content',
            PRIV_EVERYONE, $module_path);
        $this->cms_add_widget($this->cms_complete_navigation_name('archive'), 'Archive',
            PRIV_EVERYONE, $module_path.'/blog_widget/archive', 'sidebar');

        // create groups and assign
        $this->cms_add_group('Blog Editor', 'Can Add, Edit, Delete & Publish other\'s articles');
        $this->cms_add_group('Blog Author', 'Can Add, Edit, Delete & Publish his/her own articles');
        $this->cms_add_group('Blog Contributor', 'Can Add, Edit, and Delete his/her own articles');
        $this->cms_assign_navigation($this->cms_complete_navigation_name('manage_article'),'Blog Editor');
        $this->cms_assign_navigation($this->cms_complete_navigation_name('manage_article'),'Blog Author');
        $this->cms_assign_navigation($this->cms_complete_navigation_name('manage_article'),'Blog Contributor');
        $this->cms_assign_navigation($this->cms_complete_navigation_name('manage_category'),'Blog Editor');

        // add configuration
        $this->cms_add_config($this->cms_complete_navigation_name('moderation'), 'FALSE', 'Is comment in blog need moderation?');

        // add routes
        if($module_path == 'blog'){
            $controller_path = 'blog';
        }else{
            $controller_path = $module_path.'/blog';
        }
        $this->cms_add_route($controller_path.'/(:any)\.html',    $controller_path.'/index/$1', 
            'Route to blog\'s article');
        $this->cms_add_route($controller_path.'/category/(:any)', $controller_path.'/index//$1', 
            'Route to blog\'s category');
        $this->cms_add_route($controller_path.'/archive/(:any)',  $controller_path.'/index///$1', 
            'Route to blog\'s archive');
        $this->cms_add_route($controller_path.'/category',  $controller_path.'/index/', 
            'Route to blog\'s category');
        $this->cms_add_route($controller_path.'/archive',  $controller_path.'/index/', 
            'Route to blog\'s archive');

        // publication status
        $fields = array(
                'status' => $this->TYPE_VARCHAR_50_NULL,
            );
        $this->dbforge->add_field($fields);
        $this->dbforge->add_key('status', TRUE);
        $this->dbforge->create_table($this->cms_complete_table_name('publication_status'));

        // article
        $fields = array(
                'article_id' => $this->TYPE_INT_UNSIGNED_AUTO_INCREMENT,
                'article_title' => $this->TYPE_TEXT,
                'article_url' => $this->TYPE_TEXT,
                'keyword' => $this->TYPE_TEXT,
                'description' => $this->TYPE_TEXT,
                'date' => $this->TYPE_DATETIME_NULL,
                'author_user_id' => $this->TYPE_INT_UNSIGNED_NULL,
                'content' => $this->TYPE_TEXT,
                'allow_comment' => $this->TYPE_INT_UNSIGNED_NULL,
                'status' => array('type'=>'varchar', 'constraint' => 20, 'default' => 'draft', 'null' => FALSE,),
                'visited' => $this->TYPE_INT_UNSIGNED_NULL,
                'featured' => $this->TYPE_INT_UNSIGNED_NULL,
                'publish_date' => $this->TYPE_DATE_NULL,
        );
        $this->dbforge->add_field($fields);
        $this->dbforge->add_key('article_id', TRUE);
        $this->dbforge->create_table($this->cms_complete_table_name('article'));

        // category
        $fields = array(
                'category_id' => $this->TYPE_INT_UNSIGNED_AUTO_INCREMENT,
                'category_name' => $this->TYPE_VARCHAR_50_NULL,
                'description' => $this->TYPE_TEXT,
        );
        $this->dbforge->add_field($fields);
        $this->dbforge->add_key('category_id', TRUE);
        $this->dbforge->create_table($this->cms_complete_table_name('category'));

        // category_article
        $fields = array(
                'category_article_id' => $this->TYPE_INT_UNSIGNED_AUTO_INCREMENT,
                'category_id' => $this->TYPE_INT_UNSIGNED_NULL,
                'article_id' => $this->TYPE_INT_UNSIGNED_NULL,
        );
        $this->dbforge->add_field($fields);
        $this->dbforge->add_key('category_article_id', TRUE);
        $this->dbforge->create_table($this->cms_complete_table_name('category_article'));

        // comment
        $fields = array(
                'comment_id' => $this->TYPE_INT_UNSIGNED_AUTO_INCREMENT,
                'article_id' => $this->TYPE_INT_UNSIGNED_NULL,
                'date' => $this->TYPE_DATETIME_NULL,
                'author_user_id' => $this->TYPE_INT_UNSIGNED_NULL,
                'name' => $this->TYPE_VARCHAR_50_NULL,
                'email' => $this->TYPE_VARCHAR_50_NULL,
                'website' => $this->TYPE_VARCHAR_50_NULL,
                'content' => $this->TYPE_TEXT,
                'parent_comment_id' => $this->TYPE_INT_UNSIGNED_NULL,
                'read' => array('type' => 'INT', 'constraint' => 20, 'unsigned' => TRUE, 'null' => FALSE, 'default' => 0,),
                'approved'=>array('type' => 'INT', 'constraint' => 20, 'unsigned' => TRUE, 'null' => FALSE, 'default' => 0,),
        );
        $this->dbforge->add_field($fields);
        $this->dbforge->add_key('comment_id', TRUE);
        $this->dbforge->create_table($this->cms_complete_table_name('comment'));

        // photo
        $fields = array(
                'photo_id' => $this->TYPE_INT_UNSIGNED_AUTO_INCREMENT,
                'article_id' => array('type' => 'INT', 'constraint' => 10, 'unsigned' => TRUE,),
                'url' => $this->TYPE_TEXT,
                'index'     => array('type' => 'INT', 'constraint' => 20, 'unsigned' => TRUE, 'null' => FALSE, 'default' => 0,),
                'caption'   => $this->TYPE_TEXT,
        );
        $this->dbforge->add_field($fields);
        $this->dbforge->add_key('photo_id', TRUE);
        $this->dbforge->create_table($this->cms_complete_table_name('photo'));

        // publication status
        $table_name = $this->cms_complete_table_name('publication_status');
        $data = array('status'=>'draft');
        $this->db->insert($table_name, $data);
        $data = array('status'=>'published');
        $this->db->insert($table_name, $data);
        $data = array('status'=>'scheduled');
        $this->db->insert($table_name, $data);


        // category
        $table_name = $this->cms_complete_table_name('category');
        $data = array('category_name' => 'News');
        $this->db->insert($table_name, $data);
        $data = array('category_name' => 'Fun');
        $this->db->insert($table_name, $data);


        if(CMS_SUBSITE == '' && !defined('CMS_OVERRIDDEN_SUBSITE')){
            // article
            $table_name = $this->cms_complete_table_name('article');
            $data = array('article_title' => 'Scandal, A Pop Rock Girl Band From Osaka',
                'article_url' => 'scandal',
                'keyword' => 'scandal, pop rock, girl, band, osaka',
                'description' => 'Scandal is a pop rock girl band from Osaka, Japan, formed by four high school girls',
                'date'=>'2013-03-25 09:50:49',
                'status'=>'published',
                'author_user_id'=>1,
                'allow_comment'=>1,
                'content'=> '<p style="text-align: justify;">'.PHP_EOL.'    SCANDAL (スキャンダル Sukyandaru?, stylized as SCANDAL) is a Japanese pop rock girl band from Osaka, Japan. Formed in August 2006 by four high school girls, they started playing street lives until they were noticed and signed to the indie label Kitty Records. In 2008, they released three singles and a mini-album while performing shows in the United States, France, and Hong Kong. That October, Scandal released their major debut single, &quot;Doll&quot;, under Epic Records Japan.'.PHP_EOL.'</p>'.PHP_EOL.'<p style="text-align: justify;">'.PHP_EOL.'    The band has performed the theme songs for many anime, including &quot;Shōjo S&quot; for Bleach and &quot;Shunkan Sentimental&quot; for Fullmetal Alchemist: Brotherhood. With numerous overseas performances and anime theme songs, Scandal has built a considerable international fanbase.'.PHP_EOL.'</p>'.PHP_EOL.'<div style="page-break-after: always;">'.PHP_EOL.'    <span style="display: none;">&nbsp;</span>'.PHP_EOL.'</div>'.PHP_EOL.'<h3 style="text-align: justify;">'.PHP_EOL.'    Indie career'.PHP_EOL.'</h3>'.PHP_EOL.'<p style="text-align: justify;">'.PHP_EOL.'    Scandal was formed in August 2006 by four high school girls. The girls, Haruna, Mami, Tomomi, and Rina, met in an Osaka vocal and dance school called Caless. Shortly thereafter, they started performing street lives every weekend at Shiroten in Osaka Castle Park. Soon, they started getting offers from clubs in Osaka and Kyoto. The band&#39;s name originates from a sign near Studio Brotherz, a studio where they practiced in their early days. The studio is on the sixth floor of a building shared with other businesses, namely adult shops. The girls decided to choose the biggest sign among the shops, &quot;Scandal&quot; (スキャンダル Sukyandaru?), as the name for their band.'.PHP_EOL.'</p>'.PHP_EOL.'<p style="text-align: justify;">'.PHP_EOL.'    Scandal signed with indie label Kitty Records and released three singles exclusive to Tower Records in 2008. The first, &quot;Space Ranger&quot;, ranked #2 on the Tower indie charts and the other two, &quot;Koi Moyō&quot; and &quot;Kagerō&quot;, ranked #1. In March, they embarked on the Japan Nite US tour 2008, touring six major cities in the United States. They also performed at Sakura-Con, one of the largest anime conventions in the United States. In July, they performed in front of 10,000 people at France&#39;s Japan Expo and also at Hong Kong&#39;s Animation-Comic-Game Hong Kong in August. Scandal concluded their indie career with the release of their first mini-album, Yah! Yah! Yah! Hello Scandal: Maido! Scandal Desu! Yah Yah Yah!.'.PHP_EOL.'</p>'.PHP_EOL.'<h3 style="text-align: justify;">'.PHP_EOL.'    Major debut'.PHP_EOL.'</h3>'.PHP_EOL.'<p style="text-align: justify;">'.PHP_EOL.'    2008 continued to be an eventful year for Scandal. In October, they made their major debut on Epic Records Japan with the single &quot;Doll&quot;. It gave them more exposure, including appearances on mainstream music television shows like Music Station. The band released their second major single &quot;Sakura Goodbye&quot; in March 2009 to commemorate Mami and Tomomi&#39;s high school graduation. The song is a new version of their indie song, &quot;Sakura&quot;, only heard live. The following month, their then upcoming third major single &quot;Shōjo S&quot; was used as the tenth opening theme for the anime Bleach. This brought their popularity up even higher as the single ranked #6 on the Oricon charts when it was released two months later in June.'.PHP_EOL.'</p>'.PHP_EOL.'<p style="text-align: justify;">'.PHP_EOL.'    On October 14th, Scandal released their fourth major single, &quot;Yumemiru Tsubasa&quot; which was followed with their major debut album, Best Scandal, the next week. The album ranked #5 on the Oricon weekly chart, making them the first girl band since Zone to have a debut album chart in the top five. In December, Scandal embarked on their first one-man tour. Concluding the year, the band won a New Artist Award at the 51st Japan Record Award, but lost the Best New Artist Award to Big Bang.'.PHP_EOL.'</p>'.PHP_EOL.'<p style="text-align: justify;">'.PHP_EOL.'    2010 began with Scandal&#39;s fifth major single in February, &quot;Shunkan Sentimental&quot;. It was used as the fourth ending theme for the anime Fullmetal Alchemist: Brotherhood. The following month, they embarked on a spring tour, Scandal: Shunkan Sakura Zensen Tour 2010 Spring. Prior to its start, the band conducted a Twitter poll to choose a song to cover for the tour. The winner was &quot;Secret Base (Kimi ga Kureta Mono)&quot;, which was chosen out of over 600 candidates. In June, Scandal released a pop tune for summer, &quot;Taiyō to Kimi ga Egaku Story&quot;, followed by their first original ballad, &quot;Namida no Regret&quot;, in July.'.PHP_EOL.'</p>'.PHP_EOL.'<p style="text-align: justify;">'.PHP_EOL.'    Between the end of July and the beginning of August, Scandal traveled to Hong Kong. The band performed for the third consecutive year at the Animation-Comic-Game Hong Kong convention and held their first one-man live concert in Hong Kong, which sold out. SCANDAL was also featured on the cover of the Hong Kong magazine re:spect music magazine, and their previously released single &quot;Taiyō to Kimi ga Egaku Story&quot; reached #1 on the Radio Television Hong Kong J-pop chart. Earlier in the year, they were awarded a bronze newcomer award by RTHK, similar to the Hong Kong Grammys.'.PHP_EOL.'</p>'.PHP_EOL.'<p style="text-align: justify;">'.PHP_EOL.'    After returning to Japan, Scandal released their second album, Temptation Box, on August 11. The album debuted at #3 on the Oricon weekly chart, making them the first girl band to have an album chart in the top three in over a year since Chatmonchy&#39;s Kokuhaku. The album was also released in 42 other countries worldwide.'.PHP_EOL.'</p>'.PHP_EOL.'<p style="text-align: justify;">'.PHP_EOL.'    Later in August, Scandal provided the theme, insert, and ending songs for the animated film Loups=Garous, which premiered on the 28th in most of Japan. The songs were &quot;Midnight Television&quot;, &quot;Koshi-Tantan&quot;, and &quot;Sayonara My Friend&quot;, respectively. The band also appeared as themselves, marking their big-screen debut as a band. They were shown in a musical performance scene that was created with the help of motion capture, providing a realistic representation of the band&#39;s movements. Each member also had a role voicing a minor character.'.PHP_EOL.'</p>'.PHP_EOL.'<p style="text-align: justify;">'.PHP_EOL.'    Two months following Temptation Box, Scandal released their eighth major single, &quot;Scandal Nanka Buttobase&quot;, on October 6, 2010. The title track was written and composed by the husband-and-wife duo Yoko Aki and Ryudo Uzaki, who are known for creating many songs for Momoe Yamaguchi. The limited edition DVDs contains performances from the band&#39;s first television show, Shiteki Ongaku Jijō, which ran for 13 episodes from July to September 2010. In November, Scandal released a cover mini-album called R-Girl&#39;s Rock!. It features songs by female artists that they respect from the last three decades, including their cover of &quot;Secret Base (Kimi ga Kureta Mono)&quot; from May. Rina undertook her first lead vocal on the song &quot;Sunny Day Sunday&quot;.'.PHP_EOL.'</p>'.PHP_EOL.'<p style="text-align: justify;">'.PHP_EOL.'    Scandal continued into 2011 with their ninth major single on February 9. Titled &quot;Pride&quot;, the song was used as the second ending theme for the anime Star Driver: Kagayaki no Takuto. The single also includes the tracks &quot;Cute!&quot;, a collaboration with Sanrio&#39;s Cinnamoroll, and &quot;Emotion&quot;, their first song that was written solely by a band member. Their tenth major single, &quot;Haruka&quot;, was released on April 20. The title track was used as the theme song for the animated film Tofu Kozou, while the song &quot;Satisfaction&quot; was later used as the promotional song for the release of Windows 8. This was followed by their eleventh major single, &quot;Love Survive&quot;, as well as their third studio album Baby Action. They also embarked on their first Asian Tour, performing to sell out crowds in Hong Kong, Taiwan and Singapore.'.PHP_EOL.'</p>'.PHP_EOL.'<p style="text-align: justify;">'.PHP_EOL.'    2012 proved to be a year of firsts for Scandal. The title track of their twelfth single, &quot;Harukaze&quot;, was used as the the opening theme for the anime Bleach. The following month, they released their first Best Of album, Scandal Show, as well as holding their first concert at the Nippon Budokan, thus becoming the fastest girl band to perform there since their debut. In July, they released their thirteenth major single, &quot;Taiyō Scandalous&quot;. This single marked the first official release of their subunits, Dobondobondo (Mami x Tomomi) and Almond Crush (Haruna x Rina). This was followed by their fourteenth major single, &quot;Pin Heel Surfer&quot;, and their fourth major album, Queens Are Trumps: Kirifuda wa Queen. With this release, they became the first girl band to achieve fur consecutive top 5 positions in the Oricon Weekly charts. They also held a concert in Malaysia in December, becoming the first Japanese band to hold a solo concert there.'.PHP_EOL.'</p>'.PHP_EOL.'<p style="text-align: justify;">'.PHP_EOL.'    Scandal started 2013 by fulfilling one of their biggest dreams they had since their formation by performing in their hometown at the Osaka-jō Hall in March. Later that month they also performed to sellout crowds on their 2nd Asian Tour in Indonesia, Singapore and Thailand. During this period they also announced their fourteenth major single &quot;Awanai Tsumori no, Genki de ne&quot; released in May, with the title track being used as the theme song for the movie &quot;Ore wa Mada Honki Dashitenai Dake&quot;.'.PHP_EOL.'</p>'
            );
            $this->db->insert($table_name, $data);

            // category_article
            $table_name = $this->cms_complete_table_name('category_article');
            $data = array('category_id'=>2, 'article_id'=>1);
            $this->db->insert($table_name, $data);

            // photos
            $table_name = $this->cms_complete_table_name('photo');
            for($i=1; $i<9; $i++){
                $file_name = $this->duplicate_file('0'.$i.'.jpg');
                $data = array(
                        'article_id'=>1, 
                        'url'=>$file_name, 
                        'index' => $i, 
                        'caption' => 'Caption for the #'.$i.' photo.<br /> Kimi no kokoro ni tsutsumareta ai wo chikai... '.
                            'Nemurenai yoru nando sugitemo nee uketomete. '.
                            'Ima sugu aitai namida tomaranai furetakute. '.
                            'Suki dakara furushikute'
                    );
                $this->db->insert($table_name, $data);
            }

            // comment
            $table_name = $this->cms_complete_table_name('comment');
            $data = array('article_id'=>1, 'author_user_id'=>1, 'read'=>0, 'date'=>'2013-03-25 09:53:16', 'content'=>'Great comment for great article', 'approved'=>1);
            $this->db->insert($table_name, $data);
        }

    }

    private function duplicate_file($original_file_name){
        $this->load->library('image_moo');
        $image_path = FCPATH . 'modules/' . $this->cms_module_path().'/assets/uploads/';
        $file_name = (CMS_SUBSITE==''?'main_':CMS_SUBSITE) . $original_file_name;
        copy($image_path.$original_file_name, $image_path.$file_name);

        $thumbnail_name = 'thumb_'.$file_name;
        $this->image_moo->load($image_path.$file_name)->resize(800,75)->save($image_path.$thumbnail_name,true);

        return $file_name;
    }

    // IMPORT SQL FILE
    private function import_sql($file_name){
        $this->execute_SQL(file_get_contents($file_name), '/*split*/');
    }

}
