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
     * @var Minphp\Record\Record
     */
    private $record;

    /**
     * DatabaseHelper constructor.
     * @param \Minphp\Record\Record $record
     */
    public function __construct(Minphp\Record\Record $record)
    {
        $this->record = $record;
    }

    /**
     * Method to create openprovider_token table
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
            ->create(self::openproviderTokenTable);
    }

    /**
     * Method to drop openprovider_token table
     */
    public function deleteOpenproviderTokenTable(): void
    {
        $this->record->drop(self::openproviderTokenTable);
    }

    /**
     * Method to create openprovider_token table
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
     * Method to drop openprovider_token table
     */
    public function deleteOpenproviderHandlesTable(): void
    {
        $this->record->drop(self::openproviderHandlesTable, true);
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
                ->insert(self::openproviderHandlesTable, ['type' => $handle_type, 'handle' => $handle, 'service_id' => $service_id]);
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
}