<?php 

    if(!defined('_PS_VERSION_')){ exit(); }
    
    class jabandonecarts extends Module{

        public function __construct(){

            $this->name = 'jabandonecarts';
            $this->tab = 'front_office_features';
            $this->version = '1.1.0';
            $this->author ='JosAlba';
            $this->need_instance = 0;
            //Da el aspecto de bootstrap.
            $this->bootstrap = true;
            $this->ps_versions_compliancy = array(
                'min' => '1.7.0.0', 'max' => _PS_VERSION_
            );

            //Añadimos los parametros para el modulo.
            $buffero = array(
                'JABANDONECARTS_TEST'
            );

            $tiendas = $this->getSites();
            for($i=0;$i<count($tiendas);$i++){
                $buffero['jabandonecarts_'.$tiendas[$i]['shop_id'].'_codOferta']='';
            }

            $config = Configuration::getMultiple ( $buffero );

            parent::__construct();

            $this->displayName = $this->l('JAbandonecarts');
            $this->description = $this->l('Envia correos al cliente con carritos abandonados');
            $this->confirmUninstall = $this->l('¿Estás seguro de que quieres desinstalar el módulo?');
        }
        public function install(){

            if(!parent::install() ){
                return false;
            }
            
            return true;
        }
        public function uninstall(){

            if(!parent::uninstall()){
                return false;
            }
                 
            return true;
        }
        public function reset(){

            if (!$this->uninstall(false)) {
                return false;
            }
            if (!$this->install(false)) {
                return false;
            }

            return true;
        }
        public function getContent(){

            $output = null;
            if (Tools::isSubmit('submit'.$this->name)){

                $tiendas = $this->getSites();

                for($i=0;$i<count($tiendas);$i++){
                    Configuration::updateValue('jabandonecarts_'.$tiendas[$i]['shop_id'].'_codOferta',  Tools::getValue('jabandonecarts_'.$tiendas[$i]['shop_id'].'_codOferta'));
                }

                Configuration::updateValue('JABANDONECARTS_TEST',  Tools::getValue('TEST_ACTIVO'));
                $output .= $this->displayConfirmation($this->l('Actualizado'));
                
            }

            $urlCront = Context::getContext()->shop->getBaseURL(true);
            $urlCront = $urlCront.'modules/jabandonecarts/testing.php';

            $this->smarty->assign(array(
                'urltesting' => $urlCront,
            ));

            $header = $this->display(__file__, 'views/templates/admin/header.tpl');
            $tester = $this->display(__file__, 'views/templates/admin/tester.tpl');

            return $output.$header.$this->displayForm().$tester;

        }
        public function displayForm(){

            $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

            $urlCront = Context::getContext()->shop->getBaseURL(true);
            $urlCront = $urlCront.'modules/jabandonecarts/fuego.php';

            $fields_form = array();
            $fields_form[0]['form'] = array(
                'legend' => array(
                    'title' => $this->l('Configuracion'),
                    'icon'  => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'is_bool' => true,
                        'label' => $this->l('Modo test'),
                        'name' => 'TEST_ACTIVO',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                    ),
                    array(
                        'type' => 'html',
                        'html_content' => '<strong>CRON</strong><p>Buscara los carritos de la ultima hora</p><br><p><input type="text" value="'.$urlCront.'" style="width: 500px;"></p>',
                        'name' => 'hrseparate1',
                    ),

                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right'
                )
            );
            
            //Añadir configuracion por tienda.
            $tiendas = $this->getSites();

            for($i=0;$i<count($tiendas);$i++){

                $fields_form[0]['form']['input'][]=array(
                        'type' => 'html',
                        'html_content' => '<strong>Configuracion para tienda '.$tiendas[$i]['name'].'</strong><br>',
                        'name' => 'hrseparate1',
                );
                $fields_form[0]['form']['input'][]=array(
                    'type'  => 'text',
                    'label' => $this->l('Codigo descuento : '),
                    'name'  => 'jabandonecarts_'.$tiendas[$i]['shop_id'].'_codOferta',
                    'size'  => 20,
                    'required' => true
                );
            }
            

            $helper = new HelperForm();
            // Module, token and currentIndex
            $helper->module = $this;
            $helper->name_controller = $this->name;
            $helper->token = Tools::getAdminTokenLite('AdminModules');
            $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

            // Language
            $helper->default_form_language = $default_lang;
            $helper->allow_employee_form_lang = $default_lang;

            // Title and toolbar
            $helper->title          = $this->displayName;
            $helper->show_toolbar   = false; 
            $helper->toolbar_scroll = false;
            $helper->submit_action  = 'submit'.$this->name;
            $helper->toolbar_btn    = array(
                'save' =>
                array(
                    'desc' => $this->l('Save'),
                    'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                    '&token='.Tools::getAdminTokenLite('AdminModules'),
                ),
                'back' => array(
                    'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                    'desc' => $this->l('Back to list')
                )
            );

            $helper->fields_value['TEST_ACTIVO']                    = Configuration::get('JABANDONECARTS_TEST');

            for($i=0;$i<count($tiendas);$i++){
                $helper->fields_value['jabandonecarts_'.$tiendas[$i]['shop_id'].'_codOferta']   = Configuration::get('jabandonecarts_'.$tiendas[$i]['shop_id'].'_codOferta');
            }

            return $helper->generateForm($fields_form);
        }

        /**
         * Devuelve un array con las tiendas.
         * @return Array Tiendas.
         */
        private function getSites(){

            $Tiendas = Db::getInstance()->executeS('SELECT `id_shop`,`name` from `' . _DB_PREFIX_ . 'shop`');
    
            $buffero = array();

            foreach ($Tiendas as $value) {
                $buffero[] = array(
                    'id_shop'   => $value['id_shop'],
                    'name'      => $value['name']
                );
            }

            return $buffero;
        }

        /**
         * Inicia el cron, recupera los carritos abandonados.
         * @return null
         */
        public function disparo(){

            $limitetiempo   =' date_add > DATE_ADD(NOW(), INTERVAL -1 HOUR) and ';
            $carritos       = Db::getInstance()->executeS('SELECT id_customer,id_shop from `' . _DB_PREFIX_ . 'cart` WHERE '.$limitetiempo.' id_customer!=0 and id_cart not in ( select id_cart from `' . _DB_PREFIX_ . 'orders` ) order by id_cart desc');
    
            foreach ($carritos as $value) {
                $this->Controlador($value);
            }

            echo 'Send: '.count($carritos);
        }

        /**
         * Controla los pedidos para envia el mail.
         * @param int $carrito Id del carrito
         * @return null
         */
        public function Controlador($data,$test=''){

            //Id de la tienda.
            $id_shop    = (int)$data['id_shop'];
            //Oferta especial para carritos.
                //Recuperamos las tiendas para sacar la variable de oferta para cada tienda.
            $tiendas = $this->getSites();
            //Establecemos sin oferta hasta que encuentre la tienda.
            $oferta  = '';
            for($i=0;$i<count($tiendas);$i++){
                //Comprueba la oferta de la tienda.
                if((int)$tiendas[$i]['id_shop']==(int)$data['id_shop']){
                    $oferta     = Configuration::get('jabandonecarts_'.$tiendas[$i]['shop_id'].'_codOferta');
                }
            }
            
            //Sacar informacion del cliente.
            $customer   = new Customer((int)$data['id_customer']);
            //Mail del cliente con el carrito abandonado.
            $email      = $customer->email;

            if($test==''){
                $test       = Configuration::get("JABANDONECARTS_TEST");
            }

            $emailTempl = 'abandono';
            if($oferta!=''){
                $emailTempl = 'abandonoferta';
            }

            if($test!=''){
                $email = (string) Configuration::get('PS_SHOP_EMAIL', null, null, $id_shop);
                echo '<p>Mail enviado a '.$email.'</p>';
            }

            try{
                if(!Mail::Send(
                    (int)(Configuration::get('PS_LANG_DEFAULT')),   // defaut language id
                    $emailTempl,                                    // email template file to be use
                    'Carrito abandonado',                           // email subject
                    array(
                        '{oferton}' => $oferta
                    ),
                    $email, // receiver email address 
                    null,   //to_name
                    (string) Configuration::get('PS_SHOP_EMAIL', null, null, $id_shop),
                    (string) Configuration::get('PS_SHOP_NAME', null, null, $id_shop),
                    null,   //file_attachment
                    null,   //mode_smtp
                    dirname(__FILE__).'/mails/',
                    false,
                    $id_shop
                )){
                    echo 'Error if:';
                    exit();
                }
            }catch(Exception $e){
                echo 'Error try:';
                exit();
            }

        }

        /**
         * Envia un correo de prueba.
         * @return null
         */
        public function testing(){

            $carritos       = Db::getInstance()->executeS('SELECT id_customer,id_shop from `' . _DB_PREFIX_ . 'cart` WHERE id_customer!=0 and id_cart not in ( select id_cart from `' . _DB_PREFIX_ . 'orders` ) order by id_cart desc');
    
            foreach ($carritos as $value) {
                $this->Controlador($value,'test');
                exit();
            }

        }
        
    }