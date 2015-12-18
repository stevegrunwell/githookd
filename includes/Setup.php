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
     * @link https://www.kernel.org/pub/software/scm/git/docs/githooks.html
     */
    protected $whitelistedHooks = [
        'applypatch-msg',
        'pre-applypatch',
        'post-applypatch',
        'pre-commit',
        'prepare-commit-msg',
        'commit-msg',
        'post-commit',
        'pre-rebase',
        'post-checkout',
        'post-merge',
        'pre-push',
        'pre-receive',
        'update',
        'post-receive',
        'post-update',
        'push-to-checkout',
        'pre-auto-gc',
        'post-rewrite',
    ];

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

        // Show the help screen.
        if ($args['help']) {
            echo $args->getHelpScreen();
            return;
        }

        // Verify necessary directories are in place.
        if (! $this->verifyDirectories()) {
            return;
        }
    }

    /**
     * Copy valid hooks to the project's .git/hooks directory.
     */
    protected function copyHooks()
    {
        $hooks = glob($this->hooksDir);
        $count = 0;

        // Filter the hooks to remove invalid files.
        $hooks = array_filter([$this, 'filterHooks'], $hooks);

        foreach ($hooks as $hook) {
            try {
                copy($hook, $this->hooksDir);
                $count++;
            } catch (\Exception $e) {
                cli\err(sprintf(
                    'Unable to copy %s hook: %s',
                    basename($hook),
                    $e->getMessage()
                ));
            }
        }

        return $count;
    }

    /**
     * Filter Git hook filenames so that only valid files are permitted.
     *
     * @param string $filename The filename of the desired Git hook.
     * @return bool True if the filename is permitted, false otherwise.
     */
    protected function filterHooks($hook)
    {
        return in_array(basename($hook), $this->whitelistedHooks);
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

    /**
     * Verify that necessary file directories are in place.
     *
     */
    protected function verifyDirectories()
    {
        if (! is_dir($this->getProjectDir() . '/.git')) {
            cli\err('No .git directory found in your project, unable to copy Git hooks!');
            return false;

        } elseif (! is_dir($this->getProjectDir() . '/.git/hooks')) {
            cli\output('Creating hooks directory in Git repository');
            if (! mkdir($this->getProjectDir() . '/.git/hooks')) {
                cli\err('An error occurred creating .git/hooks, unable to proceed!');
                return;
            }
            return false;
        }


    }
}
