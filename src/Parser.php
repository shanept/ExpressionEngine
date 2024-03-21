<?php
/**
 * Implements the ExpressionEngine Parser.
 *
 * Provides a parser that evaluates a given expression. The parser is
 * instantiated on each individual expression and runs once only, returning the
 * result of the evaluation.
 *
 * @author Shane Thompson
 * @since 1.0.0
 */

namespace shanept\ExpressionEngine;

use shanept\ExpressionEngine\Exceptions\{
    ParseException,
    MissingRhsValueException,
    InvalidFunctionException,
    InvalidVariableException,
};

/**
 * BNF grammar for Parser expressions.
 *
 * <chars>       ::= Case-insensitive list of alphabet. regex: [a-z]
 * <alpha>       ::= <chars> | <chars> <alpha>
 * <digit>       ::= List of numerals. regex: [0-9]
 * <digits>      ::= <digit> | <digit> <digits>
 * <number>      ::= <digits> | <numbers> "." <numbers>
 * <alnum>       ::= <alpha> | <digits>
 * <logic>       ::= "&&" | "||" | ","
 *   ; comma is a lazy way to logically "AND" outside parameter lists. Within
 *   ; parameter lists, its behaviour changes to delimit parameters.
 * <ops>         ::= "<" | "<=" | ">" | ">=" | "=" | "==" | "!" | "!="
 * <variable>    ::= <chars> <alnum>
 * <parameters>  ::= <expression> | <expression> "," <parameters>
 * <function>    ::= <variable> "(" <parameters> ")"
 *   ; <function> uses <varible> grammar for naming.
 *   ; This does not make it a variable.
 * <term>        ::= <variable> | <number> | <function> | "(" <expression> ")"
 * <expression>  ::= <term> | <term> <ops> <term> |
 *                   <ops> <term> | <term> <logic> <term>
 */

class Parser
{
    /**
     * Parser tokens.
     *
     * @internal
     */
    const T_EQ  =  1,
          T_NEQ =  2,
          T_GT  =  3,
          T_GTE =  4,
          T_LT  =  5,
          T_LTE =  6,
          T_AND =  7,
          T_OR  =  8,
          T_ADD =  9,
          T_SUB = 10,
          T_MUL = 11,
          T_DIV = 12,
          T_POW = 13;

    /**
     * Maps operators to their tokens (as above)
     *
     * @internal
     */
    const OPS_TOKEN_MAP = [
        '>=' => self::T_GTE,
        '<=' => self::T_LTE,
        '==' => self::T_EQ,
        '!=' => self::T_NEQ,
        '&&' => self::T_AND,
        '||' => self::T_OR,
        '**' => self::T_POW,
        '+'  => self::T_ADD,
        '-'  => self::T_SUB,
        '*'  => self::T_MUL,
        '/'  => self::T_DIV,
        '>'  => self::T_GT,
        '<'  => self::T_LT,
    ];

    /**
     * Holds an array of all registered functions and callbacks
     */
    private $functions = [];

    /**
     * Holds an array of variables and their values
     */
    private $variables = [];

    /**
     * Contains a special variable that provides "context" to shortened syntax
     */
    private $contextValue = null;

    /**
     * The expression we are parsing
     */
    private $expression;

    /**
     * Pointer to the parser cursor
     */
    private $parser_pos = 0;

    /**
     * Are we currently parsing function arguments?
     *
     * Alters behaviour of the comma.
     */
    protected $parsing_function_args = false;

    /**
     * @param string $expression The expression to be parsed
     */
    public function __construct(string $expression)
    {
        $this->expression = str_replace(' ', '', $expression);
    }

    /**
     * Confirms the presence of a variable, determined by the $name.
     *
     * @param string $name The name of the variable to be retrieved.
     *
     * @return bool Whether or not the variable has been set.
     */
    public function hasVariable(string $name)
    {
        return array_key_exists($name, $this->variables);
    }

    /**
     * Retrieve the value of a variable that has been set.
     *
     * @see Parser::setVariable for more information on variables values.
     *
     * @param string $name The name of the variable to be retrieved.
     *
     * @return mixed|null The value previously set by Parser::setVariable() or
     *                    null if it doesn't exit.
     */
    public function getVariable(string $name)
    {
        $var = $this->variables[$name] ?? null;

        if (is_callable($var)) {
            $var = call_user_func_array($var, [&$this]);
        }

        return $var;
    }

    /**
     * Sets the value of a variable within the Parser.
     *
     * The Parser supports scalar variables and callback variables, which are to
     * be computed at runtime. If the variable is a variable function, we will
     * call the function first and return its value.
     *
     * If the variable value is a callable function, it MUST take a parser
     * reference object as the first and only parameter.
     *
     * Note that variables MUST start with a letter, and can contain only
     * letters and numbers. Any other characters may result in syntax errors.
     *
     * @param string $name  The name of the variable to be set.
     * @param mixed  $value The value to be set for the variable.
     *
     * @return void
     */
    public function setVariable(string $name, $value)
    {
        $this->variables[$name] = $value;
    }

    /**
     * Confirms whether or not the context has been set.
     *
     * @return bool Whether or not the context has been set.
     */
    public function hasContext()
    {
        return ! is_null($this->contextValue);
    }

    /**
     * Retrieves the Parser context value.
     *
     * @see Parser::setContext
     *
     * @return mixed|null The value previously set by Parser::setContext() or
     *                    null if it has not been set.
     */
    public function getContext()
    {
        return $this->contextValue;
    }

    /**
     * Sets the context against which short syntax expressions operate.
     *
     * Certain expressions may use a shorter syntax, in this case they are
     * operating against the "context" of the Parser.
     *
     * For example:
     *        Parser Context: 5
     *            Expression: < 10 && > 2
     * Equivalent Expression: 5 < 10 && 5 > 2
     *
     * The Parser will automatically insert the context value into appropriate
     * operations, allowing for the left-hand-side value to be omitted.
     *
     * @param mixed $value The value to be set for the Parser context.
     *
     * @return void
     */
    public function setContext($value)
    {
        $this->contextValue = $value;
    }

    /**
     * Confirms the presence of a function, determined by the $name.
     *
     * @see Parser::registerFunction
     *
     * @param string $name The name of the function to look for.
     *
     * @return bool Whether or not the function has been registered.
     */
    public function hasFunction(string $name)
    {
        $name = strtolower($name);

        return array_key_exists($name, $this->functions);
    }

    /**
     * Registers a function callback for use when the Parser evaluates a
     * function call.
     *
     * As the Parser evaluates the string, it identifies function signatures.
     * Any function that it finds will need to be registered. Function names
     * are case insensitive.
     *
     * @param string   $name     The name of the function to register.
     * @param callback $callback The callback for this Parser function.
     *
     * @return void
     */
    public function registerFunction(string $name, callable $callback)
    {
        // normalise function name
        $name = strtolower($name);

        $this->functions[$name] = $callback;
    }

    /**
     * Evaluate a given expression to get the result.
     *
     * This wraps the evaluateExpression function so checks can be performed
     * before returning a final value.
     *
     * @see Parser::evaluateExpression for implementation details.
     *
     * @return mixed The result of the evaluation.
     */
    public function evaluate()
    {
        $value = $this->evaluateExpression();

        if ($this->parser_pos < strlen($this->expression)) {
            throw new ParseException(sprintf(
                'Synax error encountered, incomplete evaluation of ' .
                'expression "%s". Evaluated to offset %d.',
                $this->expression,
                $this->parser_pos
            ), 1);
        }

        return $value;
    }

    /**
     * Performs the actual evaluation of the expression.
     *
     * This function is responsible for handing expression evaluation, except
     * where the evaluation of a "term" (as defined by the Parser grammar) is
     * required. The heavy lifting for terms is offloaded to the internal
     * function Parser::getNextTerm(),
     *
     * @see Parser::evaluate for public interface.
     *
     * @return mixed The result of the evaluation.
     */
    protected function evaluateExpression()
    {
        $value = $this->getNextTerm();

        while (1) {
            $operator = $this->getNextOperator();

            /**
             * This evaluation is missing a LHS value off which to derive our
             * comparison. This is handled here.
             *
             * If we found we are coming up to a sequential operator, we will
             * recurse here to evaluate the second operator first. We then go
             * back and handle that operator. The getNextTerm() call above
             * will return null, so we will use the context as the LHS value.
             *
             * Alternatively, we may not actually have sequential operators,
             * and are just missing an LHS operator (i.e. an expression "<5").
             * We handle this here too.
             */
            if (is_null($value) && ! is_null($operator)) {
                $value = $this->contextValue;
            }

            /**
             * This evaluation is missing an operator.
             * Either we are evaluating a parameter for a function call,
             * Alternatively, we have looped to the end of the expression.
             */
            if (! is_null($value) && is_null($operator)) {
                return $value;
            }

            /**
             * If we have another operator coming up, we have sequential
             * operators.
             *
             *    expression example: ">1&&<5"
             *
             * In the above example, the logical AND is the current operator,
             * and the less-than sign is the sequential operator. The less-than
             * operation should be performed first, against the contextValue.
             *
             * The result of the sequential operation will be our
             * value2. Note: we can not sequentially operate where logical AND
             * or OR are the upcoming operators.
             */
            $nextOp = $this->readAheadOperator();
            static $seq_op_blacklist = [self::T_AND, self::T_OR];

            if (! is_null($nextOp) && ! in_array($nextOp, $seq_op_blacklist)) {
                $value2 = $this->evaluateExpression();
            } else {
                $value2 = $this->getNextTerm();
            }

            /**
             * Get the RHS, failing if it is missing.
             * We should not be missing a RHS value at this point.
             */
            if (is_null($value2)) {
                throw new MissingRhsValueException(sprintf(
                    'RHS value missing in expression "%s" at offset %d.',
                    $this->expression,
                    $this->parser_pos
                ), 2);
            }

            /**
             * If we are at this point then we are definitely doing an operation
             * with a valid operator. Let's do it.
             */
            $value = $this->performOperation($value, $operator, $value2);
        }
    }

    /**
     * Evaluates "terms" in the expression.
     *
     * "Terms" is defined by the expression parser grammar and includes
     * variables, numbers, and the result of sub-expressions/function calls.
     * It does not include logical operations or comparisons.
     */
    private function getNextTerm()
    {
        static $word_boundary_charset = null;
        $value = "";
        $invert = null;
        $fl_point_found = false;

        if (is_null($word_boundary_charset)) {
            $word_boundary_charset = str_split('!@#$%^&*()-+=|\\/<>:;"\',?`~');
        }

        for ($i = &$this->parser_pos;; $i++) {
            $char = $this->expression[$i] ?? null;

            // Only consider '!' an inversion if it isn't part of '!='
            if ($char == '!' && '=' != $this->readahead(1)) {
                $invert = !$invert;
                continue;
            }

            // We are entering a sub-expression. Evaluate it
            if ($char == '(' && ! $fl_point_found) {
                // Get past open bracket
                $this->parser_pos++;

                // If we have a value, it is a function call, otherwise it is
                // a sub-expression
                if ($value === "") {
                    $value = $this->evaluateExpression();
                } else {
                    $value = $this->evaluateFunction($value);
                }

                // Get past closing bracket
                $this->parser_pos++;

                break;
            }

            // If we have any of these characters, we are at a term boundary.
            $is_word_boundary = in_array($char, $word_boundary_charset);

            // We have reached the end of our value
            if ($is_word_boundary || is_null($char)) {
                $this->parser_pos = $i;

                // Avoid use of PHP's empty() as it returns true for "0"
                if ("" !== $value) {
                    $value = $this->evaluateVarOrDigit($value);
                }

                break;
            }

            if ('.' === $char) {
                $fl_point_found = true;
            }

            if (ctype_alnum($char) || '.' === $char) {
                $value .= $char;
                continue;
            }

            throw new ParseException(sprintf(
                'Syntax error encountered, no value could be matched in ' .
                'expression "%s" at offset "%d".',
                $this->expression,
                $this->parser_pos
            ), 3);
        }

        // Avoid use of PHP's empty() as it returns true for "0"
        if ("" === $value) {
            return null;
        }

        if (! is_null($invert)) {
            $value = $invert ? !$value : !!$value;
        }

        return $value;
    }

    private function readahead($num_chars)
    {
        $read = substr($this->expression, $this->parser_pos + 1, $num_chars);

        /**
         * If we got no result, return an empty string.
         * Brings PHP <8 behaviour in line with PHP 8+.
         */
        // @codeCoverageIgnoreStart
        if (false === $read) {
            $read = "";
        }
        // @codeCoverageIgnoreEnd

        return $read;
    }

    /**
     * Helper to implement the shared operator retrieval code.
     *
     * @internal
     *
     * @see Parser::readAheadOperator
     * @see Parser::getNextOperator
     */
    private function _readAheadGetNextRawOperator($increment)
    {
        $operator = null;
        $max_length = strlen($this->expression) - $this->parser_pos;

        if ($max_length >= 2) {
            $operator = substr($this->expression, $this->parser_pos, 2);
        }

        // Test 2 character operators
        if (array_key_exists($operator, self::OPS_TOKEN_MAP)) {
            if ($increment) {
                $this->parser_pos += 2;
            }

            return self::OPS_TOKEN_MAP[$operator];
        }

        if ($max_length >= 1) {
            $operator = substr($this->expression, $this->parser_pos, 1);
        }

        // Test 1 character operators
        if (array_key_exists($operator, self::OPS_TOKEN_MAP)) {
            if ($increment) {
                $this->parser_pos++;
            }

            return self::OPS_TOKEN_MAP[$operator];
        }

        /**
         * If we are not performing a function call, allow the comma operator
         * as syntactic sugar around a logical AND.
         * If we are, the comma operator is used to delimit parameters.
         */
        if (! $this->parsing_function_args && ',' === $operator) {
            if ($increment) {
                $this->parser_pos++;
            }

            return self::T_AND;
        }

        return null;
    }

    private function readAheadOperator()
    {
        return $this->_readAheadGetNextRawOperator(false);
    }

    private function getNextOperator()
    {

        return $this->_readAheadGetNextRawOperator(true);
    }

    private function performOperation($value1, $operation, $value2)
    {
        switch ($operation) {
            case self::T_EQ:
                return $value1 == $value2;
            case self::T_NEQ:
                return $value1 != $value2;
            case self::T_GT:
                return $value1 >  $value2;
            case self::T_GTE:
                return $value1 >= $value2;
            case self::T_LT:
                return $value1 <  $value2;
            case self::T_LTE:
                return $value1 <= $value2;
            case self::T_AND:
                return $value1 && $value2;
            case self::T_OR:
                return $value1 || $value2;
            case self::T_ADD:
                return $value1 +  $value2;
            case self::T_SUB:
                return $value1 -  $value2;
            case self::T_MUL:
                return $value1 *  $value2;
            case self::T_DIV:
                return $value1 /  $value2;
            case self::T_POW:
                return $value1 ** $value2;
        }
    }

    private function evaluateFunction($func)
    {
        // Take note of the function offset in case we may throw an exception
        $func_offset = $this->parser_pos - strlen($func) - 1;
        $args = [];

        $in_args = $this->parsing_function_args;
        $this->parsing_function_args = true;

        // Let's get our parameter list
        while (true) {
            $char = $this->expression[$this->parser_pos] ?? null;

            if (is_null($char)) {
                throw new ParseException(sprintf(
                    'Syntax error encountered, unmatched parenthesis found ' .
                    'at end of expression "%s".',
                    $this->expression
                ), 4);
            }

            // Parent function handles parser_pos increment
            if (')' === $char) {
                break;
            }

            if (',' === $char) {
                $this->parser_pos++;
                continue;
            }

            // Parameters may even be expressions. Evaluate will consume as much
            // of this parameter as it can, then return to us.
            $value = $this->evaluateExpression();

            if (! is_null($value)) {
                $args[] = $value;
            }
        }

        $this->parsing_function_args = $in_args;

        // Normalise function name
        $func = strtolower($func);

        if (! $this->hasFunction($func)) {
            throw new InvalidFunctionException(sprintf(
                'Call to un-registered function "%s" in expression "%s" at ' .
                'offset "%d".',
                $func,
                $this->expression,
                $func_offset
            ), 5);
        }

        // Add the parser reference as the first parameter
        $args = [&$this, ...$args];

        // Execute the function and return
        return call_user_func_array($this->functions[$func], $args);
    }

    private function evaluateVarOrDigit($expression)
    {
        $floating_point = substr_count($expression, '.');
        $is_variable = ctype_alpha($expression[0]);

        if (
            $is_variable &&
            ! $floating_point &&
            $this->hasVariable($expression)
        ) {
            return $this->getVariable($expression);
        }

        if (! $is_variable && is_numeric($expression)) {
            return $floating_point ?
                    (float) $expression :
                    (int) $expression;
        }

        if ($is_variable) {
            /**
             * We could have reached here two separate ways.
             * The first way, we have an invalid function name (includes a dot).
             * The other way is a properly formed variable name for one that is
             * unset.
             */
            $exception_message = !! $floating_point ?
                'Invalid variable name "%s" in expression "%s" at offset %d.' :
                'Used un-set variable "%s" in expression "%s" at offset %d.';

            throw new InvalidVariableException(sprintf(
                $exception_message,
                $expression,
                $this->expression,
                // We have already consumed the bytes for the expression
                $this->parser_pos - strlen($expression)
            ), 6);
        } else {
            throw new ParseException(sprintf(
                'Invalid number format "%s" in expression "%s" at offset %d.',
                $expression,
                $this->expression,
                // We have already consumed the bytes for the expression
                $this->parser_pos - strlen($expression)
            ), 6);
        }
    }
}
