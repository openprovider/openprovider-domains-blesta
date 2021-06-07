<?php

class DatabaseHelper
{
    /**
     * @const string
     */
    private const openproviderTokenTable = 'openprovider_token';

    /**
     * @const string
     */
    private const openproviderHandlesTable = 'openprovider_handles';

    /**
     * @const string
     */
    private const openproviderServiceIdDomainIdTable = 'openprovider_mapping_service_domain';

    private Minphp\Record\Record $record;

    /**
     * DatabaseHelper constructor.
     * @param \Minphp\Record\Record $record
     */
    public function __construct(Minphp\Record\Record $record)
    {
        $this->record = $record;
    }

    /**
     * Method to create openprovider_token table if not exist
     */
    public function createOpenproviderTokenTable(): void
    {
        $this->record
            ->setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])
            ->setField('user_hash', ['type' => 'varchar', 'size' => 255])
            ->setField('token', ['type' => 'varchar', 'size' => 255])
            ->setField('until_date', ['type' => 'datetime', 'is_null' => true, 'default' => null])
            ->setKey(['id'], 'primary')
            ->setKey(['user_hash'], 'unique')
            ->create(self::openproviderTokenTable, true);
    }

    /**
     * Method to drop openprovider_token table if exist
     */
    public function deleteOpenproviderTokenTable(): void
    {
        $this->record->drop(self::openproviderTokenTable, true);
    }

    /**
     * Method to create openprovider_token table if not exist
     */
    public function createOpenproviderHandlesTable(): void
    {
        $this->record
            ->setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])
            ->setField('handle', ['type' => 'varchar', 'size' => 20])
            ->setField('type', ['type' => 'varchar', 'size' => 20])
            ->setField('service_id', ['type' => 'int', 'unsigned' => true])
            ->setKey(['id'], 'primary')
            ->create(self::openproviderHandlesTable, true);
    }

    /**
     * Method to drop openprovider_token table if exist
     */
    public function deleteOpenproviderHandlesTable(): void
    {
        $this->record->drop(self::openproviderHandlesTable, true);
    }

    /**
     * Method to create openprovider_service_id_domain_id table for mapping services with domains from openprovider
     * if not exist
     */
    public function createOpenproviderServiceIdDomainIdTable(): void
    {
        $this->record
            ->setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])
            ->setField('service_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])
            ->setField('domain_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])
            ->setKey(['id'], 'primary')
            ->create(self::openproviderServiceIdDomainIdTable, true);
    }

    /**
     * Method to drop openprovider_service_id_domain_id table for mapping services with domains from openprovider
     * if exist
     */
    public function deleteOpenproviderServiceIdDomainIdTable(): void
    {
        $this->record->drop(self::openproviderServiceIdDomainIdTable, true);
    }

    /**
     * Get token from openprovider_token table
     *
     * @param string $user_hash
     *
     * @return string
     */
    public function getOpenproviderTokenFromDatabase($user_hash): string
    {
        $datetime_now_minus_half_hour = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' -30 minutes'));

        $token = $this->record
            ->from(self::openproviderTokenTable)
            ->select()
            ->where('user_hash', '=', $user_hash)
            ->fetch();

        if (!$token) {
            return '';
        }

        $token_until_date = strtotime($token->until_date);
        if (strtotime($datetime_now_minus_half_hour) > $token_until_date) {
            return '';
        }

        return $token->token;
    }

    /**
     * Method to set token in openprovider_token table
     *
     * @param string $user_hash
     * @param string $token
     * @param string $until_date
     */
    public function setOpenproviderTokenToDatabase($user_hash, $token, $until_date): void
    {
        $this->record
            ->duplicate('token', '=', $token)
            ->duplicate('until_date', '=', $until_date)
            ->insert(self::openproviderTokenTable, ['user_hash' => $user_hash, 'token' => $token, 'until_date' => $until_date]);
    }

    /**
     * Create contact handles in database
     *
     * @param int $service_id
     * @param array $handles [ 'all' => handle, 'admin_handle' => handle, 'owner_handle' => handle, 'billing_handle' => handle, 'tech_handle' => handle ]
     */
    public function setServiceHandles($service_id, $handles): void
    {
        foreach ($handles as $handle_type => $handle) {
            $this->record
                ->insert(
                    self::openproviderHandlesTable,
                    ['type' => $handle_type, 'handle' => $handle, 'service_id' => $service_id]
                );
        }
    }

    /**
     * Delete contact handle from database
     *
     * @param int $service_id
     * @param string $handle
     */
    public function deleteServiceHandle($service_id, $handle): void
    {
        $this->record
            ->from(self::openproviderHandlesTable)
            ->where('service_id', '=', $service_id)
            ->where('handle', '=', $handle)
            ->delete();
    }

    public function setMappingServiceDomain($service_id, $domain_id): void
    {
        $this->record
            ->insert(
                self::openproviderServiceIdDomainIdTable,
                ['service_id' => $service_id, 'domain_id' => $domain_id]
            );
    }

    /**
     * Delete service domain mapping by blesta's service id
     *
     * @param $service_id
     */
    public function deleteMappingServiceDomainByServiceId($service_id): void
    {
        $this->record
            ->from(self::openproviderServiceIdDomainIdTable)
            ->where('service_id', '=', $service_id)
            ->delete();
    }

    /**
     * Delete service domain mapping by openprovider's domain id
     *
     * @param $domain_id
     */
    public function deleteMappingServiceDomainByDomainId($domain_id): void
    {
        $this->record
            ->from(self::openproviderServiceIdDomainIdTable)
            ->where('domain_id', '=', $domain_id)
            ->delete();
    }

    /**
     * @param $service_id
     * @return stdClass database row with mapping service domain info {service_id, domain_id}
     */
    public function getMappingServiceDomainByServiceId($service_id): stdClass
    {
        return $this->record
            ->from(self::openproviderServiceIdDomainIdTable)
            ->select()
            ->where('service_id', '=', $service_id)
            ->fetch();
    }
}
