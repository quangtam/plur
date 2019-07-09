<?php

namespace Tests\Unit\Rule;

use App\Rules\Lowercase;
use Tests\TestCase;

class LowercaseTest extends TestCase
{
    protected $rule;

    public function setUp():void
    {
        parent::setUp();

        $this->rule = new Lowercase();
    }

    /**
     * @return void
     */
    public function testLowercasePass()
    {
        $this->assertTrue($this->rule->passes('test', 'abc'));
        $this->assertTrue($this->rule->passes('test', '1bc'));
    }

    /**
     * @return void
     */
    public function testLowercaseFail()
    {
        $this->assertFalse($this->rule->passes('test', 'ABC'));
        $this->assertFalse($this->rule->passes('test', '1BC'));
        $this->assertFalse($this->rule->passes('test', '1bC'));
    }
}
