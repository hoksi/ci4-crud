<?php

namespace Hoksi\Ci4Crud\Core;

use CodeIgniter\Database\BaseConnection;
use Hoksi\Ci4Crud\Config\CrudConfig;
use Hoksi\Ci4Crud\Relations\DependentRelation;
use Hoksi\Ci4Crud\Relations\ManyToManyRelation;
use Hoksi\Ci4Crud\Relations\OneToManyRelation;

class RelationHandler
{
    private ?BaseConnection $db;

    public function __construct(
        private readonly CrudConfig $config,
        ?BaseConnection $db = null,
    ) {
        $this->db = $db;
    }

    public function getOptions(string $field, string $search = '', int|string $parentValue = ''): array
    {
        // 1:N 관계
        if (isset($this->config->relations[$field])) {
            $rel = $this->config->relations[$field];

            // 종속 드롭다운
            if (($rel['type'] ?? '') === 'dependent') {
                $relation = new DependentRelation(
                    childField:  $field,
                    parentField: $rel['parentField'],
                    table:       $rel['table'],
                    labelField:  $rel['label'],
                    foreignKey:  $rel['fk'],
                    db:          $this->db,
                );

                return $relation->getOptions($parentValue, $search);
            }

            // 일반 1:N
            $relation = new OneToManyRelation(
                foreignKey:  $field,
                table:       $rel['table'],
                labelField:  $rel['label'],
                dynamic:     $rel['dynamic'] ?? false,
                db:          $this->db,
            );

            return $relation->getOptions($search);
        }

        // N:N 관계
        if (isset($this->config->relationsNtoN[$field])) {
            $rel = $this->config->relationsNtoN[$field];

            $relation = new ManyToManyRelation(
                field:         $field,
                junctionTable: $rel['junctionTable'],
                relatedTable:  $rel['relatedTable'],
                junctionFk:    $rel['junctionFk'],
                relatedFk:     $rel['relatedFk'],
                labelField:    $rel['label'],
                db:            $this->db,
            );

            return $relation->getOptions($search);
        }

        return [];
    }

    public function syncNtoN(int|string $primaryId, array $postData): void
    {
        foreach ($this->config->relationsNtoN as $field => $rel) {
            if (!isset($postData[$field])) {
                continue;
            }

            $ids = is_array($postData[$field]) ? $postData[$field] : [$postData[$field]];

            $relation = new ManyToManyRelation(
                field:         $field,
                junctionTable: $rel['junctionTable'],
                relatedTable:  $rel['relatedTable'],
                junctionFk:    $rel['junctionFk'],
                relatedFk:     $rel['relatedFk'],
                labelField:    $rel['label'],
                db:            $this->db,
            );

            $relation->syncJunction($primaryId, $ids);
        }
    }
}
