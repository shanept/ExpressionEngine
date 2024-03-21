<?php

use shanept\ExpressionEngine\Parser;

class parserOperatorsTest extends parserTestCase
{
	/**
	 * @small
	 */
	public function testOneAndOneOperation()
    {
        $expression = '1&&1';
        $parser = new Parser($expression);

        $this->assertTrue($parser->evaluate());
    }

    /**
	 * @small
	 */
	public function testOneAndZeroOperation()
    {
        $expression = '1&&0';
        $parser = new Parser($expression);

        $this->assertFalse($parser->evaluate());
    }

    /**
	 * @small
	 */
	public function testZeroAndOneOperation()
    {
        $expression = '0&&1';
        $parser = new Parser($expression);

        $this->assertFalse($parser->evaluate());
    }

    /**
	 * @small
	 */
	public function testZeroAndZeroOperation()
    {
        $expression = '0&&0';
        $parser = new Parser($expression);

        $this->assertFalse($parser->evaluate());
    }

	/**
	 * @small
	 * @depends testOneAndOneOperation
	 */
	public function testOneCommaOneOperation()
    {
        $expression = '1,1';
        $parser = new Parser($expression);

        $this->assertTrue($parser->evaluate());
    }

    /**
	 * @small
	 * @depends testOneAndZeroOperation
	 */
	public function testOneCommaZeroOperation()
    {
        $expression = '1,0';
        $parser = new Parser($expression);

        $this->assertFalse($parser->evaluate());
    }

    /**
	 * @small
	 * @depends testZeroAndOneOperation
	 */
	public function testZeroCommaOneOperation()
    {
        $expression = '0,1';
        $parser = new Parser($expression);

        $this->assertFalse($parser->evaluate());
    }

    /**
	 * @small
	 * @depends testZeroAndZeroOperation
	 */
	public function testZeroCommaZeroOperation()
    {
        $expression = '0,0';
        $parser = new Parser($expression);

        $this->assertFalse($parser->evaluate());
    }

    /**
	 * @small
	 */
	public function testOneOrOneOperation()
    {
        $expression = '1||1';
        $parser = new Parser($expression);

        $this->assertTrue($parser->evaluate());
    }

    /**
	 * @small
	 */
	public function testOneOrZeroOperation()
    {
        $expression = '1||0';
        $parser = new Parser($expression);

        $this->assertTrue($parser->evaluate());
    }

    /**
	 * @small
	 */
	public function testZeroOrOneOperation()
    {
        $expression = '0||1';
        $parser = new Parser($expression);

        $this->assertTrue($parser->evaluate());
    }

    /**
	 * @small
	 */
	public function testZeroOrZeroOperation()
    {
        $expression = '0||0';
        $parser = new Parser($expression);

        $this->assertFalse($parser->evaluate());
    }

    /**
	 * @small
	 * @depends testZeroAndOneOperation
	 * @depends testZeroOrZeroOperation
	 */
	public function testZeroOrZeroAndOne()
    {
        $expression = '0||0&&1';
        $parser = new Parser($expression);

        $this->assertFalse($parser->evaluate());
    }

    /**
	 * @small
	 * @depends testZeroAndZeroOperation
	 * @depends testZeroOrOneOperation
	 */
	public function testZeroAndZeroOrOne()
    {
        $expression = '0&&0||1';
        $parser = new Parser($expression);

        $this->assertTrue($parser->evaluate());
    }

    /**
	 * @small
	 */
	public function testEqualsReturnsTrueOnSameNumbers()
    {
        $expression = '12==12';
        $parser = new Parser($expression);

        $this->assertTrue($parser->evaluate());
    }

    /**
	 * @small
	 */
	public function testEqualsReturnsTrueOnZeros()
    {
        $expression = '0==0';
        $parser = new Parser($expression);

        $this->assertTrue($parser->evaluate());
    }

    /**
	 * @small
	 */
	public function testEqualsReturnsFalseOnDifferentNumbers()
    {
        $expression = '12==10';
        $parser = new Parser($expression);

        $this->assertFalse($parser->evaluate());
    }

    /**
	 * @small
	 */
	public function testNotEqualsReturnsTrueOnDifferentNumbers()
    {
        $expression = '5!=3';
        $parser = new Parser($expression);

        $this->assertTrue($parser->evaluate());
    }

    /**
	 * @small
	 */
	public function testNotEqualsReturnsFalseOnSameNumbers()
    {
        $expression = '5!=5';
        $parser = new Parser($expression);

        $this->assertFalse($parser->evaluate());
    }

    /**
	 * @small
	 */
	public function test30GreaterThan15ReturnsTrue()
    {
        $expression = '30>15';

        $got = $this->_doEval($expression);

        $this->assertTrue($got);
    }

    /**
	 * @small
	 */
	public function test15GreaterThan30ReturnsFalse()
    {
        $expression = '15>30';

        $got = $this->_doEval($expression);

        $this->assertFalse($got);
    }

    /**
	 * @small
	 */
	public function test30GreaterThan30ReturnsFalse()
    {
        $expression = '30>30';

        $got = $this->_doEval($expression);

        $this->assertFalse($got);
    }

    /**
	 * @small
	 */
	public function test30LessThan15ReturnsFalse()
    {
        $expression = '30<15';

        $got = $this->_doEval($expression);

        $this->assertFalse($got);
    }

    /**
	 * @small
	 */
	public function test15LessThan30ReturnsTrue()
    {
        $expression = '15<30';

        $got = $this->_doEval($expression);

        $this->assertTrue($got);
    }

    /**
	 * @small
	 */
	public function test30LessThan30ReturnsFalse()
    {
        $expression = '30<30';

        $got = $this->_doEval($expression);

        $this->assertFalse($got);
    }

    /**
	 * @small
	 */
	public function test30GreaterThanOrEqual15ReturnsTrue()
    {
        $expression = '30>=15';

        $got = $this->_doEval($expression);

        $this->assertTrue($got);
    }

    /**
	 * @small
	 */
	public function test15GreaterThanOrEqual30ReturnsFalse()
    {
        $expression = '15>=30';

        $got = $this->_doEval($expression);

        $this->assertFalse($got);
    }


    /**
	 * @small
	 */
	public function test30GreaterThanOrEqual30ReturnsTrue()
    {
        $expression = '30>=30';

        $got = $this->_doEval($expression);

        $this->assertTrue($got);
    }


    /**
	 * @small
	 */
	public function test30LessThanOrEqual15ReturnsFalse()
    {
        $expression = '30<=15';

        $got = $this->_doEval($expression);

        $this->assertFalse($got);
    }

    /**
	 * @small
	 */
	public function test15LessThanOrEqual30ReturnsTrue()
    {
        $expression = '15<=30';

        $got = $this->_doEval($expression);

        $this->assertTrue($got);
    }

    /**
	 * @small
	 */
	public function test30LessThanOrEqual30ReturnsTrue()
    {
        $expression = '30<=30';

        $got = $this->_doEval($expression);

        $this->assertTrue($got);
    }

    /**
	 * @small
	 */
	public function test30Plus22Returns52()
    {
        $expression = '30+22';

        $got = $this->_doEval($expression);

        $this->assertEquals(52, $got);
    }

    /**
	 * @small
	 */
	public function test5Minus3Returns2()
    {
        $expression = '5-3';

        $got = $this->_doEval($expression);

        $this->assertEquals(2, $got);
    }

    /**
	 * @small
	 */
	public function test3Minus5ReturnsNeg2()
    {
        $expression = '3-5';

        $got = $this->_doEval($expression);

        $this->assertEquals(-2, $got);
    }

    /**
	 * @small
	 */
	public function test5Times3Returns15()
    {
        $expression = '5*3';

        $got = $this->_doEval($expression);

        $this->assertEquals(15, $got);
    }

    /**
	 * @small
	 */
	public function test15Div3Returns5()
    {
        $expression = '15/3';

        $got = $this->_doEval($expression);

        $this->assertEquals(5, $got);
    }

    /**
	 * @small
	 */
	public function test1Div2ReturnsPoint5()
    {
        $expression = '1/2';

        $got = $this->_doEval($expression);

        $this->assertEquals(0.5, $got);
    }

    /**
	 * @small
	 */
	public function test5Pow2Returns25()
    {
        $expression = '5**2';

        $got = $this->_doEval($expression);

        $this->assertEquals(25, $got);
    }

    /**
	 * @small
	 */
	public function testLogicAgainstContext()
    {
        $expression = '<5';
        $parser = new Parser($expression);
        $parser->setContext(4);

        $this->assertTrue($parser->evaluate());
    }

    /**
	 * @small
	 */
	public function testLogicAgainstInvalidContext()
    {
        $expression = '<5';
        $parser = new Parser($expression);
        $parser->setContext(7);

        $this->assertFalse($parser->evaluate());
    }

    /**
	 * @small
	 * @depends testLogicAgainstContext
	 * @depends testOneAndOneOperation
	 */
	public function testCompoundLogicReturnsTrueAgainstContext()
    {
        $expression = '<5&&>3';
        $parser = new Parser($expression);
        $parser->setContext(4);

        $this->assertTrue($parser->evaluate());
    }

    /**
	 * @small
	 * @depends testLogicAgainstInvalidContext
	 * @depends testLogicAgainstContext
	 * @depends testZeroAndOneOperation
	 */
	public function testCompoundLogicReturnsFalseAgainstInvalidContext()
    {
        $expression = '<5&&>3';
        $parser = new Parser($expression);
        $parser->setContext(7);

        $this->assertFalse($parser->evaluate());
    }

    /**
	 * @small
	 * @depends test15Div3Returns5
	 * @depends testEqualsReturnsTrueOnSameNumbers
	 * @depends testLogicAgainstContext
	 * @depends testOneAndOneOperation
	 * @depends testZeroOrOneOperation
	 */
	public function testVeryComplexLogicAgainstContext()
    {
        $expression = '/3==10&&>5&&<100&&(7==7&&3>=2&&<35&&(>95||<60))';
        $parser = new Parser($expression);
        $parser->setContext(30);

        $this->assertTrue($parser->evaluate());
    }

    /**
	 * @small
	 * @depends testVeryComplexLogicAgainstContext
	 */
	public function testVeryComplexLogicReturnsFalseAgainstInvalidContext()
    {
        $expression = '/3==10&&>5&&<100&&(7==7&&3>=2&&<35&&(>95||<60))';
        $parser = new Parser($expression);
        $parser->setContext(1000);

        $this->assertFalse($parser->evaluate());
    }
}
