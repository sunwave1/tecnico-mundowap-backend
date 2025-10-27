<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateWorkdays extends AbstractMigration
{

    private array $columns = [
        'date' => [
            'type' => 'date',
            'attributes' => ['null' => false]
        ],
        'visits' => [
            'type' => 'integer',
            'attributes' => ['null' => false]
        ],
        'completed' => [
            'type' => 'integer',
            'attributes' => ['null' => false, 'default' => 0]
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
        $table = $this->table('workdays');

        foreach ($this->columns as $columnName => $columnData) {
            $table->addColumn($columnName, $columnData["type"], $columnData["attributes"]);
        }

        $table->create();
    }
}
