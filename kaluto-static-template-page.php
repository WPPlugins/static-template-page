<?php
/*
Plugin Name:  static template page
Version: 1.0
Plugin Author: Oren Kolker
Description: Allows the theme writer to add a single page  with a single template, and refer to it from code, without user configuration !!!

Copyright (C) 2010 Oren Kolker  (orenkolker@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/


class KalutoStaticTemplatePage {

    /*
     * Collect the pages registered on functions php;
     * Hold created pages Name , with there file name ( name=>filname);
     */
    var $register_pages;
    /*
     * Indicate REset;
     */
    var $reset;
    var $donot_create;
    var $show ;


    /*
     * THis is the DB!
     * Hold created pages ids, with there file name ( id=>filname);
     */
    var $created_pages;
    var $configured;

    var $dbvalues;
    var $pages_registered = false;

    /*
     * Debug variables
     */
    var $debug = false;


    // if ($debug) {echo "line:" . __LINE__." <br>";}

    /*
     * Constructor.
     * Init values;
     * Add actions;
     */

    function KalutoStaticTemplatePage() {

        require_once dirname( __FILE__ ) . '/api.php';

        
        $this->register_pages = array();  // name -> filename
        $this->created_pages = array();   //id --> filename
        $this->show = true;
        
        $this->dbvalues = array ( 'pages' => 'KalutoStaticTemplatePages',
                                                                'configured' => 'KalutoStaticTemplateConfigured'
        );

        add_action('deleted_post',  array($this, 'remove_from_db'));
        add_action('init',  array($this, 'add_posttype'));
        add_action('admin_notices',array($this, 'showAdminMessages'));
        add_action('init', array($this, 'install_static_pages'));
        add_filter('template_include', array($this, 'change_template'));
    }

    function showAdminMessages()
    {
       if ( !isset($_GET['post_type']) || $_GET['post_type'] != 'static_page')
           return;

            echo '<div id="message" class="updated fade">';
            echo '<p><strong>';
                   if ( $_GET['defaults'] == 'recoverd')
                   {
                       _e('Defaults recovered ', $this->domain);
                   }
                   else
                   {
                        _e('Restore to Defaults ', $this->domain);
                        echo ' <a href="' . esc_url( wp_nonce_url( "edit.php?post_type=static_page&backtodefault=true",'restore-defaults' ) ) . '">' . __('here') . '</a><br />';
                               }
            echo '</strong></p></div>';
            unset($_REQUEST['defaults']);
    }
    /*
     *
     *  For finding Permalink.
     * GEt the id of the page, by its filename
     */
    function get_id_from_file($file) {

        foreach ($this->created_pages as $id => $filename) {
            if ($file == $filename)
                return $id;
        }

        return false;
    }

    function clear_db() {
        foreach ($this->dbvalues as $key => $value) {
            delete_option($value);
        }
    }
        /*
     *
     *  For finding Permalink.
     * GEt the id of the page, by its name
     */
    function get_id_from_name($name) {

        if (!isset($this->register_pages[$name])) {
            return false;
        }

        $file = $this->register_pages[$name];
        foreach ($this->created_pages as $id => $filename) {
            if ($file == $filename)
                return $id;
        }

        return false;
    }



    /*
     * Interfear the WP Template Hirerchy
     *
     * If its a static page, change it to its own template.
     *  action (template_include);
     */
    function change_template($template) {
        if ( is_single( array_keys($this->created_pages)) ) {
            $id = get_the_ID();
            $template = dirname($template) . '/' . $this->created_pages[$id];
        }
        return $template;
    }

    /*
     * Poplate  $this->register_page
     *
     */
    function register_page($name, $filename) {
        $this->register_pages[$name] = $filename;
        $this->pages_registered = true;
    }

    function delete_pages()
    {
        foreach ($this->created_pages as $key => $value) {

            wp_delete_post($key);

        }
    }

    /*
     *  The first time it runs:
     *  It creates the static pages,
     *  And Update the DB.
     *  The rest of the Times:
     *  Populate created_pages array.
     */
    function install_static_pages()
    {
        //fatch from DB
         $this->created_pages = get_option($this->dbvalues['pages'] , array());
         $this->configured = get_option($this->dbvalues['configured'] ,false);

         //TODO: Debug
//         if ($this->reset)
//         {
//             $this->delete_pages();
//             $this->clear_db();
//             $this->created_pages =array();
//
//         }
         if ( $_REQUEST['backtodefault'] == 'true' ) {
            check_admin_referer('restore-defaults');

                 $this->delete_pages();
                  $this->clear_db();
                  $this->created_pages =array();
                  wp_redirect('edit.php?post_type=static_page&defaults=recoverd');
                  exit;
         }

         if ($this->configured)
                 return;
         // Find registerd pages that are not in DB
         $to_create = array();

        foreach ($this->register_pages as $name=>$file)
        {
            if (!in_array($file, $this->created_pages))
            {
                $to_create[$name] = $file;
            }
        }


        //Add pages that are not in the db
        foreach ($to_create as $name=>$file)
        {
                $id = $this->createPage($name);
                $this->created_pages[$id] = $file;
        }
        update_option($this->dbvalues['pages'],  $this->created_pages);
        update_option($this->dbvalues['configured'],  true);

    }


    /*
     *  Create Page, and return its ID
     */
    function createPage($name) {
        // Create post object
        $my_post = array(
            'post_title' => $name,
            'post_content' => 'This page is place order for $name.',
            'post_status' => 'publish',
            'post_author' => 1,
            'post_type' => 'static_page'
        );

        // Insert the post into the database
        $id = wp_insert_post($my_post);
        return $id;
    }

    function remove_from_db($id)
    {
        if (in_array ($id ,array_keys($this->created_pages)))
        {
            unset( $this->created_pages[$id]);
            update_option($this->dbvalues['pages'], $this->created_pages);
        }
       
    }
    //////////DEBUG;
    function debug() {
        echo "Register pages: <br>";
        foreach ($this->register_pages as $name => $filename) {
            echo " $name   \t $filename<br> ";
        }
        foreach ($this->created_pages as $id => $filename) {
            echo " $id   \t $filename<br> ";
        }
    }

    function add_posttype() {
    register_post_type('static_page', array('label' =>__('Static pages',$this->domain),
        'description' => '',
        'public' => true ,
        'show_ui' => true, 
        'show_in_menu' =>$this->show,// add debug option here should be false
        'capability_type' => 'post',
        'hierarchical' => false,
        'rewrite' => array('slug' => ''),
        'query_var' => true,
        'supports' => array('title','content'),
        'show_in_nav_menus' =>true
        ) );


}


}

global $kalutoStaticTemplatePage;
$kalutoStaticTemplatePage = new KalutoStaticTemplatePage;

