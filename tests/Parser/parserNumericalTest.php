<?php

class parserNumericalTest extends parserTestCase
{
	/**
	 * @small
	 */
	public function testNumberOne()
    {
        $expression = '1';
        $value = 1;

        $got = $this->_doEval($expression, $value);

        $this->assertEquals(1, $got);
    }

    /**
	 * @small
	 * @depends parserOperatorsTest::testZeroCommaOneOperation
	 */
	public function testBelowMinimum()
    {
        $expression = 'min(2),max(4)';
        $value = 1;

        $got = $this->_doEval($expression, $value);

        $this->assertFalse($got);
    }

    /**
	 * @small
	 * @depends parserOperatorsTest::testOneCommaOneOperation
	 */
	public function testAtMinimum()
    {
        $expression = 'min(2),max(4)';
        $value = 2;

        $got = $this->_doEval($expression, $value);

        $this->assertTrue($got);
    }

    /**
	 * @small
	 * @depends parserOperatorsTest::testOneCommaOneOperation
	 */
	public function testBetweenMinAndMax()
    {
        $expression = 'min(2),max(4)';
        $value = 3;

        $got = $this->_doEval($expression, $value);

        $this->assertTrue($got);
    }

    /**
	 * @small
	 * @depends parserOperatorsTest::testOneCommaOneOperation
	 */
	public function testAtMaximum()
    {
        $expression = 'min(2),max(4)';
        $value = 4;

        $got = $this->_doEval($expression, $value);

        $this->assertTrue($got);
    }

    /**
	 * @small
	 * @depends parserOperatorsTest::testOneCommaZeroOperation
	 */
	public function testAboveMaximum()
    {
        $expression = 'min(2),max(4)';
        $value = 5;

        $got = $this->_doEval($expression, $value);

        $this->assertFalse($got);
    }

    /**
	 * @small
	 * @depends parserOperatorsTest::testZeroCommaOneOperation
	 */
	public function testNeg3BetweenMin2AndMax4()
    {
        $expression = 'min(2),max(4)';
        $value = -3;

        $got = $this->_doEval($expression, $value);

        $this->assertFalse($got);
    }

    /**
	 * @small
	 */
	public function testFloatingPointSupport()
    {
        $expression = '3.2';

        $got = $this->_doEval($expression);

        $this->assertEquals(3.2, $expression);
    }
}
