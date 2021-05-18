<?php

class Openprovider extends Module
{
    /**
     * @const string
     */
    private const moduleName = 'openprovider';

    private const openproviderTokenTable = 'openprovider_token_table';

    /**
     * @var string default module path
     */
    private static string $defaultModuleViewPath;

    public function __construct()
    {
        // Loading module config
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Loading language
        Language::loadLang(self::moduleName, null, dirname(__FILE__) . DS . "language" . DS);

        Loader::load(__DIR__ . DS . 'vendor' . DS . 'autoload.php');
        Loader::loadComponents($this, ['Input', 'Record']);

        Loader::load(__DIR__ . DS . 'apis' . DS . 'openprovider_api.php');

        self::$defaultModuleViewPath = 'components' . DS . 'modules' . DS . self::moduleName . DS;
    }

    public function install()
    {
        $this->createOpenproviderTokenTable();
    }

    public function uninstall($module_id, $last_instance)
    {
        $this->deleteOpenproviderTokenTable();
    }

    /**
     * The manageModule() method returns HTML content for the manage module page for the given module.
     * Any post data submitted will be passed by reference in $vars.
     *
     * @param mixed $module
     * @param array $vars
     * @return string
     * @throws Exception
     * @see https://docs.blesta.com/display/dev/Module+Methods#ModuleMethods-manageModule($module,array&$vars)
     */
    public function manageModule($module, array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view           = new View('manage', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView(self::$defaultModuleViewPath);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);
        $this->view->set('module', $module);

        return $this->view->fetch();
    }

    /**
     * The manageAddRow() method returns HTML content for the add module row page.
     * Any post data submitted will be passed by reference in $vars.
     *
     * @param array $vars
     * @return string
     * @throws Exception
     * @see https://docs.blesta.com/display/dev/Module+Methods#ModuleMethods-manageAddRow(array&$vars)
     */
    public function manageAddRow(array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view           = new View('add_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView(self::$defaultModuleViewPath);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);
        Loader::loadModels($this, ['Services', 'ModuleManager', 'Clients', 'ClientGroups']);

        $this->view->set('vars', (object)$vars);

        return $this->view->fetch();
    }

    /**
     * The manageEditRow() method returns HTML content for the edit module row page given the module row to update.
     * Any post data submitted will be passed by reference in $vars.
     *
     * @param stdClass $module_row
     * @param array $vars
     * @return string
     * @throws Exception
     * @see https://docs.blesta.com/display/dev/Module+Methods#ModuleMethods-manageEditRow($module_row,array&$vars)
     */
    public function manageEditRow($module_row, array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view           = new View('edit_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView(self::$defaultModuleViewPath);

        // Set initial module row meta fields for vars
        if (empty($vars)) {
            $vars = $module_row->meta;
        } else {
            if (empty($vars['test_mode'])) {
                $vars['test_mode'] = 'false';
            }
        }

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);
        Loader::loadModels($this, ['Services', 'ModuleManager', 'Clients', 'ClientGroups']);

        $this->view->set('vars', (object)$vars);

        return $this->view->fetch();
    }

    /**
     * This method attempts to add a module row given the input vars, and sets any Input errors on failure.
     * This method returns meta fields as an array containing an array of key=>value fields for each meta field and its value,
     * as well as whether the value should be encrypted.
     *
     * @param array $vars
     * @return array
     * @see https://docs.blesta.com/display/dev/Module+Methods#ModuleMethods-addModuleRow(array&$vars)
     */
    public function addModuleRow(array &$vars)
    {
        $allowed_fields = ['username', 'password', 'test_mode', 'openprovider_module'];
        $encrypted_fields = ['password'];

        // Set unspecified checkboxes
        if (empty($vars['test_mode'])) {
            $vars['test_mode'] = 'false';
        }

        $rules = $this->getRowRules($vars);

        $this->Input->setRules($rules);

        if ($this->Input->validates($vars)) {
            // Add each field
            $meta = [];

            foreach ($vars as $key => $value) {
                if (in_array($key, $allowed_fields)) {
                    $meta[] = [
                        'key' => $key,
                        'value' => $value,
                        'encrypted' => in_array($key, $encrypted_fields) ? 1 : 0
                    ];
                }
            }

            return $meta;
        }
    }

    /**
     * This method attempts to update a module row given the input vars and the module row,
     * and sets any Input errors on failure.
     * This method returns meta fields as an array containing an array of key=>value fields for each meta field and its value,
     * as well as whether the value should be encrypted.
     *
     * This method is very similar to addModuleRow().
     *
     * @param $module_row
     * @param array $vars
     * @return array
     * @see https://docs.blesta.com/display/dev/Module+Methods#ModuleMethods-editModuleRow($module_row,array&$vars)
     */
    public function editModuleRow($module_row, array &$vars)
    {
        $allowed_fields = ['username', 'password', 'test_mode', 'openprovider_module'];
        $encrypted_fields = ['password'];

        // Merge package settings on to the module row meta
        $module_row = array_merge($vars, (array)$module_row->meta);

        // Set unspecified checkboxes
        if (empty($vars['test_mode'])) {
            $vars['test_mode'] = 'false';
        }

        $rules = $this->getRowRules($vars);

        $this->Input->setRules($rules);

        if ($this->Input->validates($vars)) {
            // Add each field
            $meta = [];

            foreach ($module_row as $key => $value) {
                if (in_array($key, $allowed_fields)) {
                    $meta[] = [
                        'key' => $key,
                        'value' => $value,
                        'encrypted' => in_array($key, $encrypted_fields) ? 1 : 0
                    ];
                }
            }

            return $meta;
        }
    }

    /**
     * return list of rules for validate adding or editing reseller accounts
     *
     * @return array[][]
     */
    private function getRowRules(&$vars)
    {
        return [
            'username' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('OpenProvider.!error.username.empty', true)
                ]
            ],
            'password' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('OpenProvider.!error.password.empty', true)
                ],
                'valid_connection' => [
                    'last' => true,
                    'rule' => [
                        [$this, 'validateConnection'],
                        $vars['username'],
                        $vars['test_mode'] ?? 'false'
                    ],
                    'message' => Language::_('OpenProvider.!error.password.valid_connection', true)
                ]
            ]
        ];
    }

    public function validateConnection($password, $username, $test_mode)
    {
        $api = $this->getApi();

        $api->getConfig()->setHost($test_mode == 'true' ? OpenProviderApi::API_CTE_URL : OpenProviderApi::API_URL);
        $token = $api->call('generateAuthTokenRequest', ['username' => $username, 'password' => $password])
            ->getData()['token'] ?? '';
        
        $is_token_exists = strlen($token) > 0;

        if ($is_token_exists) {
            $this->setOpenproviderTokenToDatabase(
                $this->generateUserHash($username, $password, $test_mode == 'true'),
                $token,
                date("Y-m-d H:i:s",strtotime(date("Y-m-d H:i:s")." +48 hours"))
            );
        }
        
        return $is_token_exists;
    }

    private function getApi($username = null, $password = null, $test_mode = true)
    {
        $api = new OpenProviderApi();

        $api->getConfig()->setHost($test_mode ? OpenProviderApi::API_CTE_URL : OpenProviderApi::API_URL);

        if (is_null($username) || is_null($password)) {
            return $api;
        }

        $user_hash = $this->generateUserHash($username, $password, $test_mode);

        $token = $this->getOpenproviderTokenFromDatabase($user_hash);

        if (!$token) {
            $token = $api->call('generateAuthTokenRequest', ['username' => $username, 'password' => $password])
                ->getData()['token'] ?? '';
        }

        if (!$token) {
            return $api;
        }

        $token_until_date = date("Y-m-d H:i:s",strtotime(date("Y-m-d H:i:s")." +48 hours"));
        $this->setOpenproviderTokenToDatabase($user_hash, $token, $token_until_date);

        $api->getConfig()->setToken($token);

        return $api;
    }

    private function getOpenproviderTokenFromDatabase($user_hash)
    {
        $datetime_now_minus_half_hour = date("Y-m-d H:i:s",strtotime(date("Y-m-d H:i:s")." -30 minutes"));
        $token = $this->Record
            ->from(self::openproviderTokenTable)
            ->select()
            ->where('user_hash', '=', $user_hash)
            ->fetch();

        if (!$token) {
            return '';
        }

        $token_until_date = strtotime($token->until_date);
        if ($datetime_now_minus_half_hour < $token_until_date) {
            return '';
        }

        return $token->token;
    }

    private function setOpenproviderTokenToDatabase($user_hash, $token, $until_date)
    {
        Loader::loadComponents($this, ['Record']);
        try {
            $this->Record
                ->duplicate('token', '=', $token)
                ->duplicate('until_date', '=', $until_date)
                ->insert(self::openproviderTokenTable, ['user_hash' => $user_hash, 'token' => $token, 'until_date' => $until_date]);
        } catch (\Exception $e) {}
    }

    private function createOpenproviderTokenTable()
    {
        Loader::loadComponents($this, ['Record']);
        try {
            $this->Record
                ->setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])
                ->setField('user_hash', ['type' => 'varchar', 'size' => 255])
                ->setField('token', ['type' => 'varchar', 'size' => 255])
                ->setField('until_date', ['type' => 'datetime', 'is_null' => true, 'default' => null])
                ->setKey(['id'], 'primary')
                ->setKey(['user_hash'], 'unique')
                ->create(self::openproviderTokenTable);
        } catch (\Exception $e) {}
    }

    private function deleteOpenproviderTokenTable()
    {
        Loader::loadComponents($this, ['Record']);
        try {
            $this->Record->drop(self::openproviderTokenTable);
        } catch (\Exception $e) {}
    }

    private function generateUserHash($username, $password, $test_mode)
    {
        return md5(substr($username, 0, 2) . substr($password, 0, 2) . $test_mode ? 'on' : 'off');
    }
}
