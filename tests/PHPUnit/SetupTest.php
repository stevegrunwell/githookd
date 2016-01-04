<?php
/**
 * Tests for GitHookd\Setup.
 *
 * @package GitHookd
 */

namespace GitHookd;

use Mockery as M;
use ReflectionMethod;
use ReflectionProperty;
use org\bovigo\vfs\vfsStream;
use cli;

class SetupTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        vfsStream::setup('testDir');

        // Silence the output of the PHP CLI Tools' output.
        cli\Colors::disable();
        cli\Streams::setStream('out', fopen('php://output', 'w'));
        cli\Streams::setStream('err', fopen('php://output', 'w'));

        parent::setUp();
    }

    public function tearDown()
    {
        M::close();
        parent::tearDown();
    }

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
        $instance->shouldReceive('copyHooks')
            ->once()
            ->andReturn(0);
        $instance->shouldReceive('installComposerHooks')
            ->once()
            ->andReturn(true);

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

    public function testFilterHooks()
    {
        $instance = new Setup;
        $method   = new ReflectionMethod($instance, 'filterHooks');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($instance, 'pre-commit'));
        $this->assertFalse($method->invoke($instance, 'FOOBAR'));
    }

    public function testWriteComposerHooks()
    {
        vfsStream::newFile('testDir/composer.json');

        $fh = fopen(vfsStream::url('testDir/composer.json'), 'wb');
        fwrite($fh, json_encode(array()));
        fclose($fh);

        $instance = new Setup;

        $projectDir = new ReflectionProperty($instance, 'projectDir');
        $projectDir->setAccessible(true);
        $projectDir->setValue($instance, vfsStream::url('testDir'));

        $postInstall = new ReflectionProperty($instance, 'postInstallCmd');
        $postInstall->setAccessible(true);
        $postInstall->setValue($instance, ['my-post-install-cmd']);

        $postUpdate = new ReflectionProperty($instance, 'postUpdateCmd');
        $postUpdate->setAccessible(true);
        $postUpdate->setValue($instance, ['my-post-update-cmd']);

        $method = new ReflectionMethod($instance, 'writeComposerHooks');
        $method->setAccessible(true);
        $this->expectOutputString('Composer hooks installed successfully' . PHP_EOL);
        $this->assertTrue($method->invoke($instance));

        $composer = file_get_contents(vfsStream::url('testDir/composer.json'));
        $composer = json_decode($composer, true);

        $this->assertEquals(['my-post-install-cmd'], $composer['post-install-cmd']);
        $this->assertEquals(['my-post-update-cmd'], $composer['post-update-cmd']);
    }

    public function testWriteComposerHooksChecksHooksAreSet()
    {
        vfsStream::newFile('testDir/composer.json');

        $instance = new Setup;

        $method = new ReflectionMethod($instance, 'writeComposerHooks');
        $method->setAccessible(true);
        $this->assertFalse($method->invoke($instance));
    }

    public function testWriteComposerHooksChecksPermissions()
    {
        vfsStream::newFile('testDir/composer.json', 0400);

        $instance = new Setup;

        $projectDir = new ReflectionProperty($instance, 'projectDir');
        $projectDir->setAccessible(true);
        $projectDir->setValue($instance, vfsStream::url('testDir'));

        $postInstall = new ReflectionProperty($instance, 'postInstallCmd');
        $postInstall->setAccessible(true);
        $postInstall->setValue($instance, ['my-post-install-cmd']);

        $method = new ReflectionMethod($instance, 'writeComposerHooks');
        $method->setAccessible(true);

        $this->expectOutputRegex('/The file at .+ is not writable/');
        $this->assertFalse($method->invoke($instance));
    }

    public function testWriteComposerHooksMergesHooks()
    {
        vfsStream::newFile('testDir/composer.json');

        $composer = array(
            'post-install-cmd' => array(
                'first-command',
            ),
            'post-update-cmd' => array(
                'second-command',
            ),
        );

        $fh = fopen(vfsStream::url('testDir/composer.json'), 'wb');
        fwrite($fh, json_encode($composer));
        fclose($fh);

        $instance = new Setup;

        $projectDir = new ReflectionProperty($instance, 'projectDir');
        $projectDir->setAccessible(true);
        $projectDir->setValue($instance, vfsStream::url('testDir'));

        $postInstall = new ReflectionProperty($instance, 'postInstallCmd');
        $postInstall->setAccessible(true);
        $postInstall->setValue($instance, ['my-post-install-cmd']);

        $postUpdate = new ReflectionProperty($instance, 'postUpdateCmd');
        $postUpdate->setAccessible(true);
        $postUpdate->setValue($instance, ['my-post-update-cmd']);

        $method = new ReflectionMethod($instance, 'writeComposerHooks');
        $method->setAccessible(true);
        $this->expectOutputString('Composer hooks installed successfully' . PHP_EOL);
        $this->assertTrue($method->invoke($instance));

        $composer = file_get_contents(vfsStream::url('testDir/composer.json'));
        $composer = json_decode($composer, true);

        $this->assertEquals(['first-command', 'my-post-install-cmd'], $composer['post-install-cmd']);
        $this->assertEquals(['second-command', 'my-post-update-cmd'], $composer['post-update-cmd']);
    }
}
