<?php

declare(strict_types=1);

namespace App\Service\Genmax;

use App\Service\Utils;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Genmax Twig Extension
 *
 * Centralized naming convention handler for all Genmax code generation templates.
 * Provides functions for:
 * - Reserved keyword detection and safe naming
 * - Entity/class name transformations
 * - Method name generation (getters, setters, adders, removers)
 * - API resource naming
 * - Pluralization/singularization
 *
 * All string transformations delegate to Utils service for consistency.
 *
 * @see App\Service\Utils
 * @see https://www.postgresql.org/docs/current/sql-keywords-appendix.html
 */
class GenmaxExtension extends AbstractExtension
{
    /**
     * Complete list of SQL/PostgreSQL reserved keywords
     *
     * This list combines:
     * - PostgreSQL 18 reserved keywords
     * - SQL:2023 ANSI/ISO reserved keywords
     * - Common reserved words across major databases (MySQL, Oracle, SQL Server)
     *
     * All keywords are in UPPERCASE for case-insensitive comparison.
     * Update this list when PostgreSQL or SQL standards change.
     *
     * Last updated: 2025-10-22 for PostgreSQL 18 and SQL:2023
     */
    private const RESERVED_KEYWORDS = [
        // PostgreSQL Core Reserved
        'ALL', 'ANALYSE', 'ANALYZE', 'AND', 'ANY', 'ARRAY', 'AS', 'ASC',
        'ASYMMETRIC', 'AUTHORIZATION', 'BETWEEN', 'BIGINT', 'BINARY', 'BIT',
        'BOOLEAN', 'BOTH', 'CASE', 'CAST', 'CHAR', 'CHARACTER', 'CHECK',
        'COALESCE', 'COLLATE', 'COLLATION', 'COLUMN', 'CONCURRENTLY',
        'CONSTRAINT', 'CREATE', 'CROSS', 'CURRENT_CATALOG', 'CURRENT_DATE',
        'CURRENT_ROLE', 'CURRENT_SCHEMA', 'CURRENT_TIME', 'CURRENT_TIMESTAMP',
        'CURRENT_USER', 'DEC', 'DECIMAL', 'DEFAULT', 'DEFERRABLE', 'DESC',
        'DISTINCT', 'DO', 'ELSE', 'END', 'EXCEPT', 'EXISTS', 'EXTRACT',
        'FALSE', 'FETCH', 'FLOAT', 'FOR', 'FOREIGN', 'FREEZE', 'FROM',
        'FULL', 'GRANT', 'GROUP', 'HAVING', 'ILIKE', 'IN', 'INITIALLY',
        'INNER', 'INOUT', 'INT', 'INTEGER', 'INTERSECT', 'INTERVAL', 'INTO',
        'IS', 'ISNULL', 'JOIN', 'LATERAL', 'LEADING', 'LEFT', 'LIKE', 'LIMIT',
        'LOCALTIME', 'LOCALTIMESTAMP', 'NATIONAL', 'NATURAL', 'NCHAR', 'NONE',
        'NOT', 'NOTNULL', 'NULL', 'NULLIF', 'NUMERIC', 'OFFSET', 'ON', 'ONLY',
        'OR', 'ORDER', 'OUT', 'OUTER', 'OVERLAPS', 'OVERLAY', 'PLACING',
        'POSITION', 'PRECISION', 'PRIMARY', 'REAL', 'REFERENCES', 'RETURNING',
        'RIGHT', 'ROW', 'SELECT', 'SESSION_USER', 'SETOF', 'SIMILAR', 'SMALLINT',
        'SOME', 'SUBSTRING', 'SYMMETRIC', 'TABLE', 'TABLESAMPLE', 'THEN',
        'TIME', 'TIMESTAMP', 'TO', 'TRAILING', 'TREAT', 'TRIM', 'TRUE',
        'UNION', 'UNIQUE', 'USER', 'USING', 'VALUES', 'VARCHAR', 'VARIADIC',
        'VERBOSE', 'WHEN', 'WHERE', 'WINDOW', 'WITH',

        // SQL:2023 ANSI Additional Reserved
        'ABSOLUTE', 'ACTION', 'ADD', 'AFTER', 'AGGREGATE', 'ALIAS', 'ALLOCATE',
        'ALTER', 'ARE', 'ASSERTION', 'AT', 'BEFORE', 'BEGIN', 'BY', 'CALL',
        'CASCADE', 'CASCADED', 'CATALOG', 'CLOSE', 'COMMIT', 'CONNECT',
        'CONNECTION', 'CONTINUE', 'CORRESPONDING', 'CURSOR', 'CYCLE', 'DATA',
        'DATE', 'DAY', 'DEALLOCATE', 'DECLARE', 'DELETE', 'DEPTH', 'DEREF',
        'DESCRIBE', 'DESCRIPTOR', 'DIAGNOSTICS', 'DISCONNECT', 'DOMAIN',
        'DROP', 'DYNAMIC', 'EACH', 'ELSEIF', 'ESCAPE', 'EVERY', 'EXCEPT',
        'EXCEPTION', 'EXEC', 'EXECUTE', 'EXIT', 'EXTERNAL', 'FIRST', 'FOUND',
        'FREE', 'FUNCTION', 'GENERAL', 'GET', 'GLOBAL', 'GO', 'GOTO', 'HANDLER',
        'HOLD', 'HOUR', 'IDENTITY', 'IF', 'IMMEDIATE', 'INDICATOR', 'INPUT',
        'INSENSITIVE', 'INSERT', 'ISOLATION', 'ITERATE', 'KEY', 'LANGUAGE',
        'LARGE', 'LAST', 'LEAVE', 'LEVEL', 'LOCAL', 'LOOP', 'MATCH', 'MINUTE',
        'MODIFIES', 'MODIFY', 'MODULE', 'MONTH', 'NAMES', 'NCLOB', 'NEW', 'NEXT',
        'NO', 'OLD', 'OPEN', 'OPTION', 'OUTPUT', 'OVERLAPS', 'PAD', 'PARAMETER',
        'PARTIAL', 'PATH', 'PREPARE', 'PRESERVE', 'PRIOR', 'PRIVILEGES',
        'PROCEDURE', 'PUBLIC', 'READ', 'READS', 'RECURSIVE', 'REF', 'RELATIVE',
        'RELEASE', 'REPEAT', 'RESIGNAL', 'RESTRICT', 'RESULT', 'RETURN',
        'RETURNS', 'REVOKE', 'ROLLBACK', 'ROUTINE', 'ROWS', 'SAVEPOINT',
        'SCHEMA', 'SCROLL', 'SEARCH', 'SECOND', 'SECTION', 'SENSITIVE',
        'SEQUENCE', 'SESSION', 'SET', 'SIGNAL', 'SIZE', 'SPACE', 'SPECIFIC',
        'SQL', 'SQLEXCEPTION', 'SQLSTATE', 'SQLWARNING', 'START', 'STATE',
        'STATIC', 'SYSTEM', 'TEMPORARY', 'TERMINATE', 'THAN', 'TRANSACTION',
        'TRANSLATION', 'TRIGGER', 'UNDER', 'UNDO', 'UNTIL', 'UPDATE', 'USAGE',
        'VALUE', 'VIEW', 'WHENEVER', 'WHILE', 'WORK', 'WRITE', 'YEAR', 'ZONE',

        // Common Reserved Words (MySQL, Oracle, SQL Server compatibility)
        'ADMIN', 'AFTER', 'AGGREGATE', 'AUTO_INCREMENT', 'AVG', 'BACKUP',
        'BLOB', 'BOOLEAN', 'BREAK', 'BROWSE', 'BULK', 'CHECKPOINT', 'CLUSTERED',
        'COLLECT', 'COLUMN', 'COMMENT', 'COMMITTED', 'COMPUTE', 'CONTAINS',
        'CONTAINSTABLE', 'COUNT', 'DATABASE', 'DATABASES', 'DENY', 'DISK',
        'DISTRIBUTED', 'DOUBLE', 'DUMP', 'ENUM', 'ERRLVL', 'EXPLAIN', 'FILE',
        'FILLFACTOR', 'FREETEXT', 'FREETEXTTABLE', 'FULLTEXT', 'GOTO', 'GRANTS',
        'IDENTIFIED', 'IDENTITY_INSERT', 'IDENTITYCOL', 'IF', 'INCREMENT',
        'INDEX', 'INITIAL', 'KILL', 'LINENO', 'LOAD', 'LOCK', 'LONG', 'MAX',
        'MAXEXTENTS', 'MIN', 'MINUS', 'MODE', 'MODIFY', 'NOCHECK',
        'NONCLUSTERED', 'NOWAIT', 'NUMBER', 'OF', 'OFF', 'OFFLINE', 'OFFSETS',
        'ONLINE', 'OPENDATASOURCE', 'OPENQUERY', 'OPENROWSET', 'OPENXML',
        'OPTIMIZE', 'OVER', 'PARTITION', 'PERCENT', 'PCTFREE', 'PLAN', 'PRINT',
        'PROC', 'PURGE', 'RAISERROR', 'RAW', 'READTEXT', 'RECONFIGURE',
        'RENAME', 'REPLICATION', 'RESTORE', 'ROWCOUNT', 'ROWGUIDCOL',
        'ROWID', 'ROWNUM', 'RULE', 'SAVE', 'SETUSER', 'SHARE', 'SHOW',
        'SHUTDOWN', 'SONAME', 'SQL_BIG_RESULT', 'SQL_CALC_FOUND_ROWS',
        'SQL_SMALL_RESULT', 'STATISTICS', 'SUM', 'SYSDATE', 'SYSTIMESTAMP',
        'TEXTSIZE', 'TOP', 'TRAN', 'TRUNCATE', 'TSEQUAL', 'TYPE', 'TYPES',
        'UID', 'UNCOMMITTED', 'UPDATETEXT', 'USE', 'VALIDATE', 'VARYING',
        'WAITFOR', 'WRITETEXT', 'XOR',

        // PostgreSQL 18 New/Additional
        'STORED', 'GENERATED', 'ALWAYS', 'OVERRIDING', 'SYSTEM', 'RANGE',
        'GROUPS', 'EXCLUDE', 'TIES', 'OTHERS', 'NORMALIZE', 'NFC', 'NFD',
        'NFKC', 'NFKD', 'UESCAPE', 'AUTHORIZATION', 'ROLE', 'ADMIN',
        'ENCRYPTED', 'UNENCRYPTED', 'SYSID', 'VALID', 'PASSING', 'COLUMNS',
        'PATH', 'WRAPPER', 'CONTENT', 'DOCUMENT', 'VERSION', 'STRIP',
        'WHITESPACE', 'YES', 'NO', 'DEFINER', 'INVOKER', 'SECURITY',

        // Additional Common Conflicts
        'CLASS', 'CONST', 'CONTINUE', 'DECLARE', 'DIV', 'ECHO', 'ELSEIF',
        'EMPTY', 'EVAL', 'EXTENDS', 'FINAL', 'FOREACH', 'FUNCTION', 'GLOBAL',
        'GOTO', 'IMPLEMENTS', 'INSTANCEOF', 'INTERFACE', 'ISSET', 'LIST',
        'NAMESPACE', 'NEW', 'PRIVATE', 'PROTECTED', 'PUBLIC', 'REQUIRE',
        'REQUIRE_ONCE', 'STATIC', 'SWITCH', 'THROW', 'TRY', 'UNSET', 'VAR',
        'WHILE', 'ABSTRACT', 'ASSERT', 'CALLABLE', 'CLONE', 'ENDDECLARE',
        'ENDFOR', 'ENDFOREACH', 'ENDIF', 'ENDSWITCH', 'ENDWHILE', 'EXTENDS',
        'FINAL', 'FINALLY', 'FN', 'INSTEADOF', 'TRAIT', 'YIELD', 'FROM',
    ];

    public function getFunctions(): array
    {
        return [
            // Reserved keyword handling
            new TwigFunction('isReservedKeyword', [$this, 'isReservedKeyword']),
            new TwigFunction('getSafeColumnName', [$this, 'getSafeColumnName']),
            new TwigFunction('getSafeTableName', [$this, 'getSafeTableName']),

            // Class/Entity naming
            new TwigFunction('getClassName', [$this, 'getClassName']),
            new TwigFunction('toPascalCase', [$this, 'toPascalCase']),
            new TwigFunction('toCamelCase', [$this, 'toCamelCase']),
            new TwigFunction('toSnakeCase', [$this, 'toSnakeCase']),

            // Pluralization/Singularization
            new TwigFunction('toSingular', [$this, 'toSingular']),
            new TwigFunction('toPlural', [$this, 'toPlural']),

            // API resource naming
            new TwigFunction('toApiResourceName', [$this, 'toApiResourceName']),

            // Method naming
            new TwigFunction('getGetterName', [$this, 'getGetterName']),
            new TwigFunction('getSetterName', [$this, 'getSetterName']),
            new TwigFunction('getAdderName', [$this, 'getAdderName']),
            new TwigFunction('getRemoverName', [$this, 'getRemoverName']),
            new TwigFunction('getIsserName', [$this, 'getIsserName']),
        ];
    }

    // ==================== Reserved Keyword Detection ====================

    /**
     * Check if a property name is a reserved keyword
     *
     * @param string $propertyName The property name to check (case-insensitive)
     * @return bool True if the name is reserved, false otherwise
     */
    public function isReservedKeyword(string $propertyName): bool
    {
        return in_array(strtoupper($propertyName), self::RESERVED_KEYWORDS, true);
    }

    /**
     * Get safe column name for Doctrine @ORM\Column annotation
     *
     * ONLY adds _prop suffix if the property name is a reserved keyword.
     * Otherwise, returns the original property name (no modification).
     *
     * @param string $propertyName The property name
     * @return string Safe column name for database (only modified if reserved)
     */
    public function getSafeColumnName(string $propertyName): string
    {
        return $this->isReservedKeyword($propertyName)
            ? $propertyName . '_prop'
            : $propertyName;
    }

    /**
     * Get safe table name for Doctrine @ORM\Table annotation
     *
     * Uses Utils::camelToSnakeCase() to convert entity name, then
     * ONLY adds _table suffix if the snake_case name is a reserved keyword.
     *
     * Examples:
     * - User → user (reserved) → user_table ✅
     * - Order → order (reserved) → order_table ✅
     * - Calendar → calendar (not reserved) → calendar ✅
     * - DealStage → deal_stage (not reserved) → deal_stage ✅
     *
     * @param string $entityName The entity name (PascalCase)
     * @return string Safe table name for database (only suffixed if reserved)
     */
    public function getSafeTableName(string $entityName): string
    {
        $snakeCase = Utils::camelToSnakeCase($entityName, false);

        return $this->isReservedKeyword($snakeCase)
            ? $snakeCase . '_table'
            : $snakeCase;
    }

    // ==================== Class/Entity Naming ====================

    /**
     * Extract class name from fully qualified class name
     *
     * @param string $fullyQualifiedClassName The fully qualified class name (e.g., App\Entity\User)
     * @return string Just the class name (e.g., User)
     */
    public function getClassName(string $fullyQualifiedClassName): string
    {
        $parts = explode('\\', $fullyQualifiedClassName);
        return end($parts);
    }

    /**
     * Convert camelCase to PascalCase (ucfirst while preserving camelCase)
     * Uses Utils::toPascalCase()
     *
     * Example: accountManager -> AccountManager
     *
     * @param string $input The camelCase string
     * @return string PascalCase string
     */
    public function toPascalCase(string $input): string
    {
        return Utils::toPascalCase($input);
    }

    /**
     * Convert any string to proper camelCase
     * Uses Utils::toCamelCase()
     *
     * Example: account_manager -> accountManager, AccountManager -> accountManager
     *
     * @param string $input The input string
     * @param bool $ucFirst Whether to capitalize first letter (PascalCase)
     * @return string Properly formatted camelCase string
     */
    public function toCamelCase(string $input, bool $ucFirst = false): string
    {
        return Utils::toCamelCase($input, $ucFirst);
    }

    /**
     * Convert string to snake_case
     * Uses Utils::camelToSnakeCase()
     *
     * @param string $input The input string
     * @param bool $ucFirst Whether to capitalize first letter of each word
     * @return string snake_case string
     */
    public function toSnakeCase(string $input, bool $ucFirst = false): string
    {
        return Utils::camelToSnakeCase($input, $ucFirst);
    }

    // ==================== Pluralization/Singularization ====================

    /**
     * Convert plural English word to singular
     * Uses Utils::toSingular() for comprehensive English singularization
     *
     * @param string $word The plural word
     * @return string The singular form
     */
    public function toSingular(string $word): string
    {
        return Utils::toSingular($word);
    }

    /**
     * Convert singular English word to plural
     * Uses Utils::toPlural() for comprehensive English pluralization
     *
     * @param string $word The singular word
     * @return string The plural form
     */
    public function toPlural(string $word): string
    {
        return Utils::toPlural($word);
    }

    // ==================== API Resource Naming ====================

    /**
     * Convert entity name to API resource name (lowercase plural)
     *
     * This is used for generating API IRI paths like "/api/deals/..." or "/api/people/..."
     *
     * Examples:
     * - Deal → deals
     * - Person → people
     * - Category → categories
     * - TreeFlow → tree_flows
     *
     * @param string $entityName The entity name (PascalCase)
     * @return string The API resource name (lowercase plural)
     */
    public function toApiResourceName(string $entityName): string
    {
        // For multi-word entity names (e.g., TreeFlow), convert to snake_case first
        $snakeCase = Utils::camelToSnakeCase($entityName, false);

        // Get the last word for pluralization
        $parts = explode('_', $snakeCase);
        $lastWord = array_pop($parts);

        // Pluralize the last word
        $pluralLastWord = Utils::toPlural($lastWord);

        // Reconstruct and return
        if (empty($parts)) {
            return strtolower($pluralLastWord);
        }

        return strtolower(implode('_', $parts) . '_' . $pluralLastWord);
    }

    // ==================== Method Naming ====================

    /**
     * Generate getter method name
     *
     * Examples:
     * - name → getName
     * - isActive → getIsActive
     *
     * @param string $propertyName The property name
     * @return string The getter method name
     */
    public function getGetterName(string $propertyName): string
    {
        return 'get' . Utils::toPascalCase($propertyName);
    }

    /**
     * Generate setter method name
     *
     * Examples:
     * - name → setName
     * - isActive → setIsActive
     *
     * @param string $propertyName The property name
     * @return string The setter method name
     */
    public function getSetterName(string $propertyName): string
    {
        return 'set' . Utils::toPascalCase($propertyName);
    }

    /**
     * Generate adder method name for collection properties
     *
     * Examples:
     * - items → addItem
     * - categories → addCategory
     * - people → addPerson
     *
     * @param string $propertyName The collection property name (plural)
     * @return string The adder method name
     */
    public function getAdderName(string $propertyName): string
    {
        $singular = Utils::toSingular($propertyName);
        return 'add' . Utils::toPascalCase($singular);
    }

    /**
     * Generate remover method name for collection properties
     *
     * Examples:
     * - items → removeItem
     * - categories → removeCategory
     * - people → removePerson
     *
     * @param string $propertyName The collection property name (plural)
     * @return string The remover method name
     */
    public function getRemoverName(string $propertyName): string
    {
        $singular = Utils::toSingular($propertyName);
        return 'remove' . Utils::toPascalCase($singular);
    }

    /**
     * Generate isser method name for boolean properties
     *
     * Examples:
     * - active → isActive
     * - enabled → isEnabled
     *
     * @param string $propertyName The boolean property name
     * @return string The isser method name
     */
    public function getIsserName(string $propertyName): string
    {
        return 'is' . Utils::toPascalCase($propertyName);
    }

    // ==================== Static Utility Methods ====================

    /**
     * Get all reserved keywords (for debugging/reference)
     *
     * @return array<string>
     */
    public static function getReservedKeywords(): array
    {
        return self::RESERVED_KEYWORDS;
    }

    /**
     * Get count of reserved keywords
     *
     * @return int
     */
    public static function getReservedKeywordsCount(): int
    {
        return count(self::RESERVED_KEYWORDS);
    }
}
