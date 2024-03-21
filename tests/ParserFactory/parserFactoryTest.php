<?php

use shanept\ExpressionEngine\Parser;
use shanept\ExpressionEngine\ParserFactory;

class parserFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Parser stub with a context of (int) 3
     */
    private $numParser = null;

    /**
     * Parser stub with no context
     */
    private $nullParser = null;

    /**
     * Parser stub with context of (string) "CtxStub"
     */
    private $strParser = null;

    public function setUp(): void
    {
        if (
            ! is_null($this->numParser) &&
            ! is_null($this->nullParser) &&
            ! is_null($this->strParser)
        ) {
            return;
        }

        $this->numParser = $this->createStub(Parser::class);
        $this->numParser->method('getContext')
                        ->willReturn(3);

        $this->nullParser = $this->createStub(Parser::class);
        $this->nullParser->method('getContext')
                         ->willReturn(null);

        $this->strParser = $this->createStub(Parser::class);
        $this->strParser->method('getContext')
                        ->willReturn("CtxStub");
    }

    /**
     * @small
     */
    public function testFuncMinCallbackThrowsExecptionWithoutArguments()
    {
        $this->expectException(\Exception::class);
        $result = ParserFactory::funcMin($this->numParser);
    }

    /**
     * @small
     */
    public function testFuncMinCallbackReturnsTrueOnMinimumValue()
    {
        $minValCtxSame = 3;
        $result = ParserFactory::funcMin($this->numParser, $minValCtxSame);

        $this->assertTrue($result);
    }

    /**
     * @small
     */
    public function testFuncMinCallbackReturnsTrueAboveMinimumValue()
    {
        $minValBelowCtx = 2;
        $result = ParserFactory::funcMin($this->numParser, $minValBelowCtx);

        $this->assertTrue($result);
    }

    /**
     * @small
     */
    public function testFuncMinCallbackReturnsFalseBelowMinimumValue()
    {
        $minValAboveCtx = 4;
        $result = ParserFactory::funcMin($this->numParser, $minValAboveCtx);

        $this->assertFalse($result);
    }

    /**
     * @small
     */
    public function testFuncMinCallbackReturnsMinValueWithMultipleArgs()
    {
        $result = ParserFactory::funcMin($this->numParser, 3, 5, 2);
        $this->assertEquals(2, $result);
    }

    /**
     * @small
     */
    public function testFuncMaxCallbackThrowsExecptionWithoutArguments()
    {
        $this->expectException(\Exception::class);
        $result = ParserFactory::funcMax($this->numParser);
    }

    /**
     * @small
     */
    public function testFuncMaxCallbackReturnsTrueOnMaximumValue()
    {
        $maxValCtxSame = 3;
        $result = ParserFactory::funcMax($this->numParser, $maxValCtxSame);

        $this->assertTrue($result);
    }

    /**
     * @small
     */
    public function testFuncMaxCallbackReturnsTrueBelowMaximumValue()
    {
        $maxValAboveCtx = 4;
        $result = ParserFactory::funcMax($this->numParser, $maxValAboveCtx);

        $this->assertTrue($result);
    }

    /**
     * @small
     */
    public function testFuncMaxCallbackReturnsFalseAboveMaximumValue()
    {
        $maxValBelowCtx = 2;
        $result = ParserFactory::funcMax($this->numParser, $maxValBelowCtx);

        $this->assertFalse($result);
    }

    /**
     * @small
     */
    public function testFuncMaxCallbackReturnsMaxValueWithMultipleArgs()
    {
        $result = ParserFactory::funcMax($this->numParser, 3, 5, 2);
        $this->assertEquals(5, $result);
    }

    /**
     * @small
     */
    public function testVarLengthOnMissingString()
    {
        $this->expectException(\Exception::class);
        ParserFactory::varLength($this->nullParser);
    }

    /**
     * @small
     */
    public function testVarLengthOnContextString()
    {
        $result = ParserFactory::varLength($this->strParser);
        $this->assertEquals(7, $result);
    }

    /**
     * @small
     */
    public function testBuildParserReturnsParserInstance()
    {
        $parser = ParserFactory::buildParser('');
        $this->assertInstanceOf(Parser::class, $parser);
    }

    /**
     * @small
     * @depends parserPublicInterfaceTest::testHasFunctionReturnsTrueOnPresentFunction
     * @depends parserPublicInterfaceTest::testHasFunctionReturnsFalseOnMissingFunction
     */
    public function testVerifyBuildParserRegistersFunctions()
    {
        $registered = ['min', 'max'];

        $parser = ParserFactory::buildParser('');

        foreach ($registered as $func) {
            $this->assertTrue($parser->hasFunction($func));
        }
    }

    /**
     * @small
     * @depends parserPublicInterfaceTest::testHasVariableReturnsTrueIfVariableSet
     * @depends parserPublicInterfaceTest::testHasVariableReturnsFalseIfVariableMissing
     */
    public function testVerifyBuildParserSetsVariables()
    {
        $registered = ['length'];

        $parser = ParserFactory::buildParser('');

        foreach ($registered as $func) {
            $this->assertTrue($parser->hasVariable($func));
        }
    }

    /**
     * @small
     * @depends parserPublicInterfaceTest::testRegisterFunction
     */
    public function testBuildParserDoesntBreakOnOverriddenFunctions()
    {
        MockFactory::$callback = function(&$inst) {
            $inst->registerFunction('max', '\max');
        };

        $parser = MockFactory::buildParser('');
        $this->assertInstanceOf(Parser::class, $parser);
    }

    /**
     * @small
     * @depends parserPublicInterfaceTest::testRegisterFunction
     * @depends parserPublicInterfaceTest::testHasFunctionReturnsTrueOnPresentFunction
     * @depends parserPublicInterfaceTest::testHasFunctionReturnsFalseOnMissingFunction
     */
    public function testBuildParserRegistersExtendedFunctions()
    {
        MockFactory::$callback = function(&$inst) {
            $inst->registerFunction('myLen', '\strlen');
        };

        $parser = MockFactory::buildParser('');
        $this->assertTrue($parser->hasFunction('myLen'));
    }

    /**
     * @small
     * @depends parserPublicInterfaceTest::testVariableGetAndSet
     */
    public function testBuildParserDoesntBreakOnOverriddenVariables()
    {
        MockFactory::$callback = function(&$inst) {
            $inst->setVariable('length', 2);
        };

        $parser = MockFactory::buildParser('');
        $this->assertInstanceOf(Parser::class, $parser);
    }

    /**
     * @small
     * @depends parserPublicInterfaceTest::testVariableGetAndSet
     * @depends parserPublicInterfaceTest::testHasVariableReturnsTrueIfVariableSet
     * @depends parserPublicInterfaceTest::testHasVariableReturnsFalseIfVariableMissing
     */
    public function testBuildParserRegistersExtendedVariables()
    {
        MockFactory::$callback = function(&$inst) {
            $inst->setVariable('myVar', 3);
        };

        $parser = MockFactory::buildParser('');
        $this->assertTrue($parser->hasVariable('myVar'));
    }
}
