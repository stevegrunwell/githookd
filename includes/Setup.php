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
     * @var array $postInstallCmd An array of commands to be added to Composer's post-install-cmd
     *                            during installation.
     */
    protected $postInstallCmd = [];

    /**
     * @var array $postUpdateCmd An array of commands to be added to Composer's post-update-cmd
     *                           during installation.
     */
    protected $postUpdateCmd = [];

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
     * Determine if there are Composer hooks to be written.
     *
     * @return bool True if $postInstallCmd and/or $postUpdateCmd are not empty, false otherwise.
     */
    public function hasComposerHooks()
    {
        return ! empty($this->postInstallCmd) || ! empty($this->postUpdateCmd);
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

        if ($hookCount = $this->copyHooks()) {
            cli\line('Success: %d hook(s) has/have been copied successfully!', $hookCount);
        }

        $this->installComposerHooks();
    }

    /**
     * Copy valid hooks to the project's .git/hooks directory.
     *
     * @return int The number of hooks copied.
     */
    protected function copyHooks()
    {
        $hooks = glob($this->hooksDir . '/*');
        $count = 0;

        // Filter the hooks to remove invalid files.
        $hooks = array_filter($hooks, [$this, 'filterHooks']);

        foreach ($hooks as $hook) {
            try {
                $filename = basename($hook);

                copy($hook, $this->projectDir . '/.git/hooks/' . $filename);
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
     * If there are Composer hooks to install, do so.
     *
     * @return bool True if hooks were added to the project's composer.json file, false otherwise.
     */
    protected function installComposerHooks()
    {
        if (! $this->hasComposerHooks()) {
            return false;
        }

        if (cli\choose('This package would like to install hooks in your composer.json file. Proceed')) {
            return $this->writeComposerHooks();
        }

        return false;
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
     * @return bool True if everything's as it should be, false otherwise.
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

        return true;
    }

    /**
     * Add post-install and post-update commands to the composer.json file.
     *
     * @return bool True if the commands were written, false if nothing was written.
     */
    protected function writeComposerHooks()
    {
        if (empty($this->postInstallCmd) && empty($this->postUpdateCmd)) {
            return false;
        }

        $composer = $this->getProjectDir() . '/composer.json';

        if (! is_readable($composer) || ! is_writable($composer)) {
            cli\err(sprintf(
                'The file at %s is not writable, unable to add Composer hooks',
                $composer
            ));
            return false;
        }

        $contents = file_get_contents($composer);
        $contents = (array) json_decode($contents, true);
        $hooks    = array(
            'post-install-cmd' => $this->postInstallCmd,
            'post-update-cmd'  => $this->postUpdateCmd,
        );

        foreach ($hooks as $hook => $commands) {
            $commands = (array) $commands;

            if (isset($contents[$hook])) {
                $contents[$hook] = array_merge((array) $contents[$hook], $commands);
            } else {
                $contents[$hook] = $commands;
            }
        }

        try {
            $fh = fopen($composer, 'wb');
            fwrite($fh, json_encode($contents));
            fclose($fh);

            cli\line('Composer hooks installed successfully');

        } catch (\Exception $e) {
            cli\err(sprintf(
                'An error occurred installing Composer hooks: %s',
                $e->getMessage()
            ));
            return false;
        }

        return true;
    }
}
