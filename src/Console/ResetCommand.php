<?php namespace camdjn\TranslationManager\Console;

use Illuminate\Console\Command;
use camdjn\TranslationManager\Manager;

class ResetCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ltm:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all groups and owned translations from the database';

    /** @var \camdjn\TranslationManager\Manager  */
    protected $manager;

    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->manager->truncateTranslations();
        $this->info("All translations are deleted");
        $this->manager->truncateGroups();
        $this->info("All Groups are deleted");
        $this->manager->truncateLocales();
        $this->info("All locales are deleted");
    }


}
