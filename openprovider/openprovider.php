<?php

use Openprovider\Api\Rest\Client\Domain\Model\DomainAdditionalData;
use Openprovider\Api\Rest\Client\Person\Model\CustomerExtensionAdditionalData;

class Openprovider extends Module
{
    /**
     * @const string
     */
    private const MODULE_NAME = 'openprovider';

    /**
     * @const string
     */
    private const TRANSFER_OPERATION = 'transfer';
    private const REGISTER_OPERATION = 'register';

    /**
     * This time was chosen because 100 seconds is enough to execute any sequence of requests
     */
    private const MINIMUM_TOKEN_LIFE_TIME_IN_SECONDS = 100;

    /**
     * This lifetime is enough to use one token per session.
     * But if not, token will be requested again
     */
    private const TOKEN_LIFE_TIME_IN_MINUTES = 10;

    private const FIELDS_TO_COMPARE_CUSTOMERS = [
        'first_name',
        'last_name',
        'company_name',
        'phone_number',
        'address',
        'email',
        'country',
        'state',
        'city',
        'zipcode',
    ];

    /**
     * @var string default module path
     */
    private $default_module_view_path;

    /**
     * Openprovider constructor.
     */
    public function __construct()
    {
        // Loading module config
        $this->loadConfig(__DIR__ . DS . 'config.json');

        // Loading language
        Language::loadLang(self::MODULE_NAME, null, dirname(__FILE__) . DS . 'language' . DS);

        Loader::load(__DIR__ . DS . 'vendor' . DS . 'autoload.php');
        Loader::loadComponents($this, ['Input', 'Record']);
        Loader::loadModels($this, ['ModuleManager', 'ModuleClientMeta']);
        Loader::load(__DIR__ . DS . 'apis' . DS . 'openprovider_api.php');

        Configure::load('openprovider', __DIR__ . DS . 'config' . DS);

        $this->default_module_view_path = 'components' . DS . 'modules' . DS . self::MODULE_NAME . DS;

        if (is_null($this->getModule())) {
            $modules = $this->ModuleManager->getInstalled();
            foreach ($modules as $module) {
                if (strtolower($module->name) == self::MODULE_NAME) {
                    $this->setModule($module);
                    break;
                }
            }
        }
    }

    /**
     * The methods are invoked when the module is installed, upgraded, or uninstalled respectively.
     *
     * @return void
     *
     * @see https://docs.blesta.com/display/dev/Module+Methods#ModuleMethods-install/upgrade/uninstall()
     */
    public function install(): void
    {}

    /**
     * The methods are invoked when the module is installed, upgraded, or uninstalled respectively.
     *
     * @return void
     *
     * @see https://docs.blesta.com/display/dev/Module+Methods#ModuleMethods-install/upgrade/uninstall()
     */
    public function uninstall($module_id, $last_instance): void
    {}

    /**
     * @param mixed $module
     * @param array $vars
     *
     * @return string HTML content for the manage module page for the given module.
     * Any post data submitted will be passed by reference in $vars.
     *
     * @throws Exception
     *
     * @see https://docs.blesta.com/display/dev/Module+Methods#ModuleMethods-manageModule($module,array&$vars)
     */
    public function manageModule($module, array &$vars): string
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view           = new View('manage', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView($this->default_module_view_path);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        $this->view->set('module', $module);

        return $this->view->fetch();
    }

    /**
     * @param array $vars
     *
     * @return string HTML content for the add module row page.
     * Any post data submitted will be passed by reference in $vars.
     *
     * @throws Exception
     *
     * @see https://docs.blesta.com/display/dev/Module+Methods#ModuleMethods-manageAddRow(array&$vars)
     */
    public function manageAddRow(array &$vars): string
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view           = new View('add_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView($this->default_module_view_path);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);
        Loader::loadModels($this, ['Services', 'ModuleManager', 'Clients', 'ClientGroups']);

        $this->view->set('vars', (object)$vars);

        return $this->view->fetch();
    }

    /**
     * @param stdClass $module_row
     * @param array $vars
     *
     * @return string HTML content for the edit module row page given the module row to update.
     * Any post data submitted will be passed by reference in $vars.
     *
     * @throws Exception
     *
     * @see https://docs.blesta.com/display/dev/Module+Methods#ModuleMethods-manageEditRow($module_row,array&$vars)
     */
    public function manageEditRow($module_row, array &$vars): string
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view           = new View('edit_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView($this->default_module_view_path);

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
     * @param array $vars
     *
     * @return array meta fields as an array containing an array of key=>value fields for each meta field and its value,
     * as well as whether the value should be encrypted.
     *
     * @see https://docs.blesta.com/display/dev/Module+Methods#ModuleMethods-addModuleRow(array&$vars)
     */
    public function addModuleRow(array &$vars): array
    {
        $allowed_fields   = ['username', 'password', 'test_mode', 'openprovider_module'];
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
                        'key'       => $key,
                        'value'     => $value,
                        'encrypted' => in_array($key, $encrypted_fields) ? 1 : 0
                    ];
                }
            }

            return $meta;
        }

        return [];
    }

    /**
     * @param array $vars
     *
     * @return array[][] list of rules for validate adding or editing reseller accounts
     */
    private function getRowRules(&$vars): array
    {
        return [
            'username' => [
                'valid' => [
                    'rule'    => 'isEmpty',
                    'negate'  => true,
                    'message' => Language::_('OpenProvider.!error.username.empty', true)
                ]
            ],
            'password' => [
                'valid' => [
                    'rule'    => 'isEmpty',
                    'negate'  => true,
                    'message' => Language::_('OpenProvider.!error.password.empty', true)
                ],
                'valid_connection' => [
                    'last'    => true,
                    'message' => Language::_('OpenProvider.!error.password.valid_connection', true),
                    'rule'    => [
                        [$this, 'validateConnection'],
                        $vars['username'],
                        $vars['test_mode'] ?? 'false'
                    ],
                ]
            ]
        ];
    }

    /**
     * @param $module_row
     * @param array $vars
     *
     * @return array meta fields as an array containing an array of key=>value fields for each meta field and its value,
     * as well as whether the value should be encrypted.
     *
     * This method attempts to update a module row given the input vars and the module row,
     * and sets any Input errors on failure.
     *
     * This method is very similar to addModuleRow().
     *
     * @see https://docs.blesta.com/display/dev/Module+Methods#ModuleMethods-editModuleRow($module_row,array&$vars)
     */
    public function editModuleRow($module_row, array &$vars): array
    {
        $allowed_fields   = ['username', 'password', 'test_mode', 'openprovider_module'];
        $encrypted_fields = ['password'];

        // Set unspecified checkboxes
        if (empty($vars['test_mode'])) {
            $vars['test_mode'] = 'false';
        }

        // Merge package settings on to the module row meta
        $module_row = array_merge((array)$module_row->meta, $vars);

        $rules = $this->getRowRules($vars);

        $this->Input->setRules($rules);

        if ($this->Input->validates($vars)) {
            // Add each field
            $meta = [];

            foreach ($module_row as $key => $value) {
                if (in_array($key, $allowed_fields)) {
                    $meta[] = [
                        'key'       => $key,
                        'value'     => $value,
                        'encrypted' => in_array($key, $encrypted_fields) ? 1 : 0
                    ];
                }
            }

            return $meta;
        }
    }

    /**
     * @param null $vars
     *
     * @return ModuleFields contains all fields used when adding or editing a package,
     * including any javascript that can be executed when the page is rendered with those fields.
     * Any post data submitted will be passed in $vars.
     *
     * @see https://docs.blesta.com/display/dev/Module+Methods#ModuleMethods-getPackageFields($vars=null)
     */
    public function getPackageFields($vars = null): ModuleFields
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        $types = [
            'domain' => Language::_('OpenProvider.package_fields.type_domain', true),
        ];

        // Set type of package
        $type = $fields->label(
            Language::_('OpenProvider.package_fields.type', true),
            'openprovider_type'
        );
        $type->attach(
            $fields->fieldSelect(
                'meta[type]',
                $types,
                $this->Html->ifSet($vars->meta['type']),
                ['id' => 'openprovider_type']
            )
        );
        $fields->setField($type);

        // Set all TLD checkboxes
        $tld_options = $fields->label(Language::_('OpenProvider.package_fields.tld_options', true));

        $tlds = $this->getTlds();
        sort($tlds);

        foreach ($tlds as $tld) {
            $tld_label = $fields->label($tld, 'tld_' . $tld);
            $tld_options->attach(
                $fields->fieldCheckbox(
                    'meta[tlds][]',
                    $tld,
                    (isset($vars->meta['tlds']) && in_array($tld, $vars->meta['tlds'])),
                    ['id' => 'tld_' . $tld],
                    $tld_label
                )
            );
        }
        $fields->setField($tld_options);

        // Set nameservers
        for ($i = 1; $i <= 5; $i++) {
            $type = $fields->label(
                Language::_('OpenProvider.nameserver.ns' . $i, true),
                'openprovider_ns' . $i
            );

            $type->attach(
                $fields->fieldText(
                    'meta[ns][]',
                    $this->Html->ifSet($vars->meta['ns'][$i - 1]),
                    ['id' => 'openprovider_ns' . $i]
                )
            );

            $fields->setField($type);
        }

        return $fields;
    }

    /**
     * @param int|null $module_row_id
     *
     * @return string[] a list of the TLDs supported by the registrar module.
     *
     * @see https://docs.blesta.com/display/dev/Module+Methods#ModuleMethods-getTlds($module_row_id=null)
     */
    public function getTlds($module_row_id = null): array
    {
        return Configure::get('OpenProvider.tlds');
    }

    /**
     * @param stdClass $package
     * @param array|null $vars
     *
     * @return bool value indicating whether the given input is valid.
     *
     * @see https://docs.blesta.com/display/dev/Module+Methods#ModuleMethods-validateService($package,array$vars=null)
     */
    public function validateService($package, array $vars = null): bool
    {
        $rules = [];
        // Transfers (EPP Code)
        if (isset($vars['transfer']) && ($vars['transfer'] == '1' || $vars['transfer'] == true)) {
            $rule  = [
                'auth' => [
                    'empty' => [
                        'rule'        => ['isEmpty'],
                        'negate'      => true,
                        'message'     => Language::_('OpenProvider.!error.epp.empty', true),
                        'post_format' => 'trim'
                    ]
                ],
            ];
            $rules = array_merge($rules, $rule);
        }

        if (isset($vars['identification_type'])) {
            if ($vars['identification_type'] == 'passport_number') {
                $rule  = [
                    'passport_number' => [
                        'empty' => [
                            'rule'        => ['isEmpty'],
                            'negate'      => true,
                            'message'     => Language::_('OpenProvider.!error.passport_number.empty', true),
                            'post_format' => 'trim',
                        ],
                    ],
                    'passport_series' => [
                        'empty' => [
                            'rule'        => ['isEmpty'],
                            'negate'      => true,
                            'message'     => Language::_('OpenProvider.!error.passport_series.empty', true),
                            'post_format' => 'trim',
                        ]
                    ],
                ];
                $rules = array_merge($rules, $rule);
            }

            if ($vars['identification_type'] == 'company_registration_number') {
                $rule  = [
                    'company_registration_number' => [
                        'empty' => [
                            'rule'        => ['isEmpty'],
                            'negate'      => true,
                            'message'     => Language::_('OpenProvider.!error.passport_number.empty', true),
                            'post_format' => 'trim',
                        ],
                    ],
                ];
                $rules = array_merge($rules, $rule);
                unset($rules['passport_number']);
                unset($rules['passport_series']);
            }
        }

        if (isset($rules) && count($rules) > 0) {
            $this->Input->setRules($rules);
            return $this->Input->validates($vars);
        }

        return true;
    }

    /**
     * @param stdClass $package
     * @param array|null $vars
     * @param null $parent_package
     * @param null $parent_service
     * @param string $status The status of the service being added. Possible values:
     *
     *  - active
     *
     *  - canceled
     *
     *  - pending
     *
     *  - suspended
     *
     * @return array|void contains an array of key=>value fields for each service field and its value,
     * as well as whether the value should be encrypted.
     *
     * @throws Exception
     *
     * @see https://docs.blesta.com/display/dev/Module+Methods#ModuleMethods-addService($package,array$vars=null,$parent_package=null,$parent_service=null,$status=%22pending%22)
     */
    public function addService(
        $package,
        array $vars = null,
        $parent_package = null,
        $parent_service = null,
        $status = 'pending'
    ): ?array
    {
        // Get the module row used for this service
        $row = $this->getModuleRow();

        $is_service_domain = $package->meta->type == 'domain';
        $use_module        = isset($vars['use_module']) && $vars['use_module'] == 'true';

        if ($is_service_domain) {
            $splitted_domain_name = $this->splitDomainName($vars['domain']);
            $tld                  = '.' . $splitted_domain_name['extension'];
        }

        // taking configuration fields
        $input_fields = array_merge(
            Configure::get('OpenProvider.domain_fields'),
            (array)Configure::get('OpenProvider.domain_fields' . $tld),
            (array)Configure::get('OpenProvider.nameserver_fields'),
            (array)Configure::get('OpenProvider.transfer_fields'),
            ['years' => true, 'transfer' => $vars['transfer'] ?? 1]
        );

        // if method use module
        if ($use_module) {
            $vars = array_merge($vars, $this->getServiceFields($vars['service_id']));

            $this->createDomainInOp($row, $vars, $package);
        }

        $meta   = [];
        $fields = array_intersect_key($vars, $input_fields);
        foreach ($fields as $key => $value) {
            $meta[] = [
                'key'       => $key,
                'value'     => $value,
                'encrypted' => 0
            ];
        }

        return $meta;
    }

    /**
     * @param $service_id
     *
     * @return array
     */
    private function getServiceFields($service_id)
    {
        if (!isset($this->Services)) {
            Loader::loadModels($this, ['Services']);
        }

        $service = $this->Services->get($service_id);

        $service_data = [];
        foreach ($service->fields as $field) {
            $service_data[$field->key] = $field->value;
        }

        return $service_data;
    }

    /**
     * @param string $domain_name
     *
     * @return array ['name', 'extension']
     */
    private function splitDomainName($domain_name): array
    {
        $domain_name_array = explode('.', $domain_name);

        return [
            'name'      => trim($domain_name_array[0]),
            'extension' => implode('.', array_slice($domain_name_array, 1)),
        ];
    }

    /**
     * @param $row
     * @param $vars
     * @param $package
     *
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    private function createDomainInOp($row, $vars, $package)
    {
        $splitted_domain_name = $this->splitDomainName($vars['domain']);

        $api = $this->getApi($row->meta->username, $row->meta->password, $row->meta->test_mode == 'true');

        if ($package->meta->type == 'domain') {
            $vars['years'] = 1;

            foreach ($package->pricing as $pricing) {
                if ($pricing->id == $vars['pricing_id']) {
                    $vars['years'] = $pricing->term;
                    break;
                }
            }
        }

        $name_servers = $this->getNameServersFromVarsOrDefault($vars, $package->meta->ns);

        $customer = $this->getCustomerData($vars);

        $additional_data = $this->getAdditionalData($vars);

        if (!empty($additional_data['customer_extension_additional_data'])) {
            $customer['extension_additional_data'] = [
                [
                    'name' => $splitted_domain_name['extension'],
                    'data' => $additional_data['customer_extension_additional_data'],
                ]
            ];
        }

        // Creating contacts and saving handles to database
        $handles = [];
        $create_customer_response = $api->call('createCustomerRequest', $customer);
        $this->logRequest($api);

        if (!isset($create_customer_response->getData()['handle'])) {
            throw new Exception($create_customer_response->getMessage(), $create_customer_response->getCode());
        }

        $handle = $create_customer_response->getData()['handle'];

        $handles['all'] = $handle;

        $domain = [
            'admin_handle'   => $handle,
            'billing_handle' => $handle,
            'owner_handle'   => $handle,
            'tech_handle'    => $handle,
            'domain'         => $splitted_domain_name,
            'period'         => $vars['years'],
            'name_servers'   => $name_servers,
            'autorenew'      => 'default',
        ];

        if (!empty($additional_data['domain_additional_data'])) {
            $domain['additional_data'] = $additional_data['domain_additional_data'];
        }

        $api_command = 'createDomainRequest';
        if (isset($vars['auth']) && !empty($vars['auth'])) {
            $domain['auth_code'] = $vars['auth'];
            $api_command = 'transferDomainRequest';
        }

        $domain_response = $api->call($api_command, $domain);

        $this->logRequest($api);

        // if creation domain failed we need to delete customers for it
        if ($domain_response->getCode() != 0 || !isset($domain_response->getData()['id'])) {
            foreach ($handles as $handle) {
                $api->call('deleteCustomerRequest', ['handle' => $handle]);
                $this->logRequest($api);
            }
        }
    }

    /**
     * @param string|null $username
     * @param string|null $password
     * @param bool $test_mode
     *
     * @return OpenProviderApi OpenProvider api client.
     * if username and password are exists, this method configure api, set token and host.
     * if username or password are null, it returns clear api client that require to configure it.
     * if username and password provided but incorrect, it returns clear api client without exceptions.
     * Also this method save token to database, if it not exists or exists but expired.
     *
     * @throws Exception
     */
    private function getApi($username = null, $password = null, $test_mode = true): OpenProviderApi
    {
        $api = new OpenProviderApi();

        $api->getConfig()->setHost($test_mode ? OpenProviderApi::API_CTE_URL : OpenProviderApi::API_URL);

        if (is_null($username) || is_null($password)) {
            return $api;
        }

        $module_id = $this->getModule()->id;

        $user_hash = $this->generateUserHash($username, $password, $test_mode);

        $var_name_token_until_date = 'token_until_date_' . $user_hash;
        $var_name_token = 'token_' . $user_hash;

        $token_until_date = $this->ModuleManager->getMeta($module_id, $var_name_token_until_date);
        $token = $this->ModuleManager->getMeta($module_id, $var_name_token);

        $is_token_until_date_valid = false;

        if (isset($token_until_date->{$var_name_token_until_date}) && $token_until_date->{$var_name_token_until_date}) {
            $is_token_until_date_valid = ((new DateTime($token_until_date->{$var_name_token_until_date}))->getTimestamp()
                - (new DateTime())->getTimestamp()) > self::MINIMUM_TOKEN_LIFE_TIME_IN_SECONDS;
        }

        $is_token_exist = isset($token->{$var_name_token}) &&
            $token->{$var_name_token};

        if ($is_token_until_date_valid && $is_token_exist) {
            $api->getConfig()->setToken($token->{$var_name_token});

            return $api;
        }

        $token = $api->call('generateAuthTokenRequest', ['username' => $username, 'password' => $password])
                ->getData()['token'] ?? '';
        $this->logRequest($api);

        if (!$token) {
            return $api;
        }

        $token_until_date = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' +' . self::TOKEN_LIFE_TIME_IN_MINUTES . ' minutes'));

        $this->ModuleManager->setMeta($module_id, [
            [
                'key' => $var_name_token_until_date,
                'value' => $token_until_date
            ],
            [
                'key' => $var_name_token,
                'value' => $token,
            ]
        ]);

        $api->getConfig()->setToken($token);

        return $api;
    }

    /**
     * @param string $username
     * @param string $password
     * @param bool $test_mode
     *
     * @return string user hash by a rule
     */
    private function generateUserHash($username, $password, $test_mode): string
    {
        return substr(md5(
            substr($username, 0, 2) .
            substr($password, 0, 2) .
            ($test_mode ? 'on' : 'off')
        ), 0, 5);
    }

    /**
     * @param OpenProviderApi $api
     * @poram array $hidden_fields
     * @throws Exception
     */
    private function logRequest(OpenProviderApi $api, array $hidden_fields = []): void
    {
        $hidden_fields = array_merge($hidden_fields, ['password']);
        $last_request  = $api->getLastRequest();
        $last_response = $api->getLastResponse();

        $request_args = $last_request->getArgs();

        foreach ($request_args as $key => $value) {
            if (in_array($key, $hidden_fields)) {
                $request_args[$key] = '**********';
            }
        }

        $this->log(
            $last_request->getCommand(),
            json_encode($request_args),
            'input',
            true
        );

        $is_success = $last_response->getCode() == 0;
        $this->log(
            $last_request->getCommand(),
            $is_success ?
                json_encode($last_response->getData()) :
                "{\"message\": \"{$last_response->getMessage()}\", \"code\": \"{$last_response->getCode()}\"}",
            'output',
            $is_success
        );
    }

    /**
     * @param array $vars
     * @param array $default_name_servers
     *
     * @return array structure [['name' => name_server],]
     */
    private function getNameServersFromVarsOrDefault(array $vars, array $default_name_servers = []): array
    {
        $name_servers = [];
        for ($i = 1; $i < 6; $i++) {
            if (isset($vars['ns' . $i]) && !empty($vars['ns' . $i])) {
                $name_servers[] = [
                    'name' => $vars['ns' . $i]
                ];
            }
        }

        if (empty($name_servers)) {
            foreach ($default_name_servers as $ns) {
                if (empty($ns)) {
                    continue;
                }
                $name_servers[] = [
                    'name' => $ns,
                ];
            }
        }

        return $name_servers;
    }

    /**
     * @param array|null $vars
     *
     * @return array customer data formatted for Openprovider
     *
     * @throws Exception
     */
    private function getCustomerData(?array $vars = null): array
    {
        if (!isset($this->Clients)) {
            Loader::loadModels($this, ['Clients']);
        }
        if (!isset($this->Contacts)) {
            Loader::loadModels($this, ['Contacts']);
        }

        Loader::load(__DIR__ . DS . 'helpers' . DS . 'address_splitter.php');
        Loader::load(__DIR__ . DS . 'helpers' . DS . 'phone_analyzer.php');

        $client = $this->Clients->get($vars['client_id']);

        // We cant create domain and contacts without client information
        if (!$client) {
            throw new Exception(Language::_('OpenProvider.!error.client.not_exist', true));
        }

        // taking phone number
        $contact_numbers = $this->Contacts->getNumbers($client->contact_id);
        $contact_number  = $contact_numbers[0]->number ?? null;

        if (is_null($contact_number)) {
            throw new Exception('OpenProvider.!error.client.phone_not_exist');
        }

        // processing phone to correct format
        $contact_number = PhoneAnalyzer::makePhoneCorrectFormat($contact_number, $client->country);
        if ($contact_number) {
            $phone = PhoneAnalyzer::makePhoneArray($contact_number);
        }

        // processing address
        try {
            $contact_full_address     = $client->address1 . ' ' . $client->address2;
            $contact_splitted_address = AddressSplitter::splitAddress($contact_full_address);
            $contact_house_number     = $contact_splitted_address['houseNumberParts']['base'];
            $contact_street           = $contact_splitted_address['streetName'] .
                ' ' . $contact_splitted_address['additionToAddress2'];

        } catch (Exception $e) {
            $should_use_full_address =
                strpos($e->getMessage(), ' could not be splitted into street name and house number.') !== false;

            if (!$should_use_full_address) {
                throw $e;
            }

            $contact_street = $contact_full_address;
        }

        // putting contact data together
        $customer = [
            'company_name' => $client->company ?? null,
            'email'        => $client->email ?? null,
            'vat'          => $client->settings['tax_id'] ?? null,
            'phone'        => $phone ?? $contact_number,
            'name'         => [
                'first_name' => $client->first_name ?? null,
                'last_name'  => $client->last_name ?? null,
                'initials'   => mb_substr($client->first_name, 0, 1) . '.' . mb_substr($client->last_name, 0, 1) ?? null,
            ],
            'address'      => [
                'city'    => $client->city ?? null,
                'country' => $client->country ?? null,
                'zipcode' => $client->zip ?? null,
                'state'   => $client->state ?? null,
                'street'  => $contact_street,
                'number'  => $contact_house_number ?? null,
            ],
        ];

        return $customer;
    }

    /**
     * @param $vars
     *
     * @return array [ 'domain_additional_data', 'customer_extension_additional_data' ]
     */
    private function getAdditionalData($vars)
    {
        $domain_additional_data_keys             = array_keys(DomainAdditionalData::openAPITypes());
        $customer_extension_additional_data_keys = array_keys(CustomerExtensionAdditionalData::openAPITypes());


        $additionalData = [
            'domain_additional_data'             => [],
            'customer_extension_additional_data' => [],
        ];

        foreach ($vars as $key => $value) {
            if (in_array($key, $domain_additional_data_keys)) {
                $additionalData['domain_additional_data'][$key] = $value;
            }

            if (in_array($key, $customer_extension_additional_data_keys)) {
                $additionalData['customer_extension_additional_data'][$key] = $value;
            }
        }

        return $additionalData;
    }

    /**
     * @param stdClass $package
     * @param stdClass $service
     * @param array $vars
     * @param null $parent_package
     * @param null $parent_service
     *
     * @return array|null contains an array of key=>value fields for each service field and its value,
     * as well as whether the value should be encrypted.
     *
     * This method is very similar to addService().
     *
     * @see https://docs.blesta.com/display/dev/Module+Methods#ModuleMethods-editService($package,$service,array$vars=array(),$parent_package=null,$parent_service=null)
     */
    public function editService(
        $package,
        $service,
        array $vars = [],
        $parent_package = null,
        $parent_service = null
    ): ?array
    {
        // TODO: Change the autogenerated stub
        return parent::editService($package, $service, $vars, $parent_package, $parent_service);
    }

    /**
     * Returns all tabs to display to a admin when managing a service whose
     * package uses this module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @return array An array of tab
     * s in the format of method => title.
     *  Example: array('methodName' => "Title", 'methodName2' => "Title2")
     */
    public function getAdminTabs($package): array
    {
        if ($package->meta->type == 'domain') {
            return [
                'tabNameservers' => Language::_('OpenProvider.tab_nameservers.title', true),
                'tabDomainContacts' => Language::_('OpenProvider.tab_domain_contacts.title', true),
            ];
        }

        return [];
    }

    /**
     * Returns all tabs to display to a client when managing a service whose
     * package uses this module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @return array An array of tabs in the format of method => title.
     *  Example: array('methodName' => "Title", 'methodName2' => "Title2")
     */
    public function getClientTabs($package): array
    {
        if ($package->meta->type == 'domain') {
            return [
                'tabClientNameservers' => Language::_('OpenProvider.tab_nameservers.title', true),
                'tabClientDomainContacts' => Language::_('OpenProvider.tab_domain_contacts.title', true),
            ];
        }

        return [];
    }

    /**
     *  Admin nameservers
     *
     * @param stdClass $package package row from database
     * @param stdClass $service service row from database
     * @param array|null $get if not null, method get data for this page NOT USED IN THIS METHOD
     * @param array|null $post if not null, method update data loaded from form on this page
     * @param array|null $files NOT USED IN THIS METHOD
     *
     * @return string|bool HTML generated by the view or FALSE if something went wrong
     */
    public function tabNameservers($package, $service, array $get = null, array $post = null, array $files = null)
    {
        return $this->manageNameservers('tab_nameservers', $package, $service, $get, $post, $files);
    }

    /**
     * @param stdClass $package package row from database
     * @param stdClass $service service row from database
     * @param array|null $get if not null, method get data for this page NOT USED IN THIS METHOD
     * @param array|null $post if not null, method update data loaded from form on this page
     * @param array|null $files NOT USED IN THIS METHOD
     *
     * @return string|bool HTML generated by the view or FALSE if something went wrong
     */
    public function tabClientNameservers($package, $service, array $get = null, array $post = null, array $files = null)
    {
        return $this->manageNameservers('tab_client_nameservers', $package, $service, $get, $post, $files);
    }

    /**
     * @param stdClass $package package row from database
     * @param stdClass $service service row from database
     * @param array|null $get if not null, method get data for this page NOT USED IN THIS METHOD
     * @param array|null $post if not null, method update data loaded from form on this page
     * @param array|null $files NOT USED IN THIS METHOD
     *
     * @return string|bool HTML generated by the view or FALSE if something went wrong
     */
    public function tabDomainContacts(
        $package,
        $service,
        array $get = null,
        array $post = null,
        array $files = null
    )
    {
        return $this->manageDomainContacts('tab_domain_contacts', $package, $service, $get, $post, $files);
    }

    /**
     * @param stdClass $package package row from database
     * @param stdClass $service service row from database
     * @param array|null $get if not null, method get data for this page NOT USED IN THIS METHOD
     * @param array|null $post if not null, method update data loaded from form on this page
     * @param array|null $files NOT USED IN THIS METHOD
     *
     * @return string|bool HTML generated by the view or FALSE if something went wrong
     */
    public function tabClientDomainContacts(
        $package,
        $service,
        array $get = null,
        array $post = null,
        array $files = null
    )
    {
        return $this->manageDomainContacts('tab_client_domain_contacts', $package, $service, $get, $post, $files);
    }

    /**
     * @poram string $view view's name
     * @param stdClass $package package row from database
     * @param stdClass $service service row from database
     * @param array|null $get if not null, method get data for this page NOT USED IN THIS METHOD
     * @param array|null $post if not null, method update data loaded from form on this page
     * @param array|null $files NOT USED IN THIS METHOD
     *
     * @return string|bool HTML generated by the view or FALSE if something went wrong
     */
    private function manageNameservers(
        $view,
        $package,
        $service,
        array $get = null,
        array $post = null,
        array $files = null
    )
    {
        $domain_name = '';
        foreach ($service->fields as $field) {
            if ($field->key == 'domain') {
                $domain_name = $field->value;
                break;
            }
        }

        // TODO: if domain pending or suspended return false to make this page unavailable
        $this->view = new View($view, 'default');
        $this->view->setDefaultView($this->default_module_view_path);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $row = $this->getModuleRow($package->module_row);
        $api = $this->getApi($row->meta->username, $row->meta->password, $row->meta->test_mode == 'true');

        // Set any specific data for this tab
        $vars = new stdClass();

        $args = [
            'full_name' => $domain_name,
        ];

        $op_domain_request = $api->call('searchDomainRequest', $args);
        $this->logRequest($api);

        if ($op_domain_request->getCode() != 0 || count($op_domain_request->getData()['results']) < 1) {
            $vars->error = Language::_('OpenProvider.!error.domain.not_exist', true);
            $this->view->set('vars', $vars);

            return $this->view->fetch();
        }

        $op_domain = $op_domain_request->getData()['results'][0];

        if (!empty($post)) {
            $vars = (object) $post;

            $response = $this->modifyNameServersInOpenProvider($api, $op_domain['id'], $post['ns']);

            if ($response->getCode() != 0) {
                $vars->error = $response->getMessage() . '. code: ' . $response->getCode();
            }

            $vars->error = isset($vars->error) ?
                str_replace('"', '\'', str_replace('`', '\'', $vars->error)) :
                '';

        } else {
            $vars->ns = array_map(function ($name_server) {
                return $name_server['name'];
            }, $op_domain['name_servers']);
        }

        $this->logRequest($api);
        $this->view->set('vars', $vars);

        return $this->view->fetch();
    }



    /**
     * @param string $view view's name
     * @param stdClass $package package row from database
     * @param stdClass $service service row from database
     * @param array|null $get if not null, method get data for this page NOT USED IN THIS METHOD
     * @param array|null $post if not null, method update data loaded from form on this page
     * @param array|null $files NOT USED IN THIS METHOD
     *
     * @return string|bool HTML generated by the view or FALSE if something went wrong
     */
    private function manageDomainContacts(
        $view,
        $package,
        $service,
        array $get = null,
        array $post = null,
        array $files = null
    )
    {
        $this->view = new View($view, 'default');
        $this->view->setDefaultView($this->default_module_view_path);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $vars = new stdClass();

        // getting domain to check it exists
        $domain_name = '';
        foreach ($service->fields as $field) {
            if ($field->key == 'domain') {
                $domain_name = $field->value;
                break;
            }
        }

        if (empty($domain_name)) {
            // TODO: move directly error message to language file
            $vars->error = 'Domain name undefined!';
            $this->view->set('vars', $vars);

            return $this->view->fetch();
        }

        $row = $this->getModuleRow($package->module_row);
        $api = $this->getApi($row->meta->username, $row->meta->password, $row->meta->test_mode == 'true');

        // Check domain in Openprovider
        $domain_request = $api->call('searchDomainRequest', [
            'full_name' => $domain_name
        ]);
        $this->logRequest($api);

        if ($domain_request->getCode() != 0 || count($domain_request->getData()['results']) == 0) {
            $vars->error = Language::_('OpenProvider.!error.domain.not_exist', true);
            $this->view->set('vars', $vars);

            return $this->view->fetch();
        }

        // Getting domain contacts from openprovider
        $op_domain = $domain_request->getData()['results'][0];

        $handles = [
            'owner'   => $op_domain['owner_handle'] ?? '',
            'admin'   => $op_domain['admin_handle'] ?? '',
            'tech'    => $op_domain['tech_handle'] ?? '',
            'billing' => $op_domain['billing_handle'] ?? '',
        ];

        // Load customers from Openprovider
        $op_customers = [];
        foreach ($handles as $key => $handle) {
            if (empty($handle)) {
                unset($handles[$key]);
                continue;
            }

            if (in_array($handle, array_keys($op_customers))) {
                $op_customers[$handle]['type'][] = $key;
                continue;
            }

            $customer_request = $api->call('retrieveCustomerRequest', [
                'handle' => $handle,
            ]);

            $this->logRequest($api);

            if ($customer_request->getCode() != 0 || !$customer_request->getData()) {
                continue;
            }

            $op_customer = $customer_request->getData();

            $op_customers[$handle] = [
                'type'    => [$key],
                'contact' => $this->getCustomerArrayFromOpCustomer($op_customer),
            ];
        }

        // Make structure like [contact_type => data]
        $domain_contacts_from_op = [];
        foreach ($op_customers as $op_customer) {
            foreach ($op_customer['type'] as $type) {
                $domain_contacts_from_op[$type] = $op_customer['contact'];
            }
        }

        if (!empty($post)) {
            // preparing array fields to format [contact_type => data]
            $domain_contacts_from_post = [];

            foreach ($handles as $key => $value) {
                $domain_contacts_from_post[$key] = array_filter($post, function ($post_key) use ($key) {
                    return strpos($post_key, $key) !== false;
                }, ARRAY_FILTER_USE_KEY);

                foreach ($domain_contacts_from_post[$key] as $key_contact => $value_contact) {
                    $domain_contacts_from_post[$key][substr($key_contact, strpos($key_contact, $key) + strlen($key) + 1)] = $value_contact;
                    unset($domain_contacts_from_post[$key][$key_contact]);
                }
            }

            $result = $this->createOrReuseDomainContactsInOp($api, $op_domain['id'], $handles, $domain_contacts_from_op, $domain_contacts_from_post);

            if ($result != 'success') {
                $vars->error = $result;
            } else {
                $domain_contacts_from_op = $domain_contacts_from_post;
            }
        }

        $vars->domain_contacts = $domain_contacts_from_op;
        $this->view->set('vars', $vars);

        return $this->view->fetch();
    }

    /**
     * Modify domain's name servers
     *
     * @param OpenProviderApi $api
     * @param int $op_domain_id
     * @param array $name_servers ['name_server1', 'name_server2'...]
     *
     * @return Response
     */
    private function modifyNameServersInOpenProvider(OpenProviderApi $api, int $op_domain_id, array $name_servers): Response
    {
        $args['id'] = $op_domain_id;

        foreach ($name_servers as $ns) {
            if (empty($ns)) {
                continue;
            }

            $args['name_servers'][] = [
                'name' => $ns
            ];
        }

        return $api->call('modifyDomainRequest', $args);
    }

    /**
     * Get domain info from OpenProvider
     *
     * @param OpenProviderApi $api
     * @param int $op_domain_id
     *
     * @return Response
     */
    private function getDomainInfoFromOpenProvider(OpenProviderApi $api, int $op_domain_id): Response
    {
        $args['id'] = $op_domain_id;

        return $api->call('retrieveDomainRequest', $args);
    }

    /**
     * @param OpenProviderApi $api
     * @param int $op_domain_id
     * @param array $domain_handles [ type => handle, type => handle ]
     * @param array $customers_from_op [ type => [data], type => [data] ]
     * @param array $customers_to_update [ type => [data], type => [data] ]
     *
     * @return string 'success' or error message
     */
    private function createOrReuseDomainContactsInOp(
        OpenProviderApi $api,
        int $op_domain_id,
        array $domain_handles,
        array $customers_from_op,
        array $customers_to_update
    ): string
    {
        $new_handles = [];

        foreach ($domain_handles as $type => $handle) {
            if (
                $customers_from_op[$type]['first_name'] == $customers_to_update[$type]['first_name'] &&
                $customers_from_op[$type]['last_name'] == $customers_to_update[$type]['last_name'] &&
                $customers_from_op[$type]['company_name'] == $customers_to_update[$type]['company_name'] &&
                $customers_from_op[$type]['email'] == $customers_to_update[$type]['email'] &&
                $customers_from_op[$type]['country'] == $customers_to_update[$type]['country'] &&
                $customers_from_op[$type]['state'] == $customers_to_update[$type]['state'] &&
                $customers_from_op[$type]['address'] == $customers_to_update[$type]['address'] &&
                $customers_from_op[$type]['phone_number'] == $customers_to_update[$type]['phone_number']
            ) {
                continue;
            }

            // if something is different at first try to find customer with same data
            $search_customer_request = $api->call('searchCustomerRequest', [
                'first_name_pattern' => $customers_to_update[$type]['first_name'],
                'last_name_pattern' => $customers_to_update[$type]['last_name'],
                'company_name_pattern' => $customers_to_update[$type]['company_name'],
                'email_pattern' => $customers_to_update[$type]['email'],
            ]);

            $this->logRequest($api);

            if ($search_customer_request->getCode() != 0) {
                return $search_customer_request->getMessage();
            }

            $resembling_customers = $search_customer_request->getData()['results'];

            if (!is_null($resembling_customers) || !empty($resembling_customers)) {
                $similar_customer_found = false;
                foreach ($resembling_customers as $resembling_customer) {
                    $formatted_resembling_customer = $this->getCustomerArrayFromOpCustomer($resembling_customer);
                    if (
                        $this->compareTwoCustomerArrays(
                            $formatted_resembling_customer,
                            $customers_to_update[$type],
                            self::FIELDS_TO_COMPARE_CUSTOMERS)
                    ) {
                        $new_handles[$type . '_handle'] = $resembling_customer['handle'];
                        $similar_customer_found = true;
                        break;
                    }
                }

                if ($similar_customer_found) {
                    continue;
                }
            }

            $args = $this->getCustomerForOpFromArray($customers_to_update[$type]);

            $create_customer_request = $api->call('createCustomerRequest', $args);

            $this->logRequest($api);

            if ($create_customer_request->getCode() != 0) {
                return $create_customer_request->getMessage();
            }

            $new_handles[$type . '_handle'] = $create_customer_request->getData()['handle'];
        }

        if (count($new_handles) > 0) {
            $update_domain_request = $api->call('modifyDomainRequest', [
                'id' => $op_domain_id,
            ] + $new_handles);

            $this->logRequest($api);

            if ($update_domain_request->getCode() != 0) {
                return $update_domain_request->getMessage();
            }
        }

        return 'success';
    }

    /**
     * @param stdClass $package
     * @param null $vars
     *
     * @return ModuleFields contains fields displayed when a client goes to create a service.
     *
     * This method is very similar to getAdminAddFields().
     *
     * @see https://docs.blesta.com/display/dev/Module+Methods#ModuleMethods-getClientAddFields($package,$vars=null)
     */
    public function getClientAddFields($package, $vars = null): ModuleFields
    {
        if ($package->meta->type != 'domain') {
            return new ModuleFields();
        }

        // Handle universal domain name
        if (isset($vars->domain)) {
            $vars->domain = $vars->domain;

            $splitted_domain_name = $this->splitDomainName($vars->domain);
            $tld                  = $splitted_domain_name['extension'] ? '.' . $splitted_domain_name['extension'] : '';
        }

        // Set default name servers
        if (!isset($vars->ns) && isset($package->meta->ns)) {
            $i = 1;
            foreach ($package->meta->ns as $ns) {
                $vars->{'ns' . $i++} = $ns;
            }
        }

        // Handle transfer request
        $operation = self::REGISTER_OPERATION;
        if ((isset($vars->transfer) && $vars->transfer) || isset($vars->auth)) {
            $operation = self::TRANSFER_OPERATION;
        }

        $fields = [];
        if ($operation == self::REGISTER_OPERATION) {
            // Handle domain registration
            $fields = array_merge(
                Configure::get('OpenProvider.nameserver_fields'),
                Configure::get('OpenProvider.domain_fields'),
                (array)Configure::get('OpenProvider.domain_fields' . $tld)
            );
        }

        if ($operation == self::TRANSFER_OPERATION) {
            $fields = array_merge(
                Configure::get('OpenProvider.transfer_fields'),
                (array)Configure::get('OpenProvider.domain_fields' . $tld)
            );

            // we already know we're doing a transfer, don't make it editable
            $fields['transfer']['type']  = 'hidden';
            $fields['transfer']['label'] = null;
        }

        // We should already have the domain name don't make editable
        $fields['domain']['type']  = 'hidden';
        $fields['domain']['label'] = null;

        $module_fields = $this->arrayToModuleFields($fields, null, $vars);

        if (
            isset($fields['identification_type']) &&
            isset($fields['passport_number']) &&
            isset($fields['passport_series']) &&
            isset($fields['company_registration_number'])
        ) {
            $module_fields->setHtml(
                "
                    <script type=\"text/javascript\">
                        $(document).ready(function() {
                            $('#company_registration_number_id').prop('disabled', true).val('');
                            
                            $('#identification_type_id').change(function () {
                                if ($(this).val() == 'company_registration_number') {
                                    $('#company_registration_number_id').prop('disabled', false);
                                    $('#passport_number_id').prop('disabled', true).val('');
                                    $('#passport_series_id').prop('disabled', true).val('');
                                } else {
                                    $('#company_registration_number_id').prop('disabled', true).val('');
                                    $('#passport_number_id').prop('disabled', false);
                                    $('#passport_series_id').prop('disabled', false);
                                }
                            });
                        });
                    </script>
                "
            );
        }

        // Determine whether this is an AJAX request
        return (isset($module_fields) ? $module_fields : new ModuleFields());
    }

    /**
     * @param string $domain
     * @param null $module_row_id
     *
     * @return bool true if the domain is available or false otherwise.
     *
     * @throws Exception
     *
     * @see https://docs.blesta.com/display/dev/Module+Methods#ModuleMethods-checkAvailability($domain,$module_row_id=null)
     */
    public function checkAvailability($domain, $module_row_id = null): bool
    {
        $row  = $this->getModuleRow($module_row_id);
        $api  = $this->getApi($row->meta->username, $row->meta->password, $row->meta->test_mode == 'true');
        $args = [
            $this->splitDomainName($domain)
        ];

        $response = $api->call('checkDomainRequest', ['domains' => $args])->getData();

        $this->logRequest($api);

        return $response['results'][0]['status'] == 'free';
    }

    /**
     * @param string $password
     * @param string $username
     * @param string $test_mode 'true'/'false'
     *
     * @return bool true if credentials are correct in OpenProvider
     *
     * @throws Exception
     */
    public function validateConnection($password, $username, $test_mode = null): bool
    {
        $api = $this->getApi();

        $module_id = $this->getModule()->id;

        $api->getConfig()->setHost($test_mode == 'true' ? OpenProviderApi::API_CTE_URL : OpenProviderApi::API_URL);

        $token = $api->call('generateAuthTokenRequest', ['username' => $username, 'password' => $password])
                ->getData()['token'] ?? '';

        $this->logRequest($api);

        $is_token_exists = strlen($token) > 0;

        if ($is_token_exists) {
            $user_hash = $this->generateUserHash($username, $password, $test_mode == 'true');
            $var_name_token_until_date = 'token_until_date_' . $user_hash;
            $var_name_token = 'token_' . $user_hash;

            $this->ModuleManager->setMeta($module_id, [
                [
                    'key' => $var_name_token_until_date,
                    'value' => date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' +' . self::TOKEN_LIFE_TIME_IN_MINUTES . ' minutes'))
                ],
                [
                    'key' => $var_name_token,
                    'value' => $token,
                ]
            ]);
        }

        return $is_token_exists;
    }

    /**
     * @return mixed|null the OpenProvider first row
     */
    private function getRow()
    {
        $module_rows = $this->getRows();

        return isset($module_rows[0]) ? $module_rows[0] : null;
    }

    /**
     * @return array the OpenProvider module rows
     */
    private function getRows(): array
    {
        Loader::loadModels($this, ['ModuleManager']);

        $module_rows = [];
        $modules     = $this->ModuleManager->getInstalled();

        foreach ($modules as $module) {
            $module_data = $this->ModuleManager->get($module->id);

            foreach ($module_data->rows as $module_row) {
                if (isset($module_row->meta->openprovider_module)) {
                    $module_rows[] = $module_row;
                }
            }
        }

        return $module_rows;
    }

    /**
     * @param array $op_customer
     * @return array one-dimensional array with customer data
     */
    private function getCustomerArrayFromOpCustomer(array $op_customer): array
    {
        return [
            'first_name'   => $op_customer['name']['first_name'] ?? '',
            'last_name'    => $op_customer['name']['last_name'] ?? '',
            'company_name' => $op_customer['company_name'] ?? '',
            'email'        => $op_customer['email'] ?? '',
            'city'         => $op_customer['address']['city'] ?? '',
            'state'        => $op_customer['address']['state'] ?? '',
            'zipcode'      => $op_customer['address']['zipcode'] ?? '',
            'country'      => $op_customer['address']['country'] ?? '',

            'address' => trim(($op_customer['address']['street'] ?? '') . ' ' .
                ($op_customer['address']['number'] ?? '') . ' ' .
                ($op_customer['address']['suffix'] ?? '')),

            'phone_number' => ($op_customer['phone']['country_code'] ?? '') .
                ($op_customer['phone']['area_code'] ?? '') .
                ($op_customer['phone']['subscriber_number'] ?? ''),
        ];
    }

    /**
     * @param array $customer_array
     * @return array formatted for openprovider
     */
    private function getCustomerForOpFromArray(array $customer_array): array
    {
        Loader::load(__DIR__ . DS . 'helpers' . DS . 'address_splitter.php');
        Loader::load(__DIR__ . DS . 'helpers' . DS . 'phone_analyzer.php');

        // processing phone to correct format
        $contact_number = PhoneAnalyzer::makePhoneCorrectFormat($customer_array['phone_number'], $customer_array['country']);
        if ($contact_number) {
            $phone = PhoneAnalyzer::makePhoneArray($contact_number);
        }

        // processing address
        try {
            $contact_splitted_address = AddressSplitter::splitAddress($customer_array['address']);
            $contact_house_number     = $contact_splitted_address['houseNumberParts']['base'];
            $contact_street           = $contact_splitted_address['streetName'] .
                ' ' . $contact_splitted_address['additionToAddress2'];
        } catch (Exception $e) {
            $contact_street = $customer_array['address'];
        }

        return [
            'company_name' => $customer_array['company_name'],
            'email'        => $customer_array['email'],
            'phone'        => $phone ?? $contact_number,

            'name' => [
                'first_name' => $customer_array['first_name'] ?? null,
                'last_name'  => $customer_array['last_name'] ?? null,
                'initials'   => mb_substr($customer_array['first_name'], 0, 1) . '.' . mb_substr($customer_array['last_name'], 0, 1) ?? null,
            ],

            'address' => [
                'city'    => $customer_array['city'] ?? null,
                'country' => $customer_array['country'] ?? null,
                'zipcode' => $customer_array['zipcode'] ?? null,
                'state'   => $customer_array['state'] ?? null,
                'street'  => $contact_street,
                'number'  => $contact_house_number ?? null,
            ],
        ];
    }

    /**
     * @param array $customer_one
     * @param array $customer_two
     * @param array $fields_to_compare
     * @return bool true if customers equals
     */
    private function compareTwoCustomerArrays(array $customer_one, array $customer_two, array $fields_to_compare = []): bool
    {
        foreach ($fields_to_compare as $field) {
            if ($customer_one[$field] != $customer_two[$field]) {
                return false;
            }
        }

        return true;
    }

    /**
     * function print data
     *
     * @param ...$args
     */
    private function debug(...$args)
    {
        echo '<pre>';
        var_dump(...$args);
        echo '</pre>';
    }
}
