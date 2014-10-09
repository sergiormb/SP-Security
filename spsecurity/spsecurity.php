<?php
   /*
      Plugin Name: SP Security
      Plugin URI: http://www.twitter.com/sergiormb
      Description: Plugin de seguridad para tu Wordpress. Envía correo electrónico al acceder al panel de administración. Este plugin está desarrollado para la asignatura de Seguridad Informática de la Universidad de Córdoba. Profesor: Juan Antonio Romero del Castillo 
      Version: 1.0
      Author: Sergio Pino
      Authorhttp://www.twitter.com/sergiormb
   */
    //Funciones de configuración de instalación

      global $db_version;
      $db_version = '1.0';
             function install_spsecurity(){
                  global $wpdb;
                  global $db_version;

                  $registers = $wpdb->prefix . 'spsecurity_registers';
                  /*
                   * We'll set the default character set and collation for this table.
                   * If we don't do this, some characters could end up being converted 
                   * to just ?'s when saved in our table.
                   */
                  $charset_collate = '';

                  if ( ! empty( $wpdb->charset ) ) {
                    $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
                  }

                  if ( ! empty( $wpdb->collate ) ) {
                    $charset_collate .= " COLLATE {$wpdb->collate}";
                  }

                  $sql1 = "CREATE TABLE $registers (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                    user varchar(55) DEFAULT '' NOT NULL,
                    activity varchar(55) DEFAULT '' NOT NULL,
                    ip varchar(15) DEFAULT '' NOT NULL,
                    UNIQUE KEY id (id)
                  ) $charset_collate;";

                  require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                  dbDelta( $sql1 );

                  add_option( 'db_version', $db_version );
             }   

              register_activation_hook( __FILE__, 'install_spsecurity' );



    //Función desinstalación
             
             function uninstall_spsecurity(){
                global $wpdb; 
                $table_name = $wpdb->prefix . "spsecurity_registers";
                $sql = "DROP TABLE $table_name";
                $wpdb->query($sql);
             }

             add_action('activate_spsecurity/spsecurity.php','install_spsecurity');
             add_action('activate_spsecurity/spsecurity.php', 'uninstall_spsecurity');  

    /* Páginas del plugin
          Aquí configuramos las páginas del menu de nuestro plugin
    */
              //Panel de Bienvenida
               function spsecurity_panel(){
                  include('template/panel.html');        
               }
               //Panel de Configuración
               function spsecurity_history() {
                  global $wpdb; 
                  $table_name = $wpdb->prefix . "spsecurity_registers";
                  $result= $wpdb->get_results("SELECT * FROM $table_name ; " );

                  ?>
                <!-- Create a header in the default WordPress 'wrap' container -->
                  <div class="wrap">

                      
                      <h2>Historial de Eventos</h2>
                      <p class="description">Listado de acciones en el sistema</p>
                      <?php
                      if($result){
                        ?>
                      <table class="wp-list-table widefat fixed pages">
                          <thead>
                          <tr>
                            <th scope="col" id="cb" class="manage-column column-cb check-column" style="">Usuario</th>
                            <th scope="col" id="cb" class="manage-column column-cb check-column" style="">Evento</th>
                            <th scope="col" id="cb" class="manage-column column-cb check-column" style="">Fecha</th> 
                            <th scope="col" id="cb" class="manage-column column-cb check-column" style="">IP</th>       
                          </tr>
                          </thead>

                 <?php
                          foreach ($result as $value)
                             {
                              ?>
                              <tr class="post-1 type-post status-publish format-standard hentry category-sin-categoria alternate iedit author-self "><td><?php
                                            print_r($value->user);?></td><td><?php
                                            print_r($value->activity);?></td><td><?php
                                            print_r($value->time);?></td><td><?php
                                            print_r($value->ip);?></td></tr><?php
                             }

                  // ?>

                        

                      </table><?php }
                      else{
                        echo "No tiene ningun registro guardado.";
                      } ?>
                </div><!-- /.wrap -->
              <?php
               }
               //Panel de Historial
               function spsecurity_plugin_display() {
                ?>
                <!-- Create a header in the default WordPress 'wrap' container -->
                  <div class="wrap">

                      <div id="icon-themes" class="icon32"></div>
                      <h2>Configuración de SP Security</h2>
                      <?php settings_errors(); ?>

                  <!-- Create the form that will be used to render our options -->
                  <form method="post" action="options.php">
                    <?php settings_fields( 'spsecurity_plugin_display_options' ); ?>
                    <?php do_settings_sections( 'spsecurity_plugin_display_options' ); ?>
                    <?php submit_button(); ?>
                  </form>

                </div><!-- /.wrap -->
              <?php
                }

    /* Creación del Menú
          Añadimos el ménú y submenús
    */
              //Creación de la página principal
             function sp_security_add_menu(){   
                if (function_exists('add_options_page')) {
                   //add_menu_page
                   add_menu_page('SP Security', 'SP Security', 8, 'SPSecurity', 'spsecurity_panel');      }
             }
             if (function_exists('add_action')) {
                add_action('admin_menu', 'sp_security_add_menu'); 
             } 
             //Creación de las subpaginas Configuración e Historial
             function register_submenu_config() {
                 add_submenu_page( 'SPSecurity', 'Configuración', 'Configuración', 8, 'spsecurity_plugin_options', 'spsecurity_plugin_display' ); 
                 add_submenu_page( 'SPSecurity', 'Historial', 'Historial', 8, 'spsecurity_history', 'spsecurity_history' ); 
             }

            add_action( 'admin_menu', 'register_submenu_config' );



    /* Funciones tras enventos
          Estas funciones serán activadas tras determinados eventos del sistema
    */

            //Registro de actividades en el historial
            function sp_security_register_activity($user_name, $activity, $ip) {
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'spsecurity_registers';
                    
                    $wpdb->insert( 
                      $table_name, 
                      array( 
                        'time' => current_time( 'mysql' ),
                        'user' => $user_name, 
                        'activity' => $activity, 
                        'ip' => $ip,
                      ) 
                    );
                  }



            //Envio de email tras el logueo de usuario
            function spsecurity_email_send($user_login, $user) {
               $headers[] = 'From: SP Security <wpspsec@gmail.com>';
                if(!empty($_SERVER['HTTP_CLIENT_IP'])) {
                      $ip=$_SERVER['HTTP_CLIENT_IP'];
                  } elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                      $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
                  } else {
                      $ip=$_SERVER['REMOTE_ADDR'];
                  }
                $nav = $_SERVER['HTTP_USER_AGENT'];
               wp_mail( 'sergiormb88@gmail.com', 'Nuevo acceso de '. $user_login .' | '.date("d-m-Y H:i:s").'', 'El usuario '. $user_login .' acaba de acceder a su web. Acaba de acceder con el navegador: '.$nav.' y su dirección IP es: '.$ip.'' ,$headers );
               sp_security_register_activity( $user_login,'acceso',$ip);
            }

            add_action( 'phpmailer_init', 'my_phpmailer_example' );
            add_filter ( 'wp_login', 'spsecurity_email_send', 10, 2 );
            add_filter ( 'wp_admin', 'spsecurity_email_send', 10, 2 );
            

            /* Funciones para configurar los paneles */

            function spsecurity_initialize_plugin_options() {

              if( false == get_option( 'spsecurity_plugin_display_options' ) ) {
                add_option( 'spsecurity_plugin_display_options');
              } // end if

              // Add the section to reading settings so we can add our
              // fields to it
              add_settings_section(
                'spsecurity_general_setting_section',
                'Configuración',
                'spsecurity_general_options_callback',
                'spsecurity_plugin_display_options'
              );


              // Add the field with the names and function to use for our new
              // settings, put it in our new section
              add_settings_field(
                'spsecurity_input_email',
                'Email',
                'spsecurity_input_email_callback',
                'spsecurity_plugin_display_options',
                'spsecurity_general_setting_section',
                array(
                  'Indique el correo electrónico en el que desea recibir los avisos'
                )
              );

              add_settings_field(
                'spsecurity_input_geo',
                'Geolocalización',
                'spsecurity_input_geo_callback',
                'spsecurity_plugin_display_options',
                'spsecurity_general_setting_section'
              );

              // Register our setting so that $_POST handling is done for us and
              // our callback function just has to echo the <input>
              register_setting(
                'spsecurity_plugin_display_options',
                'spsecurity_plugin_display_options',
                'spsecurity_plugin_validate_input'
              );
            } // lanzadera_settings_api_init()

            add_action( 'admin_init', 'spsecurity_initialize_plugin_options' );

            function spsecurity_plugin_validate_input( $input ) {

              $output = array();

              foreach ( $input as $key => $value ) {

                switch ($key) {
                        case 'spsecurity_input_geo':
                          break;
                        case 'spsecurity_input_email':
                          $output[ $key ] = is_email($input[ $key ]);
                          if (!$output[ $key ]) {
                            add_settings_error('spsecurity_plugin_display_options', 'urlerror', 'El email no es válido o está vacío.', 'error');
                          }
                          break;
                }
              }

              return apply_filters( 'spsecurity_plugin_validate_input', $output, $input );

            } // end lanzadera_plugin_validate_input

            // ------------------------------------------------------------------
            // Settings section callback function
            // ------------------------------------------------------------------
            //
            // This function is needed if we added a new section. This function
            // will be run at the start of our section
            //

            function spsecurity_general_options_callback() {
              echo '<p class="description">Rellene los siguientes campos</p>';
            } // end lanzadera_general_options_callback()

           // end lanzadera_select_page_callback

            function spsecurity_input_email_callback($args) {

              $options = get_option('spsecurity_plugin_display_options');

              $html = '<input type="input" id="spsecurity_input_email" name="spsecurity_plugin_display_options[spsecurity_input_email]" size="40" value="' . esc_attr( $options['spsecurity_input_email'] ) . '"/>';
              $html .= '<p class="description">'  . $args[0] . '</p>';

              echo $html;

            } // end lanzadera_input_server_callback

            function spsecurity_input_geo_callback($args) {

                 $options = get_option( 'spsecurity_plugin_display_options' );

                  $html = '<input type="checkbox" id="spsecurity_input_geo" name="spsecurity_plugin_display_options[spsecurity_input_geo]" value="1"' . checked( 1, $options['checkbox_example'], false ) . '/>';
                  $html .= '<label for="checkbox_example">Marque la casilla si desea activar la geolocalización del usuario</label>';

                  echo $html;

            }