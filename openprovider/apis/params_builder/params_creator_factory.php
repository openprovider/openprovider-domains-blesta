<?php

require_once __DIR__ . DS . 'params_creator.php';
require_once __DIR__ . DS . 'search_domains_params_creator.php';
require_once __DIR__ . DS . 'modify_domain_params_creator.php';
require_once __DIR__ . DS . 'check_domains_params_creator.php';
require_once __DIR__ . DS . 'retrieve_price_domain_params_creator.php';

class ParamsCreatorFactory
{
    public function build($cmd): ParamsCreator
    {
        switch ($cmd) {
            case 'searchDomainRequest':
                return new SearchDomainsParamsCreator();
            case 'createDomainRequest':
            case 'modifyDomainRequest':
            case 'transferDomainRequest':
            case 'restoreDomainRequest':
            case 'renewDomainRequest':
            case 'resetAuthCodeDomainRequest':
                return new ModifyDomainParamsCreator();
            case 'checkDomainRequest':
                return new CheckDomainsParamsCreator();
            case 'retrievePriceDomainRequest':
                return new RetrievePriceDomainParamsCreator();
        }

        return new ParamsCreator();
    }
}
