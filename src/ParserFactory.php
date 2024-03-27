<?php
/**
 * Instantiates a Parser object, populated with default functions and variables.
 *
 * The Parser class is a bare bones expression parser, however it has no default
 * functions or variables implemented. The ParserFactory is responsible for
 * instantiating the Parser and registering the default functions and variables
 * prior to returning it.
 *
 * The ParserFactory is responsible for the specific implementation details of
 * each default function and variable in the Parser. A developer may opt to
 * directly use the Parser without these defaults, or may extend upon these
 * default values by extending this class or manually registering them with the
 * Parser once it has been returned.
 */

declare(strict_types=1);

namespace shanept\ExpressionEngine;

class ParserFactory
{
    /**
     * Builds a functional instance of the ExpressionEngine Parser object.
     *
     * @param string $expression The expression to be evaluated.
     *
     * @return shanept\ExpressionEngine\Parser
     */
    final public static function buildParser($expression)
    {
        $inst = new Parser($expression);

        static::extendParser($inst);

        self::maybeRegFunc($inst, 'min', [self::class, 'funcMin']);
        self::maybeRegFunc($inst, 'max', [self::class, 'funcMax']);
        self::maybeSetVar($inst, 'length', [self::class, 'varLength']);

        return $inst;
    }

    /**
     * This function should be extended by subclasses. It is called before
     * registration of functions and variables, and may be used  to overwrite
     * default registrations or to add new ones.
     *
     * @see ParserFactory::maybeRegFunc To make registered functions optional.
     * @see ParserFactory::maybeSetVar  To make set variables optional.
     *
     * @param shanept\ExpressionEngine\Parser $parser being instantiated.
     *
     * @return void
     */
    protected static function extendParser(Parser &$parser)
    {
        // Nothing to see here... Extend me!
    }

    /**
     * Registers a function in the $parser if it does not already exist.
     *
     * This is just a wrapper around Parser::registerFunction.
     *
     * @internal
     * @see shanept\ExpressionEngine\Parser::registerFunction
     *
     * @param shanept\ExpressionEngine\Parser $parser being instantiated.
     * @param string   $name     The name of the function to register.
     * @param callback $callback The callback for this Parser function.
     *
     * @return void
     */
    protected static function maybeRegFunc($parser, $name, callable $callback)
    {
        if ($parser->hasFunction($name)) {
            return;
        }

        $parser->registerFunction($name, $callback);
    }

    /**
     * Sets a variable in the $parser if it does not already exist.
     *
     * This is just a wrapper around Parser::setVariable
     *
     * @internal
     * @see shanept\ExpressionEngine\Parser::setVariable
     *
     * @param shanept\ExpressionEngine\Parser $parser being instantiated.
     * @param string $name  The name of the variable to be set.
     * @param mixed  $value The value to be set for the variable.
     *
     * @return void
     */
    protected static function maybeSetVar($parser, $name, $value)
    {
        if ($parser->hasVariable($name)) {
            return;
        }

        $parser->setVariable($name, $value);
    }

    /**
     * Provides a callback for the parser min(...) function.
     *
     * If only one argument is provided, the argument becomes the RHS value and
     * the context becomes the LHS value.
     *
     * For more than one parameter it behaves identical to PHP's min().
     *
     * @internal
     */
    public static function funcMin(&$parser, ...$args)
    {
        if (count($args) < 1) {
            throw new \RuntimeException('min no parameters');
        }

        if (count($args) === 1) {
            return $parser->getContext() >= $args[0];
        } else {
            return min(...$args);
        }
    }

    /**
     * Provides a callback for the parser max(...) function.
     *
     * If only one argument is provided, the argument becomes the RHS value and
     * the context becomes the LHS value.
     *
     * For more than one parameter it behaves identical to PHP's max().
     *
     * @internal
     */
    public static function funcMax(&$parser, ...$args)
    {
        if (count($args) < 1) {
            throw new \RuntimeException('max no parameters');
        }

        // If there is only 1 argument, compare against the context and return
        //   boolean if it is no larger than $args[0]. Otherwise, return the
        //   largest number in the dataset.
        if (count($args) === 1) {
            return $parser->getContext() <= $args[0];
        } else {
            return max(...$args);
        }
    }

    /**
     * Provides a callback for the parser length variable.
     *
     * @internal
     */
    public static function varLength(&$parser)
    {
        $string = $parser->getContext();

        if (is_null($string)) {
            throw new \RuntimeException(
                'No context to check for length.'
            );
        }

        return strlen($string);
    }
}
