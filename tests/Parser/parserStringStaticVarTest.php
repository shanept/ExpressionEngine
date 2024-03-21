<?php

use shanept\ExpressionEngine\Exceptions\InvalidVariableException;

class parserStringStaticVarTest extends ParserTestCase
{
    /**
	 * @small
	 * @depends parserOperatorsTest::test30GreaterThan15ReturnsTrue
	 */
	public function testVarCaseSensitivity()
    {
        $expression = 'Length>5';
        $value = 'string';

        $parser = $this->_initParser($expression, $value);

        // This will need to be a Parser Exception
        $this->expectException(InvalidVariableException::class);
        $parser->evaluate();
    }

    /**
	 * @small
	 * @depends parserOperatorsTest::test30GreaterThan15ReturnsTrue
	 */
	public function testLongerForStrLenGreaterThanReturnsTrue()
    {
        $expression = 'length>5';
        $value = 'string';

        $got = $this->_doEval($expression, $value);

        $this->assertTrue($got);
    }

    /**
	 * @small
	 * @depends parserOperatorsTest::test30LessThan15ReturnsFalse
	 */
	public function testTooLongForStrLenLessThanReturnsFalse()
    {
        $expression = 'length<5';
        $value = 'string';

        $got = $this->_doEval($expression, $value);

        $this->assertFalse($got);
    }

    /**
	 * @small
	 * @depends parserOperatorsTest::test30GreaterThanOrEqual15ReturnsTrue
	 */
	public function testLongerForStrLenGreaterOrEqualReturnsTrue()
    {
        $expression = 'length>=5';
        $value = 'string';

        $got = $this->_doEval($expression, $value);

        $this->assertTrue($got);
    }

    /**
	 * @small
	 * @depends parserOperatorsTest::test30LessThanOrEqual15ReturnsFalse
	 */
	public function testTooLongForStrLenLessOrEqualReturnsFalse()
    {
        $expression = 'length<=5';
        $value = 'string';

        $got = $this->_doEval($expression, $value);

        $this->assertFalse($got);
    }

    /**
	 * @small
	 * @depends parserOperatorsTest::test30GreaterThanOrEqual30ReturnsTrue
	 */
	public function testEqualLengthForStrLenGreaterOrEqualReturnsTrue()
    {
        $expression = 'length>=6';
        $value = 'string';

        $got = $this->_doEval($expression, $value);

        $this->assertTrue($got);
    }

    /**
	 * @small
	 * @depends parserOperatorsTest::test30LessThanOrEqual30ReturnsTrue
	 */
	public function testEqualLengthForStrLenLessOrEqualReturnsTrue()
    {
        $expression = 'length<=6';
        $value = 'string';

        $got = $this->_doEval($expression, $value);

        $this->assertTrue($got);
    }

    /**
	 * @small
	 * @depends parserOperatorsTest::testEqualsReturnsTrueOnSameNumbers
	 */
	public function testEqualLengthForStrLenEqualReturnsTrue()
    {
        $expression = 'length==6';
        $value = 'string';

        $got = $this->_doEval($expression, $value);

        $this->assertTrue($got);
    }

    /**
	 * @small
	 * @depends parserOperatorsTest::testEqualsReturnsFalseOnDifferentNumbers
	 */
	public function testTooShortForStrLenEqualReturnsFalse()
    {
        $expression = 'length==5';
        $value = 'string';

        $got = $this->_doEval($expression, $value);

        $this->assertFalse($got);
    }

    /**
	 * @small
	 * @depends parserOperatorsTest::testNotEqualsReturnsFalseOnSameNumbers
	 */
	public function testEqualLengthForStrLenNotEqualReturnsFalse()
    {
        $expression = 'length!=6';
        $value = 'string';

        $got = $this->_doEval($expression, $value);

        $this->assertFalse($got);
    }

    /**
	 * @small
	 * @depends parserOperatorsTest::testNotEqualsReturnsTrueOnDifferentNumbers
	 */
	public function testLongerForStrLenNotEqualReturnsTrue()
    {
        $expression = 'length!=5';
        $value = 'string';

        $got = $this->_doEval($expression, $value);

        $this->assertTrue($got);
    }
}
