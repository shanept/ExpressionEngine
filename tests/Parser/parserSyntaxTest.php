<?php

use shanept\ExpressionEngine\Parser;
use shanept\ExpressionEngine\Exceptions\{
    ParseException,
    MissingRhsValueException,
    InvalidFunctionException,
    InvalidVariableException,
};

class parserSyntaxTest extends parserTestCase
{
    /**
     * @small
     * @coversNothing
     */
	public function testInvalidSyntaxThrowsException()
    {
        $expression = '(';
        $parser = new Parser($expression);

        // This will need to be a Parser Exception
        $this->expectException(ParseException::class);
        $parser->evaluate();
    }

    /**
	 * @small
	 */
	public function testUnclosedFunctionCallThrowsException()
    {
        $expression = 'min(1';
        $parser = new Parser($expression);

        // This will need to be a Parser Exception
        $this->expectException(ParseException::class);
        $parser->evaluate();
    }

    /**
	 * @small
	 */
	public function testInvalidValueThrowsException()
    {
        // The numerical 3 is greater than the escaped NUL. If the expression
        // were to evaluate \0, it should return True. However we expect the
        // parser to fail on the NUL character.
        $expression = "3>\0";
        $parser = new Parser($expression);

        // This will need to be a Parser Exception
        $this->expectException(ParseException::class);
		$this->expectExceptionMessage("no value");
        $parser->evaluate();
    }

    /**
	 * @small
	 */
	public function testInvalidVariableNameThrowsExceptionEvenWithMatch()
    {
        $expression = 'str.len==6';
        $value = 'string';

        $parser = $this->_initParser($expression, $value);
        $parser->setVariable('str.len', 6);

        $this->expectException(InvalidVariableException::class);
        $parser->evaluate();
    }

    /**
	 * @small
	 */
	public function testInvalidNumberFormatThrowsException()
    {
        $expression = '4.0.0>3';
        $parser = $this->_initParser($expression);

        $this->expectException(ParseException::class);
		$this->expectExceptionMessage("Invalid number format");
        $parser->evaluate();
    }

    /**
	 * @small
	 */
	public function testMissingRhsThrowsException()
    {
        $expression = '6>';
        $parser = new Parser($expression);

        // This will need to be a Parser Exception
        $this->expectException(MissingRhsValueException::class);
        $parser->evaluate();
    }

    /**
	 * @small
	 */
	public function testMissingRhsInLongerExpressionThrowsException()
    {
        $expression = '6>&&1==1';
        $parser = new Parser($expression);

        // This will need to be a Parser Exception
        $this->expectException(MissingRhsValueException::class);
        $parser->evaluate();
    }

    /**
	 * @small
	 */
	public function testInvertToFalse()
    {
        $expression = '!1';
        $value = 1;

        $got = $this->_doEval($expression, $value);

        $this->assertFalse($got);
    }

    /**
	 * @small
	 * @depends testInvertToFalse
	 */
	public function testInvertToTrue()
    {
        $expression = '!0';
        $value = 1;

        $got = $this->_doEval($expression, $value);

        $this->assertTrue($got);
    }

    /**
	 * @small
	 * @depends testInvertToTrue
	 */
	public function testDoubleInvertToFalse()
    {
        $expression = '!!0';
        $value = 1;

        $got = $this->_doEval($expression, $value);

        $this->assertFalse($got);
    }

    /**
	 * @small
	 * @depends testInvertToFalse
	 */
	public function testDoubleInvertToTrue()
    {
        $expression = '!!1';
        $value = 1;

        $got = $this->_doEval($expression, $value);

        $this->assertTrue($got);
    }

    /**
	 * @small
	 * @depends testInvertToTrue
	 */
	public function testTenInverts()
    {
        $expression = '!!!!!!!!!!1';
        $value = 1;

        $got = $this->_doEval($expression, $value);

        $this->assertTrue($got);
    }

    /**
	 * @small
	 * @depends parserOperatorsTest::testEqualsReturnsTrueOnSameNumbers
	 * @depends parserOperatorsTest::test5Minus3Returns2
	 */
	public function testSubExpressionEquals()
    {
        $expression = '1==(3-2)';
        $value = 1;

        $got = $this->_doEval($expression, $value);

        $this->assertTrue($got);
    }

    /**
	 * @small
	 * @depends parserOperatorsTest::testEqualsReturnsFalseOnDifferentNumbers
	 * @depends parserOperatorsTest::test5Minus3Returns2
	 */
	public function testSubExpressionFailsOnBadComparison()
    {
        $expression = '1==(3-3)';
        $value = 1;

        $got = $this->_doEval($expression, $value);

        $this->assertFalse($got);
    }

    /**
	 * @small
	 * @depends parserOperatorsTest::test30Plus22Returns52
	 * @depends parserOperatorsTest::test5Times3Returns15
	 */
	public function test3Plus5Times2WithoutBrackets()
    {
        $expression = '3+5*2';

        $got = $this->_doEval($expression);

        // We don't support operator precedence
        $this->assertEquals(16, $got);
    }

    /**
	 * @small
	 * @depends parserOperatorsTest::test30Plus22Returns52
	 * @depends parserOperatorsTest::test5Times3Returns15
	 */
	public function test3Plus5Times2WithBrackets()
    {
        $expression = '3+(5*2)';

        $got = $this->_doEval($expression);

        $this->assertEquals(13, $got);
    }

    /**
	 * @small
	 */
	public function testInvalidFunctionCall()
    {
        $expression = 'example()';

        $parser = new Parser($expression);

        $this->expectException(InvalidFunctionException::class);
        $parser->evaluate();
    }

	/**
	 * @small
	 * @depends parserPublicInterfaceTest::testRegisterFunction
	 */
	public function testFunctionCall()
	{
		$mock = $this->getMockBuilder(\stdClass::class)
                     ->addMethods(['callbackTest'])
                     ->getMock();

        $mock->expects($this->once())
             ->method('callbackTest')
             ->with($this->isInstanceOf(Parser::class))
             ->willReturn(33);

		 $expression = 'callback()';

		$parser = new Parser($expression);
		$parser->registerFunction('callback', [$mock, 'callbackTest']);

		$result = $parser->evaluate();
		$this->assertEquals(33, $result);
	}

	/**
	 * @small
	 * @depends testFunctionCall
	 * @depends parserOperatorsTest::test30LessThan15ReturnsFalse
	 */
	public function testFunctionCallWithArgs()
	{
		$mock = $this->getMockBuilder(\stdClass::class)
                     ->addMethods(['callbackTest'])
                     ->getMock();

        $mock->expects($this->once())
             ->method('callbackTest')
             ->with(
				 $this->isInstanceOf(Parser::class),
				 $this->equalTo(3),
				 $this->equalTo(false)
			 )
             ->willReturn(33);

		$expression = 'callback(3,5<2)';

		$parser = new Parser($expression);
		$parser->registerFunction('callback', [$mock, 'callbackTest']);

		$result = $parser->evaluate();
		$this->assertEquals(33, $result);
	}

    /**
	 * @small
	 * @depends testFunctionCall
	 */
	public function testNestedFunctionCall()
    {
        $expression = 'sub(100,add(sub(65,2),sub(3,2)))';

        $got = $this->_doEval($expression);

        $this->assertEquals(36, $got);
    }

	/**
	 * @small
	 * #depends testFunctionCall
	 */
	public function testLongerInvalidSyntaxThrowsException()
    {
		// double close parenthesis in midde.
		// We need to ensure we are not being heavy handed crunching through
		// close brackets.
        $expression = '(max(5)&&min(2)))))&&max(4))';
        $parser = new Parser($expression);
        $parser->setContext(3);

		// Stop false early errors from invalid function calls
		$parser->registerFunction('min',function(&$p){ return true; });
		$parser->registerFunction('max',function(&$p){ return true; });

        // This will need to be a Parser Exception
        $this->expectException(ParseException::class);
        $parser->evaluate();
    }
}
