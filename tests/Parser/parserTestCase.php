<?php

use shanept\ExpressionEngine\Parser;

/**
 * @covers shanept\ExpressionEngine\Parser
 */
class parserTestCase extends \PHPUnit\Framework\TestCase
{
    protected function _initParser($expression, $context = null)
    {
        $parser = new Parser($expression);

        if (!is_null($context)) {
            $parser->setContext($context);
        }

        if (is_string($context)) {
            $parser->setVariable('length', strlen($context));
            $parser->setVariable('fLength', function (&$p) use ($context) {
                return strlen($context);
            });
        }

        $parser->registerFunction('min', function (&$p, $a) use ($context) {
            return $context >= $a;
        });
        $parser->registerFunction('max', function (&$p, $a) use ($context) {
            return $context <= $a;
        });
        $parser->registerFunction('add', function (&$p, $a, $b) {
            return $a + $b;
        });
        $parser->registerFunction('sub', function (&$p, $a, $b) {
            return $a - $b;
        });

        return $parser;
    }

    protected function _doEval($expression, $context = null)
    {
        $parser = $this->_initParser($expression, $context);

        return $parser->evaluate();
    }
}
