<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateVisits extends AbstractMigration
{

    private array $columns = [
        'date' => [
            'type' => 'date',
            'attributes' => ['null' => false]
        ],
        'completed' => [
            'type' => 'integer',
            'attributes' => ['null' => false, 'default' => 0]
        ],
        'forms' => [
            'type' => 'integer',
            'attributes' => ['null' => false]
        ],
        'products' => [
            'type' => 'integer',
            'attributes' => ['null' => false]
        ],
        'duration' => [
            'type' => 'integer',
            'attributes' => ['null' => false, 'default' => 0]
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
        $table = $this->table('visits');

        foreach ($this->columns as $columnName => $columnData) {
            $table->addColumn($columnName, $columnData["type"], $columnData["attributes"]);
        }

        $table->create();
    }
}
