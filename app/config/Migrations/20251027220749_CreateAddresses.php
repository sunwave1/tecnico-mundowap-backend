<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateAddresses extends AbstractMigration
{

    private array $columns = [
        'foreign_table' => [
            'type' => 'string',
            'attributes' => ['limit' => 100, 'null' => false]
        ],
        'foreign_id' => [
            'type' => 'integer',
            'attributes' => ['null' => false]
        ],
        'postal_code' => [
            'type' => 'string',
            'attributes' => ['limit' => 8, 'null' => false]
        ],
        'state' => [
            'type' => 'string',
            'attributes' => ['limit' => 2, 'null' => false]
        ],
        'city' => [
            'type' => 'string',
            'attributes' => ['limit' => 200, 'null' => false]
        ],
        'sublocality' => [
            'type' => 'string',
            'attributes' => ['limit' => 200, 'null' => false]
        ],
        'street' => [
            'type' => 'string',
            'attributes' => ['limit' => 200, 'null' => false]
        ],
        'street_number' => [
            'type' => 'string',
            'attributes' => ['limit' => 200, 'null' => false]
        ],
        'complement' => [
            'type' => 'string',
            'attributes' => ['limit' => 200, 'default' => '', 'null' => false]
        ],
    ];

    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change(): void
    {
        $table = $this->table("addresses");

        foreach ($this->columns as $columnName => $columnData) {
            $table->addColumn($columnName, $columnData["type"], $columnData["attributes"]);
        }

        $table->create();
    }
}
