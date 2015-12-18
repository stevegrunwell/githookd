<?php
/**
 * Main setup for GitHook'd.
 *
 * @package GitHookd
 */

namespace GitHookd;

use cli;

class Setup
{
    /**
     * @var string $hooks_dir The absolute path to the hooks directory.
     */
    protected $hooksDir = __DIR__ . '/../bin/hooks';

    /**
     * @var string $project_dir The absolute path to the project directory.
     */
    protected $projectDir;

    /**
     * @var array $flags Flags that should be recognized by the CLI script.
     * @link https://github.com/wp-cli/php-cli-tools/blob/master/examples/arguments.php
     */
    protected $flags = [
        'help' => [
            'description' => 'Show this help screen',
            'aliases'     => ['h'],
        ],
    ];

    /**
     * @var array $options Options that should be recognized by the CLI script.
     * @link https://github.com/wp-cli/php-cli-tools/blob/master/examples/arguments.php
     */
    protected $options = [];

    /**
     * Determine the full system path to the project root.
     *
     * @return string A full system path.
     *
     * @todo Flesh this out to include better directory checking.
     */
    public function getProjectDir()
    {
        if (null === $this->projectDir) {
            $this->projectDir = getcwd();
        }

        return $this->projectDir;
    }

    /**
     * The main installation process to set up GitHook'd for a project.
     */
    public function install()
    {
        $args = $this->registerArgs();

        // Show the help screen
        if ($args['help']) {
            echo $args->getHelpScreen();
            return;
        }

        // Verify necessary directories are in place.
        if (! is_dir($this->getProjectDir() . '/.git')) {
            cli\err('No .git directory found in your project, unable to copy Git hooks!');
            return;

        } elseif (! is_dir($this->getProjectDir() . '/.git/hooks')) {
            cli\output('Creating hooks directory in Git repository');
            if (! mkdir($this->getProjectDir() . '/.git/hooks')) {
                cli\err('An error occurred creating .git/hooks, unable to proceed!');
                return;
            }
            return;
        }
    }

    /**
     * Register arguments available via the CLI.
     *
     * @return cli\Arguments An arguments object.
     */
    protected function registerArgs()
    {
        $args = new cli\Arguments();

        if (! empty($this->flags)) {
            $args->addFlags($this->flags);
        }

        if (! empty($this->options)) {
            $args->addOptions($this->options);
        }

        $args->parse();

        return $args;
    }
}
