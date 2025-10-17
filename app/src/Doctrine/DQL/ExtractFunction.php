<?php

declare(strict_types=1);

namespace App\Doctrine\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * Custom DQL function for PostgreSQL EXTRACT
 *
 * Usage: EXTRACT(field FROM datetime_column)
 * Example: EXTRACT(HOUR FROM a.createdAt)
 */
class ExtractFunction extends FunctionNode
{
    public mixed $field;
    public mixed $datetime;

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER); // EXTRACT
        $parser->match(TokenType::T_OPEN_PARENTHESIS); // (

        // Capture the field name BEFORE matching
        $lexer = $parser->getLexer();
        $this->field = strtoupper($lexer->token->value); // HOUR, DAY, MONTH, etc.
        $parser->match(TokenType::T_IDENTIFIER); // Move past the field

        $parser->match(TokenType::T_FROM); // FROM

        // Parse the datetime expression
        $this->datetime = $parser->ArithmeticPrimary();

        $parser->match(TokenType::T_CLOSE_PARENTHESIS); // )
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return sprintf(
            'EXTRACT(%s FROM %s)',
            $this->field,
            $this->datetime->dispatch($sqlWalker)
        );
    }
}
