<?php
/**
 * Tests for GitHookd\Setup.
 *
 * @package GitHookd
 */

namespace GitHookd;

use Mockery as M;

class SetupTest extends \PHPUnit_Framework_TestCase
{

    public function testGetProjectDir()
    {
        $instance = new Setup;

        $this->assertEquals(getcwd(), $instance->getProjectDir());
    }

    public function testInstall()
    {
        $instance = M::mock(__NAMESPACE__ . '\Setup')
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $instance->shouldReceive('registerArgs')
            ->once()
            ->andReturn(new MockArgument);

        $instance->install();
    }

    public function testInstallWithHelpScreen()
    {
        $args = M::mock(__NAMESPACE__ . '\MockArgument')->makePartial();
        $args->shouldReceive('getHelpScreen')
            ->once()
            ->andReturn('HELP');
        $args['help'] = true;

        $instance = M::mock(__NAMESPACE__ . '\Setup')
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $instance->shouldReceive('registerArgs')
            ->once()
            ->andReturn($args);

        $this->expectOutputString('HELP', $instance->install());
    }
}
