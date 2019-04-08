<?php
/**
* 2007-2019 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2019 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class PrestashopBridge extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'prestashop_bridge';
        $this->tab = 'others';
        $this->version = '1.0.0';
        $this->author = 'Mighty';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('prestashop-bridge');
        $this->description = $this->l('bridges the web creators pos with prestashop');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall your prestashop-bridge? This might break your installation...');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

        if (!Configuration::get('NEW_ORDER_WEBHOOK_URL'))
            $this->warning = $this->l('No URL provided.');
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        if (Shop::isFeatureActive())
            Shop::setContext(Shop::CONTEXT_ALL);
        
        Configuration::updateValue('PRESTASHOP-BRIDGE_LIVE_MODE', false);
        Configuration::updateValue('NEW_ORDER_WEBHOOK_URL', 'https://replace-this-url.please');

        return parent::install() &&
            $this->registerHook('actionValidateOrder');
    }

    public function uninstall()
    {
        Configuration::deleteByName('PRESTASHOP-BRIDGE_LIVE_MODE');
        Configuration::deleteByName('NEW_ORDER_WEBHOOK_URL');

        return parent::uninstall() &&
            $this->unregisterHook('actionValidateOrder');
    }

    public function hookActionValidateOrder()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, Configuration::get('NEW_ORDER_WEBHOOK_URL'));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_exec($ch);
        curl_close($ch);
    }

    public function getContent()
    {
        $output = null;

        if (Tools::isSubmit('submit'.$this->name))
        {
            $my_module_name = strval(Tools::getValue('NEW_ORDER_WEBHOOK_URL'));
            if (!$my_module_name
                || empty($my_module_name)
                || !Validate::isGenericName($my_module_name))
                $output .= $this->displayError($this->l('Invalid Configuration value'));
            else
            {
                Configuration::updateValue('NEW_ORDER_WEBHOOK_URL', $my_module_name);
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }
        return $output.$this->displayForm();
    }

    public function displayForm()
    {
        // Get default language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        // Init Fields form array
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings'),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Webhook POST URL'),
                    'name' => 'NEW_ORDER_WEBHOOK_URL',
                    'size' => 20,
                    'required' => true
                )
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'button'
            )
        );

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
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = array(
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

        // Load current value
        $helper->fields_value['NEW_ORDER_WEBHOOK_URL'] = Configuration::get('NEW_ORDER_WEBHOOK_URL');

        return $helper->generateForm($fields_form);
    }
}
