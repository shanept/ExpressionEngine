<?php

use shanept\ExpressionEngine\Parser;
use shanept\ExpressionEngine\ParserFactory;

class MockFactory extends ParserFactory {
    // Put your extendParser function in here for testing
    public static $callback = null;

    protected static function extendParser(Parser &$parser)
    {
        return call_user_func_array(self::$callback, [&$parser]);
    }
}
