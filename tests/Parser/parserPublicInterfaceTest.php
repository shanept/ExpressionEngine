<?php

use shanept\ExpressionEngine\Parser;

class parserPublicInterfaceTest extends parserTestCase
{
	/**
	 * @small
	 */
	public function testHasVariableReturnsTrueIfVariableSet()
	{
		$expression = '';
		$parser = new Parser($expression);

		$parser->setVariable('testVar', 5);

		$this->assertTrue($parser->hasVariable('testVar'));
	}

	/**
	 * @small
	 */
	public function testHasVariableReturnsFalseIfVariableMissing()
	{
		$expression = '';
		$parser = new Parser($expression);

		$this->assertFalse($parser->hasVariable('testVar'));
	}

    /**
	 * @small
	 */
	public function testVariableGetAndSet()
    {
        $expression = '';
        $parser = new Parser($expression);

        $parser->setVariable('testVar', 5);

        $this->assertEquals(5, $parser->getVariable('testVar'));
    }

    /**
	 * @small
	 */
	public function testGetVariableReturnsNullOnNonExistantVariable()
    {
        $expression = '';
        $parser = new Parser($expression);

        $this->assertNull($parser->getVariable('invalid'));
    }

	/**
	 * @small
	 */
	public function testHasContextReturnsTrueIfContextSet()
	{
		$expression = '';
        $parser = new Parser($expression);

		$parser->setContext(3);

		$this->assertTrue($parser->hasContext());
	}

	/**
	 * @small
	 */
	public function testHasContextReturnsFalseIfMissingContext()
	{
		$expression = '';
		$parser = new Parser($expression);

		$this->assertFalse($parser->hasContext());
	}
    /**
	 * @small
	 */
	public function testContextGetAndSet()
    {
        $expression = '';
        $parser = new Parser($expression);

        $parser->setContext(33);

        $this->assertEquals(33, $parser->getContext());
    }

    /**
	 * @small
	 */
	public function testGetContextOnNoSetContext()
    {
        $expression = '';
        $parser = new Parser($expression);

        $this->assertNull($parser->getContext());
    }

    /**
     * Please note the intended functionality under test by this unit is simply
     *   the behaviour of calling back a set function. It is not specifically
     *   testing the parsing functionality.
     *
     * @covers shanept\ExpressionEngine\Parser::registerFunction
	 * @small
	 */
	public function testRegisterFunction()
    {
        $mock = $this->getMockBuilder(\stdClass::class)
                     ->addMethods(['callbackTest'])
                     ->getMock();

        $mock->expects($this->once())
             ->method('callbackTest')
             ->with($this->isInstanceOf(Parser::class))
             ->willReturn(33);

        // We use only a closing brace here. The closing brace signals to
        //   the evaluateFunction call where to finish parsing the expression.
        // This is not typical use. Typically, the function name and open brace
        //   are consumed by the getNextValue function which then passes flow to
        //   the evaluateFunction call. As we are bypassing this functionality,
        //   we must provide the close brace to signal the end of parameter list
        //   to the evaluateFunction call.
        $expression = ')';
        $parser = new Parser($expression);

        $parser->registerFunction('test', [$mock, 'callbackTest']);

        // invoke function
        $evalFunc = new ReflectionMethod(Parser::class, 'evaluateFunction');
        $evalFunc->invoke($parser, 'test');
    }

    /**
	 * @small
	 */
	public function testRegisterFunctionFailsOnInvalidCallback()
    {
        $expression = '';
        $parser = new Parser($expression);

        $this->expectException(TypeError::class);
        $parser->registerFunction('invalid', 3);
    }

    /**
	 * @small
	 */
	public function testHasFunctionReturnsTrueOnPresentFunction()
    {
        $mock = $this->getMockBuilder(\stdClass::class)
                     ->addMethods(['callbackTest'])
                     ->getMock();

         $mock->expects($this->never())
              ->method('callbackTest')
              ->with($this->isInstanceOf(Parser::class));

        $expression = '';
        $parser = new Parser($expression);

        $parser->registerFunction('test', [$mock, 'callbackTest']);

        $this->assertTrue($parser->hasFunction('test'));
    }

    /**
	 * @small
	 */
	public function testHasFunctionReturnsFalseOnMissingFunction()
    {
        $mock = $this->getMockBuilder(\stdClass::class)
                     ->addMethods(['callbackTest'])
                     ->getMock();

         $mock->expects($this->never())
              ->method('callbackTest')
              ->with($this->isInstanceOf(Parser::class));

        $expression = '';
        $parser = new Parser($expression);

        $parser->registerFunction('test', [$mock, 'callbackTest']);

        $this->assertFalse($parser->hasFunction('test2'));
    }
}
