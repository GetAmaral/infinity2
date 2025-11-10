<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\DBAL\Connection;

/**
 * Service to fetch entity metadata from generator tables
 */
class EntityMetadataService
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    /**
     * Get list properties for an entity
     */
    public function getListProperties(string $entityName): array
    {
        $sql = "
            SELECT
                gp.property_name,
                gp.property_label,
                gp.property_type,
                gp.sortable,
                gp.searchable,
                gp.filterable,
                gp.filter_strategy,
                gp.filter_boolean,
                gp.filter_date,
                gp.filter_numeric_range,
                gp.filter_exists,
                gp.relationship_type
            FROM generator_property gp
            JOIN generator_entity ge ON gp.entity_id = ge.id
            WHERE ge.entity_name = :entity_name
            AND gp.show_in_list = true
            ORDER BY gp.property_order, gp.property_name
        ";

        $properties = $this->connection->fetchAllAssociative($sql, [
            'entity_name' => $entityName,
        ]);

        return array_map(function ($property) {
            return [
                'name' => $property['property_name'],
                'label' => $property['property_label'],
                'type' => $property['property_type'] ?? '',
                'sortable' => (bool) $property['sortable'],
                'searchable' => (bool) $property['searchable'],
                'filterable' => (bool) $property['filterable'],
                'filterStrategy' => $property['filter_strategy'],
                'filterBoolean' => (bool) $property['filter_boolean'],
                'filterDate' => (bool) $property['filter_date'],
                'filterNumericRange' => (bool) $property['filter_numeric_range'],
                'filterExists' => (bool) $property['filter_exists'],
                'getter' => 'get' . ucfirst($property['property_name']),
                'isRelationship' => $property['relationship_type'] !== null,
            ];
        }, $properties);
    }

    /**
     * Get searchable fields for an entity
     */
    public function getSearchableFields(string $entityName): array
    {
        $sql = "
            SELECT
                gp.property_name,
                gp.property_label,
                gp.property_type
            FROM generator_property gp
            JOIN generator_entity ge ON gp.entity_id = ge.id
            WHERE ge.entity_name = :entity_name
            AND gp.searchable = true
            ORDER BY gp.property_order, gp.property_name
        ";

        $properties = $this->connection->fetchAllAssociative($sql, [
            'entity_name' => $entityName,
        ]);

        return array_map(function ($property) {
            return [
                'name' => $property['property_name'],
                'label' => $property['property_label'],
                'type' => $property['property_type'] ?? '',
            ];
        }, $properties);
    }

    /**
     * Get filterable fields for an entity
     */
    public function getFilterableFields(string $entityName): array
    {
        $sql = "
            SELECT
                gp.property_name,
                gp.property_label,
                gp.property_type,
                gp.filter_strategy,
                gp.filter_boolean,
                gp.filter_date,
                gp.filter_numeric_range,
                gp.filter_exists
            FROM generator_property gp
            JOIN generator_entity ge ON gp.entity_id = ge.id
            WHERE ge.entity_name = :entity_name
            AND gp.filterable = true
            ORDER BY gp.property_order, gp.property_name
        ";

        $properties = $this->connection->fetchAllAssociative($sql, [
            'entity_name' => $entityName,
        ]);

        return array_map(function ($property) {
            return [
                'name' => $property['property_name'],
                'label' => $property['property_label'],
                'type' => $property['property_type'] ?? '',
                'strategy' => $property['filter_strategy'],
                'boolean' => (bool) $property['filter_boolean'],
                'date' => (bool) $property['filter_date'],
                'numericRange' => (bool) $property['filter_numeric_range'],
                'exists' => (bool) $property['filter_exists'],
            ];
        }, $properties);
    }

    /**
     * Get sortable fields for an entity
     */
    public function getSortableFields(string $entityName): array
    {
        $sql = "
            SELECT
                gp.property_name,
                gp.property_label
            FROM generator_property gp
            JOIN generator_entity ge ON gp.entity_id = ge.id
            WHERE ge.entity_name = :entity_name
            AND gp.sortable = true
            ORDER BY gp.property_order, gp.property_name
        ";

        $properties = $this->connection->fetchAllAssociative($sql, [
            'entity_name' => $entityName,
        ]);

        return array_map(function ($property) {
            return [
                'name' => $property['property_name'],
                'label' => $property['property_label'],
            ];
        }, $properties);
    }

    /**
     * Get properties to show in detail views
     */
    public function getShowProperties(string $entityName): array
    {
        $sql = "
            SELECT
                gp.property_name,
                gp.property_label,
                gp.property_type,
                gp.relationship_type,
                gp.target_entity
            FROM generator_property gp
            JOIN generator_entity ge ON gp.entity_id = ge.id
            WHERE ge.entity_name = :entity_name
            AND gp.show_in_detail = true
            ORDER BY gp.property_order, gp.property_name
        ";

        $properties = $this->connection->fetchAllAssociative($sql, [
            'entity_name' => $entityName,
        ]);

        return array_map(function ($property) {
            $targetEntity = $property['target_entity'];
            $relationshipType = $property['relationship_type'];
            $relationshipRoute = null;
            $isCollection = false;

            if ($targetEntity) {
                // Convert entity class name to route prefix (e.g., Organization -> organization)
                $routePrefix = strtolower($targetEntity);
                $relationshipRoute = $routePrefix . '_show';

                // OneToMany and ManyToMany return collections
                $isCollection = in_array($relationshipType, ['OneToMany', 'ManyToMany'], true);
            }

            return [
                'name' => $property['property_name'],
                'label' => $property['property_label'],
                'type' => $property['property_type'] ?? '',
                'getter' => 'get' . ucfirst($property['property_name']),
                'isRelationship' => $relationshipType !== null,
                'isCollection' => $isCollection,
                'relationshipType' => $relationshipType,
                'relationshipRoute' => $relationshipRoute,
            ];
        }, $properties);
    }

    /**
     * Get all metadata needed for index templates
     */
    public function getIndexMetadata(string $entityName): array
    {
        $listProperties = $this->getListProperties($entityName);
        $searchableFields = $this->getSearchableFields($entityName);
        $filterableFields = $this->getFilterableFields($entityName);
        $sortableFields = $this->getSortableFields($entityName);

        return [
            // Property metadata for Twig templates (as PHP arrays)
            'listProperties' => $listProperties,
            'searchableFields' => $searchableFields,
            'filterableFields' => $filterableFields,
            'sortableFields' => $sortableFields,

            // Property metadata for client-side rendering (as JSON strings)
            'list_fields' => json_encode($listProperties),
            'searchable_fields' => json_encode($searchableFields),
            'filterable_fields' => json_encode($filterableFields),
            'sortable_fields' => json_encode($sortableFields),
        ];
    }
}
